<?php
/**
 * Static configuration settings for SolarYpsi.
 *
 * @author Nik Estep
 * @date March 1, 2013
 */

// Set the timezone
date_default_timezone_set ('America/New_York');

// Flag for setting variables
$PRODUCTION = TRUE;

// Database connection settings
//$DB_SERVER = '216.51.232.170';
$DB_SERVER = 'localhost';
$DB_DATABASE = 'solaryps_data';
$DB_USERNAME = 'solaryps_db';
$DB_PASSWORD = 'solardb102011';
$DB_PORT = 3306;
if ($PRODUCTION) {
	$DB_SOCKET = NULL;
}
else {
	$DB_SOCKET = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
}

// File repository settings
if ($PRODUCTION) {
	$REPOS_PATH_TO_PUBLIC_HTML = "/home/solaryps/public_html";
	$REPOS_ROOT_PATH = "/";
	$REPOS_ROOT_URL = "http://solar.ypsi.com";
}
else {
	$REPOS_PATH_TO_PUBLIC_HTML = "/Users/nestep/Sites/public_html";
	$REPOS_ROOT_PATH = "/public_html";
	$REPOS_ROOT_URL = "http://localhost/~nestep/public_html";
}
$REPOS_PATTERN = "/repository/{resource_type}s/{site_id}/";

// Thumbnail size settings (px)
$THUMB_MAX_WIDTH = 350;
$THUMB_MAX_HEIGHT = 300;

// Constants for chart paging
$YEARLY_CHART_POINT_SIZE = 365;
$MONTHLY_CHART_POINT_SIZE = 12;

// Constants for line coloring
$INFLOW_COLOR = 'FF0000';
$OUTFLOW_COLOR = '009900';
$GENERATION_COLOR = 'F6BD0F';

$INFLOW_PURCHASED_COLOR = 'FF0000';
$INFLOW_MIXED_COLOR = 'FF9900';
$INFLOW_FREE_COLOR = '009900';
$GENERATION_STORED_COLOR = '000000';
?>