<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'type',
    'email',
    'phonenumber',
    'active',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'travel_suppliers';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            $_data = '<a href="' . admin_url('travel_agency/supplier/' . $aRow['id']) . '" class="tw-font-medium">' . e($_data) . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('travel_agency/supplier/' . $aRow['id']) . '">' . _l('edit') . '</a>';

            if (staff_can('delete', 'travel_agency_suppliers')) {
                $_data .= ' | <a href="' . admin_url('travel_agency/delete_supplier/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'active') {
            $_data = $_data == 1
                ? '<span class="label label-success">' . _l('travel_agency_active') . '</span>'
                : '<span class="label label-default">' . _l('travel_agency_inactive') . '</span>';
        } else {
            $_data = e($_data);
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
