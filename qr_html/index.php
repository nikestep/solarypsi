<?php
// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Retrieve sites with QR codes
$sites = Array ();
$stmt = $db_link->prepare ("SELECT " .
                           "    site.id, " .
                           "    site.description " .
                           "FROM " .
                           "    site INNER JOIN site_info ON site.id = site_info.site_id " .
                           "WHERE " .
                           "    site_info.qr_code IS NOT NULL");
$stmt->execute ();
$stmt->bind_result ($site_id, $desc);
while ($stmt->fetch ()) {
    $sites[$site_id] = $desc;
}
$stmt->close ();

// Close the database connection
$db_link->close ();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>SolarYpsi | Ypsilanti, Michigan</title>
        
        <meta charset="UTF-8" />
        
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/bootstrap.min.css" />
        
        <!-- Bookmark Icon -->
        <link rel='shortcut icon' href='http://statics.solar.ypsi.com/images/icon.png' />
        
        <!-- Google Analytics tracking code -->
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
        <div id="wrap">
            <div class="container">
                <h2>SolarYpsi QR Videos</h2>
                <p>
                    Around Ypsilanti, MI are QR codes that lead to videos about solar installations in the city.
                </p>
                <p>
                    The following sites have videos.
                </p>
                <?php
                    if (isset ($_GET['notfound'])) {
                ?>
                        <div class="alert alert-danger">Requested site not found</div>
                <?php
                    }
                ?>
                <?php
                    foreach ($sites as $site_id => $desc) {
                ?>
                        <p>
                            
                            <a href="http://qr.solar.ypsi.com/installations/<?php echo $site_id; ?>">
                                <button type="button" class="btn btn-default form-control">
                                    <?php echo $desc; ?>
                                </button>
                            </a>
                        </p>
                <?php
                    }
                ?>
            </div><!--/.container -->
        </div><!--/#wrap -->
    </body>
</html>