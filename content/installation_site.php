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

<!-- Label the page -->
<h3><?php echo $data['site_desc'] ?></h3>

<!-- Provide the section links -->
<div id="dvSiteNav">
	<?php
		if ($data['meter_type'] !== 'none') {
	?>
			<div class="sitenav-item sitenav-item-inactive">
				<div class="type">Daily</div>
				Daily Chart
			</div>
			<!--<div class="sitenav-item sitenav-item-inactive">
				<div class="type">Weekly</div>
				Weekly Chart
			</div>-->
			<div class="sitenav-item sitenav-item-inactive">
				<div class="type">Yearly</div>
				Yearly Chart
			</div>
			<div class="sitenav-item sitenav-item-inactive">
				<div class="type">Monthly</div>
				Monthly Usage Chart
			</div>
	<?php
		}
	?>
	<div class="sitenav-item sitenav-item-inactive">
		<div class="type">Details</div>
		Details
	</div>
	<?php
		if (count ($data['doc_link']) > 0 || count ($data['report']) > 0) {
	?>
			<div class="sitenav-item sitenav-item-inactive">
				<div class="type">Files</div>
				Files
			</div>
	<?php
		}
	?>
</div>

<!-- Divs for charts -->
<?php
    if ($data['meter_type'] !== 'none') {
?>
		<div id="dvDaily" class="site-group">
		    <?php
		        if ($data['meter_type'] === 'enphase') {
		            echo "Below is a one day view of generated electricity. This chart may be behind " .
		                 "the current time and may change throughout the day as data is updated.<br />" .
		                 "Right now, historical data is not available, but it will be soon!";
		        }
		        else {
		            echo "Daily metering of this installation is currently unavailable.";
		        }
		    ?>
			Daily metering of this installation is currently unavailable.
			<div id="dvDailyChart" class="chart">
			
			</div>
			<?php
			    if ($data['meter_type'] === 'enphase') {
			?>
			    <div id="dvDailyChartLegend" class="chart-legend">
			        
			    </div>
			    <!--<div id="dvDailyChartControls" class="chart-controls">
    				<div class="left">
    					<div class="container">
    						<input id="btnPrevDaily" type="button" class="chart-control" value="Previous Day" />
    					</div>
    				</div>
    				<div class="right">
    					<div class="container hidden">
    						<input id="btnNextDaily" type="button" class="chart-control" value="Next Day" />
    					</div>
    				</div>
    			</div>-->
			<?php
			    }
			?>
		</div>
		<!--<div id="dvWeekly" class="site-group">
			Weekly metering of this installation is currently unavailable.
			<div id="dvWeeklyChart" class="chart">
			
			</div>
		</div>-->
		<div id="dvYearly" class="site-group">
			Yearly metering of this installation is currently unavailable. The
			below data is provided for a historical record.
			<div id="dvYearlyTitle" class="chart-title">
			
			</div>
			<div id="dvYearlyChart" class="chart">
			
			</div>
			<div id="dvYearlyChartLegend" class="chart-legend">
			
			</div>
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
		</div>
		<div id="dvMonthly" class="site-group">
			Monthly usage metering of this installation is currently
			unavailable. The below data is provided for a historical record.
			<div id="dvMonthlyTitle" class="chart-title">
			
			</div>
			<div id="dvMonthlyChart" class="chart">
			
			</div>
			<div id="dvMonthlyChartLegend" class="chart-legend">
			
			</div>
			<div id="dvMonthlyChartLegend" class="chart-legend">
			
			</div>
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
		</div>
<?php
	}
?>

<!-- Div for details -->
<div id="dvDetails" class="site-group">
	<div class="detail-row">
		<span class="label">Installation Type:</span>
		<span><?php echo formatInstallationType ($data['inst_type']); ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Installation Completed:</span>
		<span><?php echo $data['completed']; ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Number/Type of Solar Panels:</span>
		<span><?php echo $data['panel_desc']; ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Angle/Direction of Solar Panels:</span>
		<span><?php echo $data['panel_angle']; ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Size/Type of Inverter:</span>
		<span><?php echo $data['inverter']; ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Installer:</span>
		<span>
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
		</span>
	</div>
	<div class="detail-row">
		<span class="label">Rated Output:</span>
		<span><?php echo $data['rated_output'] . ' W'; ?></span>
	</div>
	<div class="detail-row">
		<span class="label">Contact:</span>
		<span>
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
		</span>
	</div>
	<?php
		if (count ($data['image']) > 0) {
	?>
			<div class="detail-row">
				<span class="label">Images</span>
				<span>(Click on an image for a larger view)
			</div>
			<?php
				foreach ($data['image'] as $id => $obj) {
			?>
					<div class="detail-image">
						<div>
							<a class="fancybox" rel="gallery" href="<?php echo $REPOS_ROOT_URL . '/' . $obj['path']; ?>"
							   title="<?php echo $obj['desc']; ?>">
								<?php
									echo "<img src='$REPOS_ROOT_URL/" . $obj['path'] . "' alt='" . $obj['title'] . "'\n"; 
									echo "height='" . $obj['thumb_height'] . "' width='" . $obj['thumb_width'] . "' />\n"; 
								?>
							</a>
						</div>
						<div class="caption">
							<?php echo $obj['desc']; ?>
						</div>
					</div>
	<?php
				}
		}
	?>
</div>


<!-- Div for files -->
<?php
	if (count ($data['doc_link']) > 0 || count ($data['report']) > 0) {
?>
		<div id="dvFiles" class="site-group">
			<?php
				if (count ($data['doc_link']) > 0) {
			?>
					<div class="files">
						<div class="header">
							Files
						</div>
						<?php
							foreach ($data['doc_link'] as $id => $obj) {
						?>
							<div class="detail-row">
								<?php
									if ($obj['res_type'] === 'document') {
								?>
										<span class="<?php echo getFiletypeClass ($obj['path']); ?>"></span>
										<span class="label">
											<?php echo "<a href='$REPOS_ROOT_URL/" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
										</span>
								<?php
									}
									else {
								?>
										<span class="type-icon link"></span>
										<span class="label">
											<?php echo "<a href='" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
										</span>
								<?php
									}
								?>
							</div>
						<?php
							}
						?>
					</div>
			<?php
				}
				if (count ($data['report']) > 0) {
			?>
					<div class="files">
						<div class="header">
							Progress Reports
						</div>
						<?php
							foreach ($data['report'] as $id => $obj) {
						?>
							<div class="detail-row">
								<span class="<?php echo getFiletypeClass ($obj['path']); ?>"></span>
								<span class="label">
									<?php echo "<a href='$REPOS_ROOT_URL/" . $obj['path'] . "' target='_blank'>" . $obj['title'] . "</a>\n"; ?>
								</span>
							</div>
						<?php
							}
						?>
					</div>
			<?php
				}
			?>
		</div>
<?php
	}
?>