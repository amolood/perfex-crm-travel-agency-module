<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'clients.company',
    db_prefix() . 'travel_client_passports.passport_number',
    db_prefix() . 'travel_client_passports.passport_expiry',
];

$sIndexColumn = 'userid';
$sTable       = db_prefix() . 'clients';

$join = ['LEFT JOIN ' . db_prefix() . 'travel_client_passports ON ' . db_prefix() . 'travel_client_passports.clientid = ' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'travel_client_passports.is_current = 1'];

// Optional ?filter=expiring query param (see manage.php's toggle link) narrows the list down
// to passports that are expired or expiring within 6 months, instead of staff having to
// manually scan every row's status badge to spot the ones needing attention.
//
// This file is include()'d by App::get_table_data() rather than run as a controller method, so
// $this is a wrapper object, not the CI superobject directly - $this->input was null here
// (confirmed live: fataled every load of this table regardless of whether ?filter was even
// present). The other table.php files in this module reach the real CI instance via $this->ci
// (e.g. groups/table.php, bookings/table.php) - matching that same established convention here.
$where = [];

if ($this->ci->input->get('filter') === 'expiring') {
    $six_months_from_now = date('Y-m-d', strtotime('+6 months'));
    $where[] = "AND " . db_prefix() . "travel_client_passports.passport_expiry IS NOT NULL AND " . db_prefix() . "travel_client_passports.passport_expiry <= '" . $six_months_from_now . "'";
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'clients.userid as id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == db_prefix() . 'clients.company') {
            $_data = '<a href="' . admin_url('travel_agency/client_passport/' . $aRow['id']) . '" class="tw-font-medium">' . e($_data) . '</a>';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_client_passports.passport_expiry') {
            $_data = $_data ? e(_d($_data)) : '';
        } else {
            $_data = $_data != '' ? e($_data) : '<span class="text-muted">' . _l('travel_agency_client_passports_none_on_file') . '</span>';
        }
        $row[] = $_data;
    }

    $passport_expiry = $aRow[db_prefix() . 'travel_client_passports.passport_expiry'];

    if (!$aRow[db_prefix() . 'travel_client_passports.passport_number']) {
        $status = '<span class="label label-default">' . _l('travel_agency_client_passports_none_on_file') . '</span>';
    } elseif ($passport_expiry && strtotime($passport_expiry) < strtotime(date('Y-m-d'))) {
        $status = '<span class="label label-danger">' . _l('travel_agency_client_passports_expired') . '</span>';
    } elseif ($passport_expiry && strtotime($passport_expiry) < strtotime('+6 months')) {
        $status = '<span class="label label-warning">' . _l('travel_agency_client_passports_expiring_soon') . '</span>';
    } else {
        $status = '<span class="label label-success">' . _l('travel_agency_client_passports_valid') . '</span>';
    }

    $row[] = $status;
    $output['aaData'][] = $row;
}
