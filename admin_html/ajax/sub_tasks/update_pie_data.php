<?php
$data = array ();

// Count sites in and out of Ypsi
$data['counts'] = array ('inypsi' => 0, 'out' => 0, 'inactive' => 0);
$stmt = $db_link->prepare ("SELECT " .
                           "    loc_city, " .
                           "    status, " .
                           "    COUNT(*) AS cnt " .
                           "FROM " .
                           "    site_info " .
                           "WHERE " .
                           "    status <> 'hidden' " .
                           "GROUP BY " .
                           "    loc_city, " .
                           "    status");
$stmt->execute ();
$stmt->bind_result ($loc_city, $status, $count);

while ($stmt->fetch ()) {
    if ($loc_city === 'in') {
        $loc_city = 'inypsi';
    }
    if ($status === 'active') {
        $data['counts'][$loc_city] += intval ($count);
    }
    else {
        $data['counts']['inactive'] += intval ($count);
    }
}

$stmt->close ();

// Count watts in and out of Ypsi (complete installations only)
$data['watts'] = array ('inypsi' => 0, 'out' => 0, 'inactive' => 0);
$stmt = $db_link->prepare ("SELECT " .
                           "    loc_city, " .
                           "    status, " .
                           "    SUM(rated_output) AS watts " .
                           "FROM " .
                           "    site_info " .
                           "WHERE " .
                           "    status <> 'hidden' " .
                           "GROUP BY " .
                           "    loc_city, " .
                           "    status");
$stmt->execute ();
$stmt->bind_result ($loc_city, $status, $watts);

while ($stmt->fetch ()) {
    if ($loc_city === 'in') {
        $loc_city = 'inypsi';
    }
    if ($status === 'active') {
        $data['watts'][$loc_city] += intVal ($watts);
    }
    else {
        $data['watts']['inactive'] += intval ($watts);
    }
}

$stmt->close ();

// Update the JSON file
$fp = fopen ("/home/solaryps/statics_html/json/piedata.json", "w");
fwrite ($fp, "jsonpSYPDCallback(");
fwrite ($fp, json_encode ($data));
fwrite ($fp, ");");
fclose ($fp);
?>