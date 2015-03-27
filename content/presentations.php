<?php
// Pull the files
$files = array ();
$stmt = $db_link->prepare ("SELECT " .
                           "    title, " .
                           "    pres_path " .
                           "FROM " .
                           "    website_presentation " .
                           "WHERE " .
                           "    pres_type='file' " .
                           "ORDER BY " .
                           "    id");
$stmt->execute ();
$stmt->bind_result ($title, $pres_path);
while ($stmt->fetch ()) {
    array_push ($files, array ('title' => $title,
                               'path' => '/statics/Presentations/' . $pres_path,
                               'icon_path' => strpos($pres_path, '.pdf') ? '/statics/images/pdf.jpg' : '/statics/images/ppt.jpg'));
}
$stmt->close ();

// Pull the videos
$videos = array ();
$stmt = $db_link->prepare ("SELECT " .
                           "    id, " .
                           "    title, " .
                           "    pres_path, " .
                           "    file_type, " .
                           "    preview_image_path, " .
                           "    video_length " .
                           "FROM " .
                           "    website_presentation " .
                           "WHERE " .
                           "    pres_type='video' " .
                           "ORDER BY " .
                           "    id");
$stmt->execute ();
$stmt->bind_result ($id, $title, $pres_path, $file_type, $preview_image_path, $video_length);
while ($stmt->fetch ()) {
    array_push ($videos, array ('id' => $id, 'title' => $title, 'path' => $pres_path,
                                'type' => $file_type, 'img_path' => $preview_image_path,
                                'length' => $video_length));
}
$stmt->close ();
?>

<div class="page-header">
    <h2>Presentations</h2>
</div>
<p class="lead">Learn more</p>

<div>
    <div style="margin-left: 15px;">
        <?php
            foreach ($files as $obj) {
        ?>
                <p>
                    <img src="<?php echo $obj['icon_path']; ?>" height='25' width='25' alt='File' />
                    <a href="<?php echo $obj['path']; ?>" target="_blank">
                        <?php echo $obj['title']; ?>
                    </a>
                </p>
        <?php
            }
        ?>
    </div>
</div>

<?php
include('../content/presentations_footer.html');
?>

<div class="page-header">
    <h2>Videos</h2>
</div>
<p class="lead">Showcasing SolarYpsi</p>

<div class="row">
    <div class="col-md-4">
        <ul class="nav nav-tabs tabs-left">
            <?php
                $first = true;
                foreach ($videos as $obj) {
            ?>
                    <li class="<?php echo $first ? 'active' : ''; $first = false; ?>">
                        <a href="#video<?php echo $obj['id']; ?>" data-toggle="tab">
                            <?php echo $obj['title']; ?>
                            <?php
                                if ($obj['length'] !== NULL) {
                                    echo " (" . $obj['length'] . ")";
                                }
                            ?>
                        </a>
                    </li>
            <?php
                }
            ?>
        </ul>
    </div><!--/.col-md-4 -->
    <div class="col-md-8">
        <div class="tab-content">
            <?php
                $first = true;
                foreach ($videos as $obj) {
            ?>
                    <div class="tab-pane <?php echo $first ? 'active' : ''; $first = false; ?>" id="video<?php echo $obj['id']; ?>">
            <?php
                    if ($obj['type'] === 'flash') {
            ?>
                        
                            <object id="player" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" name="player" width="425" height="315">
                                <param name="movie" value="http://statics.solar.ypsi.com/flash/player.swf" />
                                <param name="allowfullscreen" value="true" />
                                <param name="allowscriptaccess" value="always" />
                                <?php
                                    if ($obj['img_path'] !== NULL) {
                                ?>
                                        <param name="flashvars" value="file=http://solar.ypsi.com/statics/Presentations/<?php echo $obj['path']; ?>&image=http://solar.ypsi.com/statics/images/<?php echo $obj['img_path']; ?>" />
                                <?php
                                    }
                                    else {
                                ?>
                                        <param name="flashvars" value="file=http://solar.ypsi.com/statics/Presentations/<?php echo $obj['path']; ?>" />
                                <?php
                                    }
                                ?>
                                <embed
                                    type="application/x-shockwave-flash"
                                    id="player2"
                                    name="player2"
                                    src="http://statics.solar.ypsi.com/flash/player.swf" 
                                    width="480" 
                                    height="292"
                                    allowscriptaccess="always" 
                                    allowfullscreen="true"
                                <?php
                                    if ($obj['img_path'] !== NULL) {
                                ?>
                                        flashvars="file=http://solar.ypsi.com/statics/Presentations/<?php echo $obj['path']; ?>&image=http://solar.ypsi.com/statics/images/<?php echo $obj['img_path']; ?>"
                                <?php
                                    }
                                    else {
                                ?>
                                        flashvars="file=http://solar.ypsi.com/statics/Presentations/<?php echo $obj['path']; ?>&"
                                <?php
                                    }
                                ?> />
                            </object>
            <?php
                        }
                        else {
            ?>
                            <iframe width="475" height="315" src="<?php echo $obj['path']; ?>" frameborder="0" allowfullscreen>
                            </iframe>
            <?php
                        }
            ?>
                    </div><!--/#video<?php echo $obj['id']; ?> -->
            <?php
                }
            ?>
        </div><!--/.tab-content -->
    </div><!--/.col-md-8 -->
</div><!--/.row -->