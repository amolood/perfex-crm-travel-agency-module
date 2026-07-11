<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_groups_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single group or all groups
     * @param  mixed $id
     * @param  array $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'travel_groups.id', $id);
            $this->_join_related_tables();

            $group = $this->db->get(db_prefix() . 'travel_groups')->row();

            if ($group) {
                $group->members_count = $this->count_members($id);
            }

            return $group;
        }

        if (count($where) > 0) {
            $this->db->where($where);
        }

        $this->_join_related_tables();
        $this->db->order_by(db_prefix() . 'travel_groups.departure_date', 'desc');

        return $this->db->get(db_prefix() . 'travel_groups')->result_array();
    }

    private function _join_related_tables()
    {
        $this->db->select(db_prefix() . 'travel_groups.*, ' . db_prefix() . 'travel_packages.name as package_name, ' . db_prefix() . 'travel_packages.destination as package_destination')
            ->join(db_prefix() . 'travel_packages', db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_groups.package_id', 'left');
    }

    /**
     * Get groups departing within the given number of days, for dashboard/overview use
     * @param  int $days
     * @return array
     */
    public function get_upcoming_departures($days = 14)
    {
        $this->_join_related_tables();
        $this->db->where(db_prefix() . 'travel_groups.departure_date >=', date('Y-m-d'));
        $this->db->where(db_prefix() . 'travel_groups.departure_date <=', date('Y-m-d', strtotime('+' . intval($days) . ' days')));
        $this->db->where(db_prefix() . 'travel_groups.status !=', TRAVEL_GROUP_STATUS_CANCELLED);
        $this->db->order_by(db_prefix() . 'travel_groups.departure_date', 'asc');

        $groups = $this->db->get(db_prefix() . 'travel_groups')->result_array();

        foreach ($groups as $key => $group) {
            $groups[$key]['members_count'] = $this->count_members($group['id']);
        }

        return $groups;
    }

    /**
     * Count members currently assigned to a group
     * @param  mixed $group_id
     * @return int
     */
    public function count_members($group_id)
    {
        $this->db->where('group_id', $group_id);

        return $this->db->count_all_results(db_prefix() . 'travel_group_members');
    }

    /**
     * Add new group
     * @param  array $data
     * @return mixed
     */
    public function add($data)
    {
        $data['datecreated']     = date('Y-m-d H:i:s');
        $data['addedfrom']       = get_staff_user_id();
        $data['status']          = $data['status'] == '' ? TRAVEL_GROUP_STATUS_PLANNING : $data['status'];
        $data['departure_date']  = isset($data['departure_date']) && $data['departure_date'] != '' ? to_sql_date($data['departure_date']) : null;
        $data['return_date']     = isset($data['return_date']) && $data['return_date'] != '' ? to_sql_date($data['return_date']) : null;

        $this->db->insert(db_prefix() . 'travel_groups', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Travel Group Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update group
     * @param  array $data
     * @param  mixed $id
     * @return boolean
     */
    public function update($data, $id)
    {
        $data['departure_date']  = isset($data['departure_date']) && $data['departure_date'] != '' ? to_sql_date($data['departure_date']) : null;
        $data['return_date']     = isset($data['return_date']) && $data['return_date'] != '' ? to_sql_date($data['return_date']) : null;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'travel_groups', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Travel Group Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete group and its members
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_groups');

        if ($this->db->affected_rows() > 0) {
            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_members');

            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_itinerary_stops');

            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_transport');

            log_activity('Travel Group Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get all itinerary stops (city + days + hotel) for a group, in order
     * @param  mixed $group_id
     * @return array
     */
    public function get_itinerary_stops($group_id)
    {
        $this->db->where('group_id', $group_id);
        $this->db->order_by('display_order', 'asc');

        return $this->db->get(db_prefix() . 'travel_group_itinerary_stops')->result_array();
    }

    /**
     * Short summary of a group's itinerary cities, for use in list views
     * @param  mixed $group_id
     * @return string
     */
    public function get_destinations_summary($group_id)
    {
        $stops = $this->get_itinerary_stops($group_id);

        if (count($stops) == 0) {
            return '';
        }

        $cities = array_column($stops, 'city');

        if (count($cities) > 2) {
            return $cities[0] . ', ' . $cities[1] . ' +' . (count($cities) - 2);
        }

        return implode(', ', $cities);
    }

    /**
     * Short summary of a group's transport carriers, for use in list views
     * @param  mixed $group_id
     * @return string
     */
    public function get_transport_summary($group_id)
    {
        $transport = $this->get_transport($group_id);

        if (count($transport) == 0) {
            return '';
        }

        $carriers = array_column($transport, 'carrier_name');

        if (count($carriers) > 2) {
            return $carriers[0] . ', ' . $carriers[1] . ' +' . (count($carriers) - 2);
        }

        return implode(', ', $carriers);
    }

    /**
     * Replace all itinerary stops for a group with the given list
     * @param  mixed $group_id
     * @param  array $stops  array of ['city' => ..., 'days' => ..., 'hotel_name' => ...]
     * @return void
     */
    public function save_itinerary_stops($group_id, $stops)
    {
        $this->db->where('group_id', $group_id);
        $this->db->delete(db_prefix() . 'travel_group_itinerary_stops');

        foreach ($stops as $order => $stop) {
            if (trim($stop['city']) == '') {
                continue;
            }

            $this->db->insert(db_prefix() . 'travel_group_itinerary_stops', [
                'group_id'      => $group_id,
                'city'          => $stop['city'],
                'days'          => $stop['days'] == '' ? 1 : $stop['days'],
                'hotel_name'    => $stop['hotel_name'],
                'display_order' => $order,
            ]);
        }
    }

    /**
     * Get all transport entries (carrier + type + reference) for a group, in order
     * @param  mixed $group_id
     * @return array
     */
    public function get_transport($group_id)
    {
        $this->db->where('group_id', $group_id);
        $this->db->order_by('display_order', 'asc');

        return $this->db->get(db_prefix() . 'travel_group_transport')->result_array();
    }

    /**
     * Replace all transport entries for a group with the given list
     * @param  mixed $group_id
     * @param  array $transport  array of ['carrier_name' => ..., 'carrier_type' => ..., 'carrier_reference' => ...]
     * @return void
     */
    public function save_transport($group_id, $transport)
    {
        $this->db->where('group_id', $group_id);
        $this->db->delete(db_prefix() . 'travel_group_transport');

        foreach ($transport as $order => $entry) {
            if (trim($entry['carrier_name']) == '') {
                continue;
            }

            $this->db->insert(db_prefix() . 'travel_group_transport', [
                'group_id'          => $group_id,
                'carrier_name'      => $entry['carrier_name'],
                'carrier_type'      => $entry['carrier_type'],
                'carrier_reference' => $entry['carrier_reference'],
                'display_order'     => $order,
            ]);
        }
    }

    /**
     * Get all members (travelers) assigned to a group
     * @param  mixed $group_id
     * @return array
     */
    public function get_members($group_id)
    {
        $this->db->select(db_prefix() . 'travel_group_members.*, ' . db_prefix() . 'travel_bookings.clientid, ' . db_prefix() . 'clients.company as client_company')
            ->from(db_prefix() . 'travel_group_members')
            ->join(db_prefix() . 'travel_bookings', db_prefix() . 'travel_bookings.id = ' . db_prefix() . 'travel_group_members.booking_id', 'left')
            ->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'travel_bookings.clientid', 'left')
            ->where(db_prefix() . 'travel_group_members.group_id', $group_id)
            ->order_by(db_prefix() . 'travel_group_members.traveler_name', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Get a single group member (traveler)
     * @param  mixed $id
     * @return mixed
     */
    public function get_member($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'travel_group_members')->row();
    }

    /**
     * Get bookings eligible to be added to a group (same package, not already a member).
     *
     * Also returns each booking's client's primary contact name (fallback traveler name) and
     * their current passport data if any is on file, so the "Add Traveler" form can auto-fill
     * name/passport number/expiry the moment a booking is selected, instead of staff retyping
     * data that's already recorded against the client.
     *
     * @param  mixed $group_id
     * @param  mixed $package_id
     * @return array
     */
    public function get_eligible_bookings($group_id, $package_id)
    {
        $this->db->select(
            db_prefix() . 'travel_bookings.*, '
            . db_prefix() . 'clients.company as client_company, '
            . db_prefix() . 'contacts.firstname as contact_firstname, '
            . db_prefix() . 'contacts.lastname as contact_lastname, '
            . 'passport.passport_number as passport_number, '
            . 'passport.passport_expiry as passport_expiry, '
            . 'passport.surname as passport_surname, '
            . 'passport.given_names as passport_given_names'
        )
            ->from(db_prefix() . 'travel_bookings')
            ->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'travel_bookings.clientid', 'left')
            ->join(db_prefix() . 'contacts', db_prefix() . 'contacts.userid = ' . db_prefix() . 'travel_bookings.clientid AND ' . db_prefix() . 'contacts.is_primary = 1', 'left')
            ->join(db_prefix() . 'travel_client_passports passport', 'passport.clientid = ' . db_prefix() . 'travel_bookings.clientid AND passport.is_current = 1', 'left')
            ->where(db_prefix() . 'travel_bookings.package_id', $package_id)
            ->where('NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'travel_group_members WHERE ' . db_prefix() . 'travel_group_members.booking_id = ' . db_prefix() . 'travel_bookings.id AND ' . db_prefix() . 'travel_group_members.group_id = ' . intval($group_id) . ')', null, false);

        return $this->db->get()->result_array();
    }

    /**
     * Add a traveler (group member) to a group
     * @param  mixed $group_id
     * @param  array $data
     * @return mixed  insert id, false on failure, or 'no_seats'/'invalid_booking'/'group_not_active'
     */
    public function add_member($group_id, $data)
    {
        $group = $this->get($group_id);

        if (!$group) {
            return false;
        }

        // A group that has already departed/completed/cancelled shouldn't quietly keep
        // accumulating new travelers via this form - those states are excluded from
        // get_upcoming_departures() and other operational views, so members added here would
        // otherwise go unnoticed.
        $inactive_statuses = [TRAVEL_GROUP_STATUS_DEPARTED, TRAVEL_GROUP_STATUS_COMPLETED, TRAVEL_GROUP_STATUS_CANCELLED];

        if (in_array((int) $group->status, $inactive_statuses, true)) {
            return 'group_not_active';
        }

        $data['group_id']   = $group_id;
        $data['booking_id'] = $data['booking_id'] == '' ? 0 : $data['booking_id'];

        // get_eligible_bookings() only filters the dropdown client-side - re-validate here that
        // the posted booking_id actually exists, belongs to this group's package, and isn't
        // already attached to some group member, so a crafted request can't attach an unrelated
        // (or already-used) booking as a traveler.
        if ($data['booking_id']) {
            $this->db->where('id', $data['booking_id']);
            $this->db->where('package_id', $group->package_id);
            $booking = $this->db->get(db_prefix() . 'travel_bookings')->row();

            if (!$booking) {
                return 'invalid_booking';
            }

            $this->db->where('booking_id', $data['booking_id']);
            if ($this->db->count_all_results(db_prefix() . 'travel_group_members') > 0) {
                return 'invalid_booking';
            }

            // The "Add Traveler" form only collects name/passport number/expiry - if the
            // client behind this booking already has a passport on file (uploaded via their
            // own portal or by staff on their profile), pull the richer MRZ-derived fields from
            // it automatically instead of requiring staff to retype them for every trip. A
            // staff member can still override any of these afterwards from the member's own
            // Edit Details tab, which always takes precedence since it writes directly to this
            // row - this is only a one-time convenience fill at creation.
            $this->load->model('travel_agency/travel_client_passports_model');
            $client_passport = $this->travel_client_passports_model->get_current($booking->clientid);

            if ($client_passport) {
                $data['passport_number']      = $data['passport_number'] != '' ? $data['passport_number'] : $client_passport['passport_number'];
                // Falls back to the client's own already-SQL-format expiry only when nothing
                // was posted; a posted value still goes through the single to_sql_date() pass
                // below like every other date field in this model, so it's never converted twice.
                $data['_fallback_passport_expiry'] = $client_passport['passport_expiry'];
                $data['passport_surname']     = $client_passport['surname'];
                $data['passport_given_names'] = $client_passport['given_names'];
                $data['nationality']          = $client_passport['nationality'];
                $data['date_of_birth']        = $client_passport['date_of_birth'];
                $data['gender']               = $client_passport['gender'];
                $data['passport_mrz_raw']     = $client_passport['mrz_raw'];
            }
        }

        $lock_name = 'travel_agency_group_seats_' . (int) $group_id;
        $acquired  = $this->db->query('SELECT GET_LOCK(?, 10) AS locked', [$lock_name])->row();

        if (!$acquired || (int) $acquired->locked !== 1) {
            return 'no_seats';
        }

        try {
            if ($group->seats_total > 0 && $this->count_members($group_id) >= $group->seats_total) {
                return 'no_seats';
            }

            $data['visa_status']     = $data['visa_status'] == '' ? TRAVEL_VISA_STATUS_NOT_SUBMITTED : $data['visa_status'];
            $data['passport_expiry'] = isset($data['passport_expiry']) && $data['passport_expiry'] != ''
                ? to_sql_date($data['passport_expiry'])
                : (isset($data['_fallback_passport_expiry']) ? $data['_fallback_passport_expiry'] : null);
            unset($data['_fallback_passport_expiry']);
            $data['datecreated']     = date('Y-m-d H:i:s');

            $this->db->insert(db_prefix() . 'travel_group_members', $data);
            $insert_id = $this->db->insert_id();

            if ($insert_id) {
                log_activity('Traveler Added to Travel Group [Group ID:' . $group_id . ']');

                return $insert_id;
            }

            return false;
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
        }
    }

    /**
     * Update a group member's details (passport/visa/notes)
     *
     * $group_id is required and enforced in the WHERE clause (not just used for a redirect) so
     * that a member id can't be edited via a mismatched/guessed group_id in the URL.
     * @param  array $data
     * @param  mixed $id
     * @param  mixed $group_id
     * @return boolean
     */
    public function update_member($data, $id, $group_id)
    {
        if (isset($data['passport_expiry'])) {
            $data['passport_expiry'] = $data['passport_expiry'] != '' ? to_sql_date($data['passport_expiry']) : null;
        }

        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = $data['date_of_birth'] != '' ? to_sql_date($data['date_of_birth']) : null;
        }

        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->update(db_prefix() . 'travel_group_members', $data);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Store an uploaded photo or passport scan filename for a group member
     * @param  mixed  $id
     * @param  mixed  $group_id
     * @param  string $field  'photo' or 'passport_scan'
     * @param  string $filename
     * @return boolean
     */
    public function update_member_file($id, $group_id, $field, $filename)
    {
        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->update(db_prefix() . 'travel_group_members', [$field => $filename]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Update only the visa status of a group member
     * @param  mixed $id
     * @param  mixed $group_id
     * @param  int   $status
     * @return boolean
     */
    public function update_member_visa_status($id, $group_id, $status)
    {
        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->update(db_prefix() . 'travel_group_members', ['visa_status' => $status]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Remove a traveler from a group
     * @param  mixed $id
     * @param  mixed $group_id
     * @return boolean
     */
    public function remove_member($id, $group_id)
    {
        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->delete(db_prefix() . 'travel_group_members');

        return $this->db->affected_rows() > 0;
    }
}
