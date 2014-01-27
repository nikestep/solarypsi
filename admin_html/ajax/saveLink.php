<?php
/**
 * 
 *
 * @author Nik Estep
 * @date March 21, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Get the current maximum display order index
$stmt = $db_link->prepare ("SELECT " .
						   "    MAX(disp_order) " .
						   "FROM " .
						   "    website_link");
$stmt->bind_result ($disp_order);
$stmt->execute ();
$stmt->fetch ();
$stmt->close ();

if ($disp_order === NULL) {
	$disp_order = 0;
}
$disp_order += 1;

// Set description to NULL if it is blank
if (isset ($_POST['description']) && $_POST['description'] === '') {
	$_POST['description'] = NULL;
}

// Save the new resource order
$stmt = $db_link->prepare ("INSERT INTO " .
						   "    website_link " .
						   "( " .
						   "    title, " .
						   "    link_desc, " .
						   "    visible_link, " .
						   "    full_link, " .
						   "    disp_order " .
						   ") " .
						   "VALUES " .
						   "( " .
						   "    ?, " .
						   "    ?, " .
						   "    ?, " .
						   "    ?, " .
						   "    ? " .
						   ")");
$stmt->bind_param ('ssssi', $_POST['title'],
						    $_POST['description'],
						    $_POST['visible_link'],
						    $_POST['full_link'],
						    $disp_order);
$err_msg = '';
$success = TRUE;
$db_id = 0;
if (!$stmt->execute ()) {
	$success = FALSE;
	$err_msg = $db_link->error;
}
else {
	$db_id = $db_link->insert_id;
}
$stmt->close ();

// Commit or rollback the transaction
if ($success) {
	$db_link->commit ();
}
else {
	$db_link->rollback ();
}

// Close the database connection
$db_link->close ();

header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('success' => $success,
						 'id' => $db_id,
						 'title' => $_POST['title'],
						 'description' => $_POST['description'],
						 'visible_link' => $_POST['visible_link'],
						 'full_link' => $_POST['full_link'],
					     'err_msg' => $err_msg));
?>