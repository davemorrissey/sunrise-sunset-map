<?php

class TimeZone implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $offset;

    /**
     * @var string
     */
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        if ($id == 'UTC') {
            $this->offset = 'UTC';
        } else {
            $this->offset = 'GMT' . (new DateTime('now', new DateTimeZone($id)))->format('P');
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'offset' => $this->offset,
            'name' => $this->name
        );
    }

}

class TimeZoneService
{
    /**
     * @var array Country codes -> Time zone IDs.
     */
    private $countryCodeMap;

    /**
     * @var array List of zones.
     */
    private $timeZones;

    public function __construct()
    {
        // Map of country codes to time zone
        $countryCodeMap = array();
        $countryCodeMap["AS,WS,"] = ["US/Samoa"]; // American Samoa, Western Samoa
        $countryCodeMap["CA,"] = ["US/Pacific", "US/Mountain", "US/Central", "Canada/Saskatchewan", "US/Eastern", "Canada/Atlantic", "Canada/Newfoundland"]; // Canada
        $countryCodeMap["US,"] = ["US/Hawaii", "US/Alaska", "US/Pacific", "US/Arizona", "US/Mountain", "US/Central", "US/Eastern", "US/East-Indiana"]; // USA
        $countryCodeMap["MX,"] = ["America/Tijuana", "America/Chihuahua", "America/Mexico_City"]; // Mexico
        $countryCodeMap["CO,PE,EC,"] = ["America/Bogota"]; // Columbia, Peru, Ecuador
        $countryCodeMap["BR,"] = ["America/Bogota", "America/Manaus", "Brazil/East"]; // Brazil
        $countryCodeMap["VE,BO,"] = ["America/La_Paz"]; // Venezuela, Bolivia
        $countryCodeMap["CL,"] = ["America/Santiago"]; // Chile
        $countryCodeMap["AR,GY,"] = ["America/Buenos_Aires"]; // Argentina, Guyana
        $countryCodeMap["GL,"] = ["America/Godthab"]; // Greenland
        $countryCodeMap["UY,"] = ["America/Montevideo"]; // Uruguay
        $countryCodeMap["GS,"] = ["Atlantic/South_Georgia"]; // South Georgia
        $countryCodeMap["CV,"] = ["Atlantic/Cape_Verde"]; // Cape Verde Is
        $countryCodeMap["IS,MA,LR,"] = ["Atlantic/Reykjavik"]; // Iceland, Morocco, Liberia
        $countryCodeMap["GB,PT,IE,"] = ["Europe/London"];  // Britain, Portugal, Ireland
        $countryCodeMap["FR,ES,BE,DK,"] = ["Europe/Paris"]; //France, Spain, Belgium, Denmark
        $countryCodeMap["DE,NL,IT,AT,CH,SE,NO,"] = ["Europe/Amsterdam"]; // Germany, Netherlans, Italy, Austria, Switzerland, Sweden, Norway (assumed)
        $countryCodeMap["GR,TR,RO,"] = ["Europe/Bucharest"]; // Greece, Turkey, Romania
        $countryCodeMap["FI,UA,LV,BG,EE,LT,"] = ["Europe/Helsinki"]; // Finland, Ukraine, Latvia, Bulgaria, Estonia, Lithuania
        $countryCodeMap["PL,HR,BA,MK,"] = ["Europe/Sarajevo"]; // Poland, Croatia, Bosnia, Macedonia
        $countryCodeMap["CZ,SK,HU,SI,RS,"] = ["Europe/Belgrade"]; // Czech R, Slovakia, Hungary, Slovenia, Serbia
        $countryCodeMap["JO,"] = ["Asia/Amman"]; // Jordan
        $countryCodeMap["LB,"] = ["Asia/Beirut"]; // Lebanon
        $countryCodeMap["EG,"] = ["Africa/Cairo"]; // Egypt
        $countryCodeMap["ZW,ZA,"] = ["Africa/Harare"]; // Zimbabwe, South Africa
        $countryCodeMap["IL,"] = ["Asia/Jerusalem"]; // Israel
        $countryCodeMap["BY,"] = ["Europe/Minsk"]; // Belarus
        $countryCodeMap["IQ,"] = ["Asia/Baghdad"]; // Iraq
        $countryCodeMap["KW,SA,"] = ["Asia/Kuwait"]; // Kuwait, Saudi Arabia
        $countryCodeMap["RU,"] = ["Europe/Moscow", "Asia/Yekaterinburg", "Asia/Novosibirsk", "Asia/Krasnoyarsk", "Asia/Ulaanbaatar", "Asia/Yakutsk", "Asia/Vladivostok", "Asia/Magadan", "Pacific/Fiji"]; // Russia
        $countryCodeMap["KE,"] = ["Africa/Nairobi"]; // Kenya
        $countryCodeMap["IR,"] = ["Asia/Tehran"]; // Iran
        $countryCodeMap["AE,OM,"] = ["Asia/Muscat"]; // UAE, Oman
        $countryCodeMap["AZ,"] = ["Asia/Baku"]; // Azerbaijan
        $countryCodeMap["AM,"] = ["Asia/Yerevan"]; // Armenia
        $countryCodeMap["AF,"] = ["Asia/Kabul"]; // Afghanistan
        $countryCodeMap["PK,UZ,"] = ["Asia/Tashkent"]; // Pakistan, Uzbekistan
        $countryCodeMap["IN,"] = ["Asia/Calcutta"]; // India
        $countryCodeMap["NP,"] = ["Asia/Katmandu"]; // Nepal
        $countryCodeMap["KZ,BD,"] = ["Asia/Dhaka"]; // Kazakhstan, Bangladesh
        $countryCodeMap["MM,"] = ["Asia/Rangoon"]; // Burma
        $countryCodeMap["TH,VN,"] = ["Asia/Jakarta"]; // Thailand, Vietnam, Indonesia
        $countryCodeMap["MN,"] = ["Asia/Ulaanbaatar"]; // Mongolia
        $countryCodeMap["MY,"] = ["Asia/Kuala_Lumpur"]; // Malaysia
        $countryCodeMap["JP,"] = ["Asia/Tokyo"]; // Japan
        $countryCodeMap["KR,KP,"] = ["Asia/Seoul"]; // North Korea, South Korea
        $countryCodeMap["PG,"] = ["Pacific/Port_Moresby"]; // Papua New Guinea
        $countryCodeMap["SB,NC,"] = ["Asia/Magadan"]; // Solomon Is, New Caledonia
        $countryCodeMap["NZ,"] = ["Pacific/Auckland"]; // New Zealand
        $countryCodeMap["CN,"] = ["Asia/Hong_Kong"]; // China
        $countryCodeMap["AU,"] = ["Australia/Perth", "Australia/Adelaide", "Australia/Darwin", "Australia/Brisbane", "Australia/Sydney", "Australia/Hobart"]; // Australia
        $countryCodeMap["FJ,MH,"] = ["Pacific/Fiji"]; // Fiji, Marshall Is
        $countryCodeMap["TO,"] = ["Pacific/Tongatapu"]; // Tonga
        $this->countryCodeMap = $countryCodeMap;

        // A limited list of time zones loosely based on Windows and/or Android date settings.
        // Unknown countries Indonesia, most of Africa, some of South America, Costa Rica, Cuba, Haiti, Domenican Republic
        // Potentially incorrect countries Congo, Kazakhstan.
        $timeZones = array();
        $timeZones[] = new TimeZone("UTC", "Coordinated Universal Time");
        $timeZones[] = new TimeZone("US/Samoa", "Midway Island, Samoa");
        $timeZones[] = new TimeZone("US/Hawaii", "Hawaii");
        $timeZones[] = new TimeZone("US/Alaska", "Alaska");
        $timeZones[] = new TimeZone("US/Pacific", "Pacific Time (US & Canada)");
        $timeZones[] = new TimeZone("America/Tijuana", "Tijuana, Baja California");
        $timeZones[] = new TimeZone("US/Arizona", "Arizona");
        $timeZones[] = new TimeZone("America/Chihuahua", "Chihuahua, La Paz, Mazatlan");
        $timeZones[] = new TimeZone("US/Mountain", "Mountain Time (US & Canada)");
        $timeZones[] = new TimeZone("US/Central", "Central Time (US & Canada)");
        $timeZones[] = new TimeZone("America/Mexico_City", "Guadalajara, Mexico City, Monterray");
        $timeZones[] = new TimeZone("Canada/Saskatchewan", "Saskatchewan");
        $timeZones[] = new TimeZone("America/Bogota", "Bogota, Lima, Quito, Rio Branco");
        $timeZones[] = new TimeZone("US/Eastern", "Eastern Time (US & Canada)");
        $timeZones[] = new TimeZone("US/East-Indiana", "Indiana (East)");
        $timeZones[] = new TimeZone("Canada/Atlantic", "Atlantic Time (Canada)");
        $timeZones[] = new TimeZone("America/La_Paz", "Caracas, La Paz");
        $timeZones[] = new TimeZone("America/Manaus", "Manaus");
        $timeZones[] = new TimeZone("America/Santiago", "Santiago");
        $timeZones[] = new TimeZone("Canada/Newfoundland", "Newfoundland");
        $timeZones[] = new TimeZone("Brazil/East", "Brasilia");
        $timeZones[] = new TimeZone("America/Buenos_Aires", "Buenos Aires, Georgetown");
        $timeZones[] = new TimeZone("America/Godthab", "Greenland");
        $timeZones[] = new TimeZone("America/Montevideo", "Montevideo");
        $timeZones[] = new TimeZone("Atlantic/South_Georgia", "Mid-Atlantic");
        $timeZones[] = new TimeZone("Atlantic/Azores", "Azores");
        $timeZones[] = new TimeZone("Atlantic/Cape_Verde", "Cape Verde Is.");
        $timeZones[] = new TimeZone("Atlantic/Reykjavik", "Casablanca, Monrovia, Reykjavik");
        $timeZones[] = new TimeZone("Europe/London", "Dublin, Edinburgh, Lisbon, London");
        $timeZones[] = new TimeZone("Europe/Amsterdam", "Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna");
        $timeZones[] = new TimeZone("Europe/Belgrade", "Belgrade, Bratislava, Budapest, Ljubliana, Prague");
        $timeZones[] = new TimeZone("Europe/Paris", "Brussels, Copenhagen, Madrid, Paris");
        $timeZones[] = new TimeZone("Europe/Sarajevo", "Sarajevo, Skopje, Warsaw, Zagreb");
        $timeZones[] = new TimeZone("Asia/Amman", "Amman");
        $timeZones[] = new TimeZone("Europe/Bucharest", "Athens, Bucharest, Istanbul");
        $timeZones[] = new TimeZone("Asia/Beirut", "Beirut");
        $timeZones[] = new TimeZone("Africa/Cairo", "Cairo");
        $timeZones[] = new TimeZone("Africa/Harare", "Harare, Pretoria");
        $timeZones[] = new TimeZone("Europe/Helsinki", "Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius");
        $timeZones[] = new TimeZone("Asia/Jerusalem", "Jerusalem");
        $timeZones[] = new TimeZone("Europe/Minsk", "Minsk");
        $timeZones[] = new TimeZone("Asia/Baghdad", "Baghdad");
        $timeZones[] = new TimeZone("Asia/Kuwait", "Kuwait, Riyadh");
        $timeZones[] = new TimeZone("Europe/Moscow", "Moscow, St. Petersburg, Volgograd");
        $timeZones[] = new TimeZone("Africa/Nairobi", "Nairobi");
        $timeZones[] = new TimeZone("Asia/Tehran", "Tehran");
        $timeZones[] = new TimeZone("Asia/Muscat", "Abu Dhabi, Muscat");
        $timeZones[] = new TimeZone("Asia/Baku", "Baku");
        $timeZones[] = new TimeZone("Asia/Yerevan", "Yerevan");
        $timeZones[] = new TimeZone("Asia/Kabul", "Kabul");
        $timeZones[] = new TimeZone("Asia/Yekaterinburg", "Ekaterinburg");
        $timeZones[] = new TimeZone("Asia/Tashkent", "Islamabad, Karachi, Tashkent");
        $timeZones[] = new TimeZone("Asia/Calcutta", "Chennai, Kolkata, Mumbai, New Delhi");
        $timeZones[] = new TimeZone("Asia/Katmandu", "Katmandu");
        $timeZones[] = new TimeZone("Asia/Novosibirsk", "Novosibirsk");
        $timeZones[] = new TimeZone("Asia/Dhaka", "Astana, Dhaka");
        $timeZones[] = new TimeZone("Asia/Rangoon", "Yangon (Rangoon)");
        $timeZones[] = new TimeZone("Asia/Jakarta", "Bangkok, Hanoi, Jakarta");
        $timeZones[] = new TimeZone("Asia/Krasnoyarsk", "Krasnoyarsk");
        $timeZones[] = new TimeZone("Asia/Hong_Kong", "Beijing, Chongqing, Hong Kong, Urumqi");
        $timeZones[] = new TimeZone("Asia/Ulaanbaatar", "Irkutsk, Ulaan Bataar");
        $timeZones[] = new TimeZone("Asia/Kuala_Lumpur", "Kuala Lumpur, Singapore");
        $timeZones[] = new TimeZone("Australia/Perth", "Perth");
        $timeZones[] = new TimeZone("Asia/Taipei", "Taipei");
        $timeZones[] = new TimeZone("Asia/Tokyo", "Osaka, Sapporo, Tokyo");
        $timeZones[] = new TimeZone("Asia/Seoul", "Seoul");
        $timeZones[] = new TimeZone("Asia/Yakutsk", "Yakutsk");
        $timeZones[] = new TimeZone("Australia/Adelaide", "Adelaide");
        $timeZones[] = new TimeZone("Australia/Darwin", "Darwin");
        $timeZones[] = new TimeZone("Australia/Brisbane", "Brisbane");
        $timeZones[] = new TimeZone("Pacific/Port_Moresby", "Guam, Port Moresby");
        $timeZones[] = new TimeZone("Australia/Sydney", "Canberra, Melbourne, Sydney");
        $timeZones[] = new TimeZone("Australia/Hobart", "Hobart");
        $timeZones[] = new TimeZone("Asia/Vladivostok", "Vladivostok");
        $timeZones[] = new TimeZone("Asia/Magadan", "Magadan, Solomon Is., New Caledonia");
        $timeZones[] = new TimeZone("Pacific/Auckland", "Auckland, Wellington");
        $timeZones[] = new TimeZone("Pacific/Fiji", "Fiji, Kamchatka, Marshall Is.");
        $timeZones[] = new TimeZone("Pacific/Tongatapu", "Nuku'alofa");
        $this->timeZones = $timeZones;
    }

    public function getAllTimeZones()
    {
        return $this->timeZones;
    }

    public function getTimeZoneById($timeZoneId)
    {
        foreach ($this->timeZones as $timeZone) {
            if ($timeZone->getId() == $timeZoneId) {
                return $timeZone;
            }
        }
        $dtz = new DateTimeZone($timeZoneId);
        $name = null;
        if (strpos($timeZoneId, '/') > 0) {
            $name = str_replace('_', ' ', str_replace('-', ' ', substr($timeZoneId, strpos($timeZoneId, '/') + 1)));
        } else {
            $name = 'GMT' . (new DateTime('now', $dtz))->format('T');
        }
        return new TimeZone($timeZoneId, $name);
    }

    public function getTimeZonesForLocation($lat, $lon, $countryIso2)
    {
        // First try Google API, and attempt to return the corresponding zone from our list, otherwise build one.
        try {
            $url = 'https://maps.googleapis.com/maps/api/timezone/json?key='.getenv('API_KEY').'&location='.$lat.','.$lon.'&timestamp='.time();
            $json = json_decode(file_get_contents($url), true);
            $timeZoneId = $json['timeZoneId'];
            if ($timeZoneId) {
                $tz = $this->getTimeZoneById($timeZoneId);
                if ($tz) {
                    return array($tz);
                } else {
                    $dtz = new DateTimeZone($timeZoneId);
                    if ($json['timeZoneName']) {
                        $name= $json['timeZoneName'];
                    } elseif (strpos($timeZoneId, '/') > 0) {
                        $name = str_replace('_', ' ', str_replace('-', ' ', substr($timeZoneId, strpos($timeZoneId, '/') + 1)));
                    } else {
                        $name = 'GMT' . (new DateTime('now', $dtz))->format('T');
                    }
                    return array(0 => new TimeZone($timeZoneId, $name));
                }
            }
        } catch (Exception $e) { }

        // Last resort - use hardcoded map of country code to timezones.
        return $this->timeZonesForCountry($countryIso2);
    }

    /**
     * Get the best matching time zones for a country. Returns null if there are no suggestions.
     * @param $countryIso2 string ISO 2 country code.
     * @return array List of timezones in the country.
     */
    public function timeZonesForCountry($countryIso2)
    {
        if (strlen($countryIso2) == 0) {
            return null;
        }
        foreach ($this->countryCodeMap as $countryCodes => $timeZoneIds) {
            if (strpos($countryCodes, $countryIso2.',') !== false) {
                $matchingZones = array();
                foreach ($timeZoneIds as $timeZoneId) {
                    foreach ($this->timeZones as $timeZone) {
                        if ($timeZone->getId() == $timeZoneId) {
                            $matchingZones[] = $timeZone;
                        }
                    }
                }
                return $matchingZones;
            }
        }
        return null;
    }

}