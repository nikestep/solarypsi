<?php
/**
 * Load the details for an individual site.
 *
 * @author Nik Estep
 * @date March 13, 2013
 * @note This page is included in index.php so we already have the config and
 *       MySQL connection available
 */

function formatInstallationType ($type) {
    switch ($type) {
        case 'unknown': return 'Unknown';
        case 'public': return 'Public';
        case 'private': return 'Private';
        case 'municipal': return 'Municipal';
        case 'semiprivate': return 'Semi-Private';
        case 'commercial': return 'Commercial';
        case 'demonstration': return 'Demonstration';
        default: return $type;
    }
}

function getFiletypeClass ($path) {
    $ext = substr ($path, -4);
    
    if (strpos ($ext, 'pdf') !== FALSE) {
        return 'type-icon pdf';
    }
    else if (strpos ($ext, 'ppt') !== FALSE || strpos ($ext, 'pptx') !== FALSE) {
        return 'type-icon ppt';
    }
    else if (strpos ($ext, 'flv') !== FALSE) {
        return 'type-icon flash';
    }
    
    return 'type-icon';
}

// Pull the basic site information
$stmt = $db_link->prepare ("SELECT " .
                           "    site.description AS site_desc, " .
                           "    site_info.inst_type AS inst_type, " .
                           "    site_info.completed AS completed, " .
                           "    site_info.panel_desc AS panel_desc, " .
                           "    site_info.panel_angle AS panel_angle, " .
                           "    site_info.inverter AS inverter, " .
                           "    site_info.rated_output AS rated_output, " .
                           "    site_info.installer AS installer, " .
                           "    site_info.installer_url AS installer_url, " .
                           "    site_info.contact AS contanct, " .
                           "    site_info.contact_url AS contact_url, " .
                           "    site_info.meter_type AS meter_type " .
                           "FROM " .
                           "    site INNER JOIN site_info ON site.id = site_info.site_id " .
                           "WHERE " .
                           "    site.id=?");
$stmt->bind_param ('s', $site_id);
$stmt->execute ();
$stmt->bind_result ($data['site_desc'],
                    $data['inst_type'],
                    $data['completed'],
                    $data['panel_desc'],
                    $data['panel_angle'],
                    $data['inverter'],
                    $data['rated_output'],
                    $data['installer'],
                    $data['installer_url'],
                    $data['contact'],
                    $data['contact_url'],
                    $data['meter_type']);
$stmt->fetch ();
$stmt->close ();

// Get extra parameters if we have a metering type
if ($data['meter_type'] === 'enphase') {
    $stmt = $db_link->prepare ("SELECT " .
                               "    system_id, " .
                               "    api_key, " .
                               "    num_units " .
                               "FROM " .
                               "    enphase_system " .
                               "WHERE " .
                               "    site_id=?");
    $stmt->bind_param ('s', $site_id);
    $stmt->execute ();
    $stmt->bind_result ($data['enphase_system_id'],
                        $data['enphase_key'],
                        $data['enphase_num_units']);
    $stmt->fetch ();
    $stmt->close ();
}
else if ($data['meter_type'] === 'historical') {
    $stmt = $db_link->prepare ("SELECT " .
                               "    start_year, " .
                               "    end_year " .
                               "FROM " .
                               "    historical_system " .
                               "WHERE " .
                               "    site_id=?");
    $stmt->bind_param ('s', $site_id);
    $stmt->execute ();
    $stmt->bind_result ($data['historical_start_year'],
                        $data['historical_end_year']);
    $stmt->fetch ();
    $stmt->close ();
}

// Set up for resources
$data['document'] = array ();
$data['report'] = array ();
$data['image'] = array ();
$data['qr_video'] = array ();

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
$stmt->bind_param ('s', $site_id);
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
    $arr_type = $res_type;
    if ($res_type === 'document' || $res_type === 'link') {
        $arr_type = 'doc_link';
    }
    
    $data[$arr_type][$id] = array (
        'res_type' => $res_type,
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
?>

<!-- Store the meter type and any related parameters -->
<span id='spnMeterType' class='hidden'><?php echo $data['meter_type']; ?></span>
<?php
if ($data['meter_type'] === 'enphase') {
?>
    <span id='spnEnphaseSystemID' class='hidden'><?php echo $data['enphase_system_id']; ?></span>
    <span id='spnEnphaseKey' class='hidden'><?php echo $data['enphase_key']; ?></span>
    <span id='spnEphaseNumUnits' class='hidden'><?php echo $data['enphase_num_units']; ?></span>
<?php
}
else if ($data['meter_type'] === 'historical') {
?>
    <span id="spnHistoricalStart" class="hidden"><?php echo $data['historical_start_year']; ?></span>
    <span id="spnHistoricalEnd" class="hidden"><?php echo $data['historical_end_year']; ?></span>
<?php
}
?>

<!-- Label the page -->
<div class='page-header'>
    <h2><?php echo $data['site_desc'] ?></h2>
</div>

<!-- Provide the section links -->
<div class="row">
    <ul class="nav nav-tabs" id="dvSiteTabs">
        <?php
            if ($data['meter_type'] !== 'none') {
        ?>
                <?php
                    if ($data['meter_type'] !== 'historical') {
                ?>
                        <li class="active"><a href="#daily" data-toggle="tab">Daily Chart</a></li>
                <?php
                    }
                ?>
                <li><a href="#yearly" data-toggle="tab">Yearly Chart</a></li>
                <li><a href="#monthly" data-toggle="tab">Monthly Usage Chart</a></li>
        <?php
            }
        ?>
        <li <?php if($data['meter_type'] === 'none' || $data['meter_type'] === 'historical') echo "class='active'"; ?>><a href="#details" data-toggle="tab">Details</a></li>
        <?php
            if (count ($data['doc_link']) > 0 || count ($data['report']) > 0) {
        ?>
                <li><a href="#files" data-toggle="tab">Files</a></li>
        <?php
            }
        ?>
    </ul><!--/.nav nav-tabs -->

    <div class="tab-content">
        <!-- Divs for charts -->
        <?php
            if ($data['meter_type'] !== 'none') {
        ?>
                <?php
                    if ($data['meter_type'] !== 'historical') {
                ?>
                        <div class="tab-pane active" id="daily">
                            <p>
                                Below is view of generated electricity from today. This chart may be behind
                                the current time and may change throughout the day as data is updated.
                            </p>
                            <p>
                                Right now, historical data is not available, but it will be soon!
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div id="dvDailyChart" class="chart"></div>
                                    <div id="dvDailyChartLegend" class="chart-legend"></div>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                            <div class="row">
                                <div class="col-md-7">
                                    <p>&nbsp;</p>
                                    <button id="btnPrevDaily" type="button" class="btn btn-default pull-left">
                                        <span class="glyphicon glyphicon-chevron-left"></span>
                                        Previous Day
                                    </button>
                                    <button id="btnNextDaily" type="button" class="btn btn-default pull-right disabled">
                                        Next Day
                                        <span class="glyphicon glyphicon-chevron-right"></span>
                                    </button>
                                </div><!--/.col-md-7 -->
                                <div class="col-md-5">&nbsp;</div>
                            </div>
                        </div><!--/#daily -->
                <?php
                    }
                ?>
                <div class="tab-pane" id="yearly">
                    <p>
                        Yearly metering of this installation is currently unavailable. The data
                        below is historical and offered for reference purposes.
                    </p>
                    <?php
                        if ($data['meter_type'] === 'historical') {
                    ?>
                            <div class="row">
                                <div class="col-md-8">
                                    <img id="imgHistYearly" src="<?php echo '/repository/charts_history/' . $site_id . '/yearly_' . $data['historical_end_year'] . '.png'; ?>"
                                         class="img-responsive" alt="Yearly Chart" />
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <button id="btnPrevHistYearly" type="button" class="btn btn-default pull-left">
                                        <span class="glyphicon glyphicon-chevron-left"></span>
                                        Previous Year
                                    </button>
                                    <button id="btnNextHistYearly" type="button" class="btn btn-default pull-right disabled">
                                        Next Year
                                        <span class="glyphicon glyphicon-chevron-right"></span>
                                    </button>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                    <?php
                        }
                        else {
                    ?>
                            <div id="dvYearlyTitle" class="chart-title"></div>
                            <div id="dvYearlyChart" class="chart"></div>
                            <div id="dvYearlyChartLegend" class="chart-legend"></div>
                            <div id="dvYearlyChartControls" class="chart-controls">
                                <div class="left">
                                    <div class="container">
                                        <input id="btnPrevYearly" type="button" class="chart-control" value="Previous Year" />
                                    </div>
                                </div>
                                <div class="right">
                                    <div class="container hidden">
                                        <input id="btnNextYearly" type="button" class="chart-control" value="Next Year" />
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    ?>
                </div><!--/#yearly -->
                <div class="tab-pane" id="monthly">
                    <p>
                        Monthly usage metering of this installation is currently unavailable. The
                        data below is historical and offered for reference purposes.
                    </p>
                    <?php
                        if ($data['meter_type'] === 'historical') {
                    ?>
                            <div class="row">
                                <div class="col-md-8">
                                    <img id="imgHistMonthly" src="<?php echo '/repository/charts_history/' . $site_id . '/monthly_' . $data['historical_end_year'] . '.png'; ?>"
                                         class="img-responsive" alt="Yearly Chart" />
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <button id="btnPrevHistMonthly" type="button" class="btn btn-default pull-left">
                                        <span class="glyphicon glyphicon-chevron-left"></span>
                                        Previous Year
                                    </button>
                                    <button id="btnNextHistMonthly" type="button" class="btn btn-default pull-right disabled">
                                        Next Year
                                        <span class="glyphicon glyphicon-chevron-right"></span>
                                    </button>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                    <?php
                        }
                        else {
                    ?>
                            <div id="dvMonthlyTitle" class="chart-title"></div>
                            <div id="dvMonthlyChart" class="chart"></div>
                            <div id="dvMonthlyChartLegend" class="chart-legend"></div>
                            <div id="dvMonthlyChartControls" class="chart-controls">
                                <div class="left">
                                    <div class="container">
                                        <input id="btnPrevMonthly" type="button" class="chart-control" value="Previous Year" />
                                    </div>
                                </div>
                                <div class="right">
                                    <div class="container hidden">
                                        <input id="btnNextMonthly" type="button" class="chart-control" value="Next Year" />
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    ?>
                </div><!--/#monthly -->
        <?php
            }
        ?>

        <!-- Div for details -->
        <div class="tab-pane <?php if ($data['meter_type'] === 'none' || $data['meter_type'] === 'historical') echo 'active'; ?>" id="details">
            <p class="lead">
                Installation Specifics
            </p>
            <div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-5">
                    <dl class="dl-horizontal">
                        <dt>Installation Type</dt>
                        <dd><?php echo formatInstallationType ($data['inst_type']); ?></dd>
                        <dt>Installation Completed</dt>
                        <dd><?php echo $data['completed']; ?></dd>
                        <dt>Number/Type of Panels</dt>
                        <dd><?php echo $data['panel_desc']; ?></dd>
                        <dt>Install Specs</dt>
                        <dd><?php echo $data['panel_angle']; ?></dd>
                    </dl>
                </div><!--/.col-md-5 -->
                <div class="col-md-5">
                    <dl class="dl-horizontal">
                        <dt>Size/Type of Inverter</dt>
                        <dd><?php echo $data['inverter']; ?></dd>
                        <dt>Installer</dt>
                        <dd>
                            <?php
                                if ($data['installer_url'] === '') {
                                    echo $data['installer'];
                                }
                                else {
                                    echo "<a href='" .
                                         $data['installer_url'] .
                                         "' target='_blank'>" .
                                         $data['installer'] .
                                         "</a>";
                                }
                            ?>
                        </dd>
                        <dt>Rated Output</dt>
                        <dd><?php echo $data['rated_output'] . ' W'; ?></dd>
                        <dt>Contact</dt>
                        <dd>
                            <?php
                                if ($data['contact_url'] === '') {
                                    echo $data['contact'];
                                }
                                else {
                                    echo "<a href='" .
                                         $data['contact_url'] .
                                         "' target='_blank'>" .
                                         $data['contact'] .
                                         "</a>";
                                }
                            ?>
                        </dd>
                    </dl>
                </div><!--/.col-md-5 -->
                <div class="col-md-1">&nbsp;</div>
            </div><!--/.row -->
            <?php
                if (count ($data['image']) > 0) {
            ?>
                    <p class="lead">
                        Images
                    </p>
                    <p>
                        Click on an image for a larger view
                    </p>
                    <div class="row">
                        <?php
                            $imgNum = 1;
                            foreach ($data['image'] as $id => $obj) {
                        ?>
                                <div class="col-md-3">
                                    <p>
                                        <a class="fancybox" rel="gallery" href="<?php echo $REPOS_ROOT_URL . $obj['path']; ?>"
                                           title="<?php echo $obj['desc']; ?>">
                                            <?php
                                                echo "<img src='$REPOS_ROOT_URL" . $obj['path'] . "._thumb.jpg' alt='" . $obj['title'] . "'\n"; 
                                                echo "class='img-responsive' />\n"; 
                                            ?>
                                        </a>
                                    </p>
                                    <p class="text-center">
                                        <?php echo $obj['desc']; ?>
                                    </p>
                                </div><!--/.col-md-3 -->
                        <?php
                                if ($imgNum % 4 === 0) {
                                    # End the row and open the next one
                                    echo "</div><!--/.row --><div class='row'>\n";
                                }
                                $imgNum += 1;
                            }

                            # Finish off the row with empty cells now that we are out of images
                            while ($imgNum % 4 !== 0) {
                                echo "<div class='col-md-3'>&nbsp;</div>\n";
                                $imgNum += 1;
                            }
                        ?>
                        <div class='col-md-3'>&nbsp;</div>
                    </div><!--/.row -->
            <?php
                }
            ?>
        </div><!--/#details -->


        <!-- Div for files -->
        <?php
            if (count ($data['doc_link']) > 0 || count ($data['report']) > 0) {
        ?>
                <div class="tab-pane" id="files">
                    <div class="row">
                        <div class="col-md-1">&nbsp;</div>
                        <?php
                            if (count ($data['doc_link']) > 0) {
                        ?>
                                <div class="col-md-5">
                                    <p class="lead">
                                        Files
                                    </p>
                                    <?php
                                        foreach ($data['doc_link'] as $id => $obj) {
                                    ?>
                                        <p>
                                            <?php
                                                if ($obj['res_type'] === 'document') {
                                            ?>
                                                    <span class="<?php echo getFiletypeClass ($obj['path']); ?>"></span>
                                                    <?php echo "<a href='$REPOS_ROOT_URL" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
                                            <?php
                                                }
                                                else {
                                            ?>
                                                    <span class="type-icon link"></span>
                                                    <?php echo "<a href='" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
                                            <?php
                                                }
                                            ?>
                                        </p>
                                    <?php
                                        }
                                    ?>
                                </div><!--/.col-md-5 -->
                        <?php
                            }
                            else {
                                echo "<div class='col-md-5'>&nbsp;</div>\n";
                            }
                            if (count ($data['report']) > 0) {
                        ?>
                                <div class="col-md-5">
                                    <p class="lead">
                                        Progress Reports
                                    </p>
                                    <?php
                                        foreach ($data['report'] as $id => $obj) {
                                    ?>
                                        <p>
                                            <span class="<?php echo getFiletypeClass ($obj['path']); ?>"></span>
                                            <?php echo "<a href='$REPOS_ROOT_URL" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
                                        </p>
                                    <?php
                                        }
                                    ?>
                                </div><!--/.col-md-5 -->
                        <?php
                            }
                            else {
                                echo "<div class='col-md-5'>&nbsp;</div>\n";
                            }
                        ?>
                        <div class="col-md-1">&nbsp;</div>
                    </div><!--/.row -->
                </div><!--/#files -->
        <?php
            }
        ?>
    </div><!--/.tab-content -->
</div><!--/.row -->