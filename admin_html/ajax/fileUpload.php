<?php
/**
 * Upload a file of a given resource type for a site and locates it properly in
 * the repository and indexes the file in the database.
 *
 * @author Nik Estep
 * @date March 4, 2013
 */

// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database and disable auto-commit
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
$db_link->autocommit (FALSE);

// Build the upload directory string and other indexing parameters
$repos_pattern = str_replace ("{resource_type}", $_POST['resourceType'], $REPOS_PATTERN);
$repos_pattern = str_replace ("{site_id}", $_POST['siteID'], $repos_pattern);
$upload_dir = $REPOS_PATH_TO_PUBLIC_HTML . $repos_pattern;
$file_name = str_replace (" ", "_", basename ($_FILES['userfile']['name']));
$upload_file = $upload_dir . $file_name;
$orig_width = 0;
$orig_height = 0;
$thumb_width = 0;
$thumb_height = 0;

// Make sure the target directory exists
if (!file_exists ($upload_dir)) {
    mkdir ($upload_dir, 0777, TRUE);
}

// Determine the repository file name
//   Leave it alone unless it is an image and then we will just make it the site
//   ID and a number
if ($_POST['resourceType'] == 'image') {
    // Build a new file name based on the how many images we already have for
    // this site
    $stmt = $db_link->prepare ("SELECT " .
                               "    COUNT(*) " .
                               "FROM " .
                               "    site_resource " .
                               "WHERE " .
                               "    site_id=? " .
                               "  AND " .
                               "    res_type='image'");
    $stmt->bind_param ('s', $_POST['siteID']);
    $stmt->execute ();
    $stmt->bind_result ($idx);
    $stmt->fetch ();
    
    $idx = intval ($idx);
    $file_name = $_POST['siteID'] . '_' . sprintf ('%03d', $idx) .
                 substr ($file_name, strrpos ($file_name, '.', -4));  // File extension
    $upload_file = $upload_dir . $file_name;
    
    $stmt->close ();
    
    // Retrieve the orginal size of the image
    //   Array indices are standard from PHP API
    $size_info = getimagesize ($_FILES['userfile']['tmp_name']);
    $orig_width = $size_info[0];
    $orig_height = $size_info[1];
    
    // If image is generally smaller, don't even bother
    if ($orig_width >= $THUMB_MAX_WIDTH || $orig_height >= $THUMB_MAX_HEIGHT) {
         
        // Work out ratios
        if ($orig_width > 0) {
            $rx = $THUMB_MAX_WIDTH / $orig_width;
        }
        if ($orig_height > 0) {
            $ry = $THUMB_MAX_HEIGHT / $orig_height;
        }
        
        // Use the lowest ratio, to ensure we don't go over the wanted image size
        if ($rx > $ry) {
            $r = $ry;
        } else {
            $r = $rx;
        }
        
        //C alculate the new size based on the chosen ratio
        $thumb_width = intval ($orig_width * $r);
        $thumb_height = intval ($orig_height * $r);
        
        // Store the image that needs thumbnail creation
        $stmt2 = $db_link->prepare ("INSERT INTO " .
                                    "    images_to_convert " .
                                    "( " .
                                    "    path, " .
                                    "    thumb_width, " .
                                    "    thumb_height " .
                                    ") " .
                                    "VALUES " .
                                    "( " .
                                    "    ?, " .
                                    "    ?, " .
                                    "    ? " .
                                    ")");
        $stmt2->bind_param ('sii', $upload_file, $thumb_width, $thumb_height);
        $stmt2->execute ();
        $stmt2->close (); 
    }
    else {
        $thumb_width = $orig_width;
        $thumb_height = $orig_height;
    }
}

// Move the file
$success = FALSE;
if (move_uploaded_file ($_FILES['userfile']['tmp_name'], $upload_file)) {
    $success = TRUE;
}

// Index the file in the database
$err_msg = '';
$db_id = 0;
$index_path = '';
if ($success) {
    // Determine the resource type(s) to check against for ordering
    $res_type_1 = $_POST['resourceType'];
    $res_type_2 = $_POST['resourceType'];
    if ($res_type_1 === 'document') {
        $res_type_2 = 'link';
    }
    
    // Get the next display order index
    $stmt = $db_link->prepare ("SELECT " .
                               "    MAX(disp_order) " .
                               "FROM " .
                               "    site_resource " .
                               "WHERE " .
                               "    site_id=? " .
                               "  AND " .
                               "    (res_type=? OR res_type=?)");
    $stmt->bind_param ('sss', $_POST['siteID'],
                              $res_type_1,
                              $res_type_2);
    $stmt->execute ();
    $stmt->bind_result ($cnt);
    $stmt->fetch ();
    
    if ($cnt == NULL) {
        $cnt = 0;
    }
    $cnt += 1;
        
    $stmt->close ();
    
    // Create the resource record
    $stmt = $db_link->prepare ("INSERT INTO " .
                               "    site_resource " .
                               "( " .
                               "    site_id, " .
                               "    res_type, " .
                               "    disp_order, " .
                               "    title, " .
                               "    res_desc, " .
                               "    file_path, " .
                               "    width, " .
                               "    height, " .
                               "    thumb_width, " .
                               "    thumb_height " .
                               ") " .
                               "VALUES " .
                               "( " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ?, " .
                               "    ? " .
                               ")");
    if (!$stmt) {
        $success = FALSE;
        $err_msg = $db_link->error;
    }
    
    if ($success) {
        $index_path = $repos_pattern . $file_name;
        $stmt->bind_param ('ssisssiiii', $_POST['siteID'],
                                         $_POST['resourceType'],
                                         $cnt,
                                         $_POST['title'],
                                         $_POST['description'],
                                         $index_path,
                                         $orig_width,
                                         $orig_height,
                                         $thumb_width,
                                         $thumb_height);
        $stmt->execute ();
        
        if ($stmt->affected_rows == 0) {
            $success = FALSE;
            $err_msg = $db_link->error;
        }
        else {
            $db_id = $db_link->insert_id;
        }
        
        $stmt->close ();
    }
}

// Commit or rollback the transaction
if ($success) {
    $db_link->commit ();
}
else {
    $db_link->rollback ();
    
    if (file_exists ($upload_file)) {
        unlink ($upload_file);
    }
}

// Close the database connection
$db_link->close ();

// Send the response
header ('Content-Type: application/json');
header ('Cache-Control: no-cache, must-revalidate');
header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
echo json_encode (array ('success' => $success && ($_FILES['userfile']['error'] == '0'),
                         'id' => $db_id,
                         'err_php' => $_FILES['userfile']['error'],
                         'err_mysql' => $err_msg,
                         'base_url' => $REPOS_ROOT_URL,
                         'path' => $index_path,
                         'title' => $_POST['title'],
                         'desc' => $_POST['description'],
                         'type' => $_POST['resourceType'],
                         'thumb_width' => $thumb_width,
                         'thumb_height' => $thumb_height));    
?>