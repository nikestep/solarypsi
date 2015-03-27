<?php
/**
 * 
 *
 * @author Nik Estep
 * @date June 1, 2014
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Update the cron data
$stmt = $db_link->prepare ("UPDATE " .
                           "    cron_schedule " .
                           "SET " .
                           "    schedule=?, " .
                           "    enabled=? " .
                           "WHERE " .
                           "    name=?");
$stmt->bind_param ('sis', $_POST['schedule'], $_POST['enabled'], $_POST['name']);
$order = 1;
$success = TRUE;
$err_msg = '';
if (!$stmt->execute ()) {
    $success = FALSE;
    $err_msg = $db_link->error;
    break;
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
echo json_encode (array ('success' => $success, 'err_msg' => $err_msg));
?>