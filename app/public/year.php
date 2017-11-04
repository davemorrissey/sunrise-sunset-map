<?php

require_once('../../vendor/autoload.php');
include "../../api/SunService.php";
include "../../api/TimeZoneService.php";

error_reporting(0);

function displayLatLon($lat, $lon) {
    
    $latD = floor(abs($lat));
    $latM = round((abs($lat) - $latD)*60);
    if ($latM >= 60) {
        $latM -= 60;
        $latD++;
    }
    
    $latD = str_pad($latD, 2, '0', STR_PAD_LEFT);
    $latM = str_pad($latM, 2, '0', STR_PAD_LEFT);
    $latI = $lat >= 0 ? 'N' : 'S';
    
    $lonD = floor(abs($lon));
    $lonM = round((abs($lon) - $lonD)*60);
    if ($lonM >= 60) {
        $lonM -= 60;
        $lonD++;
    }
    
    $lonD = str_pad($lonD, 3, '0', STR_PAD_LEFT);
    $lonM = str_pad($lonM, 2, '0', STR_PAD_LEFT);
    $lonI = $lon >= 0 ? 'E' : 'W';
    
    return $latD.'&#xb0;'.$latM.'\''.$latI.' '.$lonD.'&#xb0;'.$lonM.'\''.$lonI;
    
}

function uniord($c) {
    $h = ord($c{0});
    if ($h <= 0x7F) {
        return $h;
    } else if ($h < 0xC2) {
        return false;
    } else if ($h <= 0xDF) {
        return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
    } else if ($h <= 0xEF) {
        return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
                                 | (ord($c{2}) & 0x3F);
    } else if ($h <= 0xF4) {
        return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
                                 | (ord($c{2}) & 0x3F) << 6
                                 | (ord($c{3}) & 0x3F);
    } else {
        return false;
    }
}

$utc = new DateTimeZone('UTC');

$lat = $_GET['lat'];
$lon = $_GET['lon'];
$year = $_GET['year'];
$tz = new DateTimeZone($_GET['tz']);
$place = $_GET['name'];
$dateUtc = new DateTime($year.'-01-01 00:00:00', $utc);

if ($lat >= -90 && $lat < -89) {
    $lat = -89;
}
if ($lat <= 90 && $lat > 89) {
    $lat = 89;
}

$sun = new SunService();

$latLonStr = displayLatLon($lat, $lon);

$sunDays = array();
while ($dateUtc->format('Y') == $year) {
    $sunDays[$dateUtc->format('m')][$dateUtc->format('d')] = $sun->calcDay($dateUtc, $lat, -$lon, $tz);
    $dateUtc->modify('+1 day');    
}

$utf8 = false;
$utf8marker=chr(128);
$count=0;
while(isset($place{$count})){
    if($place{$count}>=$utf8marker) {
        $parsechar=substr($place,$count,2);
        $count+=2;
    } else {
        $parsechar=$place{$count};
        $count++;
    }
    if (uniord($parsechar) > 255) {
        $utf8 = true;
    }
}

$months = array();
array_push($months, '');
array_push($months, 'January');
array_push($months, 'February');
array_push($months, 'March');
array_push($months, 'April');
array_push($months, 'May');
array_push($months, 'June');
array_push($months, 'July');
array_push($months, 'August');
array_push($months, 'September');
array_push($months, 'October');
array_push($months, 'November');
array_push($months, 'December');

$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setFontSubsetting(false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('www.sunrisesunsetmap.com');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(13, 12, 13);
$pdf->setHeaderMargin(PDF_MARGIN_HEADER);
$pdf->setFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->AddPage();
$pdf->SetFont('helvetica', '', 15);
$pdf->writeHTML('<p align="center">Sunrise and sunset times for '.$year.'</p>', true, false, true, false, 'center');

if ($place != null) {
    if ($utf8) {
        $pdf->SetFont('freesans', 'B', 25);
    } else {
        $pdf->SetFont('helvetica', 'B', 25);
    }
    $pdf->writeHTML('<p align="center">'.$place.'</p>', true, false, true, false, 'center');
} else {
    $pdf->SetFont('helvetica', 'B', 25);
    $pdf->writeHTML('<p align="center">'.$latLonStr.'</p>', true, false, true, false, 'center');
}

$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML('<p align="center">www.sunrisesunsetmap.com</p>', true, false, true, false, 'center');
$pdf->SetFont('helvetica', 'B', 15);
$pdf->writeHTML('<p align="center"></p>', true, false, true, false, 'center');
$pdf->SetFont('helvetica', '', 8);

$table = '<table cellpadding="2" cellspacing="0" border="1" width="936">';
$table = $table.'<tr>';
$table = $table.'<th style="background-color:#000000;color:#000000" width="30" align="center">-</th>';
for ($m = 1; $m < 13; $m++) {
    $table = $table.'<th width="1" align="center" style="background-color:#000000"></th>';
    $table = $table.'<th style="background-color:#000000;color:#FFFFFF" width="74" align="center">'.$months[$m].'</th>';
}
$table = $table.'<th width="1" align="center" style="background-color:#000000"></th>';
$table = $table.'<th style="background-color:#000000;color:#000000" width="30" align="center">-</th>';
$table = $table.'</tr></table>';
$pdf->writeHTML($table, false, false, false, false, '');
for ($d = 1; $d < 32; $d++) {
    $background = $d%2 == 0 ? '#EEEEEE' : '#FFFFFF';
    
    $table = '<table cellpadding="2" cellspacing="0" border="1" width="936"><tr>';
    $table = $table.'<td width="30" align="center" style="background-color:'.$background.'">'.$d.'</td>';
    for ($m = 1; $m < 13; $m++) {
        $sunDay = $sunDays[$m < 10 ? '0'.$m : ''.$m][$d < 10 ? '0'.$d : ''.$d];
        $table = $table.'<td width="1" align="center" style="background-color:#000000"></td>';
        if ($sunDay) {
            if ($sunDay->special != null) {
                $table = $table.'<td align="center" style="background-color:'.$background.'" width="74">'.$sunDay->special.'</td>';
            } else {
                if ($sunDay->sunrise != null) {
                    $table = $table.'<td align="center" style="background-color:'.$background.'" width="37">'.$sunDay->sunrise->format('H:i').'</td>';
                } else {
                    $table = $table.'<td align="center" style="background-color:'.$background.';color:'.$background.'" width="37">-</td>';
                }
                if ($sunDay->sunset != null) {
                    $table = $table.'<td align="center" style="background-color:'.$background.'" width="37">'.$sunDay->sunset->format('H:i').'</td>';
                } else {
                    $table = $table.'<td align="center" style="background-color:'.$background.';color:'.$background.'" width="37">-</td>';
                }
            }
        } else {
            $table = $table.'<td align="center" style="background-color:'.$background.';color:'.$background.'" width="74">-</td>';
        }
        
    }
    $table = $table.'<td width="1" align="center" style="background-color:#000000"></td>';
    $table = $table.'<td width="30" align="center"style="background-color:'.$background.'">'.$d.'</td>';
    $table = $table.'</tr></table>';
    $pdf->writeHTML($table, false, false, false, false, '');
}


if ($_GET['tz'] == "UTC") {
    $pdf->writeHTML('<p align="center"><br/>All times are in UTC</p>', true, false, true, false, 'center');
} else {
    $pdf->writeHTML('<p align="center"><br/>Times are local and include daylight savings where appropriate.</p>', true, false, true, false, 'center');
}

//Close and output PDF document
$pdf->Output('sunrise_sunset_'.$year.'.pdf', 'I');
