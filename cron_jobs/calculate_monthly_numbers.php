<?
$local_tz = new DateTimeZone ('America/New_York');

// Track success of portions
$success = True;

// Get the year/month to calculate for
$date_month = new DateTime (null, $local_tz);
$date_month->sub (date_interval_create_from_date_string ('1 day'));
$year = intVal ($date_month->format ('Y'));
$month = intVal ($date_month->format ('m'));

// Get data points
$systems = Array ();
if ($stmt_inner = $db_link->prepare ("SELECT DISTINCT " .
                                     "    site_id, " .
                                     "    SUM(inflow), " .
                                     "    SUM(outflow), " .
                                     "    SUM(generation) " .
                                     "FROM " .
                                     "    data_yearly " .
                                     "GROUP BY " .
                                     "    site_id, " .
                                     "    point_year, " .
                                     "    point_month " .
                                     "HAVING " .
                                     "    point_year = ? " .
                                     "  AND " .
                                     "    point_month = ?")) {
    $stmt_inner->bind_param ('ii', $year, $month);
    if (!$stmt_inner->execute ()) {
        $job_result = 'Error';
        $job_msg = '33 ' . $db_link->error;
        $success = false;
    }
    else {
        $stmt_inner->bind_result ($site_id, $inflow, $outflow, $generation);
        while ($stmt_inner->fetch ()) {
            $systems[$site_id] = Array ('inflow' => $inflow,
                                        'outflow' => $outflow,
                                        'generation' => $generation);
        }
    }
    $stmt_inner->close ();
}
else {
    $job_result = 'Error';
    $job_msg = '48 ' . $db_link->error;
    $success = false;
}

// Store the new calculations
if ($success) {
    foreach ($systems as $site_id => $vals) {
        $stmt_inner = $db_link->prepare ("INSERT INTO " .
                                         "    data_monthly " .
                                         "VALUES " .
                                         "    (?, ?, ?, ?, ?, ?)");
        $stmt_inner->bind_param ('siiiii', $site_id,
                                           $year,
                                           $month,
                                           $vals['inflow'],
                                           $vals['outflow'],
                                           $vals['generation']);
        if (!$stmt_inner->execute ()) {
            $job_result = 'Error';
            $job_msg = '69 ' . $db_link->error;
            $success = false;
        }
    }
}

// Mark successful
if ($success) {
    $job_result = 'Success';
}
?>