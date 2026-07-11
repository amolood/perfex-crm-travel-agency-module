<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_suppliers_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single supplier or all suppliers
     * @param  mixed $id
     * @return mixed
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'travel_suppliers')->row();
        }

        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'travel_suppliers')->result_array();
    }

    /**
     * Add new supplier
     * @param  array $data
     * @return mixed  insert id, 'invalid_data' if name/phonenumber is blank, or false on failure
     */
    public function add($data)
    {
        // Client-side validation (appValidateForm) can be bypassed by anyone submitting the
        // form directly - name and phone number are the minimum needed to actually reach a
        // supplier, so both are enforced here too, not just in the browser.
        if (!$this->has_required_fields($data)) {
            return 'invalid_data';
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();
        $data['active']      = isset($data['active']) ? 1 : 0;

        $this->db->insert(db_prefix() . 'travel_suppliers', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Travel Supplier Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * @param  array $data
     * @return boolean
     */
    private function has_required_fields($data)
    {
        return isset($data['name']) && trim($data['name']) !== ''
            && isset($data['phonenumber']) && trim($data['phonenumber']) !== '';
    }

    /**
     * Update supplier
     * @param  array $data
     * @param  mixed $id
     * @return mixed  true, 'invalid_data' if name/phonenumber is blank, or false on failure
     */
    public function update($data, $id)
    {
        if (!$this->has_required_fields($data)) {
            return 'invalid_data';
        }

        $data['active'] = isset($data['active']) ? 1 : 0;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_suppliers', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Supplier Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete supplier
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('supplier_id', $id);
        $total_packages = $this->db->get(db_prefix() . 'travel_packages')->num_rows();

        if ($total_packages > 0) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_suppliers');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Supplier Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }
}
