<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Daily staff notifications for at-risk passports, near-term departures and overdue invoices -
 * hooked into Perfex's own cron run (after_cron_run) rather than a separate cron entry, and
 * guarded to fire at most once per calendar day since the site cron runs every few minutes.
 */
class Travel_notifications_model extends App_Model
{
    const LAST_RUN_OPTION = 'travel_agency_notifications_last_run';

    public function __construct()
    {
        parent::__construct();
    }

    public function run_daily_checks()
    {
        if (get_option(self::LAST_RUN_OPTION) === date('Y-m-d')) {
            return;
        }

        update_option(self::LAST_RUN_OPTION, date('Y-m-d'));

        $this->load->model('staff_model');
        $staff = $this->staff_model->get('', ['active' => 1]);

        if (empty($staff)) {
            return;
        }

        $this->notify_at_risk_passports($staff);
        $this->notify_upcoming_departures($staff);
        $this->notify_overdue_invoices($staff);
    }

    /**
     * Groups departing within 30 days that have at least one traveler with an expired or
     * soon-to-expire (within 6 months of departure) passport.
     */
    private function notify_at_risk_passports($staff)
    {
        $this->load->model('travel_agency/travel_groups_model');
        $groups = $this->travel_groups_model->get_upcoming_departures(30);

        foreach ($groups as $group) {
            if (empty($group['has_at_risk_passport'])) {
                continue;
            }

            foreach ($staff as $member) {
                add_notification([
                    'description'     => 'travel_agency_notification_at_risk_passport',
                    'touserid'        => $member['staffid'],
                    'fromcompany'     => 1,
                    'fromuserid'      => 0,
                    'link'            => 'travel_agency/group/' . $group['id'],
                    'additional_data' => serialize([$group['name']]),
                ]);
            }
        }
    }

    /**
     * Groups departing within the next 3 days - a final operational reminder.
     */
    private function notify_upcoming_departures($staff)
    {
        $this->load->model('travel_agency/travel_groups_model');
        $groups = $this->travel_groups_model->get_upcoming_departures(3);

        foreach ($groups as $group) {
            foreach ($staff as $member) {
                add_notification([
                    'description'     => 'travel_agency_notification_upcoming_departure',
                    'touserid'        => $member['staffid'],
                    'fromcompany'     => 1,
                    'fromuserid'      => 0,
                    'link'            => 'travel_agency/group/' . $group['id'],
                    'additional_data' => serialize([$group['name'], _d($group['departure_date'])]),
                ]);
            }
        }
    }

    /**
     * Invoices linked to travel bookings that are overdue (Perfex marks these via its own cron,
     * so by the time this runs the status is already accurate).
     */
    private function notify_overdue_invoices($staff)
    {
        $this->db->select(db_prefix() . 'travel_bookings.id as booking_id, ' . db_prefix() . 'invoices.id as invoice_id, ' . db_prefix() . 'invoices.number, ' . db_prefix() . 'invoices.duedate');
        $this->db->from(db_prefix() . 'travel_bookings');
        $this->db->join(db_prefix() . 'invoices', db_prefix() . 'invoices.id = ' . db_prefix() . 'travel_bookings.invoiceid');
        $this->db->where(db_prefix() . 'invoices.status', 4); // STATUS_OVERDUE
        $this->db->where(db_prefix() . 'travel_bookings.status !=', TRAVEL_BOOKING_STATUS_CANCELLED);
        $overdue = $this->db->get()->result_array();

        foreach ($overdue as $row) {
            foreach ($staff as $member) {
                add_notification([
                    'description'     => 'travel_agency_notification_overdue_invoice',
                    'touserid'        => $member['staffid'],
                    'fromcompany'     => 1,
                    'fromuserid'      => 0,
                    'link'            => 'invoices/list_invoices/' . $row['invoice_id'],
                    'additional_data' => serialize([format_invoice_number($row['invoice_id'])]),
                ]);
            }
        }
    }
}
