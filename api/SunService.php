<?php
// Public domain. Translated and adapted from NOAA.

class SunDay
{

    /**
     * @var DateTime
     */
    public $transit = null;

    /**
     * @var DateTime
     */
    public $sunrise = null;

    /**
     * @var DateTime
     */
    public $sunset = null;

    /**
     * @var string
     */
    public $special = null;

}

class SunPosition
{

    public $azimuth = 0;

    public $elevation = 0;

}

class SunService
{

    /**
     * @param $date DateTime
     * @param $minutes float
     * @param $tz DateTimeZone
     * @return mixed
     */
    private function createDateTime($date, $minutes, $tz)
    {
        $floatHour = $minutes / 60.0;
        $hour = floor($floatHour);
        $floatMinute = 60.0 * ($floatHour - floor($floatHour));
        $minute = floor($floatMinute + 0.5);
        $addDays = 0;
        if ($minute >= 60) {
            $minute -= 60;
            $hour++;
        }
        while ($hour > 23) {
            $hour -= 24;
            $addDays++;
        }
        while ($hour < 0) {
            $hour += 24;
            $addDays--;
        }
        $duration = $addDays . " days " . "$hour" . " hours " . $minute . " minutes";
        if ($minutes >= 0) {
            $duration = "+" . $duration;
        }

        $dateTime = clone $date;
        $dateTime->modify($duration);
        $dateTime->setTimezone($tz);
        return $dateTime;
    }

    /**
     * @param $date DateTime
     * @param $lat float
     * @param $lon float
     * @param $tz DateTimeZone
     * @return SunDay
     */
    public function calcDay($date, $lat, $lon, $tz)
    {
        $sunDay = new SunDay();
        $julianDay = $this->calcJD($date->format('Y'), $date->format('m'), $date->format('d'));
        $julianCent = $this->calcTimeJulianCent($julianDay);
        $sunrise = $this->calcUpUTC($julianDay, $lat, $lon, 90.833);
        $sunset = $this->calcDownUTC($julianDay, $lat, $lon, 90.833);
        $solarNoon = $this->calcSolNoonUTC($julianCent, $lon);
        if (is_nan($sunrise)) {
            $sunDay->sunrise = null;
        } else {
            $sunDay->sunrise = $this->createDateTime($date, $sunrise, $tz);
        }
        if (is_nan($sunset)) {
            $sunDay->sunset = null;
        } else {
            $sunDay->sunset = $this->createDateTime($date, $sunset, $tz);
        }
        $solarNoonDT = $this->createDateTime($date, $solarNoon, $tz);
        $sunDay->transit = $solarNoonDT;
        $solarNoonPosition = $this->calcPosition($solarNoonDT, $lat, $lon);
        if (is_nan($sunrise) && is_nan($sunset)) {
            if ($solarNoonPosition->elevation > 0) {
                $sunDay->special = "RISEN";
            } else {
                $sunDay->special = "SET";
            }
        }
        return $sunDay;
    }

    private function radToDeg($angleRad)
    {
        return (180.0 * $angleRad / M_PI);
    }

    private function degToRad($angleDeg)
    {
        return (M_PI * $angleDeg / 180.0);
    }

    private function calcJD($year, $month, $day)
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);
        $JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;
        return $JD;
    }

    private function calcTimeJulianCent($jd)
    {
        $T = ($jd - 2451545.0) / 36525.0;
        return $T;
    }

    private function calcJDFromJulianCent($t)
    {
        $JD = $t * 36525.0 + 2451545.0;
        return $JD;
    }

    private function calcGeomMeanLongSun($t)
    {
        if (is_nan($t)) {
            return $t;
        }
        $L0 = 280.46646 + $t * (36000.76983 + 0.0003032 * $t);
        while ($L0 > 360.0) {
            $L0 -= 360.0;
        }
        while ($L0 < 0.0) {
            $L0 += 360.0;
        }
        return $L0;
    }

    private function calcGeomMeanAnomalySun($t)
    {
        $M = 357.52911 + $t * (35999.05029 - 0.0001537 * $t);
        return $M;
    }

    private function calcEccentricityEarthOrbit($t)
    {
        $e = 0.016708634 - $t * (0.000042037 + 0.0000001267 * $t);
        return $e;
    }

    private function calcSunEqOfCenter($t)
    {
        $m = $this->calcGeomMeanAnomalySun($t);
        $mrad = $this->degToRad($m);
        $sinm = sin($mrad);
        $sin2m = sin($mrad + $mrad);
        $sin3m = sin($mrad + $mrad + $mrad);
        $C = $sinm * (1.914602 - $t * (0.004817 + 0.000014 * $t)) + $sin2m * (0.019993 - 0.000101 * $t) + $sin3m * 0.000289;
        return $C;
    }

    private function calcSunTrueLong($t)
    {
        $l0 = $this->calcGeomMeanLongSun($t);
        $c = $this->calcSunEqOfCenter($t);
        $O = $l0 + $c;
        return $O;
    }

    private function calcSunApparentLong($t)
    {
        $o = $this->calcSunTrueLong($t);
        $omega = 125.04 - 1934.136 * $t;
        $lambda = $o - 0.00569 - 0.00478 * sin($this->degToRad($omega));
        return $lambda;
    }

    private function calcMeanObliquityOfEcliptic($t)
    {
        $seconds = 21.448 - $t * (46.8150 + $t * (0.00059 - $t * (0.001813)));
        $e0 = 23.0 + (26.0 + ($seconds / 60.0)) / 60.0;
        return $e0;
    }

    private function calcObliquityCorrection($t)
    {
        $e0 = $this->calcMeanObliquityOfEcliptic($t);
        $omega = 125.04 - 1934.136 * $t;
        $e = $e0 + 0.00256 * cos($this->degToRad($omega));
        return $e;
    }

    private function calcSunDeclination($t)
    {
        $e = $this->calcObliquityCorrection($t);
        $lambda = $this->calcSunApparentLong($t);
        $sint = sin($this->degToRad($e)) * sin($this->degToRad($lambda));
        $theta = $this->radToDeg(asin($sint));
        return $theta;
    }

    private function calcEquationOfTime($t)
    {
        $epsilon = $this->calcObliquityCorrection($t);
        $l0 = $this->calcGeomMeanLongSun($t);
        $e = $this->calcEccentricityEarthOrbit($t);
        $m = $this->calcGeomMeanAnomalySun($t);
        $y = tan($this->degToRad($epsilon) / 2.0);
        $y *= $y;
        $sin2l0 = sin(2.0 * $this->degToRad($l0));
        $sinm = sin($this->degToRad($m));
        $cos2l0 = cos(2.0 * $this->degToRad($l0));
        $sin4l0 = sin(4.0 * $this->degToRad($l0));
        $sin2m = sin(2.0 * $this->degToRad($m));
        $Etime = $y * $sin2l0 - 2.0 * $e * $sinm + 4.0 * $e * $y * $sinm * $cos2l0
            - 0.5 * $y * $y * $sin4l0 - 1.25 * $e * $e * $sin2m;
        return $this->radToDeg($Etime) * 4.0;
    }

    private function calcHourAngleUp($lat, $solarDec, $elevation)
    {
        $latRad = $this->degToRad($lat);
        $sdRad = $this->degToRad($solarDec);
        $HA = (acos(cos($this->degToRad($elevation)) / (cos($latRad) * cos($sdRad)) - tan($latRad) * tan($sdRad)));
        return $HA;
    }

    private function calcHourAngleDown($lat, $solarDec, $elevation)
    {
        $latRad = $this->degToRad($lat);
        $sdRad = $this->degToRad($solarDec);
        $HA = (acos(cos($this->degToRad($elevation)) / (cos($latRad) * cos($sdRad)) - tan($latRad) * tan($sdRad)));
        return -$HA;
    }

    private function calcUpUTC($JD, $latitude, $longitude, $elevation)
    {
        $t = $this->calcTimeJulianCent($JD);
        $noonmin = $this->calcSolNoonUTC($t, $longitude);
        $tnoon = $this->calcTimeJulianCent($JD + $noonmin / 1440.0);
        $eqTime = $this->calcEquationOfTime($tnoon);
        $solarDec = $this->calcSunDeclination($tnoon);
        $hourAngle = $this->calcHourAngleUp($latitude, $solarDec, $elevation);
        $delta = $longitude - $this->radToDeg($hourAngle);
        $timeDiff = 4 * $delta;
        $timeUTC = 720 + $timeDiff - $eqTime;
        $newt = $this->calcTimeJulianCent($this->calcJDFromJulianCent($t) + $timeUTC / 1440.0);
        $eqTime = $this->calcEquationOfTime($newt);
        $solarDec = $this->calcSunDeclination($newt);
        $hourAngle = $this->calcHourAngleUp($latitude, $solarDec, $elevation);
        $delta = $longitude - $this->radToDeg($hourAngle);
        $timeDiff = 4 * $delta;
        $timeUTC = 720 + $timeDiff - $eqTime;
        return $timeUTC;
    }

    private function calcSolNoonUTC($t, $longitude)
    {
        $tnoon = $this->calcTimeJulianCent($this->calcJDFromJulianCent($t) + $longitude / 360.0);
        $eqTime = $this->calcEquationOfTime($tnoon);
        $solNoonUTC = 720 + ($longitude * 4) - $eqTime; // min
        $newt = $this->calcTimeJulianCent($this->calcJDFromJulianCent($t) - 0.5 + $solNoonUTC / 1440.0);
        $eqTime = $this->calcEquationOfTime($newt);
        $solNoonUTC = 720 + ($longitude * 4) - $eqTime; // min
        return $solNoonUTC;
    }

    private function calcDownUTC($JD, $latitude, $longitude, $elevation)
    {
        $t = $this->calcTimeJulianCent($JD);
        $noonmin = $this->calcSolNoonUTC($t, $longitude);
        $tnoon = $this->calcTimeJulianCent($JD + $noonmin / 1440.0);
        $eqTime = $this->calcEquationOfTime($tnoon);
        $solarDec = $this->calcSunDeclination($tnoon);
        $hourAngle = $this->calcHourAngleDown($latitude, $solarDec, $elevation);
        $delta = $longitude - $this->radToDeg($hourAngle);
        $timeDiff = 4 * $delta;
        $timeUTC = 720 + $timeDiff - $eqTime;
        $newt = $this->calcTimeJulianCent($this->calcJDFromJulianCent($t) + $timeUTC / 1440.0);
        $eqTime = $this->calcEquationOfTime($newt);
        $solarDec = $this->calcSunDeclination($newt);
        $hourAngle = $this->calcHourAngleDown($latitude, $solarDec, $elevation);
        $delta = $longitude - $this->radToDeg($hourAngle);
        $timeDiff = 4 * $delta;
        $timeUTC = 720 + $timeDiff - $eqTime;
        return $timeUTC;
    }

    /**
     * @param $dateTime DateTime
     * @param $lat float
     * @param $lon float
     * @return SunPosition
     */
    private function calcPosition($dateTime, $lat, $lon)
    {
        if (($lat >= -90) && ($lat < -89.8)) {
            $lat = -89.8;
        }
        if (($lat <= 90) && ($lat > 89.8)) {
            $lat = 89.8;
        }
        $dateTimeUtc = clone $dateTime;
        $dateTimeUtc->setTimezone(new DateTimeZone('UTC'));
        $timenow = $dateTimeUtc->format('H') + ($dateTimeUtc->format('i') / 60.0) + ($dateTimeUtc->format('s') / 3600.0);
        $JD = $this->calcJD($dateTimeUtc->format('Y'), $dateTimeUtc->format('m'), $dateTimeUtc->format('d'));
        $T = $this->calcTimeJulianCent($JD + $timenow / 24.0);
        $theta = $this->calcSunDeclination($T);
        $Etime = $this->calcEquationOfTime($T);
        $eqTime = $Etime;
        $solarDec = $theta;
        $offsetHours = 0;
        $solarTimeFix = $eqTime - 4.0 * $lon + 60.0 * $offsetHours;
        $trueSolarTime = ($dateTimeUtc->format('H') * 60.0) + ($dateTimeUtc->format('i')) + ($dateTimeUtc->format('s') / 60.0) + $solarTimeFix;
        while ($trueSolarTime > 1440) {
            $trueSolarTime -= 1440;
        }
        $hourAngle = $trueSolarTime / 4.0 - 180.0;
        if ($hourAngle < -180) {
            $hourAngle += 360.0;
        }
        $haRad = $this->degToRad($hourAngle);
        $csz = sin($this->degToRad($lat)) *
            sin($this->degToRad($solarDec)) +
            cos($this->degToRad($lat)) *
            cos($this->degToRad($solarDec)) * cos($haRad);
        if ($csz > 1.0) {
            $csz = 1.0;
        } else if ($csz < -1.0) {
            $csz = -1.0;
        }
        $zenith = $this->radToDeg(acos($csz));
        $azDenom = (cos($this->degToRad($lat)) * sin($this->degToRad($zenith)));
        if (abs($azDenom) > 0.001) {
            $azRad = ((sin($this->degToRad($lat)) *
                        cos($this->degToRad($zenith))) -
                    sin($this->degToRad($solarDec))) / $azDenom;
            if (abs($azRad) > 1.0) {
                if ($azRad < 0) {
                    $azRad = -1.0;
                } else {
                    $azRad = 1.0;
                }
            }
            $azimuth = 180.0 - $this->radToDeg(acos($azRad));
            if ($hourAngle > 0.0) {
                $azimuth = -$azimuth;
            }
        } else {
            if ($lat > 0.0) {
                $azimuth = 180.0;
            } else {
                $azimuth = 0.0;
            }
        }
        if ($azimuth < 0.0) {
            $azimuth += 360.0;
        }
        $exoatmElevation = 90.0 - $zenith;
        if ($exoatmElevation > 85.0) {
            $refractionCorrection = 0.0;
        } else {
            $te = tan($this->degToRad($exoatmElevation));
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
        $solarZen = $zenith - $refractionCorrection;
        $sunPosition = new SunPosition();
        $sunPosition->azimuth = $azimuth;
        $sunPosition->elevation = 90 - $solarZen;
        return $sunPosition;
    }

}
