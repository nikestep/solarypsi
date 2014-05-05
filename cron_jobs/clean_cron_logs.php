<?php
// Delete old cron logs
if ($stmt_inner = $db_link->prepare ("DELETE FROM " .
                                     "    cron_log " .
                                     "WHERE " .
                                     "    run_time < NOW() - INTERVAL 1 WEEK")) {
    if (!$stmt_inner->execute ()) {
           $job_result = 'Error';
           $job_msg = '9 ' . $db_link->error;
    }
    else {
        $job_result = 'Success';
        $job_msg = $stmt_inner->affected_rows . ' rows cleared';
    }
    $stmt_inner->close ();
}
else {
    $job_result = 'Error';
    $job_msg = '19 ' . $db_link->error;
}
?>