<?php
$data = array ();

// Pull the necessary site information
$stmt = $db_link->prepare ("SELECT " .
                           "    site.id AS site_id, " .
                           "    site.description AS site_desc, " .
                           "    site_info.loc_long AS loc_long, " .
                           "    site_info.loc_lat AS loc_lat, " .
                           "    site_info.status AS status, " .
                           "    site_info.meter_type AS meter_type " .
                           "FROM " .
                           "    site INNER JOIN site_info ON site.id = site_info.site_id " .
                           "WHERE " .
                           "    site_info.status <> 'hidden'");
$stmt->execute ();
$stmt->bind_result ($site_id,
                    $site_desc,
                    $loc_long,
                    $loc_lat,
                    $status,
                    $meter_type);

while ($stmt->fetch ()) {
    $obj = array ();
    $obj['type'] = 'Feature';
    $obj['properties'] = array ('id' => $site_id,
                                'meter_type' => $meter_type,
                                'status' => $status);
    $obj['properties']['popupContent'] = "<b>" .
                                         "    <a href='./installations/$site_id'>" .
                                         "        $site_desc" .
                                         "    </a>" .
                                         "</b>";
    $obj['geometry'] = array ('type' => 'Point',
                              'coordinates' => array ($loc_long, $loc_lat));
    array_push ($data, $obj);
}

$stmt->close ();

// Update the JSON file
$fp = fopen ("/home/solaryps/statics_html/json/mappoints.json", "w");
fwrite ($fp, "jsonpSYMPCallback(");
fwrite ($fp, json_encode ($data));
fwrite ($fp, ");");
fclose ($fp);
?>