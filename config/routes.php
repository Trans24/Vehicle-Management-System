<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'Frontendbooking';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['triptracking/(:any)'] = 'triptracking';
