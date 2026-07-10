<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_packages_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single package or all packages
     * @param  mixed $id
     * @param  array $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'travel_packages')->row();
        }

        if (count($where) > 0) {
            $this->db->where($where);
        }

        $this->db->order_by('datecreated', 'desc');

        return $this->db->get(db_prefix() . 'travel_packages')->result_array();
    }

    /**
     * Get all active packages, for use in booking dropdowns
     * @return array
     */
    public function get_active()
    {
        return $this->get('', ['active' => 1]);
    }

    /**
     * Get all active packages that still have seats remaining, for the client portal
     * @return array
     */
    public function get_bookable()
    {
        $this->load->model('travel_agency/travel_bookings_model');

        $packages = $this->get_active();

        foreach ($packages as $key => $package) {
            if ($package['seats_available'] <= 0) {
                // 0 = unlimited seats
                $packages[$key]['seats_remaining'] = null;
                continue;
            }

            $booked                             = $this->travel_bookings_model->get_booked_seats($package['id']);
            $remaining                          = $package['seats_available'] - $booked;
            $packages[$key]['seats_remaining']  = max(0, $remaining);
        }

        $packages = array_filter($packages, function ($package) {
            return $package['seats_remaining'] === null || $package['seats_remaining'] > 0;
        });

        return array_values($packages);
    }

    /**
     * Add new package
     * @param  array $data
     * @return mixed
     */
    public function add($data)
    {
        $data['datecreated']    = date('Y-m-d H:i:s');
        $data['addedfrom']      = get_staff_user_id();
        $data['active']         = isset($data['active']) ? 1 : 0;
        $data['supplier_id']    = $data['supplier_id'] == '' ? 0 : $data['supplier_id'];
        $data['type_id']        = $data['type_id'] == '' ? 0 : $data['type_id'];
        $data['currency']       = $data['currency'] == '' ? get_base_currency()->id : $data['currency'];
        $data['cost']           = isset($data['cost']) && $data['cost'] != '' ? $data['cost'] : 0;
        $data['start_date']     = isset($data['start_date']) && $data['start_date'] != '' ? to_sql_date($data['start_date']) : null;
        $data['end_date']       = isset($data['end_date']) && $data['end_date'] != '' ? to_sql_date($data['end_date']) : null;

        $this->db->insert(db_prefix() . 'travel_packages', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Travel Package Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update package
     * @param  array $data
     * @param  mixed $id
     * @return boolean
     */
    public function update($data, $id)
    {
        $data['active']      = isset($data['active']) ? 1 : 0;
        $data['supplier_id'] = $data['supplier_id'] == '' ? 0 : $data['supplier_id'];
        $data['type_id']     = $data['type_id'] == '' ? 0 : $data['type_id'];
        $data['currency']    = $data['currency'] == '' ? get_base_currency()->id : $data['currency'];
        $data['cost']        = isset($data['cost']) && $data['cost'] != '' ? $data['cost'] : 0;
        $data['start_date']  = isset($data['start_date']) && $data['start_date'] != '' ? to_sql_date($data['start_date']) : null;
        $data['end_date']    = isset($data['end_date']) && $data['end_date'] != '' ? to_sql_date($data['end_date']) : null;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_packages', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Package Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Calculate profit per seat and margin percentage for a package
     * @param  mixed $id
     * @return array
     */
    public function calculate_profit($id)
    {
        $package = is_object($id) ? $id : $this->get($id);

        $profit_per_seat = $package->price - $package->cost;
        $margin_percent  = $package->price > 0 ? ($profit_per_seat / $package->price) * 100 : 0;

        return [
            'profit_per_seat' => $profit_per_seat,
            'margin_percent'  => round($margin_percent, 2),
        ];
    }

    /**
     * Delete package
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('package_id', $id);
        $total_bookings = $this->db->get(db_prefix() . 'travel_bookings')->num_rows();

        if ($total_bookings > 0) {
            return false;
        }

        $this->db->where('package_id', $id);
        $total_groups = $this->db->get(db_prefix() . 'travel_groups')->num_rows();

        if ($total_groups > 0) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_packages');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Package Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }
}
