<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['travel_agency']                      = 'travel_clients/index';
$route['travel_agency/itinerary/(:num)']     = 'travel_clients/itinerary/$1';
$route['travel_agency/packages']             = 'travel_clients/packages';
$route['travel_agency/apply/(:num)']         = 'travel_clients/apply/$1';
$route['travel_agency/passport']             = 'travel_clients/passport';
$route['travel_agency/passport_file/(:num)'] = 'travel_clients/passport_file/$1';
