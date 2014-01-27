<?php
/**
 * 
 *
 * @author Nik Estep
 * @date March 6, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Determine the path to save the file to
if ($PRODUCTION) {
	$file_path = '../../content/' . $_POST['type'] . '.html';
}
else {
	$file_path = '../../public_html/content/' . $_POST['type'] . '.html';
}

// Prepare content
$content = html_entity_decode ($_POST['html']);
$content = str_replace ('\"', '"', $content);
$content = str_replace ("\'", "'", $content);

// Save the file
$success = TRUE;
if (file_put_contents ($file_path, $content) === FALSE) {
	$success = FALSE;
}

// Send the response
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('success' => $success));
?>