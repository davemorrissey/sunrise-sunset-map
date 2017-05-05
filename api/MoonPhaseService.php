<?php
// Public domain. Translated and adapted from unknown source.

class MoonPhaseService
{

    private function jyear($td)
    {
        $td += 0.5;
        $z = floor($td);
        $f = $td - $z;
        if ($z < 2299161.0) {
            $a = $z;
        } else {
            $alpha = floor(($z - 1867216.25) / 36524.25);
            $a = $z + 1 + $alpha - floor($alpha / 4);
        }
        $b = $a + 1524;
        $c = floor(($b - 122.1) / 365.25);
        $d = floor(365.25 * $c);
        $e = floor(($b - $d) / 30.6001);
        $mm = floor(($e < 14) ? ($e - 1) : ($e - 13));
        return array(floor(($mm > 2) ? ($c - 4716) : ($c - 4715)), $mm, floor($b - $d - floor(30.6001 * $e) + $f));
    }

    private function jhms($j)
    {
        $j += 0.5;
        $ij = ($j - floor($j)) * 86400.0;
        return array(floor($ij / 3600), floor(($ij / 60) % 60), floor($ij % 60));
    }

    private function dtr($d)
    {
        return ($d * M_PI) / 180.0;
    }

    private function dsin($x)
    {
        return sin($this->dtr($x));
    }

    private function dcos($x)
    {
        return cos($this->dtr($x));
    }

    private function truephase($k, $phase)
    {
        $SynMonth = 29.53058868;
        $k += $phase;
        $t = $k / 1236.85;
        $t2 = $t * $t;
        $t3 = $t2 * $t;
        $pt = 2415020.75933 + $SynMonth * $k + 0.0001178 * $t2 - 0.000000155 * $t3 + 0.00033 * $this->dsin(166.56 + 132.87 * $t - 0.009173 * $t2);
        $m = 359.2242 + 29.10535608 * $k - 0.0000333 * $t2 - 0.00000347 * $t3;
        $mprime = 306.0253 + 385.81691806 * $k + 0.0107306 * $t2 + 0.00001236 * $t3;
        $f = 21.2964 + 390.67050646 * $k - 0.0016528 * $t2 - 0.00000239 * $t3;
        if (($phase < 0.01) || (abs($phase - 0.5) < 0.01)) {
            $pt += (0.1734 - 0.000393 * $t) * $this->dsin($m)
                + 0.0021 * $this->dsin(2 * $m)
                - 0.4068 * $this->dsin($mprime)
                + 0.0161 * $this->dsin(2 * $mprime)
                - 0.0004 * $this->dsin(3 * $mprime)
                + 0.0104 * $this->dsin(2 * $f)
                - 0.0051 * $this->dsin($m + $mprime)
                - 0.0074 * $this->dsin($m - $mprime)
                + 0.0004 * $this->dsin(2 * $f + $m)
                - 0.0004 * $this->dsin(2 * $f - $m)
                - 0.0006 * $this->dsin(2 * $f + $mprime)
                + 0.0010 * $this->dsin(2 * $f - $mprime)
                + 0.0005 * $this->dsin($m + 2 * $mprime);
        } else if ((abs($phase - 0.25) < 0.01 || (abs($phase - 0.75) < 0.01))) {
            $pt += (0.1721 - 0.0004 * $t) * $this->dsin($m)
                + 0.0021 * $this->dsin(2 * $m)
                - 0.6280 * $this->dsin($mprime)
                + 0.0089 * $this->dsin(2 * $mprime)
                - 0.0004 * $this->dsin(3 * $mprime)
                + 0.0079 * $this->dsin(2 * $f)
                - 0.0119 * $this->dsin($m + $mprime)
                - 0.0047 * $this->dsin($m - $mprime)
                + 0.0003 * $this->dsin(2 * $f + $m)
                - 0.0004 * $this->dsin(2 * $f - $m)
                - 0.0006 * $this->dsin(2 * $f + $mprime)
                + 0.0021 * $this->dsin(2 * $f - $mprime)
                + 0.0003 * $this->dsin($m + 2 * $mprime)
                + 0.0004 * $this->dsin($m - 2 * $mprime)
                - 0.0003 * $this->dsin(2 * $m + $mprime);
            if ($phase < 0.5)
                $pt += 0.0028 - 0.0004 * $this->dcos($m) + 0.0003 * $this->dcos($mprime);
            else
                $pt += -0.0028 + 0.0004 * $this->dcos($m) - 0.0003 * $this->dcos($mprime);
        }
        return $pt;
    }

    private function calendar($j, $zone)
    {
        $date = $this->jyear($j);
        $time = $this->jhms($j);
        $str = $date[0] . '-' . $date[1] . '-' . $date[2] . ' ' . $time[0] . ':' . $time[1] . ':' . $time[2] . '.000';
        $calendar = new DateTime($str, new DateTimeZone('UTC'));
        $calendar->setTimezone($zone);
        return $calendar;
    }

    public function getYearEvents($year, $zone)
    {
        $events = array();
        $k1 = floor(($year - 1900) * 12.3685) - 4;
        for ($j = 0; true; $j++) {
            $newTime = $this->truephase($k1, 0);
            $newCal = $this->calendar($newTime, $zone);
            if ($newCal->format('Y') == $year) {
                $event = new MoonPhaseEvent(0, 'New', $newCal);
                array_push($events, $event);
            }
            $fqTime = $this->truephase($k1, 0.25);
            $fqCal = $this->calendar($fqTime, $zone);
            if ($fqCal->format('Y') == $year) {
                $event = new MoonPhaseEvent(0.25, 'First Quarter', $fqCal);
                array_push($events, $event);
            }
            $fullTime = $this->truephase($k1, 0.5);
            $fullCal = $this->calendar($fullTime, $zone);
            if ($fullCal->format('Y') == $year) {
                $event = new MoonPhaseEvent(0.5, 'Full', $fullCal);
                array_push($events, $event);
            }
            $lqTime = $this->truephase($k1, 0.75);
            $lqCal = $this->calendar($lqTime, $zone);
            if ($lqCal->format('Y') == $year) {
                $event = new MoonPhaseEvent(0.75, 'Last Quarter', $lqCal);
                array_push($events, $event);
            }
            if ($newCal->format('Y') > $year && $fqCal->format('Y') > $year && $fullCal->format('Y') > $year && $lqCal->format('Y') > $year) {
                break;
            }
            $k1++;
        }
        return $events;
    }

    /**
     * @param $dateMidnight DateTime
     * @return float
     */
    public function getNoonPhase($dateMidnight)
    {
        $events = $this->getYearEvents($dateMidnight->format('Y'), $dateMidnight->getTimezone());
        $before = null;
        $after = null;
        $dateNoon = clone $dateMidnight;
        $dateNoon->modify('+12 hours');
        foreach ($events as $event) {
            if ($event->dateTime->format('U') < $dateNoon->format('U')) {
                $before = $event;
            } else if ($event->dateTime->format('U') > $dateNoon->format('U') && $after == null) {
                $after = $event;
            }
        }
        $defaultPhaseMs = 637860;
        $msNoon = $dateNoon->format('U');
        if ($before == null) {
            $msAfter = $after->dateTime->format('U');
            $phaseAfter = $after->phase;
            $msBefore = $msAfter - $defaultPhaseMs;
            $phaseBefore = $phaseAfter == 0 ? 0.75 : $phaseAfter - 0.25;
        } else if ($after == null) {
            $msBefore = $before->dateTime->format('U');
            $phaseBefore = $before->phase;
            $msAfter = $msBefore + $defaultPhaseMs;
        } else {
            $msBefore = $before->dateTime->format('U');
            $phaseBefore = $before->phase;
            $msAfter = $after->dateTime->format('U');
        }
        return $phaseBefore + ((($msNoon - $msBefore) / ($msAfter - $msBefore)) * 0.25);
    }

    /**
     * @param $dateMidnight DateTime
     * @return mixed|null
     */
    private function getDayEvent($dateMidnight)
    {
        $events = $this->getYearEvents($dateMidnight->format('Y'), $dateMidnight->getTimezone());
        foreach ($events as $event) {
            if ($event->dateTime->format('Y:m:d') == $dateMidnight->format('Y:m:d')) {
                return $event;
            }
        }
        return null;
    }

    public function getNoonPhaseName($dateMidnight)
    {
        $phase = $this->getNoonPhase($dateMidnight);
        $phaseName = null;
        if ($phase < 0.25) {
            $phaseName = 'Evening Crescent';
        } else if ($phase < 0.5) {
            $phaseName = 'Waxing Gibbous';
        } else if ($phase < 0.75) {
            $phaseName = 'Waning Gibbous';
        } else {
            $phaseName = 'Morning Crescent';
        }
        $event = $this->getDayEvent($dateMidnight);
        if ($event != null) {
            $phaseName = $event->phaseName;
        }
        return $phaseName;
    }

}

class MoonPhaseEvent
{

    public function __construct($phase, $phaseName, $dateTime)
    {
        $this->phase = $phase;
        $this->phaseName = $phaseName;
        $this->dateTime = $dateTime;
    }

    var $phase;

    var $phaseName;

    var $dateTime;

}