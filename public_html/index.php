<?php
// Include the configuration file
include ('/home/solaryps/config/config.php');

// Open connection to MySQL database
$db_link = new mysqli ($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, $DB_PORT, $DB_SOCKET);

// Declare page parameter variables
$page = 'index';
$site_id = NULL;

// Collect URL parameters
if (isset ($_GET['page'])) {
    $page = $_GET['page'];
}
if (isset ($_GET['siteID'])) {
    $site_id = $_GET['siteID'];

    // Validate site ID
    $stmt = $db_link->prepare ("SELECT " .
                               "    COUNT(*) " .
                               "FROM " .
                               "    site " .
                               "WHERE " .
                               "    site.id=?");
    $stmt->bind_param ('s', $site_id);
    $stmt->execute ();
    $stmt->bind_result ($count);
    $stmt->fetch ();
    $stmt->close ();

    if ($count !== 1) {
        $page = 'error';
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>SolarYpsi | Ypsilanti, Michigan</title>
        
        <meta charset="UTF-8" />
        
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-1.9.1.min.js"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/moment.min.js"></script>
        
        <?php
            if ($page === 'index') {
        ?>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/leaflet/leaflet-0.5.1.js"></script>
                <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/leaflet-0.5.1.css" />
                <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/leaflet-0.5.1.ie.css" />
        <?php
            }
            else if ($page === 'install') {
        ?>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/fancyapps/fancybox/jquery.fancybox.pack.js"></script>
                <!--[if lte IE 8]><script type="text/javascript" src="http://statics.solar.ypsi.com/js/excanvas.min.js"></script><![endif]-->
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/flot/jquery.flot.js"></script>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/flot/jquery.flot.stack.js"></script>
        <?php
            }
            else if ($page === 'presentations') {
        ?>
                <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/bootstrap.vertical-tabs.min.css" />
        <?php
            }
        ?>
        
        <script type="text/javascript" src="/statics/script.js"></script>
        <script type="text/javascript">
            google.load ('visualization', '1', { packages: ['corechart'] });
            <?php echo "g_site_id = '$site_id';\n"; ?>
        </script>
        
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/jquery.fancybox.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="/statics/style.css" />
        
        <!-- Bookmark Icon -->
        <link rel='shortcut icon' href='http://statics.solar.ypsi.com/images/icon.png' />
    </head>
    <body>
        <!-- Wrap all page content here -->
        <div id="wrap">
            <!-- Fixed navbar -->
            <div class="navbar navbar-default navbar-wrapper navbar-fixed-top" role="navigation">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="/index">
                            <img src="http://statics.solar.ypsi.com/images/icon.png" alt="SolarYpsi | Ypsilanti, MI"
                                 style="height: 32px; margin-top: -9px; width: 32px;" />
                            SolarYpsi
                        </a>
                    </div><!--/.navbar-header -->
                    <div class="collapse navbar-collapse bs-navbar-collapse">
                        <ul class="nav navbar-nav">
                            <li <?php if ($page === "index") { echo "class='active'"; } ?>><a href="/index">Home</a></li>
                            <li <?php if ($page === "install") { echo "class='active'"; }?>><a href="/installations">Installations</a></li>
                            <li <?php if ($page === "links") { echo "class='active'"; } ?>><a href="/links">Links</a></li>
                            <li <?php if ($page === "presentations") { echo "class='active'"; } ?>><a href="/presentations">Presentations</a></li>
                            <li <?php if ($page === "events") { echo "class='active'"; } ?>><a href="/events">Events</a></li>
                            <li <?php if ($page === "about") { echo "class='active'"; } ?>><a href="/about">About</a></li>
                            <li <?php if ($page === "contact") { echo "class='active'"; } ?>><a href="/contact">Contact</a></li>
                            <li><a href="/blog" target="_blank">Blog</a></li>
                        </ul>
                        <div class="col-sm-3 col-md-3 pull-right">
                            <img id="imgWeatherImg" src="/statics/images/blank.png" alt="Weather"
                                 style="height: 50px; width: 50px;" />
                            <span id="spnWeatherTemp"></span>
                            - Ypsilanti, MI
                        </div>
                    </div><!--/.nav-collapse -->
                </div><!--/.container -->
            </div><!--/.navbar -->

            <div class="container">
                <div class="row">
                    <?php
                        switch ($page) {
                            case "index":
                                include ('../content/index.html');
                                break;
                            case "install":
                                if ($site_id === NULL) {
                                    include ('../content/installation_list.php');
                                }
                                else if ($site_id === 'comparison') {
                                    include ('../content/installation_comparison.php');
                                }
                                else {
                                    include ('../content/installation_site.php');
                                }
                                break;
                            case "links":
                                echo "<div class='page-header'><h2>Links</h2></div>\n";
                                echo "<p class='lead'>Useful resources regarding solar power</p>\n";
                                include ('../content/links.php');
                                break;
                            case "presentations":
                                include ('../content/presentations.html');
                                break;
                            case "events":
                                echo "<div class='page-header'><h2>Upcoming Events</h2></div>\n";
                                echo "<p class='lead'>Come out and see us!</p>\n";
                                include ('../content/events.html');
                                break;
                            case "about":
                                echo "<div class='page-header'><h2>About</h2></div>\n";
                                echo "<p class='lead'>Some history on the project</p>\n";
                                include ('../content/about.html');
                                break;
                            case "contact":
                                echo "<div class='page-header'><h2>Contact</h2></div>\n";
                                echo "<p class='lead'>Get in touch with us to learn more!</p>\n";
                                include ('../content/contact.html');
                                break;
                            default:
                                include ('../content/404.html');
                                break;
                        }
                    ?>
                </div><!--/.row -->
            </div><!--/.container -->
        </div><!--/.wrap -->

        <div id="footer">
            <div class="container">
                <p>
                    Created and maintained by <a href="http://www.nikestep.me/" target="_blank">Nik Estep</a>
                </p>
                <p>
                    Web hosting generously provided by <a href="http://www.hdl.com/" target="_blank">HDL.com</a>
                </p>
            </div><!--/.container -->
        </div><!--/.footer -->

        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-8609577-1', 'ypsi.com');
            ga('send', 'pageview');
        </script>
    </body>
</html>

<?php
// Close the database connection
$db_link->close ();
?>