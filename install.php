<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'travel_suppliers')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `phonenumber` varchar(45) NOT NULL DEFAULT '',
  `notes` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_suppliers`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_packages')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `destination` varchar(191) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `supplier_id` int(11) NOT NULL DEFAULT '0',
  `duration_days` int(11) NOT NULL DEFAULT '1',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` int(11) NOT NULL DEFAULT '0',
  `seats_available` int(11) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_bookings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_bookings` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `clientid` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL DEFAULT '0',
  `invoiceid` int(11) NOT NULL DEFAULT '0',
  `travelers` int(11) NOT NULL DEFAULT '1',
  `travel_date` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text NOT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `invoiceid` (`invoiceid`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!in_array('deleted_customer_name', $CI->db->list_fields(db_prefix() . 'travel_bookings'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_bookings`
  ADD COLUMN `deleted_customer_name` varchar(191) DEFAULT NULL;');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_groups')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_groups` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `departure_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `seats_total` int(11) NOT NULL DEFAULT '0',
  `hotel_name` varchar(191) NOT NULL DEFAULT '',
  `hotel_city` varchar(100) NOT NULL DEFAULT '',
  `hotel_check_in` date DEFAULT NULL,
  `hotel_check_out` date DEFAULT NULL,
  `carrier_name` varchar(191) NOT NULL DEFAULT '',
  `carrier_type` varchar(50) NOT NULL DEFAULT '',
  `carrier_reference` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text NOT NULL,
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!in_array('calendar_event_id', $CI->db->list_fields(db_prefix() . 'travel_groups'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_groups`
  ADD COLUMN `calendar_event_id` int(11) NOT NULL DEFAULT \'0\';');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_group_members')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL DEFAULT '0',
  `traveler_name` varchar(191) NOT NULL DEFAULT '',
  `passport_number` varchar(100) NOT NULL DEFAULT '',
  `passport_expiry` date DEFAULT NULL,
  `visa_status` tinyint(1) NOT NULL DEFAULT '1',
  `visa_number` varchar(100) NOT NULL DEFAULT '',
  `notes` text NOT NULL,
  `datecreated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `booking_id` (`booking_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_package_types')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_package_types` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_package_types`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_package_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');

    $default_types = [
        ['name' => 'السياحة', 'display_order' => 1],
        ['name' => 'الحج', 'display_order' => 2],
        ['name' => 'العمرة', 'display_order' => 3],
        ['name' => 'إقامة عمل', 'display_order' => 4],
        ['name' => 'زيارة عائلية', 'display_order' => 5],
        ['name' => 'زيارة شخصية', 'display_order' => 6],
    ];

    foreach ($default_types as $type) {
        $CI->db->insert(db_prefix() . 'travel_package_types', $type);
    }
}

if (!in_array('type_id', $CI->db->list_fields(db_prefix() . 'travel_packages'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_packages`
  ADD COLUMN `type_id` int(11) NOT NULL DEFAULT \'0\' AFTER `destination`,
  ADD KEY `type_id` (`type_id`);');
}

if (!in_array('cost', $CI->db->list_fields(db_prefix() . 'travel_packages'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_packages`
  ADD COLUMN `cost` decimal(15,2) NOT NULL DEFAULT \'0.00\' AFTER `price`;');
}

if ($CI->db->where('name', 'SAR')->count_all_results(db_prefix() . 'currencies') == 0) {
    $CI->db->insert(db_prefix() . 'currencies', [
        'symbol'             => ' ر.س',
        'name'               => 'SAR',
        'decimal_separator'  => '.',
        'thousand_separator' => ',',
        'placement'          => 'after',
        'isdefault'          => 0,
    ]);
}

if ($CI->db->where('name', 'SAR')->where('placement', 'before')->count_all_results(db_prefix() . 'currencies') > 0) {
    $CI->db->where('name', 'SAR');
    $CI->db->update(db_prefix() . 'currencies', [
        'symbol'    => ' ر.س',
        'placement' => 'after',
    ]);
}

if (!in_array('photo', $CI->db->list_fields(db_prefix() . 'travel_group_members'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_members`
  ADD COLUMN `photo` varchar(191) NOT NULL DEFAULT \'\',
  ADD COLUMN `passport_scan` varchar(191) NOT NULL DEFAULT \'\',
  ADD COLUMN `passport_mrz_raw` text NOT NULL,
  ADD COLUMN `passport_surname` varchar(191) NOT NULL DEFAULT \'\',
  ADD COLUMN `passport_given_names` varchar(191) NOT NULL DEFAULT \'\',
  ADD COLUMN `nationality` varchar(100) NOT NULL DEFAULT \'\',
  ADD COLUMN `date_of_birth` date DEFAULT NULL,
  ADD COLUMN `gender` varchar(10) NOT NULL DEFAULT \'\';');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_group_itinerary_stops')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_group_itinerary_stops` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `city` varchar(191) NOT NULL DEFAULT '',
  `days` int(11) NOT NULL DEFAULT '1',
  `hotel_name` varchar(191) NOT NULL DEFAULT '',
  `display_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_itinerary_stops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_itinerary_stops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_group_transport')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_group_transport` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `carrier_name` varchar(191) NOT NULL DEFAULT '',
  `carrier_type` varchar(50) NOT NULL DEFAULT '',
  `carrier_reference` varchar(100) NOT NULL DEFAULT '',
  `display_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_transport`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_group_transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (in_array('hotel_name', $CI->db->list_fields(db_prefix() . 'travel_groups'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_groups`
  DROP COLUMN `hotel_name`,
  DROP COLUMN `hotel_city`,
  DROP COLUMN `hotel_check_in`,
  DROP COLUMN `hotel_check_out`,
  DROP COLUMN `carrier_name`,
  DROP COLUMN `carrier_type`,
  DROP COLUMN `carrier_reference`;');
}

if (!$CI->db->table_exists(db_prefix() . 'travel_supplier_payments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_supplier_payments` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` int(11) NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `notes` text NOT NULL,
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

/**
 * Passports belong to the client, not to any single booking/group/trip - a client's passport is
 * relevant to every trip they take, and they may renew it over time. Every upload/update inserts
 * a NEW row rather than overwriting one, so old passports remain as permanent history; only one
 * row per client is ever flagged is_current = 1 at a time.
 */
if (!$CI->db->table_exists(db_prefix() . 'travel_client_passports')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "travel_client_passports` (
  `id` int(11) NOT NULL,
  `clientid` int(11) NOT NULL,
  `passport_number` varchar(100) NOT NULL DEFAULT '',
  `surname` varchar(191) NOT NULL DEFAULT '',
  `given_names` varchar(191) NOT NULL DEFAULT '',
  `nationality` varchar(100) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) NOT NULL DEFAULT '',
  `passport_expiry` date DEFAULT NULL,
  `scan_file` varchar(191) NOT NULL DEFAULT '',
  `mrz_raw` text NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `datecreated` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_client_passports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `clientid_is_current` (`clientid`, `is_current`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_client_passports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (in_array('address', $CI->db->list_fields(db_prefix() . 'travel_suppliers'))) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'travel_suppliers`
  DROP COLUMN `address`,
  DROP COLUMN `city`,
  DROP COLUMN `country`;');
}
