<?php
function translate_to_css_style ($icon) {
    switch ($icon) {
        case 'clear-day':
            return 'wi-day-sunny';
        case 'clear-night':
            return 'wi-night-clear';
        case 'rain':
            return 'wi-showers';
        case 'snow':
            return 'wi-snow';
        case 'sleet':
            return 'wi-snow';
        case 'wind':
            return 'wi-day-cloudy-gusts';
        case 'fog':
            return 'wi-fog';
        case 'cloudy':
            return 'wi-cloudy';
        case 'partly-cloudy-day':
            return 'wi-day-sunny-overcast';
        case 'partly-cloudy-night':
            return 'wi-night-cloudy';
        default:
            return 'wi-cloudy';
    }
}

// Array to hold the data points
$data_points = array ();

// Retrieve the data from the internets
$url = 'https://api.forecast.io/forecast/' . $FORECAST_IO_API_KEY . '/' .
       $SITE_WEATHER_LATITUDE . ',' . $SITE_WEATHER_LONGITUDE;
$str = file_get_contents ($url);

// Parse JSON
$json = json_decode ($str, TRUE);

// Load data points
$data_points['curr_temp'] = intVal ($json['currently']['temperature']);
$data_points['icon_class'] = translate_to_css_style ($json['currently']['icon']);

// If we got both data points, update the JSON file
if ($data_points['curr_temp'] != '' && $data_points['icon_class'] != '') {
    $fp = fopen ("../statics_html/json/weather.json", "w");
    fwrite ($fp, "jsonpSYWCallback(");
    fwrite ($fp, json_encode ($data_points));
    fwrite ($fp, ");");
    fclose ($fp);
    $job_result = 'Success';
}
else {
    $job_result = 'Error';
    $job_msg = '36 Unable to collect data points';
}
?>