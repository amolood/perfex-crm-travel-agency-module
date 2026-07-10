<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_supplier_payments_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single payment or all payments for a supplier
     * @param  mixed $id
     * @return mixed
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'travel_supplier_payments')->row();
        }

        $this->db->order_by('date', 'desc');
        $this->db->order_by('id', 'desc');

        return $this->db->get(db_prefix() . 'travel_supplier_payments')->result_array();
    }

    public function get_for_supplier($supplier_id)
    {
        $this->db->where('supplier_id', $supplier_id);

        return $this->get();
    }

    /**
     * Add new supplier payment
     * @param  array $data
     * @return mixed  insert id, false on failure, or 'invalid_amount' when amount is not a positive number
     */
    public function add($data)
    {
        $data['supplier_id']  = (int) $data['supplier_id'];
        $data['currency']     = $data['currency'] == '' ? get_base_currency()->id : $data['currency'];
        // Rounded explicitly to match the amount column's decimal(15,2) precision - relying on
        // MySQL to silently round on insert would make the recorded amount not match whatever
        // the staff member actually typed in without any indication that a rounding happened.
        $data['amount']       = isset($data['amount']) && $data['amount'] != '' ? round((float) $data['amount'], 2) : 0;

        // A zero/negative amount would silently inflate the computed balance due (balance =
        // due - paid, and paid is a plain SUM()) instead of representing a real payment.
        if ($data['amount'] <= 0) {
            return 'invalid_amount';
        }

        $data['date']         = isset($data['date']) && $data['date'] != '' ? to_sql_date($data['date']) : date('Y-m-d');
        $data['datecreated']  = date('Y-m-d H:i:s');
        $data['addedfrom']    = get_staff_user_id();

        $this->db->insert(db_prefix() . 'travel_supplier_payments', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Travel Supplier Payment Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Delete a supplier payment
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_supplier_payments');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Supplier Payment Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Calculate what is due to a supplier per currency, based on package costs
     * of non-cancelled bookings, minus payments already made
     *
     * @param  mixed $supplier_id
     * @return array keyed by currency id: ['due' => .., 'paid' => .., 'balance' => .., 'currency' => currency row]
     */
    public function get_account_summary($supplier_id)
    {
        $this->load->model('currencies_model');

        $due_rows = $this->db->select(db_prefix() . 'travel_packages.currency, SUM(' . db_prefix() . 'travel_packages.cost * ' . db_prefix() . 'travel_bookings.travelers) as total_due')
            ->from(db_prefix() . 'travel_bookings')
            ->join(db_prefix() . 'travel_packages', db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_bookings.package_id')
            ->where(db_prefix() . 'travel_packages.supplier_id', $supplier_id)
            ->where(db_prefix() . 'travel_bookings.status !=', TRAVEL_BOOKING_STATUS_CANCELLED)
            ->group_by(db_prefix() . 'travel_packages.currency')
            ->get()->result_array();

        $paid_rows = $this->db->select('currency, SUM(amount) as total_paid')
            ->from(db_prefix() . 'travel_supplier_payments')
            ->where('supplier_id', $supplier_id)
            ->group_by('currency')
            ->get()->result_array();

        $summary = [];

        foreach ($due_rows as $row) {
            $summary[$row['currency']]['due'] = (float) $row['total_due'];
        }

        foreach ($paid_rows as $row) {
            $summary[$row['currency']]['paid'] = (float) $row['total_paid'];
        }

        $result = [];

        foreach ($summary as $currency_id => $amounts) {
            $due     = $amounts['due'] ?? 0;
            $paid    = $amounts['paid'] ?? 0;
            $result[] = [
                'currency' => $this->currencies_model->get($currency_id),
                'due'      => $due,
                'paid'     => $paid,
                'balance'  => $due - $paid,
            ];
        }

        return $result;
    }

    /**
     * Calculate the account summary (due/paid/balance per currency) for every supplier at once,
     * for use in overview listings
     *
     * @return array keyed by supplier_id, each value same shape as get_account_summary()
     */
    public function get_all_account_summaries()
    {
        $this->load->model('currencies_model');

        $due_rows = $this->db->select(db_prefix() . 'travel_packages.supplier_id, ' . db_prefix() . 'travel_packages.currency, SUM(' . db_prefix() . 'travel_packages.cost * ' . db_prefix() . 'travel_bookings.travelers) as total_due')
            ->from(db_prefix() . 'travel_bookings')
            ->join(db_prefix() . 'travel_packages', db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_bookings.package_id')
            ->where(db_prefix() . 'travel_bookings.status !=', TRAVEL_BOOKING_STATUS_CANCELLED)
            ->group_by(db_prefix() . 'travel_packages.supplier_id, ' . db_prefix() . 'travel_packages.currency')
            ->get()->result_array();

        $paid_rows = $this->db->select('supplier_id, currency, SUM(amount) as total_paid')
            ->from(db_prefix() . 'travel_supplier_payments')
            ->group_by('supplier_id, currency')
            ->get()->result_array();

        $summary = [];

        foreach ($due_rows as $row) {
            $summary[$row['supplier_id']][$row['currency']]['due'] = (float) $row['total_due'];
        }

        foreach ($paid_rows as $row) {
            $summary[$row['supplier_id']][$row['currency']]['paid'] = (float) $row['total_paid'];
        }

        $result = [];

        foreach ($summary as $supplier_id => $currencies) {
            foreach ($currencies as $currency_id => $amounts) {
                $due  = $amounts['due'] ?? 0;
                $paid = $amounts['paid'] ?? 0;

                $result[$supplier_id][] = [
                    'currency' => $this->currencies_model->get($currency_id),
                    'due'      => $due,
                    'paid'     => $paid,
                    'balance'  => $due - $paid,
                ];
            }
        }

        return $result;
    }
}
