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

<div class='page-header'>
    <h2>Project Installations</h2>
</div>
<p class="lead">
    Click on an installation to learn more
</p>

<!-- Comparison site -->
<div class="row installation-list-row">
    <div class="row">
        <div class="col-md-12">
            <h4><a href='/installations/comparison'>Installation Comparison</a></h4>
        </div><!--/.col-md-12 -->
    </div><!--/.row -->
    <div class="row">
        <div class="col-md-2">
            <img src="/statics/images/site_comparison.jpg"
                 alt="Compare Solar Production Among Sites"
                 style="height: 100%; width: 100%;" />
        </div><!--/.col-md-2 -->
        <div class="col-md-8">
            Compare the solar generation at different installations on SolarYpsi.
        </div><!--/.col-md-8 -->
        <div class="col-md-2">
            &nbsp;
        </div><!--/.col-md-2 -->
    </div><!--/.row -->
</div><!--/.row -->

<?php
// Iterate over each site and add its content to the page
$stmt = $db_link->prepare ("SELECT " .
                           "    site.id AS siteid, " .
                           "    site.description AS site_desc, " .
                           "    site_info.list_desc AS list_desc, " .
                           "    (SELECT site_resource.file_path FROM site_resource WHERE site_resource.site_id = siteid AND res_type = 'image' AND disp_order = 1) AS img_file_path, " .
                           "    (SELECT site_resource.title FROM site_resource WHERE site_resource.site_id = siteid AND res_type = 'image' AND disp_order = 1) AS img_alt, " .
                           "    site_info.meter_type AS meter_type " .
                           "FROM " .
                           "    site INNER JOIN site_info ON site.id = site_info.site_id " .
                           "WHERE " .
                           "    site_info.status <> 'hidden' " .
                           "ORDER BY " .
                           "    site_desc");
$stmt->execute ();
$stmt->bind_result ($id, $desc, $list_desc, $img_file_path, $img_alt, $meter_type);

while ($stmt->fetch ()) {
?>
    <div class="row installation-list-row">
        <div class="row">
            <div class="col-md-8">
                <?php echo "<h4><a href='/installations/$id'>$desc</a></h4>\n"; ?>
            </div><!--/.col-md-8 -->
            <div class="col-md-2">
                <?php
                    if ($meter_type === 'enphase') {
                        echo "<h4><span class='label label-success pull-right'>Enphase</span></h4>\n";
                    }
                    else if ($meter_type === 'historical') {
                        echo "<h4><span class='label label-warning pull-right'>Historical Data</span></h4>\n";
                    }
                ?>
            </div><!--/.col-md-2 -->
            <div class="col-md-2">&nbsp;</div>
        </div><!--/.row -->
        <div class="row">
            <div class="col-md-2">
                <?php
                    if ($img_file_path === NULL) {
                        echo "<img src='/statics/images/blank.png' alt='Image Not Found' class='img-responsive' />\n";
                    }
                    else {
                        echo "<img src='${REPOS_ROOT_URL}${img_file_path}._thumb.jpg' alt='$img_alt' class='img-responsive' />\n";
                    }
                ?>
            </div><!--/.col-md-2 -->
            <div class="col-md-8">
                <?php echo "$list_desc\n"; ?>
            </div><!--/.col-md-8 -->
            <div class="col-md-2">
                &nbsp;
            </div><!--/.col-md-2 -->
        </div><!--/.row -->
    </div><!--/.row -->
<?php
}

$stmt->close ();
?>