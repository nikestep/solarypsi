<?php
/**
 * Load the list of installations.
 *
 * @author Nik Estep
 * @date March 13, 2013
 * @note This page is included in index.php so we already have the config and
 *       MySQL connection available
 */
?>

<h3>Project Installations</h3>
<p>
	There are currently several solar installations in and around Ypsilanti. 
	More about each one can be found below and clicking the installation name 
	will take you to that installation's page.
</p>

<?php
// Iterate over each site and add its content to the page
$stmt = $db_link->prepare ("SELECT " .
						   "    site.id AS siteid, " .
						   "    site.description AS site_desc, " .
						   "    site_info.list_desc AS list_desc, " .
						   "    (SELECT site_resource.file_path FROM site_resource WHERE site_resource.site_id = siteid AND res_type = 'image' AND disp_order = 1) AS img_file_path, " .
						   "    (SELECT site_resource.title FROM site_resource WHERE site_resource.site_id = siteid AND res_type = 'image' AND disp_order = 1) AS img_alt " .
						   "FROM " .
						   "    site INNER JOIN site_info ON site.id = site_info.site_id " .
						   "WHERE " .
						   "    site_info.status <> 'hidden' " .
						   "ORDER BY " .
						   "    site_desc");
$stmt->execute ();
$stmt->bind_result ($id, $desc, $list_desc, $img_file_path, $img_alt);

while ($stmt->fetch ()) {
?>

	<div class="install-block">
		<div class="header">
			<?php echo "<a href='/installations/$id'>$desc</a>\n"; ?>
		</div>
		<div class="image">
			<?php
				if ($img_file_path === NULL) {
					echo "<img src='/statics/images/blank.png' alt='Image Not Found' />\n";
				}
				else {
					echo "<img src='$REPOS_ROOT_URL$img_file_path' alt='$img_alt' />\n";
				}
			?>
		</div>
		<div class="text">
			<?php echo "$list_desc\n"; ?>
		</div>
	</div>	

<?php
}

$stmt->close ();
?>

<!-- Comparison site -->
<div class="install-block">
	<div class="header">
		<?php echo "<a href='/installations/comparison'>Installation Comparison</a>\n"; ?>
	</div>
	<div class="image">
		<img src="/statics/images/site_comparison.jpg"
			 alt="Compare Solar Production Among Sites" />
	</div>
	<div class="text">
		Compare the solar generation at different installations on SolarYpsi.
	</div>
</div>