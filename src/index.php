<!DOCTYPE html>
<?php
require_once '../api/TimeZoneService.php';
$date = new DateTime('Europe/London');
$thisYear = $date->format('Y');
$date->modify('+1 year');
$nextYear = $date->format('Y');
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="keywords" content="sunrise, sunset, sunrise sunset, sunrise times, sunrise time, sunrise timings, sunset times, sunset time, sunset timings, sunrise map, sunset map, sunrise sunset map, sunrise calculator, sunset calculator, moonrise, moonset, moonrise times, moonset times, moon phase, lunar phase"/>
        <meta name="description" content="Find sunrise, sunset, moonrise and moonset times for any location worldwide in <?php print $thisYear; ?> and <?php print $nextYear; ?> with one click."/>
        <meta name="robots" content="index,follow"/>
        <title>Worldwide sunrise, sunset, moonrise and moonset times for <?php print $thisYear; ?> and <?php print $nextYear; ?></title>
        <link rel="shortcut icon" href="/favicon.ico" type="image/ico" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato|Roboto+Slab">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/css.css" type="text/css"/>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php print getenv('API_KEY'); ?>&v=3.26"></script>
        <script type="text/javascript" src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
        <script src="js/datepicker.js" type="text/javascript"></script>
        <script type="text/javascript">
            <?php
            $timeZoneService = new TimeZoneService();
            print 'var timeZones = '.json_encode($timeZoneService->getAllTimeZones());
            ?>
        </script>
    </head>
    <body onload="loadMap()">
        <div id="map-container">
            <div id="map-controls">
                <a onclick="mapZoom(-1, this)" id="zoom-out"><i class="fa fa-minus"></i></a>
                <a onclick="mapZoom(1, this)" id="zoom-in"><i class="fa fa-plus"></i></a>
                <a onclick="typeSelect()" id="type-select"><span id="type-select-value">Normal</span><i class="fa fa-caret-down"></i></a>
                <div id="type-selection">
                    <ul>
                        <li><a onclick="mapType('normal', this)" id="type-normal">Normal</a></li>
                        <li><a onclick="mapType('satellite', this)" id="type-satellite">Satellite</a></li>
                        <li><a onclick="mapType('hybrid', this)" id="type-hybrid">Hybrid</a></li>
                        <li><a onclick="mapType('terrain', this)" id="type-terrain">Terrain</a></li>
                    </ul>
                </div>
            </div>
            <div id="map"></div>
        </div>
        <div id="info-container">
            <div id="welcome">
                <h1>Welcome</h1>
                <p id="topintro">
                    To start, browse the map and click your location.
                </p>
                <p id="subintro">
                    With this map, you can find sunrise, sunset, moonrise and moonset times for <?php print $thisYear; ?>,
                    <?php print $nextYear; ?> and beyond for any location worldwide, simply by clicking the map.
                </p>
                <div id="searchoptions">
                    <div id="opt-mylocation">
                        <a id="opt-mylocation-button">
                            <i id="opt-mylocation-loading" class="fa fa-spin fa-spinner"></i>
                            <span id="opt-mylocation-label"><i class="fa fa-map-marker"></i> My Location</i></span>
                            <span id="opt-mylocation-error"></span>
                        </a>
                    </div>
                    <a id="opt-majorcities" onclick="toggleCitySelection()">
                        Major Cities
                    </a>
                    <div id="opt-flags">
                        <a onclick="goTo('gb')"><img alt="Great Britain" src="img/flag/GB.png"/></a>
                        <a onclick="goTo('us')"><img alt="United States" src="img/flag/US.png"/></a>
                        <a onclick="goTo('au')"><img alt="Australia" src="img/flag/AU.png"/></a>
                        <a onclick="goTo('nz')"><img alt="New Zealand" src="img/flag/NZ.png"/></a>
                        <a onclick="goTo('fr')"><img alt="France" src="img/flag/FR.png"/></a>
                        <a onclick="goTo('de')"><img alt="Germany" src="img/flag/DE.png"/></a>
                        <a onclick="goTo('es')"><img alt="Spain" src="img/flag/ES.png"/></a>
                        <a onclick="goTo('it')"><img alt="Italy" src="img/flag/IT.png"/></a>
                        <a onclick="goTo('mx')"><img alt="Mexico" src="img/flag/MX.png"/></a>
                        <a onclick="goTo('br')"><img alt="Brazil" src="img/flag/BR.png"/></a>
                    </div>
                </div>
                <div id="credit">
                    &copy;2005-<?php print $thisYear; ?> <a href="http://www.davemorrissey.com/" target="_blank">David Morrissey</a>
                    <br/>
                    <a href="https://github.com/davemorrissey/sunrise-sunset-map" target="_blank"><i class="fa fa-github"></i> View source on GitHub</a>
                </div>
            </div>

            <div id="cityselection">
                <h1>Major cities <a onclick="toggleCitySelection()"><i class="fa fa-times"></i></a></h1>
                <div id="citylistwrapper">
                    <ol>
                        <?php include "cities.php"; ?>
                    </ol>
                </div>
            </div>
            <div id="tzselection">
                <h1>Time zone selection <a onclick="hideTimeZoneSelection()"><i class="fa fa-times"></i></a></h1>
                <div id="tzlistswrapper">
                    <div id="tzmatchcontainer" style="display: none;">
                        <h2>Best matches</h2>
                        <div id="tzmatchlist"></div>
                        <h2>All time zones</h2>
                    </div>
                    <div id="tzalllist"></div>
                </div>
            </div>
            <div id="data">
                <h1 id="data-h1"></h1>
                <h2 id="data-h2"></h2>
                <div id="tzwarning" onclick="showTimeZoneSelection();">
                    <i class="fa fa-globe"></i> Set time zone
                </div>
                <div id="datenav">
                    <div id="date-current"><span id="date-current-value"></span><a id="date-picker-toggle" onclick="toggleDatePicker()"><i class="fa fa-calendar"></i></a></div>
                    <div id="date-buttons"><button class="back" onclick="changeDate('-m', this)"><i class="fa fa-caret-left"></i> M</button><button class="back" onclick="changeDate('-w', this)"><i class="fa fa-caret-left"></i> W</button><button class="back" onclick="changeDate('-d', this)"><i class="fa fa-caret-left"></i> D</button><button class="forward" onclick="changeDate('+d', this)">D <i class="fa fa-caret-right"></i></button><button class="forward" onclick="changeDate('+w', this)">W <i class="fa fa-caret-right"></i></button><button class="forward" onclick="changeDate('+m', this)">M <i class="fa fa-caret-right"></i></button></div>
                    <div id="datepicker-wrapper">
                        <div id="datepicker"></div>
                    </div>
                </div>
                <div id="sun">
                    <img src="img/sun.png" alt="Sun" id="sunicon"/>
                    <div id="sunriseset">
                        <div id="sunrise"><span id="sunriselabel">Rise</span> <span id="sunrisevalue"></span>&nbsp;<span id="sunrisetz"></span></div>
                        <div id="sunset"><span id="sunsetlabel">Set</span> <span id="sunsetvalue"></span>&nbsp;<span id="sunsettz"></span></div>
                    </div>
                    <div id="sunspecial"></div>
                    <div id="export">
                        <a href="" class="pdf" id="exportyear"></a>
                    </div>
                </div>
                <span class="clear"><!--  --></span>
                <div id="moon">
                    <img src="img/moon/moon-050.png" alt="Moon" id="moonimggraphic"/>
                    <div id="moonriseset">
                        <div id="moonrise"><span id="moonriselabel">Rise</span> <span id="moonrisevalue"></span>&nbsp;<span id="moonrisetz"></span></div>
                        <div id="moonset"><span id="moonsetlabel">Set</span> <span id="moonsetvalue"></span>&nbsp;<span id="moonsettz"></span></div>
                    </div>
                    <div id="moonspecial"></div>
                    <div id="moonphase"><span id="moonphaselabel">Phase</span> <span id="moonphasevalue"></span></div>
                </div>
                <span class="clear"><!--  --></span>
                <div id="zone">
                    <a id="zone-link" onclick="showTimeZoneSelection()"><i class="fa fa-globe"></i></a>
                    <span id="zone-header"><span id="zone-offset"></span></span>
                    <span id="zone-name"></span>
                </div>
            </div>
            <div id="loading">
                <i class="fa fa-spinner fa-spin"></i>
                <br/>
                Loading...
            </div>
        </div>
        <span class="clear"><!-- --></span>
    </body>
    <script src="js/map.js" type="text/javascript"></script>
</html>