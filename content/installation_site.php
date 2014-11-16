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

function formatMeteringType ($type) {
    switch ($type) {
		case 'enphase':
			return "<a href='https://www.enphase.com/'><img src='http://statics.solar.ypsi.com/images/enphase_logo.png' alt='Enphase Energy' /></a>";
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
                               "    earliest_date " .
                               "FROM " .
                               "    enphase_system " .
                               "WHERE " .
                               "    site_id=?");
    $stmt->bind_param ('s', $site_id);
    $stmt->execute ();
    $stmt->bind_result ($data['earliest_date']);
    $stmt->fetch ();
    $stmt->close ();
    $temp = new DateTime ($data['earliest_date']);
    $data['earliest_date'] = $temp->format ('F j, Y');
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
    <span id='spnEarliestDate' class='hidden'><?php echo $data['earliest_date']; ?></span>
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
                <?php
                    if ($data['meter_type'] === 'historical') {
                ?>
                        <li><a href="#monthly" data-toggle="tab">Monthly Usage Chart</a></li>
                <?php
                    }
                ?>
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
                                This chart shows solar production in five-minute increments throughout the day.
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <h3 id="dailyTitle" class="text-center"></h3>
                                        <div id="dvDailyChart" class="chart"></div>
                                        <div id="dvDailyChartLegend" class="chart-legend"></div>
                                    </div>
                                    <p>&nbsp;</p>
                                    <div class="row">
                                        <div class="col-xs-3">
                                            <button id="btnPrevDaily" type="button" class="btn btn-default pull-left vert-middle">
                                                <span class="glyphicon glyphicon-chevron-left"></span>
                                                Previous Day
                                            </button>
                                        </div>
                                        <div class="col-md-6 hidden-xs hidden-sm vert-middle">
                                            <div class="sleep-warning alert alert-warning text-center hide">
                                                It's early and the panels might still be sleeping!
                                            </div>
                                        </div>
                                        <div class="col-xs-6 visible-xs visible-sm">&nbsp;</div>
                                        <div class="col-xs-3">
                                            <button id="btnNextDaily" type="button" class="btn btn-default pull-right disabled vert-middle">
                                                Next Day
                                                <span class="glyphicon glyphicon-chevron-right"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12 visible-xs visible-sm small-top-margin">
                                            <div class="sleep-warning alert alert-warning text-center hide">
                                                It's early and the panels might still be sleeping!
                                            </div>
                                        </div>
                                    </div>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-md-12 col-sm-6">
											<h3>Data Source</h3>
											<p class='chart-sizer'>
											    <?php echo formatMeteringType ($data['meter_type']); ?>
											</p>
                                            <p class='chart-sizer'>
                                                Data back until <?php echo $data['earliest_date']; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-12 col-sm-6">
                                            <h3>Weather</h3>
                                            <p class='chart-sizer'>
                                                Sunrise at <span id="spnSunrise"></span>
                                            </p>
                                            <p class='chart-sizer'>
                                                Solar noon at <span id="spnNoon"></span>
                                            </p>
                                            <p class='chart-sizer'>
                                                Sunset at <span id="spnSunset"></span>
                                            </p>
                                            <p class='chart-sizer'>
                                                <i id="iDailyWeatherIcon"></i>&nbsp;&nbsp;&nbsp;
                                                <i class='glyphicon glyphicon-chevron-down'></i>
                                                <span id="spnDailyTempMin"></span>
                                                <i class="wi-fahrenheit"></i>&nbsp; &nbsp;
                                                <i class='glyphicon glyphicon-chevron-up'></i>
                                                <span id="spnDailyTempMax"></span>
                                                <i class="wi-fahrenheit"></i>
                                            </p>
                                            <p>
                                                <a href="http://forecast.io/">
                                                    <span class="label label-primary" target="_blank">
                                                        Powered by Forecast.io
                                                    </span>
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--/#daily -->
                <?php
                    }
                ?>
                <div class="tab-pane" id="yearly">
                    <?php
                        if ($data['meter_type'] === 'historical') {
                    ?>
                            <p class="chart-sizer">
                                Yearly metering of this installation is currently unavailable. The data
                                below is historical and offered for reference purposes.
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <img id="imgHistYearly" src="<?php echo '/repository/charts_history/' . $site_id . '/yearly_' . $data['historical_end_year'] . '.png'; ?>"
                                             class="img-responsive" alt="Yearly Chart" />
                                    </div>
                                    <div class="row">
                                        <button id="btnPrevHistYearly" type="button" class="btn btn-default pull-left">
                                            <span class="glyphicon glyphicon-chevron-left"></span>
                                            Previous Year
                                        </button>
                                        <button id="btnNextHistYearly" type="button" class="btn btn-default pull-right disabled">
                                            Next Year
                                            <span class="glyphicon glyphicon-chevron-right"></span>
                                        </button>
                                    </div>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                    <?php
                        }
                        else {
                    ?>
                            <p>
                                This chart shows solar production during the given year. The line chart has daily generation
                                totals and the bar chart has monthly generation totals.
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <h3 id="yearlyTitle" class="text-center"></h3>
                                        <div id="dvYearlyChart" class="chart"></div>
                                        <div id="dvYearlyChartLegend" class="chart-legend chart-legend-lower"></div>
                                    </div>
                                    <div class="row">
                                        <p>&nbsp;</p>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 col-xs-12">
                                            <button id="btnPrevYearly" type="button" class="btn btn-default pull-left">
                                                <span class="glyphicon glyphicon-chevron-left"></span>
                                                Previous Year
                                            </button>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <span>
                                                <input type="radio" id="rbtnYearlyDaily" name="yearlyChartType" value="line"
                                                       class="rbtn-yearly" checked="checked" /> Daily Totals
                                            </span>
                                            <span>
                                                <input type="radio" id="rbtnYearlyMonthly" name="yearlyChartType" value="bar"
                                                       class="rbtn-yearly" />Monthly Totals
                                            </span>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <button id="btnNextYearly" type="button" class="btn btn-default pull-right disabled">
                                                Next Year
                                                <span class="glyphicon glyphicon-chevron-right"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">
                                	<div class="col-sm-12">
										<h3>Data Source</h3>
										<p class='chart-sizer'>
										    <?php echo formatMeteringType ($data['meter_type']); ?>
										</p>
                                        <p class='chart-sizer'>
                                            Data back until <?php echo $data['earliest_date']; ?>
                                        </p>
                                    </div><!--/.col-sm-12 -->
                                </div><!--/.col-md-4 -->
                            </div>
                    <?php
                        }
                    ?>
                </div><!--/#yearly -->
                <?php
                    if ($data['meter_type'] === 'historical') {
                ?>
                        <div class="tab-pane" id="monthly">
                            <p>
                                Monthly usage metering of this installation is currently unavailable. The
                                data below is historical and offered for reference purposes.
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <img id="imgHistMonthly" src="<?php echo '/repository/charts_history/' . $site_id . '/monthly_' . $data['historical_end_year'] . '.png'; ?>"
                                             class="img-responsive" alt="Monthly Usage Chart" />
                                    </div>
                                    <div class="row">
                                        <button id="btnPrevHistMonthly" type="button" class="btn btn-default pull-left">
                                            <span class="glyphicon glyphicon-chevron-left"></span>
                                            Previous Year
                                        </button>
                                        <button id="btnNextHistMonthly" type="button" class="btn btn-default pull-right disabled">
                                            Next Year
                                            <span class="glyphicon glyphicon-chevron-right"></span>
                                        </button>
                                    </div>
                                </div><!--/.col-md-8 -->
                                <div class="col-md-4">&nbsp;</div>
                            </div>
                        </div><!--/#monthly -->
                <?php
                    }
                ?>
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
                                <div class="col-md-3 col-sm-6 col-xs-12">
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
                                </div><!--/.col-md-3 col-sm-6 col-xs-12 -->
                        <?php
                                if ($imgNum % 4 === 0) {
                                    # End the row and open the next one
                                    echo "</div><!--/.row --><div class='row'>\n";
                                }
                                $imgNum += 1;
                            }

                            # Finish off the row with empty cells now that we are out of images
                            while ($imgNum % 4 !== 0) {
                                echo "<div class='col-md-3 col-sm-6 col-xs-12'>&nbsp;</div>\n";
                                $imgNum += 1;
                            }
                        ?>
                        <div class='col-md-3 col-sm-6 col-xs-12'>&nbsp;</div>
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
                                <div class="col-md-5 col-sm-12">
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
                                </div><!--/.col-md-5 col-sm-12 -->
                        <?php
                            }
                            else {
                                echo "<div class='col-md-5 col-sm-12'>&nbsp;</div>\n";
                            }
                            if (count ($data['report']) > 0) {
                        ?>
                                <div class="col-md-5 col-sm-12">
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
                                </div><!--/.col-md-5 col-sm-12 -->
                        <?php
                            }
                            else {
                                echo "<div class='col-md-5 col-sm-12'>&nbsp;</div>\n";
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