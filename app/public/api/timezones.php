<?php

include "../../../api/TimeZoneService.php";
header('Content-Type: application/json');

$timeZoneService = new TimeZoneService();
print json_encode($timeZoneService->getAllTimeZones());