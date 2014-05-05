<?php
// Array to hold the data points
$data_points = array ();

// Open the stream to the Yahoo! weather RSS feed
$fp = fopen ("http://weather.yahooapis.com/forecastrss?p=48197", "r") or die ("Cannot read RSS data file.");

// Parse the temperature and weather type image URL
$data = fread ($fp, 1000000);
$data = preg_split ("/\<img/", $data);

$data2 = $data[0];
$data2 = preg_split ("/yweather:condition/", $data2);
$temp = preg_split ("/temp=/", $data2[1]);
$temp = preg_split ('/"/', $temp[1]);
$data_points['currTemp'] = $temp[1];

$imgURL = $data[1];
$imgURL = preg_split ('/"/', $imgURL);
$data_points['imageURL'] = $imgURL[1];

// Close the stream
fclose ($fp);

// If we got both data points, update the JSON file
if ($data_points['currTemp'] != '' && $data_points['imageURL'] != '') {
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