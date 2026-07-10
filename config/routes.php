<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['travel_agency']                      = 'travel_clients/index';
$route['travel_agency/itinerary/(:num)']     = 'travel_clients/itinerary/$1';
$route['travel_agency/packages']             = 'travel_clients/packages';
$route['travel_agency/apply/(:num)']         = 'travel_clients/apply/$1';
