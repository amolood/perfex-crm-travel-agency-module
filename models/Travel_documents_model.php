<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_documents_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  string $rel_type  'booking' or 'group'
     * @param  mixed  $rel_id
     * @return array
     */
    public function get_for($rel_type, $rel_id)
    {
        $this->db->where('rel_type', $rel_type);
        $this->db->where('rel_id', $rel_id);
        $this->db->order_by('datecreated', 'desc');

        return $this->db->get(db_prefix() . 'travel_documents')->result_array();
    }

    /**
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'travel_documents')->row();
    }

    /**
     * @param  array $data  rel_type, rel_id, document_type, original_name, filename, notes
     * @return mixed  insert id or false
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();

        $this->db->insert(db_prefix() . 'travel_documents', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Travel Document Uploaded [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $document = $this->get($id);

        if (!$document) {
            return false;
        }

        $path = travel_agency_document_upload_path($document->rel_type, $document->rel_id) . $document->filename;

        if (file_exists($path)) {
            unlink($path);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_documents');

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Document Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }
}
