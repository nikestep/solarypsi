/**
 * This is the Javascript file that drives the SolarYpsi main website.
 
 * @author Nik Estep
 * @date March 12, 2013
 */

g_currViedo = 'GoogleSearch';
g_map = null;
g_charts = {
    Daily: {
        plot: null,
        loaded: false,
        curr_date: moment (),
        min_idx: 0,
        max_idx: 4,
        options: {
            series: {
                lines: {
                    show: true,
                    steps: false
                },
                bars: {
                    show: false
                },
                hoverable: true
            },
            xaxis: {
                ticks: [],
                tickLength: 0
            },
            legend: {
                show: true,
                noColums: 1
            },
            grid: {
                borderWidth: 2,
                aboveData: true
            }
        }
    },
    Weekly: {
        plot: null,
        loaded: false,
        curr_idx: 0,
        min_idx: 0,
        max_idx: 4
    },
    Yearly: {
        plot: null,
        loaded: false,
        curr_idx: 0,
        min_idx: 0,
        max_idx: 4
    },
    Monthly: {
        plot: null,
        loaded: false,
        curr_idx: 0,
        min_idx: 0,
        max_idx: 4
    }
};

$(function () {
    // Detect if the map is in view
    if ($("#dvMap").length === 1) {
        setupMap ();
    }
    
    // Activate tabs
    $('#dvSiteTabs a').click(function (e) {
      e.preventDefault()
      $(this).tab('show')
    })
    
    // Retrieve the weather
    updateWeather ();
    setInterval (function () { updateWeather (); }, 600000);  // 10 minutes
    
    // Build the pie charts
    buildPieCharts ();
    
    // Bind DOM events
    bindEvents ();
    
    // Run chart tasks if necessary
    var meter_type = $("#spnMeterType").html ();
    if (meter_type === 'enphase') {
        loadDailyChart ();
    }
    else if (meter_type === 'historical') {
        g_charts.Yearly.curr_idx = parseInt($("#spnHistoricalEnd").html ());
        g_charts.Yearly.max_idx = g_charts.Yearly.curr_idx;
        g_charts.Yearly.min_idx = parseInt($("#spnHistoricalStart").html ());
        g_charts.Monthly.curr_idx = g_charts.Yearly.curr_idx;
        g_charts.Monthly.max_idx = g_charts.Yearly.max_idx;
        g_charts.Monthly.min_idx = g_charts.Yearly.min_idx;
    }

    // Apply fancybox
    if ($(".fancybox").length > 0) {
        $(".fancybox").fancybox ({
            openEffect    : 'none',
            closeEffect    : 'none'
        });
    }
});

/**
 * Bind events to DOM elements.
 */
function bindEvents () {
    // If we are on a regular stie page, handle clicking a button to change
    // the data view
    $("#btnPrevDaily").on ('click', function (event) {
        g_charts.Daily.curr_date.subtract ('days', 1);
        loadDailyChart ();
    });
    $("#btnNextDaily").on ('click', function (event) {
        g_charts.Daily.curr_date.add ('days', 1);
        loadDailyChart ();
    });

    // If we are on a historical site page, handle clicking a button to change
    // a chart year
    $("#btnPrevHistYearly").on ('click', function (event) {
        g_charts.Yearly.curr_idx -= 1;
        loadHistoryChart ('Yearly');
    });
    $("#btnNextHistYearly").on ('click', function (event) {
        g_charts.Yearly.curr_idx += 1;
        loadHistoryChart ('Yearly');
    });
    $("#btnPrevHistMonthly").on ('click', function (event) {
        g_charts.Monthly.curr_idx -= 1;
        loadHistoryChart ('Monthly');
    });
    $("#btnNextHistMonthly").on ('click', function (event) {
        g_charts.Monthly.curr_idx += 1;
        loadHistoryChart ('Monthly');
    });
}


function setupMap () {
    // Set variables
    L.Icon.Default.imagePath = 'http://statics.solar.ypsi.com/images';
    
    // Create the map
    g_map = L.map ('dvMap');
    
    // Build the OSM layer
    var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var osmAttrib = 'Map data © OpenStreetMap contributors';
    var osm = new L.TileLayer (osmUrl, 
                               {
                                       minZoom: 4,
                                       maxZoom: 17,
                                       attribution: osmAttrib
                               });
    
    // Set map basics
    g_map.setView (new L.LatLng (42.2404, -83.6846), 12);
    g_map.addLayer (osm);
    
    // Request the data points
    $.ajax ({
        url: 'http://statics.solar.ypsi.com/json/mappoints.json',
        dataType: 'jsonp',
        jsonpCallback: 'jsonpSYMPCallback',
        success: function (data) {
            var geo = L.geoJson (data,
                                 {
                                      onEachFeature: function (feature, layer) {
                                          var icon_clr = 'orange';
                                           if (feature.properties.meter_type !== 'none') {
                                               icon_clr = 'green';
                                           }
                                           else if (feature.properties.status === 'inactive') {
                                               icon_clr = 'blue';
                                           }
                                           
                                           var myIcon = L.icon({
                                             iconUrl: 'http://statics.solar.ypsi.com/images/marker-' + icon_clr + '-icon.png',
                                             //iconRetinaUrl: 'http://statics.solar.ypsi.com/images/marker-' + icon_clr + '-icon@2x.png',
                                             /*iconSize: [38, 95],
                                             iconAnchor: [22, 94],
                                             popupAnchor: [-3, -76],*/
                                             shadowUrl: 'http://statics.solar.ypsi.com/images/marker-shadow.png'/*,
                                             shadowSize: [68, 95],
                                             shadowAnchor: [22, 94]*/
                                         });
                                           
                                           layer.setIcon (myIcon);
                                         layer.bindPopup (feature.properties.popupContent);
                                     }
                                 }).addTo (g_map);
        },
        error: function () {
        
        }
    });
}

/**
 * Make the AJAX request to get the current weather conditions and update the
 * page.
 */
function updateWeather () {
    $.ajax ({
        url: 'http://statics.solar.ypsi.com/json/weather.json',
        data: {},
        dataType: 'jsonp',
        jsonpCallback: 'jsonpSYWCallback',
        success: function (data) {
            if (data.currTemp) {
                $("#spnWeatherTemp").html (data.currTemp + '&deg; F');
            }
            if (data.imageURL) {
                $("#imgWeatherImg").attr ('src', data.imageURL);
            }
        }
    });
}


function buildPieCharts () {
    $.ajax ({
        url: 'http://statics.solar.ypsi.com/json/piedata.json',
        dataType: 'jsonp',
        jsonpCallback: 'jsonpSYPDCallback',
        success: function (data) {
            // Build the basic chart options
            var opts = {
                //colors: ['#FD7E0D', '#333333'],
                colors: ['#FF9900', '#FFCC00', '#333333'],
                fontSize: 11,
                is3D: true,
                legend: {
                    position: 'bottom'
                },
                pieSliceText: 'label',
                pieSliceTextStyle: {
                    color: 'black'
                },
                titleTextStyle: {
                    fontSize: 14
                }
            };
            
            // Site count chart
            var count_total = data.counts.inypsi +
                              data.counts.out +
                              data.counts.inactive;
            var table = google.visualization.arrayToDataTable ([
                ['Location', 'Count'],
                ['In Ypsi', data.counts.inypsi],
                ['Out of Ypsi', data.counts.out],
                ['Inactive', data.counts.inactive]
            ]);
            var options = $.extend ({}, opts, {
                title: count_total + ' Sites'
            });
            
            if (data.counts.inactive > data.counts.inypsi &&
                data.counts.inactive > data.counts.out) {
                options.pieSliceTextStyle.color = 'white';
            }
            
            var chart = new google.visualization.PieChart (document.getElementById ('dvPieSites'));
            chart.draw (table, options);
            
            // Watt count chart
            var watt_total = data.watts.inypsi +
                             data.watts.out +
                             data.watts.inactive;
            table = google.visualization.arrayToDataTable ([
                ['Location', 'Watts'],
                ['In Ypsi', data.watts.inypsi],
                ['Out of Ypsi', data.watts.out],
                ['Inactive', data.watts.inactive]
            ]);
            options = $.extend ({}, opts, {
                title: rationalizeWatts (watt_total) + ' Potential'
            });
            
            if (data.watts.inactive > data.watts.inypsi &&
                data.watts.inactive > data.watts.out) {
                options.pieSliceTextStyle.color = 'white';
            }
            
            chart = new google.visualization.PieChart (document.getElementById ('dvPieWatts'));
            chart.draw (table, options);
        }
    });
}


function loadHistoryChart (type) {
    if (type === 'Yearly') {
        $("#imgHistYearly").attr ('src', '/repository/charts_history/' +
                                         g_site_id +
                                         '/yearly_' +
                                         g_charts.Yearly.curr_idx +
                                         '.png');
        if (g_charts.Yearly.curr_idx === g_charts.Yearly.min_idx) {
            $("#btnPrevHistYearly").addClass ('disabled');
        }
        else {
            $("#btnPrevHistYearly").removeClass ('disabled');
        }
        if (g_charts.Yearly.curr_idx === g_charts.Yearly.max_idx) {
            $("#btnNextHistYearly").addClass ('disabled');
        }
        else {
            $("#btnNextHistYearly").removeClass ('disabled');
        }
    }
    else if (type === 'Monthly') {
        $("#imgHistMonthly").attr ('src', '/repository/charts_history/' +
                                          g_site_id +
                                          '/monthly_' +
                                          g_charts.Monthly.curr_idx +
                                          '.png');
        if (g_charts.Monthly.curr_idx === g_charts.Monthly.min_idx) {
            $("#btnPrevHistMonthly").addClass ('disabled');
        }
        else {
            $("#btnPrevHistMonthly").removeClass ('disabled');
        }
        if (g_charts.Monthly.curr_idx === g_charts.Monthly.max_idx) {
            $("#btnNextHistMonthly").addClass ('disabled');
        }
        else {
            $("#btnNextHistMonthly").removeClass ('disabled');
        }
    }
}


function loadDailyChart () {
    var currdate = g_charts.Daily.curr_date;
    var startdate = currdate.format ('YYYY-MM-DD');
    var enphaseAPI = 'https://api.enphaseenergy.com/api/systems/' + $("#spnEnphaseSystemID").html () + '/stats';
    $.ajax ({
        url: '/ajax/getDailyChartData.php',
        method: 'GET',
        data: {
            siteID: g_site_id,
            date: startdate
        },
        dataType: 'json',
        success: function (data) {
            if (data.success !== undefined && data.success) {
                // Prepare the data
                var plotdata = {
                    label: 'Generation Meter',
                    color: '#F6BD0F',
                    data: []
                };
                
                // Load or update the chart
                if (!g_charts.Daily.loaded) {
                    // Load the chart
                    g_charts.Daily.options.xaxis.ticks = data.options.xaxis.ticks;
                    g_charts.Daily.options.legend.container = $("#dvDailyChartLegend");
                    $("#dvDailyChartLegend").show ();
                    g_charts.Daily.plot = $.plot ($("#dvDailyChart"), [data.data[2]], g_charts.Daily.options);
                    g_charts.Daily.loaded = true;
                }
                else {
                    g_charts.Daily.plot.setData ([data.data[2]]);
                    g_charts.Daily.plot.setupGrid ();
                    g_charts.Daily.plot.draw ();
                }
                
                // Set the title
                if (g_charts.Daily.curr_date.isSame (moment (), 'day')) {
                    $("#dailyTitle").html ('Today');
                }
                else {
                    $("#dailyTitle").html (g_charts.Daily.curr_date.format ('MMMM D, YYYY'));
                }
                
                // Set the navigation buttons
                if (g_charts.Daily.curr_date.isSame (moment ('2014-01-01', 'day'))) {
                    $("#btnPrevDaily").addClass ('disabled');
                }
                else {
                    $("#btnPrevDaily").removeClass ('disabled');
                }
                
                if (g_charts.Daily.curr_date.isSame (moment (), 'day')) {
                    $("#btnNextDaily").addClass ('disabled');
                }
                else {
                    $("#btnNextDaily").removeClass ('disabled');
                }
            }
        },
        error: function () {
            alert ('error');
        }
    });
}


function loadChartIndex (type, idx) {
    g_charts[type].curr_idx = idx;
    
    $.ajax ({
        url: '/ajax/get' + type + 'ChartData.php',
        method: 'POST',
        data: {
            siteID: g_site_id,
            chartIdx: g_charts[type].curr_idx = idx
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                if (!g_charts[type].loaded) {
                    data.options.legend.container = $("#dv" + type + "ChartLegend");
                    $("#dv" + type + "ChartLegend").show ();
                    g_charts[type].plot = $.plot ($("#dv" + type + "Chart"), data.data, data.options);
                    $("#dv" + type + "ChartControls").show ();
                    g_charts[type].loaded = true;
                }
                else {
                    g_charts[type].plot.setData (data.data);
                    g_charts[type].plot.setupGrid ();
                    g_charts[type].plot.draw ();
                }
                
                $("#dv" + type + "Title").html (data.title);
                    
                if (g_charts[type].curr_idx === g_charts[type].min_idx) {
                    $("#dv" + type + "ChartControls > .right > .container").hide ();
                }
                else {
                    $("#dv" + type + "ChartControls > .right > .container").show ();
                }
                
                if (g_charts[type].curr_idx === g_charts[type].max_idx) {
                    $("#dv" + type + "ChartControls > .left > .container").hide ();
                }
                else {
                    $("#dv" + type + "ChartControls > .left > .container").show ();
                }
            }
        },
        error: function () {
        
        }
    });
}


function rationalizeWatts (watts) {
    if (watts > 1000000) {
        return ((watts / 1000000).toFixed (2)) + ' mW';
    }
    else if (watts > 1000) {
        return ((watts / 1000).toFixed (2)) + ' kW';
    }
    else {
        return watts + 'W';
    }
}