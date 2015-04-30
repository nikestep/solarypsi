<?php
/**
 * 
 *
 * @author Nik Estep
 * @date April 30, 2015
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Delete the link
$stmt = $db_link->prepare ("DELETE FROM " .
                           "    website_link " .
                           "WHERE " .
                           "    id=?");
$stmt->bind_param ('i', $_POST['id']);
$err_msg = '';
$success = TRUE;
if (!$stmt->execute ()) {
    $success = FALSE;
    $err_msg = $db_link->error;
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
                         'err_msg' => $err_msg));
?>