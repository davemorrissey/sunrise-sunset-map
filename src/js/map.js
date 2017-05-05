/* global $, google */
var map;
var selectedDate = new Date();
var monthArrayLong = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
var dayArrayMed = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
var datepickerCalendar = false;
var selectedLocation = null;

var tzSelection = $('#tzselection');
var citySelection = $('#cityselection');
var datePicker;

function loadMap() {
    var mapOptions = {
            center: new google.maps.LatLng(54.5, -3.5),
            zoom: 5,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: true
    };
    map = new google.maps.Map($('#map')[0], mapOptions);

    $('#date-current-value').text(dateLong(selectedDate));

    datePicker = new DatePicker($('#datepicker'), function(date) {
        selectedDate = date;
        if (selectedLocation) {
            $('#date-current-value').text(dateLong(selectedDate));
            clearDateData();
            loadData(selectedLocation, selectedDate);
        }
    });
    
    populateTimeZoneList();
    
    google.maps.event.addListener(map, 'click', function(event) {
        if (event.latLng) {
            if (tzSelection.hasClass('slide-in')) {
                tzSelection.addClass('slide-out');
                tzSelection.removeClass('slide-in');
            }
            $('#tzwarning').removeClass('active');
            setSelectedLocation({ point: event.latLng });
        }
    });

    if (navigator.geolocation) {
        $('#opt-mylocation-button').on('click', function() {
            $('#opt-mylocation-button').addClass('loading');
            $('#opt-mylocation-button').removeClass('error');
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#opt-mylocation-button').removeClass('loading');
                if (position && position.coords && position.coords.latitude && position.coords.longitude) {
                    var point = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    map.setCenter(point);
                    map.setZoom(9);
                    setSelectedLocation({ point: point });
                } else {
                    $('#opt-mylocation-button').addClass('error');
                    $('#opt-mylocation-error').text('Location Unavailable');
                }
            }, function(error) {
                $('#opt-mylocation-button').removeClass('loading');
                $('#opt-mylocation-button').addClass('error');
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        $('#opt-mylocation-error').text('Permission Denied');
                        break;
                    default:
                        $('#opt-mylocation-error').text('Location Unavailable');
                        break;
                }
            })
        });
    } else {
        $('#opt-mylocation').hide();
    }
    
}

function changeDate(diff, element) {
    switch (diff) {
        case '-m':
            selectedDate.setMonth(selectedDate.getMonth() - 1);
            break;
        case '+m':
            selectedDate.setMonth(selectedDate.getMonth() + 1);
            break;
        case '-w':
            selectedDate.setDate(selectedDate.getDate() - 7);
            break;
        case '+w':
            selectedDate.setDate(selectedDate.getDate() + 7);
            break;
        case '-d':
            selectedDate.setDate(selectedDate.getDate() - 1);
            break;
        case '+d':
            selectedDate.setDate(selectedDate.getDate() + 1);
            break;
    }
    element.blur();
    $('#date-current-value').text(dateLong(selectedDate));
    datePicker.updateDate(selectedDate);
    clearDateData();
    loadData(selectedLocation, selectedDate);
    return false;
}

function mapZoom(diff, element) {
    if (map) {
        map.setZoom(map.getZoom() + diff);
    }
    element.blur();
    return false;
}
function mapType(type, element) {
    var types = {
        normal: { name: 'Normal', typeId: google.maps.MapTypeId.ROADMAP },
        satellite: { name: 'Satellite', typeId: google.maps.MapTypeId.SATELLITE },
        hybrid: { name: 'Hybrid', typeId: google.maps.MapTypeId.HYBRID },
        terrain: { name: 'Terrain', typeId: google.maps.MapTypeId.TERRAIN }
    };
    if (map && types[type]) {
        $('#type-select-value').text(types[type].name);
        map.setMapTypeId(types[type].typeId);
    }
    typeSelect();
    element.blur();
    return false;
}

function typeSelect() {
    var typeSelection = $('#type-selection');
    if (typeSelection.hasClass('active')) {
        typeSelection.removeClass('active');
    } else {
        typeSelection.addClass('active');
    }
}

var geocoder = new google.maps.Geocoder();

var icon = new google.maps.MarkerImage('http://www.sunrisesunsetmap.com/img/pushpin.png',
    new google.maps.Size(32, 32),
    new google.maps.Point(0,0),
    new google.maps.Point(9, 32));
var shadow = new google.maps.MarkerImage('http://www.sunrisesunsetmap.com/img/pushpin_shadow.png',
    new google.maps.Size(59, 32),
    new google.maps.Point(0,0),
    new google.maps.Point(9, 32));
var marker = new google.maps.Marker({
    icon: icon,
    shadow: shadow
});

function setSelectedLocation(newLocation) {
    clearAllData();
    selectedLocation = newLocation;
    marker.setPosition(selectedLocation.point);
    marker.setMap(map);
    
    var loc = selectedLocation;
    geocoder.geocode({'latLng': selectedLocation.point}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            var locationDetails = extractLocationDetails(results);
            if (locationDetails.countryIso2) {
                loc.countryIso2 = locationDetails.countryIso2;
            }
            if (!loc.name) {
                loc.name = locationDetails.name;
            }
        }
        loadData(selectedLocation, selectedDate);
    });
}

function extractLocationDetails(results) {
    var countryIso2 = null, admin1 = null, admin2 = null, city = null, town = null;
    results.forEach(function(address) {
        address.address_components.forEach(function(address_component) {
            address_component.types.forEach(function(type) {
                switch(type) {
                    case 'administrative_area_level_2':
                        admin2 = address_component.long_name;
                        break;
                    case 'administrative_area_level_1':
                        admin1 = address_component.long_name;
                        break;
                    case 'locality':
                        city = address_component.long_name;
                        break;
                    case 'postal_town':
                        town = address_component.long_name;
                        break;
                    case 'country':
                        countryIso2 = address_component.short_name;
                        break;
                }
            })
        });
    });
    return { countryIso2: countryIso2, name: town || city || admin2 || admin1 };
}

function loadData(thisLocation, thisDate) {
    thisDate = new Date(thisDate.getFullYear(), thisDate.getMonth(), thisDate.getDate());
    $.get('/api.php', {
        lat: thisLocation.point.lat(),
        lon: thisLocation.point.lng(),
        date: dateDmy(selectedDate),
        tz: thisLocation.timeZone ? thisLocation.timeZone.id : null,
        country: thisLocation.countryIso2 }, function(data) {
        if (thisLocation != selectedLocation) {
            console.log('location changed while loading');
            return;
        }
        if (dateDmy(thisDate) != dateDmy(selectedDate)) {
            console.log('date changed while loading');
            return;
        }
        if (data.timeZoneMatches) {
            thisLocation.timeZoneMatches = data.timeZoneMatches;
        }
        if (data.timeZone && !thisLocation.timeZone) {
            thisLocation.timeZone = data.timeZone;
        } else if (!thisLocation.timeZone || (thisLocation.timeZoneMatches && thisLocation.timeZoneMatches.length > 1)) {
            showTimeZoneWarning();
        }
        if (thisLocation.timeZone) {
            $('#zone-offset').text(data.timeZone.offset);
            $('#zone-name').text(data.timeZone.name);
        } else {
            $('#zone-offset').text('UTC');
            $('#zone-name').text('Coordinated Universal Time');
        }

        if (thisLocation.name) {
            $('#data-h2').show();
            $('#data-h1').text(thisLocation.name);
            $('#data-h2').text(displayLatLon(thisLocation.point.lat(), thisLocation.point.lng()));
        } else {
            $('#data-h2').hide();
            $('#data-h1').text(displayLatLon(thisLocation.point.lat(), thisLocation.point.lng()));
        }

        $('#sunriseset').hide();
        $('#sunrise').hide();
        $('#sunset').hide();
        $('#sunspecial').hide();
        if (data.sun.type == 'RISEN') {
            $('#sunspecial').show();
            $('#sunspecial').text('Risen all day');
        } else if (data.sun.type == 'SET') {
            $('#sunspecial').show();
            $('#sunspecial').text('Set all day');
        } else {
            $('#sunriseset').show();
            $('#sunrise').show();
            $('#sunset').show();

            if (data.sun.rise) {
                $('#sunrisevalue').text(data.sun.rise.time);
                $('#sunrisetz').text(data.sun.rise.zoneShort);
            } else {
                $('#sunrisevalue').text('None');
                $('#sunrisetz').text('');
            }
            if (data.sun.set) {
                $('#sunsetvalue').text(data.sun.set.time);
                $('#sunsettz').text(data.sun.set.zoneShort);
            } else {
                $('#sunrisevalue').text('None');
                $('#sunrisetz').text('');
            }
        }
        $('#moonriseset').hide();
        $('#moonrise').hide();
        $('#moonset').hide();
        $('#moonspecial').hide();
        if (data.moon.type == 'RISEN') {
            $('#moonspecial').show();
            $('#moonspecial').text('Risen all day');
        } else if (data.moon.type == 'SET') {
            $('#moonspecial').show();
            $('#moonspecial').text('Set all day');
        } else {
            $('#moonriseset').show();
            $('#moonrise').show();
            $('#moonset').show();

            if (data.moon.rise) {
                $('#moonrisevalue').text(data.moon.rise.time);
                $('#moonrisetz').text(data.moon.rise.zoneShort);
            } else {
                $('#moonrisevalue').text('None');
                $('#moonrisetz').text('');
            }
            if (data.moon.set) {
                $('#moonsetvalue').text(data.moon.set.time);
                $('#moonsettz').text(data.moon.set.zoneShort);
            } else {
                $('#moonsetvalue').text('None');
                $('#moonsettz').text('');
            }
        }
        $('#moonphasevalue').text(data.moon.phase);
        $('#moonimggraphic').attr('src', 'img/moon/moon-' + data.moon.image + '.png');

        var yearLink = '/year.php?lat=' + thisLocation.point.lat() +
            '&lon=' + thisLocation.point.lng() +
            '&tz=' + (thisLocation.timeZone ? thisLocation.timeZone.id : 'UTC') +
            '&year=' + thisDate.getFullYear() +
            '&name=' + encodeURI(thisLocation.name);
        $('#exportyear').attr('href', yearLink);
        $('#exportyear').attr('target', '_blank');
        $('#exportyear').html('<i class="fa fa-cloud-download"></i>&nbsp; ' + thisDate.getFullYear() + ' calendar (PDF)');

        $('#data').removeClass('loading');
        $('#loading').hide();
        $('#data').show();
    }).fail(function() {
        console.log('load error');
    });

}

function clearDateData() {
    $('#data').addClass('loading');
}

function clearAllData() {
    clearDateData();
    $('#data').hide();
    $('#welcome').hide();
    $('#data-h2').hide();
    $('#loading').show();
}
        
function toggleDatePicker() {
    if (datepickerCalendar) {
        datepickerCalendar = false;
        $('#datepicker-wrapper').hide();
        $('#date-buttons').show();
    } else {
        datepickerCalendar = true;
        $('#datepicker-wrapper').show();
        $('#date-buttons').hide();
    }
}

function setManualTimeZone(timeZone) {
    hideTimeZoneWarning();
    tzSelection.removeClass('slide-in');
    tzSelection.addClass('slide-out');
    if (selectedLocation) {
        selectedLocation.timeZone = timeZone;
        clearDateData();
        loadData(selectedLocation, selectedDate);
    }
}

function showTimeZoneWarning() {
    $('#tzwarning').addClass('active');
}

function hideTimeZoneWarning() {
    $('#tzwarning').removeClass('active');
}

function showTimeZoneSelection() {
    $('#tzmatchcontainer').hide();
    var node = $('#tzmatchlist');
    node.empty();

    if (selectedLocation && selectedLocation.timeZoneMatches) {
        $('#tzmatchcontainer').show();
        var tzList = $('#tzmatchlist');
        selectedLocation.timeZoneMatches.forEach(function(timeZone) {
            var a = $('<a></a>');
            a.on('click', function() { setManualTimeZone(timeZone); });
            a.text(' ' + timeZone.name);
            var offset = $('<span></span>');
            offset.text(timeZone.offset);
            a.prepend(offset);
            tzList.append(a);
        });
    }

    tzSelection.removeClass('slide-out');
    tzSelection.addClass('slide-in');
    $('#tzlistswrapper').scrollTop(0);
}

function hideTimeZoneSelection() {
    tzSelection.addClass('slide-out');
    tzSelection.removeClass('slide-in');
}

function populateTimeZoneList() {
    var tzList = $('#tzalllist');
    timeZones.forEach(function(timeZone) {
        var a = $('<a></a>');
        a.on('click', function() { setManualTimeZone(timeZone); });
        a.text(' ' + timeZone.name);
        var offset = $('<span></span>');
        offset.text(timeZone.offset);
        a.prepend(offset);
        tzList.append(a);
    });
}

function goTo(code) {
    var zoom = 0;
    if ($('#map').width() > 700) {
        zoom = 1;
    }
    if (code == 'gb') { map.setCenter(new google.maps.LatLng(54.5, -3.5)); map.setZoom(5 + zoom); }
    if (code == 'us') { map.setCenter(new google.maps.LatLng(39.6, -97.4)); map.setZoom(3 + zoom); }
    if (code == 'au') { map.setCenter(new google.maps.LatLng(-27.8, 133)); map.setZoom(4 + zoom); }
    if (code == 'nz') { map.setCenter(new google.maps.LatLng(-42, 172)); map.setZoom(5 + zoom); }
    if (code == 'fr') { map.setCenter(new google.maps.LatLng(47, 2.6)); map.setZoom(5 + zoom); }
    if (code == 'es') { map.setCenter(new google.maps.LatLng(40.1, -3.2)); map.setZoom(6 + zoom); }
    if (code == 'de') { map.setCenter(new google.maps.LatLng(51.3, 9.9)); map.setZoom(6 + zoom); }
    if (code == 'it') { map.setCenter(new google.maps.LatLng(42.3, 12.6)); map.setZoom(6 + zoom); }
    if (code == 'mx') { map.setCenter(new google.maps.LatLng(23.9, -102.4)); map.setZoom(4 + zoom); }
    if (code == 'br') { map.setCenter(new google.maps.LatLng(-14.3, -56.6)); map.setZoom(4 + zoom); }
}

var expandedCountryCode = null;

function toggleCitySelection() {
    if (citySelection.hasClass('slide-in')) {
        citySelection.removeClass('slide-in');
        citySelection.addClass('slide-out');
    } else {
        citySelection.removeClass('slide-out');
        citySelection.addClass('slide-in');
    }
}

function toggleCountry(countryCode) {
    if (expandedCountryCode) {
        $('#cities-' + expandedCountryCode).hide();
        $('#country-' + expandedCountryCode).addClass('inactive');
    }
    if (expandedCountryCode != countryCode) {
        $('#cities-' + countryCode).show();
        $('#country-' + countryCode).addClass('active');
        expandedCountryCode = countryCode;
    } else {
        expandedCountryCode = null;
    }
}

function showCity(name, lat, lon) {
    toggleCitySelection();
    var point = new google.maps.LatLng(lat, lon);
    map.setCenter(point);
    map.setZoom(9);
    setSelectedLocation({ point: point, name: name });
}

function displayLatLon(lat, lon) {
    var latDbl = Math.abs(lat);
    var latStr = zeroPad(parseInt(latDbl), 2) + '\xB0';
    latDbl = (latDbl - parseInt(latDbl)) * 60;
    latStr += zeroPad(parseInt(latDbl), 2) + "'";
    
    if (lat > 0) {
        latStr += "N";
    } else {
        latStr += "S";
    }
    
    var lngDbl = Math.abs(lon);
    var lngStr = zeroPad(parseInt(lngDbl), 3) + '\xB0';
    lngDbl = (lngDbl - parseInt(lngDbl)) * 60;
    lngStr += zeroPad(parseInt(lngDbl), 2) + "'";
    
    if (lon > 0) {
        lngStr += "E";
    } else {
        lngStr += "W";
    }
    
    return latStr + " " + lngStr;

}

function dateLong(date) {
    return dayArrayMed[date.getDay()] + ' ' + date.getDate() + ' ' + monthArrayLong[date.getMonth()] + ' ' + date.getFullYear();
}

function dateDmy(date) {
    return date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
}

// Helper method for location display, pads a string with leading zeros.
function zeroPad(num, length) {
    num = "000" + num;
    return num.substring(num.length - length);
}