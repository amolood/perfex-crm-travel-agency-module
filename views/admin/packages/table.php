<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'travel_packages.name',
    db_prefix() . 'travel_packages.destination',
    db_prefix() . 'travel_suppliers.name',
    db_prefix() . 'travel_packages.price',
    db_prefix() . 'travel_packages.seats_available',
    db_prefix() . 'travel_packages.active',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'travel_packages';

$join = ['LEFT JOIN ' . db_prefix() . 'travel_suppliers ON ' . db_prefix() . 'travel_suppliers.id = ' . db_prefix() . 'travel_packages.supplier_id'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [db_prefix() . 'travel_packages.id as id', db_prefix() . 'travel_packages.currency as currency', db_prefix() . 'travel_packages.cost as cost']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == db_prefix() . 'travel_packages.name') {
            $_data = '<a href="' . admin_url('travel_agency/package/' . $aRow['id']) . '" class="tw-font-medium">' . e($_data) . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('travel_agency/package/' . $aRow['id']) . '">' . _l('edit') . '</a>';

            if (staff_can('delete', 'travel_agency')) {
                $_data .= ' | <a href="' . admin_url('travel_agency/delete_package/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_packages.price') {
            $this->ci->load->model('currencies_model');
            $package_currency = $this->ci->currencies_model->get($aRow['currency']);
            $_data            = e(app_format_money($_data, $package_currency ? $package_currency : get_base_currency()));
        } elseif ($aColumns[$i] == db_prefix() . 'travel_packages.active') {
            $_data = $_data == 1
                ? '<span class="label label-success">' . _l('travel_agency_active') . '</span>'
                : '<span class="label label-default">' . _l('travel_agency_inactive') . '</span>';
        } else {
            $_data = e($_data);
        }
        $row[] = $_data;
    }

    $profit_per_seat = $aRow[db_prefix() . 'travel_packages.price'] - $aRow['cost'];
    $row[]           = e(app_format_money($profit_per_seat, $package_currency ? $package_currency : get_base_currency()));

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
