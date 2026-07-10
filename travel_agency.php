<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Travel Agency
Description: Manage travel packages, supplier relationships and customer bookings, with a client portal for viewing bookings and itineraries.
Version: 1.0.0
Author: Digitalize.sd
Author URI: https://digitalize.sd
Requires at least: 2.3.*
*/

define('TRAVEL_AGENCY_MODULE_NAME', 'travel_agency');

define('TRAVEL_BOOKING_STATUS_PENDING', 1);
define('TRAVEL_BOOKING_STATUS_CONFIRMED', 2);
define('TRAVEL_BOOKING_STATUS_CANCELLED', 3);
define('TRAVEL_BOOKING_STATUS_COMPLETED', 4);

define('TRAVEL_GROUP_STATUS_PLANNING', 1);
define('TRAVEL_GROUP_STATUS_CONFIRMED', 2);
define('TRAVEL_GROUP_STATUS_DEPARTED', 3);
define('TRAVEL_GROUP_STATUS_COMPLETED', 4);
define('TRAVEL_GROUP_STATUS_CANCELLED', 5);

define('TRAVEL_VISA_STATUS_NOT_SUBMITTED', 1);
define('TRAVEL_VISA_STATUS_SUBMITTED', 2);
define('TRAVEL_VISA_STATUS_APPROVED', 3);
define('TRAVEL_VISA_STATUS_REJECTED', 4);

define('TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER', FCPATH . 'uploads/travel_group_members/');

/**
 * Get the upload folder path for a group member's files (photo/passport scan)
 *
 * @param  mixed $member_id
 *
 * @return string
 */
function travel_agency_group_member_upload_path($member_id)
{
    return TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER . $member_id . '/';
}

/**
 * Ensure the group-members uploads folder (which holds passport scans and personal photos)
 * denies direct web access, the same way every other Perfex uploads subfolder does.
 *
 * _maybe_create_upload_path() (core helper) only writes an index.html to block directory
 * listing - it does not write a deny-all .htaccess like the folders under core Perfex do
 * (uploads/clients/, uploads/invoices/, etc.), so without this a file's exact URL could be
 * fetched directly with no authentication at all, bypassing the staff permission check in
 * view_group_member_file(). Called before every upload so it self-heals on installs where the
 * folder already existed without one, not just on fresh installs.
 *
 * @return void
 */
function travel_agency_secure_group_member_uploads_folder()
{
    if (!file_exists(TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER)) {
        mkdir(TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER, 0755, true);
    }

    $htaccess = TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER . '.htaccess';

    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
    }
}

/**
 * Determine a passport-expiry warning level relative to a group's departure date.
 * Many destinations require at least 6 months of passport validity beyond travel.
 *
 * @param  string $passport_expiry  SQL date (Y-m-d)
 * @param  string $departure_date   SQL date (Y-m-d), may be null
 *
 * @return string  'danger', 'warning', or '' (no issue)
 */
function travel_agency_passport_expiry_warning_class($passport_expiry, $departure_date)
{
    if (!$passport_expiry) {
        return '';
    }

    $reference_date = $departure_date ?: date('Y-m-d');

    $expiry_ts       = strtotime($passport_expiry);
    $reference_ts    = strtotime($reference_date);
    $six_months_ts   = strtotime('+6 months', $reference_ts);

    if ($expiry_ts <= $reference_ts) {
        return 'danger';
    }

    if ($expiry_ts <= $six_months_ts) {
        return 'warning';
    }

    return '';
}

hooks()->add_action('admin_init', 'travel_agency_module_init_menu_items');
hooks()->add_action('admin_init', 'travel_agency_permissions');
hooks()->add_action('app_init', 'travel_agency_client_menu_item');
hooks()->add_filter('get_dashboard_widgets', 'travel_agency_add_dashboard_widget');

function travel_agency_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'travel_agency/widget',
        'container' => 'right-4',
    ];

    return $widgets;
}

/**
 * Register activation module hook
 */
register_activation_hook(TRAVEL_AGENCY_MODULE_NAME, 'travel_agency_module_activation_hook');

function travel_agency_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';

    travel_agency_secure_group_member_uploads_folder();
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(TRAVEL_AGENCY_MODULE_NAME, [TRAVEL_AGENCY_MODULE_NAME]);

/**
 * Init travel agency admin menu items
 * @return null
 */
function travel_agency_module_init_menu_items()
{
    $CI = &get_instance();

    if (staff_can('view', 'travel_agency')) {
        $CI->app_menu->add_sidebar_menu_item('travel_agency', [
            'name'     => _l('travel_agency'),
            'href'     => admin_url('travel_agency/packages'),
            'icon'     => 'fa-solid fa-plane',
            'position' => 45,
        ]);

        $CI->app_menu->add_sidebar_children_item('travel_agency', [
            'slug'     => 'travel_agency-packages',
            'name'     => _l('travel_agency_packages'),
            'href'     => admin_url('travel_agency/packages'),
            'icon'     => 'fa-solid fa-suitcase',
            'position' => 5,
        ]);

        $CI->app_menu->add_sidebar_children_item('travel_agency', [
            'slug'     => 'travel_agency-package-types',
            'name'     => _l('travel_agency_package_types'),
            'href'     => admin_url('travel_agency/package_types'),
            'icon'     => 'fa-solid fa-tags',
            'position' => 7,
        ]);

        $CI->app_menu->add_sidebar_children_item('travel_agency', [
            'slug'     => 'travel_agency-bookings',
            'name'     => _l('travel_agency_bookings'),
            'href'     => admin_url('travel_agency/bookings'),
            'icon'     => 'fa-solid fa-calendar-check',
            'position' => 10,
        ]);

        $CI->app_menu->add_sidebar_children_item('travel_agency', [
            'slug'     => 'travel_agency-groups',
            'name'     => _l('travel_agency_groups'),
            'href'     => admin_url('travel_agency/groups'),
            'icon'     => 'fa-solid fa-people-group',
            'position' => 12,
        ]);

        if (staff_can('view', 'travel_agency_suppliers')) {
            $CI->app_menu->add_sidebar_children_item('travel_agency', [
                'slug'     => 'travel_agency-suppliers',
                'name'     => _l('travel_agency_suppliers'),
                'icon'     => 'fa-solid fa-truck-fast',
                'href'     => admin_url('travel_agency/suppliers'),
                'position' => 15,
            ]);

            $CI->app_menu->add_sidebar_children_item('travel_agency', [
                'slug'     => 'travel_agency-supplier-accounts',
                'name'     => _l('travel_agency_supplier_accounts'),
                'icon'     => 'fa-solid fa-money-bill-wave',
                'href'     => admin_url('travel_agency/supplier_accounts'),
                'position' => 16,
            ]);
        }
    }
}

/**
 * Register travel agency client area menu tab
 * @return null
 */
function travel_agency_client_menu_item()
{
    if (!is_client_logged_in()) {
        return;
    }

    add_theme_menu_item('travel_agency', [
        'name'     => _l('travel_agency_my_bookings'),
        'icon'     => 'fa-solid fa-plane',
        'href'     => site_url('travel_agency'),
        'position' => 60,
    ]);
}

/**
 * Register staff permissions for the travel agency module
 * @return null
 */
function travel_agency_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('travel_agency', $capabilities, _l('travel_agency_packages_and_bookings'));

    $supplier_capabilities = [];

    $supplier_capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('travel_agency_suppliers', $supplier_capabilities, _l('travel_agency_suppliers'));
}

/**
 * Get all available travel booking statuses
 *
 * @return array
 */
function get_travel_booking_statuses()
{
    return [
        TRAVEL_BOOKING_STATUS_PENDING   => _l('travel_booking_status_pending'),
        TRAVEL_BOOKING_STATUS_CONFIRMED => _l('travel_booking_status_confirmed'),
        TRAVEL_BOOKING_STATUS_CANCELLED => _l('travel_booking_status_cancelled'),
        TRAVEL_BOOKING_STATUS_COMPLETED => _l('travel_booking_status_completed'),
    ];
}

/**
 * Format a travel booking status id to its label
 *
 * @param  int $status
 *
 * @return string
 */
function format_travel_booking_status($status)
{
    $statuses = get_travel_booking_statuses();

    return isset($statuses[$status]) ? $statuses[$status] : '';
}

/**
 * Get the bootstrap label class for a travel booking status
 *
 * @param  int $status
 *
 * @return string
 */
function travel_booking_status_label_class($status)
{
    switch ($status) {
        case TRAVEL_BOOKING_STATUS_CONFIRMED:
            return 'success';
        case TRAVEL_BOOKING_STATUS_CANCELLED:
            return 'danger';
        case TRAVEL_BOOKING_STATUS_COMPLETED:
            return 'info';
        default:
            return 'warning';
    }
}

/**
 * Get all available travel group (تفويج) statuses
 *
 * @return array
 */
function get_travel_group_statuses()
{
    return [
        TRAVEL_GROUP_STATUS_PLANNING  => _l('travel_group_status_planning'),
        TRAVEL_GROUP_STATUS_CONFIRMED => _l('travel_group_status_confirmed'),
        TRAVEL_GROUP_STATUS_DEPARTED  => _l('travel_group_status_departed'),
        TRAVEL_GROUP_STATUS_COMPLETED => _l('travel_group_status_completed'),
        TRAVEL_GROUP_STATUS_CANCELLED => _l('travel_group_status_cancelled'),
    ];
}

/**
 * Format a travel group status id to its label
 *
 * @param  int $status
 *
 * @return string
 */
function format_travel_group_status($status)
{
    $statuses = get_travel_group_statuses();

    return isset($statuses[$status]) ? $statuses[$status] : '';
}

/**
 * Get the bootstrap label class for a travel group status
 *
 * @param  int $status
 *
 * @return string
 */
function travel_group_status_label_class($status)
{
    switch ($status) {
        case TRAVEL_GROUP_STATUS_CONFIRMED:
            return 'success';
        case TRAVEL_GROUP_STATUS_DEPARTED:
            return 'info';
        case TRAVEL_GROUP_STATUS_COMPLETED:
            return 'default';
        case TRAVEL_GROUP_STATUS_CANCELLED:
            return 'danger';
        default:
            return 'warning';
    }
}

/**
 * Get all available visa statuses for a group member
 *
 * @return array
 */
function get_travel_visa_statuses()
{
    return [
        TRAVEL_VISA_STATUS_NOT_SUBMITTED => _l('travel_visa_status_not_submitted'),
        TRAVEL_VISA_STATUS_SUBMITTED     => _l('travel_visa_status_submitted'),
        TRAVEL_VISA_STATUS_APPROVED      => _l('travel_visa_status_approved'),
        TRAVEL_VISA_STATUS_REJECTED      => _l('travel_visa_status_rejected'),
    ];
}

/**
 * Format a visa status id to its label
 *
 * @param  int $status
 *
 * @return string
 */
function format_travel_visa_status($status)
{
    $statuses = get_travel_visa_statuses();

    return isset($statuses[$status]) ? $statuses[$status] : '';
}

/**
 * Get the bootstrap label class for a visa status
 *
 * @param  int $status
 *
 * @return string
 */
function travel_visa_status_label_class($status)
{
    switch ($status) {
        case TRAVEL_VISA_STATUS_APPROVED:
            return 'success';
        case TRAVEL_VISA_STATUS_REJECTED:
            return 'danger';
        case TRAVEL_VISA_STATUS_SUBMITTED:
            return 'info';
        default:
            return 'warning';
    }
}
