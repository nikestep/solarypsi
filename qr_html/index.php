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
    $stmt->bind_result ($desc,
                        $video_id);
    $stmt->fetch ();
    $stmt->close ();
    
    // Close the database connection
    $db_link->close ();
    
    if ($video_id === '') {
        header ('Location: http://qr.solar.ypsi.com/not_found.html');
        exit();
    }
}
else {
    header ('Location: http://qr.solar.ypsi.com/not_found.html');
    exit();
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>SolarYpsi | Ypsilanti, Michigan</title>
		
		<meta charset="UTF-8" />
		
		<script type="application/x-javascript" src="https://www.youtube.com/iframe_api"></script>
		<script type="application/x-javascript">
            var player;
            function onYouTubeIframeAPIReady () {
                player = new YT.Player ('player', {
                    height: '390',
                    width: '640',
                    videoId: '<?php echo $video_id; ?>',
                    events: {
                        'onReady': onPlayerReady,
                        'onStateChange': onPlayerStateChange
                    }
                });
            }
            
            function onPlayerReady (event) {
                event.target.playVideo();
            }
            
            function onPlayerStateChange (event) {
                if (event.data == YT.PlayerState.ENDED) {
                    location.href = 'http://solar.ypsi.com/installations/<?php echo $site_id; ?>/';
                }
            }
        </script>
		
		<!-- Bookmark Icon -->
    	<link rel='shortcut icon' href='http://statics.solar.ypsi.com/images/icon.png' />
	</head>
    <body>
        <h1><?php echo $desc; ?></h1>
        <div id="player"></div>
        <script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push (['_setAccount', 'UA-8609577-1']);
			_gaq.push (['_trackPageview']);

			(function () {
				var ga = document.createElement ('script');
				ga.type = 'text/javascript';
				ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName ('script')[0];
				s.parentNode.insertBefore (ga, s);
			}) ();
		</script>
    </body>
</html>