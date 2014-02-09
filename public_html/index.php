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
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>SolarYpsi | Ypsilanti, Michigan</title>
        
        <meta charset="UTF-8" />
        
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-1.9.1.min.js"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-ui-1.10.1.custom.min.js"></script>
        <script type="text/javascript" src="http://statics.solar.ypsi.com/js/bootstrap.min.js"></script>
        
        <?php
            if ($page === 'index') {
        ?>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/leaflet/leaflet-0.5.1.js"></script>
        <?php
            }
        ?>
        
        <?php
            if ($page === 'install') {
        ?>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/fancyapps/fancybox/jquery.fancybox.pack.js"></script>
                <!--[if lte IE 8]><script type="text/javascript" src="http://statics.solar.ypsi.com/js/excanvas.min.js"></script><![endif]-->
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/flot/jquery.flot.js"></script>
                <script type="text/javascript" src="http://statics.solar.ypsi.com/js/jquery-plugins/flot/jquery.flot.stack.js"></script>
        <?php
            }
        ?>
        
        <script type="text/javascript" src="/statics/script.js"></script>
        <script type="text/javascript">
            google.load ('visualization', '1', { packages: ['corechart'] });
            <?php echo "g_site_id = '$site_id';\n"; ?>
        </script>
        
        <link rel="stylesheet" type="text/css" href="/statics/style.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/jquery-ui-1.10.1.custom.min.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/leaflet-0.5.1.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/leaflet-0.5.1.ie.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/jquery.fancybox.css" />
        <link rel="stylesheet" type="text/css" href="http://statics.solar.ypsi.com/css/bootstrap.min.css" />
        
        <!-- Bookmark Icon -->
        <link rel='shortcut icon' href='http://statics.solar.ypsi.com/images/icon.png' />
    </head>
    <body>
        <div id="container">
            <div id="nav">
                <ul>
                    <li <?php if ($page === "index") { echo "class='active'"; } ?>><a href="/index">Home</a></li>
                    <li <?php if ($page === "install") { echo "class='active'"; }?>><a href="/installations">Installations</a></li>
                    <li <?php if ($page === "links") { echo "class='active'"; } ?>><a href="/links">Links</a></li>
                    <li <?php if ($page === "presentations") { echo "class='active'"; } ?>><a href="/presentations">Presentations</a></li>
                    <li <?php if ($page === "events") { echo "class='active'"; } ?>><a href="/events">Events</a></li>
                    <li <?php if ($page === "about") { echo "class='active'"; } ?>><a href="/about">About</a></li>
                    <li <?php if ($page === "contact") { echo "class='active'"; } ?>><a href="/contact">Contact</a></li>
                    <li><a href="/blog" target="_blank">SolarYpsi Blog</a></li>
                </ul>
            </div>
            <div id="inner">
                <div id="header">
                    <!-- No content, controlled by CSS -->
                </div>
                <div id="content">
                    <?php
                        switch ($page) {
                            case "index":
                                include ('../content/index.html');
                                echo "<div id='dvMap'></div><p>Click a Pin for Details</p>\n";
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
                                echo "<h3>LINKS</h3>\n";
                                include ('../content/links.php');
                                break;
                            case "presentations":
                                echo "<h3>PRESENTATIONS</h3>\n";
                                include ('../content/presentations.html');
                                break;
                            case "events":
                                echo "<h3>Upcomming Events</h3>\n";
                                include ('../content/events.html');
                                break;
                            case "about":
                                echo "<h3>ABOUT</h3>\n";
                                include ('../content/about.html');
                                break;
                            case "contact":
                                echo "<h3>CONTACT</h3>\n";
                                include ('../content/contact.html');
                                break;
                            default:
                                include ('../content/404.html');
                                break;
                        }
                    ?>
                </div>
                <div id="right">
                    <div class="right-block">
                        <div class="label">
                            Select a Site for Details
                        </div>
                        <div class="text-centered">
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
                        </div>
                    </div>
                    <div class="right-block">
                        <div class="label">
                            Ypsi Weather
                        </div>
                        <div class="text-centered">
                            <img id="imgWeatherImg" src="/statics/images/blank.png" alt="Weather" />
                            <span id="spnWeatherTemp"></span>
                        </div>
                    </div>
                    <div class="right-block">
                        <div class="label">
                            On SolarYpsi
                        </div>
                        <div id="dvPieSites" class="pie">
                        
                        </div>
                        <div id="dvPieWatts" class="pie">
                        
                        </div>
                    </div>
                </div>
            </div>
            <div id="footer">
                <span class="attribute">
                    Created and maintained by <a href="http://www.nicholasestep.com/" target="_blank">Nik Estep</a>
                </span>
                <span class="attribute">
                    Web hosting generously provided by <a href="http://www.hdl.com/" target="_blank">HDL.com</a>
                </span>
            </div>
        </div>
        <!--<script type="text/javascript">
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
        </script>-->
    </body>
</html>

<?php
// Close the database connection
$db_link->close ();
?>