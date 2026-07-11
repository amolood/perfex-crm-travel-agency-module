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
define('TRAVEL_CLIENT_PASSPORTS_UPLOADS_FOLDER', FCPATH . 'uploads/travel_client_passports/');
define('TRAVEL_DOCUMENTS_UPLOADS_FOLDER', FCPATH . 'uploads/travel_documents/');

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
 * Get the upload folder path for documents attached to a booking or a group
 *
 * @param  string $rel_type  'booking' or 'group'
 * @param  mixed  $rel_id
 *
 * @return string
 */
function travel_agency_document_upload_path($rel_type, $rel_id)
{
    // A single path segment, not $rel_type/$rel_id nested two levels deep - _maybe_create_upload_path()
    // (core helper) only does a non-recursive mkdir(), so it can create just one new directory level
    // under the already-existing TRAVEL_DOCUMENTS_UPLOADS_FOLDER at a time.
    return TRAVEL_DOCUMENTS_UPLOADS_FOLDER . $rel_type . '_' . $rel_id . '/';
}

/**
 * Get the upload folder path for a client's passport scans
 *
 * @param  mixed $clientid
 *
 * @return string
 */
function travel_agency_client_passport_upload_path($clientid)
{
    return TRAVEL_CLIENT_PASSPORTS_UPLOADS_FOLDER . $clientid . '/';
}

/**
 * Ensure an uploads folder that holds passport scans/personal photos denies direct web access,
 * the same way every other Perfex uploads subfolder does.
 *
 * _maybe_create_upload_path() (core helper) only writes an index.html to block directory
 * listing - it does not write a deny-all .htaccess like the folders under core Perfex do
 * (uploads/clients/, uploads/invoices/, etc.), so without this a file's exact URL could be
 * fetched directly with no authentication at all, bypassing the staff/client ownership checks
 * in the controllers that serve these files. Called before every upload so it self-heals on
 * installs where the folder already existed without one, not just on fresh installs.
 *
 * @param  string $folder  absolute path, trailing slash
 *
 * @return void
 */
function travel_agency_secure_uploads_folder($folder)
{
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
    }

    $htaccess = $folder . '.htaccess';

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

/**
 * Serve a file inline (Content-Disposition: inline) so a browser renders it directly instead of
 * downloading it - used for embedding passport scan images as <img> tags. Core's
 * force_download() (system/helpers/download_helper.php) always sends
 * Content-Disposition: attachment with no inline option, so this is implemented directly rather
 * than modifying that vendor/core helper.
 *
 * @param  string $path  absolute filesystem path, already validated by the caller
 *
 * @return void
 */
function travel_agency_serve_file_inline($path)
{
    $mime = @mime_content_type($path);

    if (!$mime) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime      = $extension === 'pdf' ? 'application/pdf' : 'application/octet-stream';
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . basename($path) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($path);
    exit;
}

/**
 * ISO-3166-1 alpha-3 code -> Arabic country name, for displaying the nationality read from a
 * passport's MRZ (which only ever contains the 3-letter code, e.g. "SDN") in a human-readable
 * form. Not exhaustive - covers Sudan and the nationalities this system is realistically likely
 * to see; any code not in this list falls back to showing the raw code as-is rather than
 * failing, since an unlisted code is still meaningful information even untranslated.
 *
 * @return array
 */
function travel_agency_nationality_names()
{
    return [
        'SDN' => 'السودان',
        'EGY' => 'مصر',
        'SAU' => 'السعودية',
        'ARE' => 'الإمارات',
        'QAT' => 'قطر',
        'KWT' => 'الكويت',
        'BHR' => 'البحرين',
        'OMN' => 'عُمان',
        'JOR' => 'الأردن',
        'LBN' => 'لبنان',
        'SYR' => 'سوريا',
        'IRQ' => 'العراق',
        'YEM' => 'اليمن',
        'LBY' => 'ليبيا',
        'TUN' => 'تونس',
        'DZA' => 'الجزائر',
        'MAR' => 'المغرب',
        'SOM' => 'الصومال',
        'ERI' => 'إريتريا',
        'ETH' => 'إثيوبيا',
        'TCD' => 'تشاد',
        'SSD' => 'جنوب السودان',
        'TUR' => 'تركيا',
        'PAK' => 'باكستان',
        'IND' => 'الهند',
        'GBR' => 'المملكة المتحدة',
        'USA' => 'الولايات المتحدة',
        'CAN' => 'كندا',
        'DEU' => 'ألمانيا',
        'FRA' => 'فرنسا',
    ];
}

/**
 * Human-readable nationality label from an MRZ-style ISO alpha-3 code, e.g. "SDN" -> "السودان".
 * Falls back to the raw code itself when it isn't in travel_agency_nationality_names().
 *
 * @param  string $code
 *
 * @return string
 */
function travel_agency_format_nationality($code)
{
    $code  = mb_strtoupper(trim((string) $code));
    $names = travel_agency_nationality_names();

    return $code !== '' ? ($names[$code] ?? $code) : '';
}

/**
 * Human-readable gender label from an MRZ-style single-letter code ('M'/'F').
 *
 * @param  string $code
 *
 * @return string
 */
function travel_agency_format_gender($code)
{
    $code = mb_strtoupper(trim((string) $code));

    if ($code === 'M') {
        return _l('travel_agency_gender_male');
    }

    if ($code === 'F') {
        return _l('travel_agency_gender_female');
    }

    return $code;
}

hooks()->add_action('admin_init', 'travel_agency_module_init_menu_items');
hooks()->add_action('admin_init', 'travel_agency_permissions');
hooks()->add_action('app_init', 'travel_agency_client_menu_item');
hooks()->add_filter('get_dashboard_widgets', 'travel_agency_add_dashboard_widget');
hooks()->add_action('before_client_deleted', 'travel_agency_cleanup_client_data');
hooks()->add_action('after_cron_run', 'travel_agency_run_daily_notifications');

/**
 * Fires on every cron run (site cron hits /cron every few minutes) but
 * Travel_notifications_model::run_daily_checks() guards itself to actually do work at most once
 * per calendar day, notifying active staff about at-risk passports, near-term departures and
 * overdue invoices tied to travel bookings.
 */
function travel_agency_run_daily_notifications()
{
    $CI = &get_instance();
    $CI->load->model('travel_agency/travel_notifications_model');
    $CI->travel_notifications_model->run_daily_checks();
}

/**
 * Fired before a client is deleted from core CRM. Without this, travel_client_passports rows
 * (PII with no FK constraint back to clients) and their scan files would be orphaned forever -
 * unreachable by any UI (the admin list LEFT JOINs from clients, so a deleted client's row just
 * disappears from view while the file/row rot on disk) - and their travel_bookings would keep
 * counting against package seat capacity permanently, since get_booked_seats() only excludes
 * cancelled bookings.
 *
 * Passports are deleted outright (PII should not survive client deletion). Bookings are kept as
 * historical records but cancelled (frees the seat) and stamped with the client's name, matching
 * core Perfex's own convention for other client-referencing tables (tblinvoices.deleted_customer_name
 * etc.) - deleting them outright would risk leaving a dangling travel_group_members.booking_id
 * with nothing behind it.
 *
 * @param  mixed $clientid
 *
 * @return void
 */
function travel_agency_cleanup_client_data($clientid)
{
    $CI = &get_instance();

    $CI->load->model('travel_agency/travel_client_passports_model');
    $passports = $CI->travel_client_passports_model->get_history($clientid);

    foreach ($passports as $passport) {
        $CI->travel_client_passports_model->delete($passport['id'], $clientid);
    }

    $company_name = get_company_name($clientid);

    $CI->db->where('clientid', $clientid);
    $CI->db->where('status !=', TRAVEL_BOOKING_STATUS_CANCELLED);
    $CI->db->update(db_prefix() . 'travel_bookings', [
        'status'                => TRAVEL_BOOKING_STATUS_CANCELLED,
        'deleted_customer_name' => $company_name,
    ]);
}

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

    travel_agency_secure_uploads_folder(TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER);
    travel_agency_secure_uploads_folder(TRAVEL_CLIENT_PASSPORTS_UPLOADS_FOLDER);
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

        $CI->app_menu->add_sidebar_children_item('travel_agency', [
            'slug'     => 'travel_agency-reports',
            'name'     => _l('travel_agency_reports'),
            'href'     => admin_url('travel_agency/reports'),
            'icon'     => 'fa-solid fa-chart-line',
            'position' => 20,
        ]);

        if (staff_can('view', 'customers')) {
            $CI->app_menu->add_sidebar_children_item('travel_agency', [
                'slug'     => 'travel_agency-client-passports',
                'name'     => _l('travel_agency_client_passports'),
                'href'     => admin_url('travel_agency/client_passports'),
                'icon'     => 'fa-solid fa-passport',
                'position' => 13,
            ]);
        }

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

    add_theme_menu_item('travel_agency_passport', [
        'name'     => _l('travel_agency_my_passport'),
        'icon'     => 'fa-solid fa-passport',
        'href'     => site_url('travel_agency/passport'),
        'position' => 61,
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
