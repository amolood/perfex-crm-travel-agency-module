<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_bookings_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single booking or all bookings
     * @param  mixed $id
     * @param  array $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'travel_bookings.id', $id);
            $this->_join_related_tables();

            return $this->db->get(db_prefix() . 'travel_bookings')->row();
        }

        if (count($where) > 0) {
            $this->db->where($where);
        }

        $this->_join_related_tables();
        $this->db->order_by(db_prefix() . 'travel_bookings.datecreated', 'desc');

        return $this->db->get(db_prefix() . 'travel_bookings')->result_array();
    }

    /**
     * Get all bookings belonging to a customer, for use in the client portal
     * @param  mixed $customer_id
     * @return array
     */
    public function get_customer_bookings($customer_id)
    {
        return $this->get('', [db_prefix() . 'travel_bookings.clientid' => $customer_id]);
    }

    private function _join_related_tables()
    {
        $this->db->select(db_prefix() . 'travel_bookings.*, ' . db_prefix() . 'travel_packages.name as package_name, ' . db_prefix() . 'travel_packages.destination as package_destination, ' . db_prefix() . 'travel_packages.currency as package_currency, ' . db_prefix() . 'clients.company as client_company')
            ->join(db_prefix() . 'travel_packages', db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_bookings.package_id', 'left')
            ->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'travel_bookings.clientid', 'left');
    }

    /**
     * Count seats already booked for a package, excluding cancelled bookings
     * @param  mixed $package_id
     * @param  mixed $exclude_booking_id  booking id to exclude (when updating an existing booking)
     * @return int
     */
    public function get_booked_seats($package_id, $exclude_booking_id = null)
    {
        $this->db->select_sum('travelers')
            ->where('package_id', $package_id)
            ->where('status !=', TRAVEL_BOOKING_STATUS_CANCELLED);

        if ($exclude_booking_id) {
            $this->db->where('id !=', $exclude_booking_id);
        }

        $result = $this->db->get(db_prefix() . 'travel_bookings')->row();

        return $result && $result->travelers ? (int) $result->travelers : 0;
    }

    /**
     * Check whether a package has enough seats available for the given number of travelers
     * @param  mixed $package_id
     * @param  int   $travelers
     * @param  mixed $exclude_booking_id
     * @return boolean
     */
    public function has_available_seats($package_id, $travelers, $exclude_booking_id = null)
    {
        $this->load->model('travel_packages_model');
        $package = $this->travel_packages_model->get($package_id);

        if (!$package || $package->seats_available <= 0) {
            // seats_available not set (0) is treated as unlimited
            return true;
        }

        $booked = $this->get_booked_seats($package_id, $exclude_booking_id);

        return ($booked + $travelers) <= $package->seats_available;
    }

    /**
     * Serialize seat-capacity check-and-write for a single package via a MySQL named lock, so
     * two concurrent bookings (e.g. two client-portal applications, or a staff member and a
     * client, submitting for the last remaining seat(s) at the same moment) can't both pass
     * has_available_seats() before either has inserted and jointly oversell the package. The
     * lock is scoped to the package id only, so bookings on different packages never block
     * each other.
     * @param  mixed    $package_id
     * @param  callable $callback  runs with the lock held, returns the callback's own result
     * @return mixed  the callback's result, or 'no_seats' if the lock itself could not be acquired
     */
    private function with_seat_lock($package_id, callable $callback)
    {
        $lock_name = 'travel_agency_seats_' . (int) $package_id;

        $acquired = $this->db->query('SELECT GET_LOCK(?, 10) AS locked', [$lock_name])->row();

        if (!$acquired || (int) $acquired->locked !== 1) {
            return 'no_seats';
        }

        try {
            return $callback();
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
        }
    }

    /**
     * Add new booking, optionally generating a linked invoice
     * @param  array $data
     * @return mixed  insert id, false on failure, or 'no_seats' if the package has no capacity left
     */
    public function add($data)
    {
        $create_invoice = isset($data['create_invoice']) && $data['create_invoice'] == 'true';
        unset($data['create_invoice']);

        $data['travelers'] = (int) $data['travelers'];

        if ($data['travelers'] < 1) {
            $data['travelers'] = 1;
        }

        return $this->with_seat_lock($data['package_id'], function () use ($data) {
            if (!$this->has_available_seats($data['package_id'], $data['travelers'])) {
                return 'no_seats';
            }

            $data['datecreated'] = date('Y-m-d H:i:s');
            $data['addedfrom']   = get_staff_user_id();
            $data['contact_id']  = $data['contact_id'] == '' ? 0 : $data['contact_id'];
            $data['travel_date'] = isset($data['travel_date']) && $data['travel_date'] != '' ? to_sql_date($data['travel_date']) : null;
            $data['status']      = $data['status'] == '' ? TRAVEL_BOOKING_STATUS_PENDING : $data['status'];

            $this->load->model('travel_packages_model');
            $package = $this->travel_packages_model->get($data['package_id']);

            $data['total'] = $package ? ($package->price * $data['travelers']) : 0;

            $this->db->insert(db_prefix() . 'travel_bookings', $data);
            $insert_id = $this->db->insert_id();

            if (!$insert_id) {
                return false;
            }

            log_activity('New Travel Booking Added [ID:' . $insert_id . ']');

            if ($create_invoice && $package) {
                $invoice_id = $this->create_invoice_for_booking($insert_id, $package, $data);

                if ($invoice_id) {
                    $this->db->where('id', $insert_id);
                    $this->db->update(db_prefix() . 'travel_bookings', ['invoiceid' => $invoice_id]);
                } else {
                    log_activity('Travel Booking Invoice Creation Failed [Booking ID:' . $insert_id . ']');
                }
            }

            return $insert_id;
        });
    }

    /**
     * Create an invoice for a booking's package cost
     * @param  mixed  $booking_id
     * @param  object $package
     * @param  array  $data
     * @return mixed
     */
    private function create_invoice_for_booking($booking_id, $package, $data)
    {
        $this->load->model('invoices_model');

        $amount = $package->price * $data['travelers'];

        $invoice_data = [
            'clientid'       => $data['clientid'],
            'date'           => _d(date('Y-m-d')),
            'duedate'        => _d(date('Y-m-d')),
            'currency'       => $package->currency != 0 ? $package->currency : get_base_currency()->id,
            'sale_agent'     => get_staff_user_id(),
            'billing_street' => '',
            'billing_city'   => '',
            'billing_state'  => '',
            'billing_zip'    => '',
            'billing_country' => 0,
            'status'         => 1,
            'subtotal'       => $amount,
            'total'          => $amount,
            'newitems'       => [
                1 => [
                    'description'      => $package->name . ' - ' . $package->destination,
                    'long_description' => '',
                    'qty'              => $data['travelers'],
                    'unit'             => '',
                    'taxname'          => [],
                    'rate'             => $package->price,
                    'order'            => 1,
                ],
            ],
            'allowed_payment_modes' => [],
        ];

        return $this->invoices_model->add($invoice_data);
    }

    /**
     * Update booking
     * @param  array $data
     * @param  mixed $id
     * @return mixed  true, false, or 'no_seats' if the package has no capacity left
     */
    public function update($data, $id)
    {
        $data['contact_id']  = $data['contact_id'] == '' ? 0 : $data['contact_id'];
        $data['travel_date'] = isset($data['travel_date']) && $data['travel_date'] != '' ? to_sql_date($data['travel_date']) : null;

        if (isset($data['travelers'])) {
            $data['travelers'] = (int) $data['travelers'];

            if ($data['travelers'] < 1) {
                $data['travelers'] = 1;
            }
        }

        if (isset($data['package_id']) || isset($data['travelers'])) {
            $booking    = $this->get($id);
            $package_id = isset($data['package_id']) ? $data['package_id'] : $booking->package_id;
            $travelers  = isset($data['travelers']) ? $data['travelers'] : $booking->travelers;

            $result = $this->with_seat_lock($package_id, function () use ($package_id, $travelers, $id, &$data) {
                if (!$this->has_available_seats($package_id, $travelers, $id)) {
                    return 'no_seats';
                }

                $this->load->model('travel_packages_model');
                $package = $this->travel_packages_model->get($package_id);

                if ($package) {
                    $data['total'] = $package->price * $travelers;
                }

                return true;
            });

            if ($result === 'no_seats') {
                return 'no_seats';
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_bookings', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Booking Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Update only the booking status
     * @param  mixed $id
     * @param  int   $status
     * @return mixed  true, false, or 'no_seats' if reactivating this booking would oversell the package
     */
    public function update_status($id, $status)
    {
        $booking = $this->get($id);

        if (!$booking) {
            return false;
        }

        // Moving a booking OUT of cancelled re-occupies its seats - re-check capacity the same
        // way add()/update() do, since a cancelled booking's seats are excluded from every
        // other capacity check and other bookings may have filled them in the meantime.
        if ((int) $booking->status === TRAVEL_BOOKING_STATUS_CANCELLED && (int) $status !== TRAVEL_BOOKING_STATUS_CANCELLED) {
            $result = $this->with_seat_lock($booking->package_id, function () use ($booking, $id) {
                return $this->has_available_seats($booking->package_id, $booking->travelers, $id);
            });

            if ($result !== true) {
                return 'no_seats';
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_bookings', ['status' => $status]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Booking Status Updated [ID:' . $id . ', Status:' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete booking
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_bookings');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Booking Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }
}
