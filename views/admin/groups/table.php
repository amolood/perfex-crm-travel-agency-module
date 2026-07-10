<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'travel_groups.name',
    db_prefix() . 'travel_packages.name',
    db_prefix() . 'travel_groups.departure_date',
    db_prefix() . 'travel_groups.status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'travel_groups';

$join = ['LEFT JOIN ' . db_prefix() . 'travel_packages ON ' . db_prefix() . 'travel_packages.id = ' . db_prefix() . 'travel_groups.package_id'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [db_prefix() . 'travel_groups.id as id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == db_prefix() . 'travel_groups.name') {
            $_data = '<a href="' . admin_url('travel_agency/group/' . $aRow['id']) . '" class="tw-font-medium">' . e($_data) . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('travel_agency/group/' . $aRow['id']) . '">' . _l('edit') . '</a>';

            if (staff_can('delete', 'travel_agency')) {
                $_data .= ' | <a href="' . admin_url('travel_agency/delete_group/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_groups.departure_date') {
            $_data = $_data ? e(_d($_data)) : '';
        } elseif ($aColumns[$i] == db_prefix() . 'travel_groups.status') {
            $_data = '<span class="label label-' . travel_group_status_label_class($_data) . '">' . e(format_travel_group_status($_data)) . '</span>';
        } else {
            $_data = e($_data);
        }
        $row[] = $_data;
    }
    $row[] = e($this->ci->travel_groups_model->get_destinations_summary($aRow['id']));
    $row[] = e($this->ci->travel_groups_model->get_transport_summary($aRow['id']));
    $row[] = e($this->ci->travel_groups_model->count_members($aRow['id']));
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
