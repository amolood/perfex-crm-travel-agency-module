<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_package_types_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single type or all types
     * @param  mixed $id
     * @param  array $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'travel_package_types')->row();
        }

        if (count($where) > 0) {
            $this->db->where($where);
        }

        $this->db->order_by('display_order', 'asc');

        return $this->db->get(db_prefix() . 'travel_package_types')->result_array();
    }

    /**
     * Get all active types, for use in package type dropdowns
     * @return array
     */
    public function get_active()
    {
        return $this->get('', ['active' => 1]);
    }

    /**
     * Add new type
     * @param  array $data
     * @return mixed
     */
    public function add($data)
    {
        unset($data['id']);
        $data['active']        = isset($data['active']) ? 1 : 0;
        $data['display_order'] = $data['display_order'] == '' ? 0 : $data['display_order'];

        $this->db->insert(db_prefix() . 'travel_package_types', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Travel Package Type Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update type
     * @param  array $data
     * @param  mixed $id
     * @return boolean
     */
    public function update($data, $id)
    {
        unset($data['id']);
        $data['active']        = isset($data['active']) ? 1 : 0;
        $data['display_order'] = $data['display_order'] == '' ? 0 : $data['display_order'];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_package_types', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Package Type Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete type, only if not used by any package
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('type_id', $id);
        $total_packages = $this->db->get(db_prefix() . 'travel_packages')->num_rows();

        if ($total_packages > 0) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_package_types');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Package Type Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }
}
