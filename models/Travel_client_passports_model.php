<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_client_passports_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get a single passport record by id
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'travel_client_passports')->row();
    }

    /**
     * Get the client's current (most recently uploaded, active) passport, or null if they
     * have none on file yet
     * @param  mixed $clientid
     * @return mixed
     */
    public function get_current($clientid)
    {
        $this->db->where('clientid', $clientid);
        $this->db->where('is_current', 1);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);

        return $this->db->get(db_prefix() . 'travel_client_passports')->row_array();
    }

    /**
     * Get every passport ever uploaded for this client, newest first - the full historical
     * record, including superseded ones
     * @param  mixed $clientid
     * @return array
     */
    public function get_history($clientid)
    {
        $this->db->where('clientid', $clientid);
        $this->db->order_by('id', 'desc');

        return $this->db->get(db_prefix() . 'travel_client_passports')->result_array();
    }

    /**
     * Record a new passport for a client. Never overwrites a previous one - the prior
     * "current" row (if any) is flagged is_current = 0 and kept permanently as history, and
     * this insert becomes the new current record.
     * @param  mixed $clientid
     * @param  array $data
     * @return mixed  insert id, or false on failure
     */
    public function add($clientid, $data)
    {
        $data['clientid']        = (int) $clientid;
        $data['passport_expiry'] = isset($data['passport_expiry']) && $data['passport_expiry'] != '' ? to_sql_date($data['passport_expiry']) : null;
        $data['date_of_birth']   = isset($data['date_of_birth']) && $data['date_of_birth'] != '' ? to_sql_date($data['date_of_birth']) : null;
        $data['is_current']      = 1;
        $data['datecreated']     = date('Y-m-d H:i:s');
        $data['addedfrom']       = get_staff_user_id();

        $this->db->where('clientid', $clientid);
        $this->db->where('is_current', 1);
        $this->db->update(db_prefix() . 'travel_client_passports', ['is_current' => 0]);

        $this->db->insert(db_prefix() . 'travel_client_passports', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Passport Recorded for Client [Client ID:' . $clientid . ', Passport ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Store an uploaded scan filename against an existing passport record
     * @param  mixed  $id
     * @param  mixed  $clientid  enforced in the WHERE clause so a passport id can't be written
     *                           to under a mismatched client id
     * @param  string $filename
     * @return boolean
     */
    public function update_scan_file($id, $clientid, $filename)
    {
        $this->db->where('id', $id);
        $this->db->where('clientid', $clientid);
        $this->db->update(db_prefix() . 'travel_client_passports', ['scan_file' => $filename]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a passport record - e.g. one uploaded by mistake, a duplicate, or a genuinely
     * unwanted historical entry. $clientid is enforced in the WHERE clause so a passport id
     * can't be deleted under a mismatched client id.
     *
     * If the deleted row was the client's current passport, the next most recent remaining
     * record (if any) is promoted to current, so the client doesn't appear to have no passport
     * on file when older history still exists.
     * @param  mixed $id
     * @param  mixed $clientid
     * @return boolean
     */
    public function delete($id, $clientid)
    {
        $passport = $this->get($id);

        if (!$passport || (int) $passport->clientid !== (int) $clientid) {
            return false;
        }

        if ($passport->scan_file != '') {
            $path = travel_agency_client_passport_upload_path($clientid) . $passport->scan_file;

            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $this->db->where('id', $id);
        $this->db->where('clientid', $clientid);
        $this->db->delete(db_prefix() . 'travel_client_passports');

        if ($this->db->affected_rows() === 0) {
            return false;
        }

        if ((int) $passport->is_current === 1) {
            $this->db->where('clientid', $clientid);
            $this->db->order_by('id', 'desc');
            $this->db->limit(1);
            $next = $this->db->get(db_prefix() . 'travel_client_passports')->row();

            if ($next) {
                $this->db->where('id', $next->id);
                $this->db->update(db_prefix() . 'travel_client_passports', ['is_current' => 1]);
            }
        }

        log_activity('Passport Record Deleted [Client ID:' . $clientid . ', Passport ID:' . $id . ']');

        return true;
    }
}
