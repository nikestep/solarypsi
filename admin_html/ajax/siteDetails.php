<?php
/**
 * Check a potential new site ID.
 *
 * @author Nik Estep
 * @date March 1, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Array to hold data and base part of URL
$data = array ();
$data['base_url'] = $REPOS_ROOT_URL;

// Pull the basic site information
$stmt = $db_link->prepare ("SELECT " .
						   "    inst_type, " .
						   "    completed, " .
						   "    panel_desc, " .
						   "    panel_angle, " .
						   "    inverter, " .
						   "    rated_output, " .
						   "    installer, " .
						   "    installer_url, " .
						   "    contact, " .
						   "    contact_url, " .
						   "    list_desc, " .
						   "    status, " .
						   "    loc_city, " .
						   "    loc_long, " .
						   "    loc_lat, " .
						   "    max_wh, " .
						   "    max_kw, " .
						   "    meter_type, " .
                           "    qr_code " .
						   "FROM " .
						   "    site_info " .
						   "WHERE " .
						   "    site_id=?");
$stmt->bind_param ('s', $_POST['siteID']);
$stmt->execute ();
$stmt->bind_result ($data['inst_type'],
					$data['completed'],
					$data['panel_desc'],
					$data['panel_angle'],
					$data['inverter'],
					$data['rated_output'],
					$data['installer'],
					$data['installer_url'],
					$data['contact'],
					$data['contact_url'],
					$data['list_desc'],
					$data['status'],
					$data['loc_city'],
					$data['loc_long'],
					$data['loc_lat'],
					$data['max_wh'],
					$data['max_kw'],
					$data['meter_type'],
                    $data['qr_code']);
$stmt->fetch ();
$stmt->close ();

// Set up for resources
$data['doc_link'] = array ();
$data['report'] = array ();
$data['image'] = array ();

// Retrieve and store all resources
$stmt = $db_link->prepare ("SELECT " .
						   "    id, " .
						   "    res_type, " .
						   "    disp_order, " .
						   "    title, " .
						   "    res_desc, " .
						   "    file_path, " .
						   "    width, " .
						   "    height, " .
						   "    thumb_width, " .
						   "    thumb_height " .
						   "FROM " .
						   "    site_resource " .
						   "WHERE " .
						   "    site_id=? " .
						   "  AND " .
						   "    deleted = 0 " .
						   "ORDER BY " .
						   "    res_type, " .
						   "    disp_order ASC");
$stmt->bind_param ('s', $_POST['siteID']);
$stmt->execute ();
$stmt->bind_result ($id,
					$res_type,
					$disp_order,
					$title,
					$res_desc,
					$file_path,
					$width,
					$height,
					$thumb_width,
					$thumb_height);

while ($stmt->fetch ()) {
	$type = $res_type;
	if ($type === 'document' || $type === 'link') {
		$type = 'doc_link';
	}
	$data[$type][$id] = array (
		'type' => $res_type,
		'disp_order' => $disp_order,
		'title' => $title,
		'desc' => $res_desc,
		'path' => $file_path,
		'width' => $width,
		'height' => $height,
		'thumb_width' => $thumb_width,
		'thumb_height' => $thumb_height,
	);
}

$stmt->close ();

// Close the database connection
$db_link->close ();

// Encode the reponse
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode ($data);
?>