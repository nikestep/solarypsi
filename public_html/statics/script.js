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
        curr_date: new Date(),
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
    if ($("#spnEnphaseSystemID").length === 1) {
        loadChart ('Daily');
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
    // Change the page to the newly selected site
    $("#selSites").on ('change', function (event) {
        if ($("#selSites").val () === 'SELECT') {
            return;
        }
        else {
            window.location.href = '/installations/' + $("#selSites").val ();
        }
    });
    
    // If we are on the site page, handle clicking a button to change a chart
    // index
    $(".chart-control").on ('click', function (event) {
        // Call the load method with the proper parameters
        if ($(this).attr ('id').indexOf ('PrevYearly') > 0) {
            loadChartIndex ('Yearly', g_charts.Yearly.curr_idx + 1);
        }
        else if ($(this).attr ('id').indexOf ('NextYearly') > 0) {
            loadChartIndex ('Yearly', g_charts.Yearly.curr_idx - 1);
        }
        else if ($(this).attr ('id').indexOf ('PrevMonthly') > 0) {
            loadChartIndex ('Monthly', g_charts.Monthly.curr_idx + 1);
        }
        else if ($(this).attr ('id').indexOf ('NextMonthly') > 0) {
            loadChartIndex ('Monthly', g_charts.Monthly.curr_idx - 1);
        }
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
                $("#spnWeatherTemp").html (data.currTemp + ' F');
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
    /*var data = google.visualization.arrayToDataTable ([
    ['Task', 'Hours per Day'],
    ['Work',     11],
    ['Eat',      2],
    ['Commute',  2],
    ['Watch TV', 2],
    ['Sleep',    7]
    ]);

    var options = {
    title: 'My Daily Activities'
    };

    var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
    chart.draw(data, options);*/
}


function switchVideo (newVid) {
    if (g_currViedo == newVid) {
        return;
    }
    
    $('#li' + g_currViedo).removeClass ('vidActive');
    $('#li' + newVid).addClass ('vidActive');
    $('#vid' + g_currViedo).addClass ('hidden');
    $('#vid' + newVid).removeClass ('hidden');
    g_currViedo = newVid;
}


function loadChart (type) {
    if (type === 'Daily' && !g_charts.Daily.loaded) {
        if ($("#spnMeterType").html () === 'enphase') {
            loadDailyChart ();
        }
    }
    else if (type === 'Weekly' && !g_charts.Weekly.loaded) {
        loadChartIndex ('Weekly', 0);
    }
    else if (type === 'Yearly' && !g_charts.Yearly.loaded) {
        loadChartIndex ('Yearly', 0);
    }
    else if (type === 'Monthly' && !g_charts.Monthly.loaded) {
        loadChartIndex ('Monthly', 0);
    }
}


function loadDailyChart () {
    var currdate = g_charts['Daily'].curr_date;
    var startdate = currdate.getFullYear () + '-' + ((currdate.getMonth () + 1) < 10 ? '0' + (currdate.getMonth () + 1) : (currdate.getMonth () + 1)) + '-' + (currdate.getDate < 10 ? '0' + currdate.getDate () : currdate.getDate ()) + 'T00:00-5:00';
    var titledate = 'Data from Today';
    var enphaseAPI = 'https://api.enphaseenergy.com/api/systems/' + $("#spnEnphaseSystemID").html () + '/stats';
    $.ajax ({
        url: enphaseAPI,
        method: 'GET',
        data: {
            start: startdate,
            key: $("#spnEnphaseKey").html ()
        },
        dataType: 'jsonp',
        success: function (data) {
            if (data.system_id !== undefined) {
                // Prepare the data
                var plotdata = {
                    label: 'Generation Meter',
                    color: '#F6BD0F',
                    data: []
                };
                var hour = 0;
                var minute = 5;
                var intidx = 0;
                var plotidx = 1;
                var dt = new Date (data.intervals[intidx].end_date);
                var units = parseInt ($("#spnEphaseNumUnits").html ());
                while (hour < 24) {
                    
                    if (dt.getHours () === hour && dt.getMinutes () === minute) {
                        var enwh = data.intervals[intidx].enwh;
                        if (units !== 1) {
                            enwh = enwh * (units / data.total_devices);
                        }
                        plotdata.data.push ([plotidx, enwh]);
                        intidx += 1;
                        if (intidx < data.intervals.length) {
                            dt = new Date (data.intervals[intidx].end_date);
                        }
                    }
                    else {
                        plotdata.data.push ([plotidx, null]);
                    }
                    
                    minute += 5;
                    if (minute === 60) {
                        hour += 1;
                        minute = 0;
                    }
                    plotidx += 1;
                }
                //data.intervals = [{devices_reporting, end_date, powr, enwh}]
                // Load or update the chart
                if (!g_charts.Daily.loaded) {
                    // Load x-axis ticks
                    var hour = 0, minute = 0, idx = 1;
                    while (hour < 23 || minute < 55) {
                        if (hour === 3 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '3:00']);
                        }
                        else if (hour === 6 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '6:00']);
                        }
                        else if (hour === 9 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '9:00']);
                        }
                        else if (hour === 12 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '12:00']);
                        }
                        else if (hour === 15 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '3:00']);
                        }
                        else if (hour === 18 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '6:00']);
                        }
                        else if (hour === 21 && minute === 0) {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '9:00']);
                        }
                        else {
                            g_charts.Daily.options.xaxis.ticks.push ([idx, '']);
                        }
                        
                        idx += 1;
                        minute += 5;
                        if (minute === 60) {
                            hour += 1;
                            if (hour != 23) {
                                minute = 0;
                            }
                        }
                    }
                    // Load the chart
                    g_charts.Daily.options.legend.container = $("#dvDailyChartLegend");
                    $("#dvDailyChartLegend").show ();
                    g_charts.Daily.plot = $.plot ($("#dvDailyChart"), [plotdata], g_charts.Daily.options);
                    //$("#dvDailyChartControls").show ();
                    g_charts.Daily.loaded = true;
                }
                else {
                    g_charts.Daily.plot.setData (data.data);
                    g_charts.Daily.plot.setupGrid ();
                    g_charts.Daily.plot.draw ();
                }
                
                // Set the title
                $("#dvDailyTitle").html (titledate);
                
                // Set the navigation buttons
                /*if (g_charts.Daily.curr_idx === g_charts.Daily.min_idx) {
                    $("#dvDailyChartControls > .right > .container").hide ();
                }
                else {
                    $("#dvDailyChartControls > .right > .container").show ();
                }
                
                if (g_charts.Daily.curr_idx === g_charts.Daily.max_idx) {
                    $("#dvDailyChartControls > .left > .container").hide ();
                }
                else {
                    $("#dvDailyChartControls > .left > .container").show ();
                }*/
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