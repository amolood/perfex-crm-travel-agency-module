<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_reports_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Revenue, seats booked and margin per package, excluding cancelled bookings.
     * @return array
     */
    public function get_package_revenue()
    {
        $this->db->select(db_prefix() . 'travel_packages.id, ' . db_prefix() . 'travel_packages.name, '
            . db_prefix() . 'travel_packages.destination, ' . db_prefix() . 'travel_packages.currency, '
            . db_prefix() . 'travel_packages.price, ' . db_prefix() . 'travel_packages.cost, '
            . db_prefix() . 'travel_packages.seats_available, '
            . 'COALESCE(SUM(CASE WHEN ' . db_prefix() . 'travel_bookings.status != ' . TRAVEL_BOOKING_STATUS_CANCELLED
            . ' THEN ' . db_prefix() . 'travel_bookings.total ELSE 0 END), 0) as revenue, '
            . 'COALESCE(SUM(CASE WHEN ' . db_prefix() . 'travel_bookings.status != ' . TRAVEL_BOOKING_STATUS_CANCELLED
            . ' THEN ' . db_prefix() . 'travel_bookings.travelers ELSE 0 END), 0) as seats_booked');
        $this->db->from(db_prefix() . 'travel_packages');
        $this->db->join(db_prefix() . 'travel_bookings', db_prefix() . 'travel_bookings.package_id = ' . db_prefix() . 'travel_packages.id', 'left');
        $this->db->group_by(db_prefix() . 'travel_packages.id');
        $this->db->order_by('revenue', 'desc');

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['cost_total']   = $row['cost'] * $row['seats_booked'];
            $row['margin_total'] = $row['revenue'] - $row['cost_total'];
            $row['occupancy_percent'] = $row['seats_available'] > 0
                ? round(($row['seats_booked'] / $row['seats_available']) * 100, 1)
                : null;
        }

        return $rows;
    }

    /**
     * Amount paid vs. balance due per supplier, joined with linked package count.
     * @return array
     */
    public function get_supplier_summary()
    {
        $this->load->model('travel_suppliers_model');
        $this->load->model('travel_supplier_payments_model');

        $suppliers  = $this->travel_suppliers_model->get();
        $summaries  = $this->travel_supplier_payments_model->get_all_account_summaries();
        $rows       = [];

        foreach ($suppliers as $supplier) {
            $this->db->where('supplier_id', $supplier['id']);
            $package_count = $this->db->get(db_prefix() . 'travel_packages')->num_rows();

            $due = 0;
            $paid = 0;
            $currency = null;

            if (isset($summaries[$supplier['id']])) {
                foreach ($summaries[$supplier['id']] as $row) {
                    $due += $row['due'];
                    $paid += $row['paid'];
                    $currency = $row['currency'];
                }
            }

            $rows[] = [
                'id'            => $supplier['id'],
                'name'          => $supplier['name'],
                'type'          => $supplier['type'],
                'package_count' => $package_count,
                'due'           => $due,
                'paid'          => $paid,
                'balance'       => $due - $paid,
                'currency'      => $currency,
            ];
        }

        return $rows;
    }

    /**
     * Booking volume grouped by month for the last $months months.
     * @param  int $months
     * @return array
     */
    public function get_monthly_bookings($months = 12)
    {
        $from = date('Y-m-01', strtotime('-' . ((int) $months - 1) . ' months'));

        $this->db->select('DATE_FORMAT(datecreated, "%Y-%m") as month, COUNT(*) as bookings_count, '
            . 'COALESCE(SUM(CASE WHEN status != ' . TRAVEL_BOOKING_STATUS_CANCELLED . ' THEN total ELSE 0 END), 0) as revenue');
        $this->db->where('datecreated >=', $from);
        $this->db->group_by('month');
        $this->db->order_by('month', 'asc');

        return $this->db->get(db_prefix() . 'travel_bookings')->result_array();
    }
}
