<?php
// Public domain. Translated and adapted from unknown source.

class MoonPosition
{

    public $azimuth = 0;

    public $elevation = 0;

}

class MoonDay
{

    /**
     * @var DateTime
     */
    public $transit = null;

    /**
     * @var DateTime
     */
    public $rise = null;

    /**
     * @var DateTime
     */
    public $set = null;

    /**
     * @var string
     */
    public $special = null;

}

class MoonService
{

    /**
     * @param $lat float
     * @param $lon float
     * @param $dateMidnight DateTime
     * @return MoonDay
     */
    public function calcDay($lat, $lon, $dateMidnight)
    {
        $moonDay = new MoonDay();
        $hours = 24;
        $hourEls = array();
        $hourAzs = array();
        $radiusCorrection = 0.5;
        for ($hour = 0; $hour <= $hours; $hour++) {
            $calendar = clone $dateMidnight;
            $calendar->modify($hour . " hours");
            $hourPosition = $this->calcPositionDateTime($lat, $lon, $calendar);
            $hourEls[$hour] = $hourPosition->elevation;
            $hourAzs[$hour] = $hourPosition->azimuth;

            if ($hour > 0 && $this->sign($hourEls[$hour], $radiusCorrection) != $this->sign($hourEls[$hour - 1], $radiusCorrection)) {

                $diff = $hourEls[$hour] - $hourEls[$hour - 1];
                $minuteGuess = round(60 * abs($hourEls[$hour - 1] / $diff));

                $calendar->modify("-1 hour");
                $calendar->modify("-" . $calendar->format('i') . " minutes");
                $calendar->modify("+" . $minuteGuess . " minutes");

                $initPosition = $this->calcPositionDateTime($lat, $lon, $calendar);
                $initEl = $initPosition->elevation;

                $direction = $this->sign($initEl, $radiusCorrection) == $this->sign($hourEls[$hour - 1], $radiusCorrection) ? 1 : -1;

                $safety = 0;
                while ($safety < 60) {
                    $add = "+1 minute";
                    if ($direction < 0) {
                        $add = "-1 minute";
                    }
                    $calendar->modify($add);

                    $thisPosition = $this->calcPositionDateTime($lat, $lon, $calendar);
                    $thisEl = $thisPosition->elevation;

                    if ($this->sign($thisEl, $radiusCorrection) != $this->sign($initEl, $radiusCorrection)) {
                        if (abs($thisEl + $radiusCorrection) > abs($initEl + $radiusCorrection)) {
                            // Previous time was closer. Use previous iteration's values.
                            $add = "-1 minute";
                            if ($direction < 0) {
                                $add = "+1 minute";
                            }
                            $calendar->modify($add);
                        }
                        if ($this->sign($hourEls[$hour - 1], $radiusCorrection) < 0) {
                            if ($hour <= 24 && $calendar->format('z') == $dateMidnight->format('z')) {
                                $moonDay->rise = clone $calendar;
                            }
                        } else {
                            if ($hour <= 24 && $calendar->format('z') == $dateMidnight->format('z')) {
                                $moonDay->set = clone $calendar;
                            }
                        }
                        break;
                    }

                    // Set for next minute.
                    $initEl = $thisEl;
                    $safety++;
                }

            }

            if ($moonDay->rise != null && $moonDay->set != null) {
                break;
            } else if ($moonDay->rise == null && $hour == 24) {
                break;
            }
        }

        if ($moonDay->rise == null && $moonDay->set == null) {
            if ($hourEls[12] > 0) {
                $moonDay->special = "RISEN";
            } else {
                $moonDay->special = "SET";
            }
        }

        return $moonDay;
    }

    private function sector($azimuth)
    {
        if ($azimuth >= 0 && $azimuth < 180) {
            return 1;
        } else {
            return 2;
        }
    }

    /**
     * @param $lat float
     * @param $lon float
     * @param $dateTime DateTime
     * @return MoonPosition
     */
    private function calcPositionDateTime($lat, $lon, $dateTime)
    {
        $dateTimeUtc = clone $dateTime;
        $dateTimeUtc->setTimezone(new DateTimeZone('UTC'));
        return $this->calcMoonPosition($lat, $lon, $dateTimeUtc);
    }

    private function calcMoonPosition($lat, $lon, $dateTime)
    {
        $d = $this->dayNumber($dateTime);
        $obliquityOfEliptic = $this->obliquityOfEcliptic($this->julianCent($dateTime));
        $N = $this->norm360(125.1228 - 0.0529538083 * $d); // (Long asc. node)
        $i = $this->norm360(5.1454); // (Inclination)
        $w = $this->norm360(318.0634 + 0.1643573223 * $d); // (Arg. of perigee)
        $a = 60.2666; // (Mean distance)
        $e = 0.054900; // (Eccentricity)
        $M = $this->norm360(115.3654 + 13.0649929509 * $d); // (Mean anomaly)
        $E0 = $M + (180 / M_PI) * $e * sin($this->toRadians($M)) * (1 + $e * cos($this->toRadians($M)));
        $E1 = 10e10;
        $loopCount = 0;
        while (abs($E1 - $E0) > 0.005 && $loopCount < 10) {
            $E1 = $E0 - ($E0 - (180 / M_PI) * $e * sin($this->toRadians($E0)) - $M) / (1 - $e * cos($this->toRadians($E0)));
            $loopCount++;
        }
        $planarX = $a * (cos($this->toRadians($E1)) - $e);
        $planarY = $a * sqrt(1 - $e * $e) * sin($this->toRadians($E1));
        $geoR = sqrt($planarX * $planarX + $planarY * $planarY); // (Earth radii)
        $trueAnomaly = $this->norm360($this->toDegrees(atan2($this->toRadians($planarY), $this->toRadians($planarX)))); // (Degrees)
        $geoRectEclip = array(
            $geoR * (cos($this->toRadians($N)) * cos($this->toRadians($trueAnomaly + $w)) - sin($this->toRadians($N)) * sin($this->toRadians($trueAnomaly + $w)) * cos($this->toRadians($i))),
            $geoR * (sin($this->toRadians($N)) * cos($this->toRadians($trueAnomaly + $w)) + cos($this->toRadians($N)) * sin($this->toRadians($trueAnomaly + $w)) * cos($this->toRadians($i))),
            $geoR * sin($this->toRadians($trueAnomaly + $w)) * sin($this->toRadians($i))
        );
        $geoRLonLatEclip = $this->rectangularToSpherical($geoRectEclip);
        $ws = $this->norm360(282.9404 + 4.70935E-5 * $d); // (longitude of perihelion)
        $Ms = $this->norm360(356.0470 + 0.9856002585 * $d); // (mean anomaly)
        $Ls = $this->norm360($ws + $Ms);
        $Lm = $this->norm360($N + $w + $M);
        $Mm = $M;
        $D = $this->norm360($Lm - $Ls);
        $F = $this->norm360($Lm - $N);
        $geoRLonLatEclip[1] = $geoRLonLatEclip[1]
            - 1.274 * sin($this->toRadians($Mm - 2 * $D))
            + 0.658 * sin($this->toRadians(2 * $D))
            - 0.186 * sin($this->toRadians($Ms))
            - 0.059 * sin($this->toRadians(2 * $Mm - 2 * $D))
            - 0.057 * sin($this->toRadians($Mm - 2 * $D + $Ms))
            + 0.053 * sin($this->toRadians($Mm + 2 * $D))
            + 0.046 * sin($this->toRadians(2 * $D - $Ms))
            + 0.041 * sin($this->toRadians($Mm - $Ms))
            - 0.035 * sin($this->toRadians($D))
            - 0.031 * sin($this->toRadians($Mm + $Ms))
            - 0.015 * sin($this->toRadians(2 * $F - 2 * $D))
            + 0.011 * sin($this->toRadians($Mm - 4 * $D));
        $geoRLonLatEclip[2] = $geoRLonLatEclip[2]
            - 0.173 * sin($this->toRadians($F - 2 * $D))
            - 0.055 * sin($this->toRadians($Mm - $F - 2 * $D))
            - 0.046 * sin($this->toRadians($Mm + $F - 2 * $D))
            + 0.033 * sin($this->toRadians($F + 2 * $D))
            + 0.017 * sin($this->toRadians(2 * $Mm + $F));
        $geoRLonLatEclip[0] = $geoRLonLatEclip[0]
            - 0.58 * cos($this->toRadians($Mm - 2 * $D))
            - 0.46 * cos($this->toRadians(2 * $D));
        $geoRectEclip = $this->sphericalToRectangular($geoRLonLatEclip);
        $geoRectEquat = $this->eclipticToEquatorial($geoRectEclip, $obliquityOfEliptic);
        $geoRRADec = $this->rectangularToSpherical($geoRectEquat);
        $topoRRADec = $this->geoToTopo($geoRRADec, $lat, $lon, $dateTime);
        $topoAzEl = $this->raDecToAzEl($topoRRADec, $lat, $lon, $dateTime);
        $position = new MoonPosition();
        $position->azimuth = $topoAzEl[0];
        $position->elevation = $this->refractionCorrection($topoAzEl[1]);
        return $position;
    }

    private function refractionCorrection($elevation)
    {
        $exoatmElevation = $elevation;
        if ($exoatmElevation > 85.0) {
            $refractionCorrection = 0.0;
        } else {
            $te = tan($this->toRadians($exoatmElevation));
            if ($exoatmElevation > 5.0) {
                $refractionCorrection = 58.1 / $te - 0.07 / ($te * $te * $te) +
                    0.000086 / ($te * $te * $te * $te * $te);
            } else if ($exoatmElevation > -0.575) {
                $refractionCorrection = 1735.0 + $exoatmElevation *
                    (-518.2 + $exoatmElevation * (103.4 +
                            $exoatmElevation * (-12.79 +
                                $exoatmElevation * 0.711)));
            } else {
                $refractionCorrection = -20.774 / $te;
            }
            $refractionCorrection = $refractionCorrection / 3600.0;
        }
        return $elevation + $refractionCorrection;

    }

    /**
     * @param $dateTime DateTime
     * @return int
     */
    private function dayNumber($dateTime)
    {
        $year = $dateTime->format('Y');
        $month = $dateTime->format('m');
        $day = $dateTime->format('d');
        $fraction = ($dateTime->format('H') / 24) + ($dateTime->format('i') / (60 * 24)) + ($dateTime->format('s') / (60 * 60 * 24));
        return 367 * $year - floor((7 * ($year + (floor(($month + 9) / 12)))) / 4) + floor((275 * $month) / 9) + $day - 730530 + $fraction;
    }

    private function julianDay($dateTime)
    {
        return $this->dayNumber($dateTime) + 2451543.5;
    }

    private function julianCent($dateTime)
    {
        $jd = $this->julianDay($dateTime);
        $T = ($jd - 2451545.0) / 36525.0;
        return $T;
    }

    private function rectangularToSpherical($xyz)
    {
        $r = sqrt($xyz[0] * $xyz[0] + $xyz[1] * $xyz[1] + $xyz[2] * $xyz[2]);
        $lon = $this->norm360($this->toDegrees(atan2($this->toRadians($xyz[1]), $this->toRadians($xyz[0]))));
        $lat = $this->toDegrees(atan2($this->toRadians($xyz[2]), $this->toRadians(sqrt($xyz[0] * $xyz[0] + $xyz[1] * $xyz[1]))));
        return array($r, $lon, $lat);
    }

    private function sphericalToRectangular($rLonLat)
    {
        $x = $rLonLat[0] * cos($this->toRadians($rLonLat[1])) * cos($this->toRadians($rLonLat[2]));
        $y = $rLonLat[0] * sin($this->toRadians($rLonLat[1])) * cos($this->toRadians($rLonLat[2]));
        $z = $rLonLat[0] * sin($this->toRadians($rLonLat[2]));
        return array($x, $y, $z);
    }

    private function eclipticToEquatorial($xyzEclip, $o)
    {
        $xEquat = $xyzEclip[0];
        $yEquat = $xyzEclip[1] * cos($this->toRadians($o)) - $xyzEclip[2] * sin($this->toRadians($o));
        $zEquat = $xyzEclip[1] * sin($this->toRadians($o)) + $xyzEclip[2] * cos($this->toRadians($o));
        return array($xEquat, $yEquat, $zEquat);
    }

    private function raDecToAzEl($rRaDecl, $lat, $lon, $dateTime)
    {
        $LSTh = $this->localSiderealTimeHours($lon, $dateTime);
        $RAh = $rRaDecl[1] / 15.0;
        $HAd = 15 * $this->norm24($LSTh - $RAh);
        $x = cos($this->toRadians($HAd)) * cos($this->toRadians($rRaDecl[2]));
        $y = sin($this->toRadians($HAd)) * cos($this->toRadians($rRaDecl[2]));
        $z = sin($this->toRadians($rRaDecl[2]));
        $xhor = $x * sin($this->toRadians($lat)) - $z * cos($this->toRadians($lat));
        $yhor = $y;
        $zhor = $x * cos($this->toRadians($lat)) + $z * sin($this->toRadians($lat));
        $azimuth = $this->norm360($this->toDegrees(atan2($this->toRadians($yhor), $this->toRadians($xhor))) + 180.0);
        $trueElevation = $this->toDegrees(atan2($this->toRadians($zhor), $this->toRadians(sqrt($xhor * $xhor + $yhor * $yhor))));
        return array($azimuth, $trueElevation);
    }

    private function geoToTopo($rRaDec, $lat, $lon, $dateTime)
    {
        $gclat = $lat - 0.1924 * sin($this->toRadians(2 * $lat));
        $rho = 0.99833 + 0.00167 * cos($this->toRadians(2 * $lat));
        $mpar = $this->toDegrees(asin(1 / $rRaDec[0]));
        $LST = $this->localSiderealTimeHours($lon, $dateTime);
        $HA = $this->norm360(($LST * 15) - $rRaDec[1]);
        $g = $this->toDegrees(atan(tan($this->toRadians($gclat)) / cos($this->toRadians($HA))));
        $topRA = $rRaDec[1] - $mpar * $rho * cos($this->toRadians($gclat)) * sin($this->toRadians($HA)) / cos($this->toRadians($rRaDec[2]));
        $topDecl = $rRaDec[2] - $mpar * $rho * sin($this->toRadians($gclat)) * sin($this->toRadians($g - $rRaDec[2])) / sin($this->toRadians($g));
        return array($rRaDec[0], $topRA, $topDecl);
    }

    /**
     * @param $lon float
     * @param $dateTime DateTime
     * @return float
     */
    private function localSiderealTimeHours($lon, $dateTime)
    {

        $d = $this->dayNumber($dateTime);
        $UT = $dateTime->format('H') + ($dateTime->format('i') / 60.0) + ($dateTime->format('s') / 3600.0);

        $ws = $this->norm360(282.9404 + 4.70935E-5 * $d); // (longitude of perihelion)
        $Ms = $this->norm360(356.0470 + 0.9856002585 * $d); // (mean anomaly)
        $Ls = $this->norm360($ws + $Ms);

        $GMST0 = $Ls / 15 + 12.0;

        return $this->norm24($GMST0 + $UT + $lon / 15.0);

    }

    private function obliquityOfEcliptic($t)
    {
        $seconds = 21.448 - $t * (46.8150 + $t * (0.00059 - $t * (0.001813)));
        return 23.0 + (26.0 + ($seconds / 60.0)) / 60.0;
    }

    private function norm360($degrees)
    {
        while ($degrees < 0.0) {
            $degrees += 360.0;
        }
        while ($degrees > 360.0) {
            $degrees -= 360.0;
        }
        return $degrees;
    }

    private function norm24($hours)
    {
        while ($hours < 0.0) {
            $hours += 24.0;
        }
        while ($hours > 24.0) {
            $hours -= 24.0;
        }
        return $hours;
    }

    private function sign($value, $plus)
    {
        return ($value + $plus) < 0.0 ? -1 : 1;
    }

    private function toDegrees($angleRad)
    {
        return (180.0 * $angleRad / M_PI);
    }

    private function toRadians($angleDeg)
    {
        return (M_PI * $angleDeg / 180.0);
    }

    private function binarySearchNoon($lat, $lon, $initialSector, $initialTimestamp, $intervalMs, $searchDirection, $depth)
    {
        $thisTimestamp = $initialTimestamp + ($searchDirection * $intervalMs);
        $thisPosition = $this->calcPositionDateTime($lat, $lon, $thisTimestamp);
        $thisSector = $this->sector($thisPosition->azimuth);
        if ($intervalMs < 15000 || $depth > 10) {
            return $thisPosition;
        }
        if ($thisSector == $initialSector) {
            return $this->binarySearchNoon($lat, $lon, $thisSector, $thisTimestamp, $intervalMs / 2, $searchDirection, $depth + 1);
        } else {
            return $this->binarySearchNoon($lat, $lon, $thisSector, $thisTimestamp, $intervalMs / 2, -$searchDirection, $depth + 1);
        }
    }

}