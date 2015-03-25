<?php
// Include the configuration file
include ('/home/solaryps/config/config.php');

// Declare page variables
$site_id = NULL;
$desc = NULL;
$video_id = NULL;

// Collect URL parameters
if (isset ($_GET['siteID'])) {
    $site_id = $_GET['siteID'];
    
    // Open connection to MySQL database
    $db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);
    
    // Retrieve data
    $stmt = $db_link->prepare ("SELECT " .
                               "    site.description AS site_desc, " .
                               "    site_info.qr_code AS video_id " .
                               "FROM " .
                               "    site INNER JOIN site_info ON site.id = site_info.site_id " .
                               "WHERE " .
                               "    site.id=?");
    $stmt->bind_param ('s', $site_id);
    $stmt->execute ();
    $stmt->bind_result ($desc, $video_id);
    $stmt->fetch ();
    $stmt->close ();
    
    // Close the database connection
    $db_link->close ();
    
    if ($video_id === '' || $video_id === null) {
        header ('Location: http://qr.solar.ypsi.com/index.php?notfound');
        exit();
    }
}
else {
    header ('Location: http://qr.solar.ypsi.com/index.php?notfound');
    exit();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>SolarYpsi | Ypsilanti, Michigan</title>
        
        <meta charset="UTF-8" />
        
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/bootstrap.min.css" />
		
		<script src="http://statics.solar.ypsi.com/js/jquery-1.11.1.min.js"></script>
		<script src="http://statics.solar.ypsi.com/js/jquery-plugins/jQuery.tubeplayer.min.js"></script>
		<script>
			$(function() {
				$("#player").tubeplayer({
				    width: 640,
				    height: 390,
				    allowFullScreen: 'true',
					autoPlay: true,
					swfobjectURL: 'window',
				    initialVideo: '<?php echo $video_id; ?>',
				    onPlayerEnded: function() {
				    	location.href = 'http://solar.ypsi.com/installations/<?php echo $site_id; ?>/';
				    }
				});
			});
		</script>
        
        <!-- Bookmark Icon -->
        <link rel='shortcut icon' href='http://statics.solar.ypsi.com/images/icon.png' />
        
        <!-- Google Analytics tacking code -->
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', '<?php echo $GA_QR_TRACK_ID; ?>', '<?php echo $GA_DOMAIN; ?>');
            ga('send', 'pageview');
        </script>
    </head>
    <body>
        <!-- Wrap all page content here -->
        <div id="wrap">
            <div class="container">
                <h2><?php echo $desc; ?></h2>
                <div id="player"></div>
            </div><!--/.container -->
        </div><!--/#wrap -->
    </body>
</html>