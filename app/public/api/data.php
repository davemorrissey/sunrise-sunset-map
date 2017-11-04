<?php

include "../../../api/SunService.php";
include "../../../api/MoonService.php";
include "../../../api/MoonPhaseService.php";
include "../../../api/TimeZoneService.php";
header('Content-Type: application/json');

$utc = new DateTimeZone('UTC');

$lat = floatval($_GET['lat']);
$lon = floatval($_GET['lon']);
$timezoneId = $_GET['tz'];
$countryIso2 = $_GET['country'];
$timeZone = null;
$timeZoneDetail = null;
$timeZoneMatches = null;
$timeZoneService = new TimeZoneService();

// If valid timezone is given in query, use it without looking up the location.
if ($timezoneId) {
    try {
        $timeZone = new DateTimeZone($timezoneId);
        $timeZoneDetail = $timeZoneService->getTimeZoneById($timezoneId);
    } catch (Exception $e) { }
}
// No timezone in query, resolve using service.
if (!$timeZone) {
    $timeZone = $utc;
    $timeZoneMatches = $timeZoneService->getTimeZonesForLocation($lat, $lon, $countryIso2);
    if ($timeZoneMatches) {
        if (count($timeZoneMatches) == 1) {
            $timeZoneDetail = $timeZoneMatches[0];
            $timeZone = new DateTimeZone($timeZoneDetail->getId());
        }
    }
}

$dateUtc = new DateTime($_GET['date'], $utc);
$dateLocal = new DateTime($_GET['date'], $timeZone);

if ($lat >= -90 && $lat < -89) {
    $lat = -89;
}
if ($lat <= 90 && $lat > 89) {
    $lat = 89;
}

$sunDay = (new SunService())->calcDay($dateUtc, $lat, -$lon, $timeZone);
$moonDay = (new MoonService())->calcDay($lat, $lon, $dateLocal);

$moonPhase = new MoonPhaseService();
$phaseDouble = $moonPhase->getNoonPhase($dateLocal);
$phaseNum = substr(($phaseDouble / 10) . '0000', 2, 3);
$phaseName = $moonPhase->getNoonPhaseName($dateLocal);

$data = array();
$data['timeZone'] = $timeZoneDetail;
$data['timeZoneMatches'] = $timeZoneMatches;

$sun = array();
if ($sunDay->special != null) {
    $sun['type'] = $sunDay->special;
} else {
    if ($sunDay->sunrise) {
        $sun['rise'] = array(
            'zoneShort' => $sunDay->sunrise->format('T'),
            'zoneLong' => $sunDay->sunrise->getTimezone()->getName(),
            'time' => $sunDay->sunrise->format('H:i')
        );
    }
    if ($sunDay->sunset) {
        $sun['set'] = array(
            'zoneShort' => $sunDay->sunset->format('T'),
            'zoneLong' => $sunDay->sunset->getTimezone()->getName(),
            'time' => $sunDay->sunset->format('H:i')
        );
    }
}
$data['sun'] = $sun;

$moon = array();
$moon['phase'] = $phaseName;
$moon['image'] = $phaseNum;
if ($moonDay->special != null) {
    $moon['type'] = $moonDay->special;
} else {
    if ($moonDay->rise) {
        $moon['rise'] = array(
            'zoneShort' => $moonDay->rise->format('T'),
            'zoneLong' => $moonDay->rise->getTimezone()->getName(),
            'time' => $moonDay->rise->format('H:i')
        );
    }
    if ($moonDay->set) {
        $moon['set'] = array(
            'zoneShort' => $moonDay->set->format('T'),
            'zoneLong' => $moonDay->set->getTimezone()->getName(),
            'time' => $moonDay->set->format('H:i')
        );
    }
}
$data['moon'] = $moon;

print json_encode($data);