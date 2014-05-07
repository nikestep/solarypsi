<?php
/**
 * Static configuration settings for SolarYpsi.
 *
 * @author Nik Estep
 * @date March 1, 2013
 */

// Set the timezone
date_default_timezone_set ('America/New_York');

// Database connection settings
$DB_SERVER = 'localhost';
$DB_DATABASE = 'db';
$DB_USERNAME = 'db_user';
$DB_PASSWORD = 'db_password';
$DB_PORT = 3306;
$DB_SOCKET = NULL;

// Forecast.io settings
$FORECAST_IO_API_KEY = '';
$SITE_WEATHER_LATITUDE = 0;
$SITE_WEATHER_LONGITUDE = 0;

// Google Analytics settings
$GA_TRACK_ID = '';
$GA_QR_TRACK_ID = '';
$GA_DOMAIN = 'example.com';

// File repository settings
$REPOS_PATH_TO_PUBLIC_HTML = "/home/solaryps/public_html";
$REPOS_ROOT_PATH = "/";
$REPOS_ROOT_URL = "http://solar.ypsi.com";
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