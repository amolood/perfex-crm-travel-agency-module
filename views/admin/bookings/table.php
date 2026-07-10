<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'travel_packages.name',
    db_prefix() . 'clients.company',
    db_prefix() . 'travel_bookings.travelers',
    db_prefix() . 'travel_bookings.travel_date',
    db_prefix() . 'travel_bookings.total',
    db_prefix() . 'travel_bookings.status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'travel_bookings';

$join = [
    'LEFT JOIN ' . db_prefix() . 'travel_packages ON ' . db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_bookings.package_id',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'travel_bookings.clientid',
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [db_prefix() . 'travel_bookings.id as id', db_prefix() . 'travel_packages.currency as package_currency']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == db_prefix() . 'travel_packages.name') {
            $_data = '<a href="' . admin_url('travel_agency/booking/' . $aRow['id']) . '" class="tw-font-medium">' . e($_data) . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('travel_agency/booking/' . $aRow['id']) . '">' . _l('edit') . '</a>';

            if (staff_can('delete', 'travel_agency')) {
                $_data .= ' | <a href="' . admin_url('travel_agency/delete_booking/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_bookings.travel_date') {
            $_data = $_data ? e(_d($_data)) : '';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_bookings.total') {
            $this->ci->load->model('currencies_model');
            $booking_currency = $this->ci->currencies_model->get($aRow['package_currency']);
            $_data            = e(app_format_money($_data, $booking_currency ? $booking_currency : get_base_currency()));
        } elseif ($aColumns[$i] == db_prefix() . 'travel_bookings.status') {
            $_data = '<span class="label label-' . travel_booking_status_label_class($_data) . '">' . e(format_travel_booking_status($_data)) . '</span>';
        } else {
            $_data = e($_data);
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
