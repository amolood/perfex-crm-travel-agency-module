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

            // Surfaces departures that need attention (an expired/soon-to-expire traveler
            // passport) directly on the dashboard, instead of that only being visible by
            // opening each group's roster page individually.
            $groups[$key]['has_at_risk_passport'] = $this->has_at_risk_passport($group['id'], $group['departure_date']);
        }

        return $groups;
    }

    /**
     * Whether any traveler in this group has an expired or soon-to-expire passport relative to
     * the group's departure date - reuses the same warning logic already shown per-member on
     * the group roster page (travel_agency_passport_expiry_warning_class()).
     * @param  mixed  $group_id
     * @param  string $departure_date
     * @return boolean
     */
    private function has_at_risk_passport($group_id, $departure_date)
    {
        $this->db->where('group_id', $group_id);
        $this->db->where('passport_expiry IS NOT NULL');
        $members = $this->db->get(db_prefix() . 'travel_group_members')->result_array();

        foreach ($members as $member) {
            if (travel_agency_passport_expiry_warning_class($member['passport_expiry'], $departure_date) !== '') {
                return true;
            }
        }

        return false;
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

            $this->sync_calendar_event($insert_id);

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

        $updated = $this->db->affected_rows() > 0;

        // Keep the calendar event's dates/title in sync even when nothing else about the group
        // row changed (affected_rows() is 0 if departure/return happen to already match what
        // was submitted), so re-saving the form always reflects the current state.
        $this->sync_calendar_event($id);

        if ($updated) {
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
        $group = $this->get($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'travel_groups');

        if ($this->db->affected_rows() > 0) {
            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_members');

            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_itinerary_stops');

            $this->db->where('group_id', $id);
            $this->db->delete(db_prefix() . 'travel_group_transport');

            if ($group && $group->calendar_event_id) {
                $this->db->where('eventid', $group->calendar_event_id);
                $this->db->delete(db_prefix() . 'events');
            }

            log_activity('Travel Group Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Create or update the calendar event (tblevents) tied to this group's departure/return
     * dates, and store its id back onto the group row so future saves update the same event
     * instead of creating a new one each time.
     *
     * A single event spans the whole trip (start = departure_date, end = return_date) rather
     * than two separate point-in-time events, since that is how a multi-day trip naturally
     * reads on a calendar. If the group has no departure_date, any previously-linked event is
     * removed instead - there is nothing meaningful left to show.
     *
     * @param  mixed $group_id
     * @return void
     */
    private function sync_calendar_event($group_id)
    {
        // The read-check-insert-writeback sequence below is not atomic on its own - two staff
        // members saving the same group at nearly the same moment (realistic here, since the
        // group form's "Edit Details" and itinerary/transport sections post separately to the
        // same action) could otherwise both see calendar_event_id == 0 and both insert a new
        // tblevents row, leaving a duplicate, permanently-orphaned event behind. Scoped to this
        // group id only, same GET_LOCK/RELEASE_LOCK pattern already used for seat allocation in
        // add_member() below.
        $lock_name = 'travel_agency_group_calendar_' . (int) $group_id;
        $acquired  = $this->db->query('SELECT GET_LOCK(?, 10) AS locked', [$lock_name])->row();

        if (!$acquired || (int) $acquired->locked !== 1) {
            return;
        }

        try {
            $group = $this->get($group_id);

            if (!$group) {
                return;
            }

            if (!$group->departure_date) {
                if ($group->calendar_event_id) {
                    $this->db->where('eventid', $group->calendar_event_id);
                    $this->db->delete(db_prefix() . 'events');

                    $this->db->where('id', $group_id);
                    $this->db->update(db_prefix() . 'travel_groups', ['calendar_event_id' => 0]);
                }

                return;
            }

            $title = $group->name;

            if (!empty($group->package_destination)) {
                $title .= ' - ' . $group->package_destination;
            }

            $event_data = [
                'title'       => $title,
                'description' => nl2br(_l('travel_agency_group_calendar_event_description', [format_travel_group_status($group->status)])),
                'start'       => $group->departure_date . ' 00:00:00',
                'end'         => ($group->return_date ?: $group->departure_date) . ' 23:59:59',
                'public'      => 1,
            ];

            if ($group->calendar_event_id) {
                $this->db->where('eventid', $group->calendar_event_id);
                $exists = $this->db->get(db_prefix() . 'events')->row();

                if ($exists) {
                    $this->db->where('eventid', $group->calendar_event_id);
                    $this->db->update(db_prefix() . 'events', $event_data);

                    return;
                }
            }

            // No linked event yet (new group, or the event was manually deleted from the
            // calendar since) - create one and store its id back onto the group row.
            $event_data['userid'] = get_staff_user_id() ?: $group->addedfrom;
            $event_data['color']  = '#3a87ad';

            $this->db->insert(db_prefix() . 'events', $event_data);
            $event_id = $this->db->insert_id();

            if ($event_id) {
                $this->db->where('id', $group_id);
                $this->db->update(db_prefix() . 'travel_groups', ['calendar_event_id' => $event_id]);
            }
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
        }
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
            . 'passport.given_names as passport_given_names, '
            . 'passport.nationality as passport_nationality, '
            . 'passport.date_of_birth as passport_date_of_birth, '
            . 'passport.gender as passport_gender'
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
     * Re-copy passport fields (surname, given names, nationality, DOB, gender, MRZ, passport
     * number/expiry) from the client's CURRENT passport record onto an existing group member.
     *
     * add_member() only copies this data once, at the moment the member is first added - if the
     * client later renews their passport (uploads a new one via their portal or the admin
     * client-passport screen), the group roster's snapshot silently goes stale with no
     * indication anything changed. This lets staff explicitly pull the latest passport data
     * into an existing roster entry on demand, rather than that mismatch going unnoticed until
     * someone happens to compare the two records by hand.
     *
     * @param  mixed $id        group member id
     * @param  mixed $group_id  enforced in the WHERE clause, same as update_member()
     * @return mixed  true on success, or 'no_client_passport'/'invalid_member' on failure
     */
    public function refresh_member_from_client_passport($id, $group_id)
    {
        $this->db->select(db_prefix() . 'travel_group_members.*, ' . db_prefix() . 'travel_bookings.clientid as clientid')
            ->from(db_prefix() . 'travel_group_members')
            ->join(db_prefix() . 'travel_bookings', db_prefix() . 'travel_bookings.id = ' . db_prefix() . 'travel_group_members.booking_id', 'left')
            ->where(db_prefix() . 'travel_group_members.id', $id)
            ->where(db_prefix() . 'travel_group_members.group_id', $group_id);

        $member = $this->db->get()->row();

        if (!$member || !$member->clientid) {
            return 'invalid_member';
        }

        $this->load->model('travel_agency/travel_client_passports_model');
        $client_passport = $this->travel_client_passports_model->get_current($member->clientid);

        if (!$client_passport) {
            return 'no_client_passport';
        }

        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->update(db_prefix() . 'travel_group_members', [
            'passport_number'      => $client_passport['passport_number'],
            'passport_expiry'      => $client_passport['passport_expiry'],
            'passport_surname'     => $client_passport['surname'],
            'passport_given_names' => $client_passport['given_names'],
            'nationality'          => $client_passport['nationality'],
            'date_of_birth'        => $client_passport['date_of_birth'],
            'gender'               => $client_passport['gender'],
            'passport_mrz_raw'     => $client_passport['mrz_raw'],
        ]);

        return true;
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
    /**
     * Other active groups of the same package a traveler could be transferred into.
     * @param  mixed $group_id
     * @param  mixed $package_id
     * @return array
     */
    public function get_other_groups_for_package($group_id, $package_id)
    {
        $inactive_statuses = [TRAVEL_GROUP_STATUS_DEPARTED, TRAVEL_GROUP_STATUS_COMPLETED, TRAVEL_GROUP_STATUS_CANCELLED];

        $this->db->where('package_id', $package_id);
        $this->db->where('id !=', $group_id);
        $this->db->where_not_in('status', $inactive_statuses);
        $this->db->order_by('departure_date', 'asc');

        return $this->db->get(db_prefix() . 'travel_groups')->result_array();
    }

    public function remove_member($id, $group_id)
    {
        $this->db->where('id', $id);
        $this->db->where('group_id', $group_id);
        $this->db->delete(db_prefix() . 'travel_group_members');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Move a traveler from one group to another group of the SAME package (e.g. rescheduling to
     * a later departure of the same trip). Both groups' seat locks are acquired in ascending id
     * order so two simultaneous transfers crossing the same two groups can never deadlock, and
     * the destination's seat capacity is re-checked under lock exactly like add_member() does.
     * @param  mixed $member_id
     * @param  mixed $from_group_id
     * @param  mixed $to_group_id
     * @return mixed true, false, or 'no_seats'/'different_package'/'not_found'
     */
    public function transfer_member($member_id, $from_group_id, $to_group_id)
    {
        if ((int) $from_group_id === (int) $to_group_id) {
            return 'not_found';
        }

        $from_group = $this->get($from_group_id);
        $to_group   = $this->get($to_group_id);

        if (!$from_group || !$to_group) {
            return 'not_found';
        }

        if ((int) $from_group->package_id !== (int) $to_group->package_id) {
            return 'different_package';
        }

        $this->db->where('id', $member_id);
        $this->db->where('group_id', $from_group_id);
        $member = $this->db->get(db_prefix() . 'travel_group_members')->row();

        if (!$member) {
            return 'not_found';
        }

        $ids         = [(int) $from_group_id, (int) $to_group_id];
        sort($ids);
        $lock_names  = array_map(function ($id) {
            return 'travel_agency_group_seats_' . $id;
        }, $ids);

        foreach ($lock_names as $lock_name) {
            $acquired = $this->db->query('SELECT GET_LOCK(?, 10) AS locked', [$lock_name])->row();

            if (!$acquired || (int) $acquired->locked !== 1) {
                foreach ($lock_names as $release_name) {
                    $this->db->query('SELECT RELEASE_LOCK(?)', [$release_name]);
                }

                return 'no_seats';
            }
        }

        try {
            if ($to_group->seats_total > 0 && $this->count_members($to_group_id) >= $to_group->seats_total) {
                return 'no_seats';
            }

            $transfer_note = trim($member->notes) !== '' ? $member->notes . "\n" : '';
            $transfer_note .= sprintf(_l('travel_agency_group_member_transfer_note'), $from_group->name, date('Y-m-d'));

            $this->db->where('id', $member_id);
            $this->db->update(db_prefix() . 'travel_group_members', [
                'group_id' => $to_group_id,
                'notes'    => $transfer_note,
            ]);

            if ($this->db->affected_rows() > 0) {
                log_activity('Traveler Moved Between Travel Groups [Member ID:' . $member_id . ', From:' . $from_group_id . ', To:' . $to_group_id . ']');

                return true;
            }

            return false;
        } finally {
            foreach ($lock_names as $release_name) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$release_name]);
            }
        }
    }
}
