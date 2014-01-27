<?php
// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>SolarYpsi | Admin Portal</title>
		
		<meta charset="UTF-8" />
		
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-1.9.1.min.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-ui-1.10.1.custom.min.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/jquery.wysiwyg.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/controls/wysiwyg.colorpicker.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/controls/wysiwyg.cssWrap.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/controls/wysiwyg.image.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/controls/wysiwyg.link.js"></script>
		<script type="text/javascript" src="http://statics.solar.ypsi.com/js/jwysiwyg/controls/wysiwyg.table.js"></script>
		<script type="text/javascript" src="./statics/script.js"></script>
		
		<link rel="stylesheet" type="text/css" href="./statics/style.css" />
		<link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/jquery-ui-1.10.1.custom.min.css" />
		<link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/js/jwysiwyg/jquery.wysiwyg.css" />
		
		<!-- Bookmark Icon -->
    	<link rel='shortcut icon' href='http://statics.solar.ypsi.com/img/icon.png' />
	</head>
	<body>
		<div id="container">
			<div id="header">
				<h1>SolarYpsi Administration Portal</h1>
			</div>
			<div id="main">
				<div class="tabs">
					<ul>
						<li><a href="#tabs-installs">Installations</a></li>
						<li><a href="#tabs-grids">Site Grid</a></li>
						<li><a href="#tabs-links">Links Page</a></li>
						<li><a href="#tabs-presentations">Presentations Page</a></li>
						<li><a href="#tabs-event">Events Page</a></li>
						<li><a href="#tabs-about">About Page</a></li>
						<li><a href="#tabs-contact">Contact Page</a></li>
						<li><a href="#tabs-cron">Cron</a></li>
					</ul>
					<div id="tabs-installs">
						<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
							<div class="header">
								Edit Installation
							</div>
							<div>
								<select id="selSites">
									<option value="SELECT">-- Select a Site</option>
									<?php
										$stmt = $db_link->prepare ("SELECT " .
																   "    id, " .
																   "    description " .
																   "FROM " .
																   "    site " .
																   "ORDER BY " .
																   "    description");
										$stmt->execute ();
										$stmt->bind_result ($id, $desc);
	
										while ($stmt->fetch ()) {
											echo "<option value='$id'>$desc</option>\n";
										}
	
										$stmt->close ();
									?>
								</select>
								<input type="button" id="btnEditSite"
									   value="Edit" class="button" />
							</div>
						</div>
						<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
							<div class="header">
								Create New Installation
							</div>
							<div class="instructions">
								Do not use spaces in the Site ID
							</div>
							<div class="formRow">
								<span class="inputLabel">
									Site ID:
								</span>
								<span class="inputElement">
									<input type="text" id="txtNewSiteID" />
									<span id="spnNewSiteIDValid"></span>
								</span>
							</div>
							<div class="formRow">
								<span class="inputLabel">
									Descriptive Name:
								</span>
								<span class="inputElement">
									<input type="text" id="txtNewSiteDesc" />
								</span>
							</div>
							<div class="formRow">
								<span class="inputLabel">&nbsp;</span>
								<span>
									<input type="button" id="btnCreateSite"
										   value="Create" class="button" />
								</span>
							</div>
						</div>
						<div id="dvEditSite" class="col1wide">
							<div class="header">
								Edit Information for <span id="spnSiteEditLabel"></span>
							</div>
							<div class="tabs">
								<ul>
									<li><a href="#tabs-edit-1">Basic Information</a></li>
									<li><a href="#tabs-edit-2">Documents + Links</a></li>
									<li><a href="#tabs-edit-3">Reports</a></li>
									<li><a href="#tabs-edit-4">Images</a></li>
									<li><a href="#tabs-edit-5">QR Video</a></li>
								</ul>
								<div id="tabs-edit-1">
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Public Information
										</div>
										<div class="description">
											Fill in any or all values below.
										</div>
										<div class="instructions">
											Note that any value that is not entered will not be
											displayed to the user (including the label).
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Type:
											</span>
											<span class="inputElement">
												<select id="selType">
													<option value="unknown">Unknown</option>
													<option value="public">Public</option>
													<option value="municipal">Municipal</option>
													<option value="private">Private</option>
													<option value="semiprivate">Semi-Private</option>
													<option value="commercial">Commercial</option>
													<option value="demonstration">Demonstration</option>
												</select>
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Completed:
											</span>
											<span class="inputElement">
												<input type="text" id="txtCompleted" maxlength="32" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Number of Panels:
											</span>
											<span class="inputElement">
												<input type="text" id="txtNumberPanels" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Angle of Panels:
											</span>
											<span class="inputElement">
												<input type="text" id="txtAnglePanels" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Inverter:
											</span>
											<span class="inputElement">
												<input type="text" id="txtInverter" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Installer:
											</span>
											<span class="inputElement">
												<input type="text" id="txtInstaller" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Installer URL:
											</span>
											<span class="inputElement">
												<input type="text" id="txtInstallerURL" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Rated Output (W):
											</span>
											<span class="inputElement">
												<input type="text" id="txtOutput" maxlength="12" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Contact:
											</span>
											<span class="inputElement">
												<input type="text" id="txtContact" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Contact URL:
											</span>
											<span class="inputElement">
												<input type="text" id="txtContactURL" maxlength="128" size="29" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												List Description:
											</span>
											<span class="inputElement">
												<textarea id="txaList" rows="4" cols="27">
												
												</textarea>
											</span>
										</div>
									</div>
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Internal Configuration
										</div>
										<div class="description">
											These configuration values are used internally and are not
											directly displayed to the user.
										</div>
										<div class="formRow">
											<span class="inputLabel">
												System Status:
											</span>
											<span class="inputElement">
												<select id="selStatus">
												    <option value="hidden">Hidden</option>
													<option value="inactive">Inactive</option>
													<option value="active">Active</option>
												</select>
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												In Ypsilanti:
											</span>
											<span class="inputElement">
												<select id="selInCity">
													<option value="in">Yes</option>
													<option value="out">No</option>
												</select>
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Latitude:
											</span>
											<span class="inputElement">
												<input type="text" id="txtLatitude" maxlength="10" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Longitude:
											</span>
											<span class="inputElement">
												<input type="text" id="txtLongitude" maxlength="10" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Max Wh:
											</span>
											<span class="inputElement">
												<input type="text" id="txtMaxWH" maxlength="10" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Max kW:
											</span>
											<span class="inputElement">
												<input type="text" id="txtMaxKW" maxlength="10" />
											</span>
										</div>
										<div class="formRow">
											<span class="inputLabel">
												Metering Type:
											</span>
											<span class="inputElement">
												<select id="selMeteringType">
													<option value="none">Not Metered</option>
													<option value="solarypsi">Laptop Metering</option>
													<option value="enphase">Enphase Metering</option>
												</select>
											</span>
										</div>
									</div>
									<div class="col1wide ui-widget ui-widget-content ui-corner-all tablike-padding centered top10px formRow">
										<input type="button" id="btnSaveBasic" value="Save" />
										<span id="spnBasicSaveValid"></span>
									</div>
								</div>
								<div id="tabs-edit-2">
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Organize Uploaded Documents / Links
										</div>
										<div class="description">
											The order below is the order that the documents and links will
											be listed on the SolarYpsi site.
										</div>
										<div class="instructions">
											Drag and drop to re-arrange.
										</div>
										<div class="sortContainer">
											<ul id="ulDocumentSort" class="sortable-list">
												
											</ul>
										</div>
										<div class="formRow">
											<span class="shortInputLabel">&nbsp;</span>
											<span class="inputElement">
												<input type="button" id="btnSaveDocuments" value="Save Ordering" />
												<span id="spnDocumentSortResult"></span>
											</span>
										</div>
									</div>
									<div class="col2wide">
										<div class="ui-widget ui-widget-content ui-corner-all tablike-padding">
											<div class="subheader">
												Upload a New Document
											</div>
											<div>
												<form id="frmDocument" enctype="multipart/form-data">
													<!-- Necessary hidden fields to pass information to the server
														 Note that JS updates the siteID -->
													<input type="hidden" name="resourceType" value="document" />
													<input type="hidden" name="siteID" class="frmHiddenSiteID" value="" />
													<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
													
													<!-- Visible form content -->
													<div class="formRow">
														<span class="shortInputLabel">
															Title:
														</span>
														<span class="inputElement">
															<input type="text" name="title" maxlength="128" size="36" />
														</span>
													</div>
													<!--<div class="formRow">
														<span class="shortInputLabel">
															Description:
														</span>
														<span class="inputElement">
															<input type="text" name="description" maxlength="512" size="36" />
														</span>
													</div>-->
													<input type="hidden" name="description" value="" />
													<div class="formRow">
														<span class="shortInputLabel">
															Document:
														</span>
														<span class="inputElement">
															<input type="file" name="userfile" />
														</span>
													</div>
													<div class="formRow">
														<span class="shortInputLabel">&nbsp;</span>
														<span class="inputElement">
															<input type="button" id="btnUploadDocument" value="Upload" />
															<span class="upload-valid"></span>
														</span>
													</div>
												</form>
												<div id="divPrgDocument" class="hidden">
													<progress id="prgDocument"></progress>
												</div>
											</div>
										</div>
										<div class="top10px ui-widget ui-widget-content ui-corner-all tablike-padding">
											<div class="subheader">
												Add a Link
											</div>
											<div>
												<form id="frmDocLinks">
													<!-- Necessary hidden field to pass information to the server
														 Note that JS updates the siteID -->
													<input type="hidden" name="resourceType" value="link" />
													<input type="hidden" name="siteID" class="frmHiddenSiteID" value="" />
													
													<!-- Visible form content -->
													<div class="formRow">
														<span class="shortInputLabel">
															Title:
														</span>
														<span class="inputElement">
															<input type="text" name="title" maxlength="128" size="36" />
														</span>
													</div>
													<!--<div class="formRow">
														<span class="shortInputLabel">
															Description:
														</span>
														<span class="inputElement">
															<input type="text" name="description" maxlength="512" size="36" />
														</span>
													</div>-->
													<input type="hidden" name="description" value="" />
													<div class="formRow">
														<span class="shortInputLabel">
															Link:
														</span>
														<span class="inputElement">
															<input type="text" name="link" maxlength="512" size="36" />
														</span>
													</div>
													<div class="formRow">
														<span class="shortInputLabel">&nbsp;</span>
														<span class="inputElement">
															<input type="button" id="btnUploadLink" value="Save" />
															<span class="upload-valid"></span>
														</span>
													</div>
												</form>
											</div>
										</div>
									</div>
								</div>
								<div id="tabs-edit-3">
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Organize Uploaded Reports
										</div>
										<div class="description">
											The order below is the order that the reports will be listed
											on the SolarYpsi site.
										</div>
										<div class="instructions">
											Drag and drop to re-arrange.
										</div>
										<div class="sortContainer">
											<ul id="ulReportSort" class="sortable-list">
												
											</ul>
										</div>
										<div class="formRow">
											<span class="shortInputLabel">&nbsp;</span>
											<span class="inputElement">
												<input type="button" id="btnSaveReports" value="Save Ordering" />
												<span id="spnReportSortResult"></span>
											</span>
										</div>
									</div>
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Upload a New Report
										</div>
										<div>
											<form id="frmReport" enctype="multipart/form-data">
												<!-- Necessary hidden fields to pass information to the form
													 Note that JS updates the siteID -->
												<input type="hidden" name="resourceType" value="report" />
												<input type="hidden" name="siteID" class="frmHiddenSiteID" value="" />
												<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
												
												<!-- Visible form content -->
												<div class="formRow">
													<span class="shortInputLabel">
														Title:
													</span>
													<span class="inputElement">
														<input type="text" name="title" maxlength="128" size="36" />
													</span>
												</div>
												<!--<div class="formRow">
													<span class="shortInputLabel">
														Description:
													</span>
													<span class="inputElement">
														<input type="text" name="description" maxlength="512" size="36" />
													</span>
												</div>-->
												<input type="hidden" name="description" value="" />
												<div class="formRow">
													<span class="shortInputLabel">
														Report:
													</span>
													<span class="inputElement">
														<input type="file" name="userfile" />
													</span>
												</div>
												<div class="formRow">
													<span class="shortInputLabel">&nbsp;</span>
													<span class="inputElement">
														<input type="button" id="btnUploadReport" value="Upload" />
														<span class="upload-valid"></span>
													</span>
												</div>
											</form>
											<div id="divPrgReport" class="hidden">
												<progress id="prgReport"></progress>
											</div>
										</div>
									</div>
								</div>
								<div id="tabs-edit-4">
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="instructions">
											At some point, you will be able to select the "cover" image for this site
											here. This is the image that will be displayed next to the site name on
											the list of installations page.
										</div>
									</div>
									<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
										<div class="subheader">
											Upload a New Image
										</div>
										<div class="instructions">
											Upload the full-size image, not a thumbnail.
										</div>
										<div class="instructions">
											At this time, you can only upload images with a three character
											extension (e.g. 'jpg' and NOT 'jpeg').
										</div>
										<div>
											<form id="frmImage" enctype="multipart/form-data">
												<!-- Necessary hidden fields to pass information to the form
													 Note that JS updates the siteID -->
												<input type="hidden" name="resourceType" value="image" />
												<input type="hidden" name="siteID" class="frmHiddenSiteID" value="" />
												<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
												
												<!-- Visible form content -->
												<div class="formRow">
													<span class="shortInputLabel">
														Alt Text:
													</span>
													<span class="inputElement">
														<input type="text" name="title" maxlength="128" size="36" />
													</span>
												</div>
												<div class="formRow">
													<span class="shortInputLabel">
														Description:
													</span>
													<span class="inputElement">
														<input type="text" name="description" maxlength="512" size="36" />
													</span>
												</div>
												<!--<input type="hidden" name="description" value="" />-->
												<div class="formRow">
													<span class="shortInputLabel">
														Image:
													</span>
													<span class="inputElement">
														<input type="file" name="userfile" />
													</span>
												</div>
												<div class="formRow">
													<span class="shortInputLabel">&nbsp;</span>
													<span class="inputElement">
														<input type="button" id="btnUploadImage" value="Upload" />
														<span class="upload-valid"></span>
													</span>
												</div>
											</form>
											<div id="divPrgImage" class="hidden">
												<progress id="prgImage"></progress>
											</div>
										</div>
									</div>
									<div class="col1wide ui-widget ui-widget-content ui-corner-all tablike-padding top10px">
										<div class="subheader">
											Organize Uploaded Images
										</div>
										<div class="description">
											The order below is the order that the reports will be listed
											on the SolarYpsi site.
										</div>
										<div class="instructions">
											Drag and drop to re-arrange.
										</div>
										<div>
											<div class="sortContainer">
												<ul id="ulImageSort" class="sortable-list">
												
												</ul>
											</div>
										</div>
										<div class="top10px">
											<div class="centered">
												<input type="button" id="btnSaveImages" value="Save Ordering" />
												<span id="spnImageSortResult"></span>
											</div>
										</div>
									</div>
								</div>
								<div id="tabs-edit-5">
									<div class="instructions">
										Enter the ID from the Youtube embed link.
									</div>
									<div class="instructions">
										(e.g. http://youtu.be/ABC012ZX => ABC012ZX)
									</div>
									<div class="formRow centered">
										<span class="shortInputLabel">Embed ID:</span>
										<span class="inputElement">
											<input type="text" id="txtQR" maxlength="32" size="24" />
										</span>
										<span class="inputElement">
											<input type="button" id="btnSaveQR" value="Save Embed ID" />
											<span id="spnQRResult"></span>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="tabs-grids">
						<div class="instructions">
							I am envisioning putting a quick-reference grid that displays some information
							about each site in a table format that allows for easy reference and quick
							edits to maintain consistency.
						</div>
					</div>
					<div id="tabs-links">
						<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
							<div class="subheader">
								Organize Link
							</div>
							<div class="description">
								The order below is the order that the links will
								be listed on the SolarYpsi site.
							</div>
							<div class="instructions">
								Drag and drop to re-arrange.
							</div>
							<div class="sortContainer">
								<ul id="ulLinkSort" class="sortable-list">
									<?php
										$stmt = $db_link->prepare ("SELECT " .
																   "    id, " .
																   "    title, " .
																   "    link_desc, " .
																   "    visible_link, " .
																   "    full_link " .
																   "FROM " .
																   "    website_link " .
																   "ORDER BY " .
																   "    disp_order");
										$stmt->execute ();
										$stmt->bind_result ($id,
															$title,
															$desc,
															$visible_link,
															$full_link);
	
										while ($stmt->fetch ()) {
											echo "<li class='ui-state-default'>\n";
											echo "<span class='sortable-hidden-id hidden'>$id</span>\n";
											echo "<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>\n";
											echo "<span class='link-content'>\n";
											echo "<div>\n";
											echo "<span class='bold'>$title</span>\n";
											if ($desc !== NULL) {
												echo "<span>($desc)</span>\n";
											}
											echo "</div>\n";
											echo "<div class='url-and-edit'>\n";
											echo "<span class='link-url'>\n";
											echo "<a href='$full_link'>$visible_link</a>\n";
											echo "</span>\n";
											/*echo "<span class='link-edit'>\n";
											echo "<span class='edit-delete-icon-width action-edit-link ui-state-default ui-corner-all'><span class='ui-icon ui-icon-pencil'>&nbsp;</span></span>\n";
											echo "<span class='edit-delete-icon-width action-delete-link ui-state-default ui-corner-all'><span class='ui-icon ui-icon-trash'>&nbsp;</span></span>\n";
											echo "</span>\n";*/
											echo "</div>\n";
											echo "</span>\n";
											echo "</li>\n";
										}
	
										$stmt->close ();
									?>
								</ul>
							</div>
							<div class="formRow">
								<span class="shortInputLabel">&nbsp;</span>
								<span class="inputElement">
									<input type="button" id="btnSaveLinks" value="Save Ordering" />
									<span id="spnLinkSortResult"></span>
								</span>
							</div>
						</div>
						<div class="col2wide ui-widget ui-widget-content ui-corner-all tablike-padding">
							<div class="subheader">
								Add a New Link
							</div>
							<div>
								<form id="frmLink" enctype="multipart/form-data">
									<div class="formRow">
										<span class="shortInputLabel">
											Title:
										</span>
										<span class="inputElement">
											<input type="text" name="title" maxlength="256" size="36" />
										</span>
									</div>
									<div class="formRow">
										<span class="shortInputLabel">
											Description:
										</span>
										<span class="inputElement">
											<input type="text" name="description" maxlength="256" size="36" />
										</span>
									</div>
									<div class="formRow">
										<span class="shortInputLabel">
											Visible Link:
										</span>
										<span class="inputElement">
											<input type="text" name="visible_link" maxlength="256" size="36" />
										</span>
									</div>
									<div class="formRow">
										<span class="shortInputLabel">
											Full Link:
										</span>
										<span class="inputElement">
											<input type="text" name="full_link" maxlength="1024" size="36" />
										</span>
									</div>
									<div class="formRow">
										<span class="shortInputLabel">&nbsp;</span>
										<span class="inputElement">
											<input type="button" id="btnSaveLink" value="Add" />
											<span class="upload-valid"></span>
										</span>
									</div>
								</form>
							</div>
						</div>
					</div>
					<div id="tabs-presentations">
						<div class="instructions">
							This tab is in progress, more patience, I suppose. I'm sorry for these delays.
						</div>
						<div class="tabs">
							<ul>
								<li><a href="#tabs-pres-1">Files</a></li>
								<li><a href="#tabs-pres-2">Videos</a></li>
								<li><a href="#tabs-pres-3">Footer Message</a></li>
							</ul>
							<div id="tabs-pres-1">
							
							</div>
							<div id="tabs-pres-2">
							
							</div>
							<div id="tabs-pres-3">
							
							</div>
						</div>
					</div>
					<div id="tabs-event">
						<div class="instructions">
							Below you can edit and save the content for the Events page on SolarYpsi.
							The page header is automatically added before the content on the page.
						</div>
						<div class="top10px">
							<textarea id="txaEvents" rows="25" cols="117">
								<?php
									if ($PRODUCTION) {
										include ('/home/solaryps/content/events.html');
									}
									else {
										include ('../public_html/content/events.html');
									}
								?>
							</textarea>
						</div>
						<div class="top10px centered formRow">
							<input type="button" id="btnSaveEvents" value="Save" />
							<span id="spnContentEventsResult"></span>
						</div>
					</div>
					<div id="tabs-about">
						<div class="instructions">
							Below you can edit and save the content for the About page on SolarYpsi.
							The page header is automatically added before the content on the page.
						</div>
						<div class="top10px">
							<textarea id="txaAbout" rows="25" cols="117">
								<?php
									if ($PRODUCTION) {
										include ('/home/solaryps/content/about.html');
									}
									else {
										include ('../public_html/content/about.html');
									}
								?>
							</textarea>
						</div>
						<div class="top10px centered formRow">
							<input type="button" id="btnSaveAbout" value="Save" />
							<span id="spnContentAboutResult"></span>
						</div>
					</div>
					<div id="tabs-contact">
						<div class="instructions">
							Below you can edit and save the content for the Contact page on SolarYpsi.
							The page header is automatically added before the content on the page.
						</div>
						<div class="top10px">
							<textarea id="txaContact" rows="25" cols="117">
								<?php
									if ($PRODUCTION) {
										include ('/home/solaryps/content/contact.html');
									}
									else {
										include ('../public_html/content/contact.html');
									}
								?>
							</textarea>
						</div>
						<div class="top10px centered formRow">
							<input type="button" id="btnSaveContact" value="Save" />
							<span id="spnContentContactResult"></span>
						</div>
					</div>
					<div id="tabs-cron">
						<table>
							<thead>
								<tr>
									<th class="name">Job Name</th>
									<th class="path">Relative Path</th>
									<th class="schedule">Schedule</th>
									<th class="enabled">Enabled</th>
									<th class="actions">&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$stmt = $db_link->prepare ("SELECT " .
															   "    name, " .
															   "    path, " .
															   "    schedule, " .
															   "    enabled " .
															   "FROM " .
															   "    cron_schedule");
									$stmt->execute ();
									$stmt->store_result ();
									$stmt->bind_result ($name,
														$path,
														$schedule,
														$enabled);
									while ($stmt->fetch ()) {
								?>
										<tr>
											<td class="name"><?php echo $name; ?></td>
											<td class="path"><?php echo $path; ?></td>
											<td class="schedule"><?php echo $schedule; ?></td>
											<td class="enabled"><?php $val = ($enabled == 1) ? "Yes" : "No"; echo $val; ?></td>
											<td class="actions">
												<ul class="icons-edit-buttons ui-widget ui-helper-clearfix">
													<li class="edit ui-state-default ui-corner-all" title="Edit">
														<span class="ui-icon ui-icon-pencil"></span>
													</li>
													<li class="delete ui-state-default ui-corner-all" title="Delete">
														<span class="ui-icon ui-icon-trash"></span>
													</li>
												</ul>
											</td>
										</tr>
								<?php
									}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

<?php
// Close the database connection
$db_link->close ();
?>