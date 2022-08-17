<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

$lists['states'] = array(
'AA' => 'Armed Forces Americas',
'AE' => 'Armed Forces Europe',
'AK' => 'Alaska',
'AL' => 'Alabama',
'AP' => 'Armed Forces Pacific',
'AS' => 'American Samoa',
'AZ' => 'Arizona',
'AR' => 'Arkansas',
'CA' => 'California',
'CO' => 'Colorado',
'CT' => 'Connecticut',
'DE' => 'Delaware',
'DC' => 'District of Columbia',
'FM' => 'Federated States of Micronesia',
'FL' => 'Florida',
'GA' => 'Georgia',
'GU' => 'Guam',
'HI' => 'Hawaii',
'ID' => 'Idaho',
'IL' => 'Illinois',
'IN' => 'Indiana',
'IA' => 'Iowa',
'KS' => 'Kansas',
'KY' => 'Kentucky',
'LA' => 'Louisiana',
'ME' => 'Maine',
'MH' => 'Marshall Islands',
'MD' => 'Maryland',
'MA' => 'Massachusetts',
'MI' => 'Michigan',
'MN' => 'Minnesota',
'MS' => 'Mississippi',
'MO' => 'Missouri',
'MT' => 'Montana',
'NE' => 'Nebraska',
'NV' => 'Nevada',
'NH' => 'New Hampshire',
'NJ' => 'New Jersey',
'NM' => 'New Mexico',
'NY' => 'New York',
'NC' => 'North Carolina',
'ND' => 'North Dakota',
'MP' => 'Northern Mariana Islands',
'OH' => 'Ohio',
'OK' => 'Oklahoma',
'OR' => 'Oregon',
'PW' => 'Palau',
'PA' => 'Pennsylvania',
'PR' => 'Puerto Rico',
'RI' => 'Rhode Island',
'SC' => 'South Carolina',
'SD' => 'South Dakota',
'TN' => 'Tennessee',
'TX' => 'Texas',
'UT' => 'Utah',
'VT' => 'Vermont',
'VI' => 'Virgin Islands',
'VA' => 'Virginia',
'WA' => 'Washington',
'WV' => 'West Virginia',
'WI' => 'Wisconsin',
'WY' => 'Wyoming'
);
$lists['states'] = array_keys($lists['states']);
$lists['states'] = array_combine($lists['states'], $lists['states']);
asort($lists['states']);

$lists['countries'] = array(
'ABW' => 'Aruba',
'AFG' => 'Afghanistan',
'AGO' => 'Angola',
'AIA' => 'Anguilla',
'ALA' => 'Aland Islands',
'ALB' => 'Albania',
'AND' => 'Andorra',
'ANT' => 'Netherlands Antilles',
'ARE' => 'United Arab Emirates',
'ARG' => 'Argentina',
'ARM' => 'Armenia',
'ASM' => 'American Samoa',
'ATA' => 'Antarctica',
'ATF' => 'French Southern Territories',
'ATG' => 'Antigua and Barbuda',
'AUS' => 'Australia',
'AUT' => 'Austria',
'AZE' => 'Azerbaijan',
'BDI' => 'Burundi',
'BEL' => 'Belgium',
'BEN' => 'Benin',
'BES' => 'Bonaire, Sint Eustatius and Saba',
'BFA' => 'Burkina Faso',
'BGD' => 'Bangladesh',
'BGR' => 'Bulgaria',
'BHR' => 'Bahrain',
'BHS' => 'Bahamas',
'BIH' => 'Bosnia and Herzegovina',
'BLM' => 'Saint Barthelemy',
'BLR' => 'Belarus',
'BLZ' => 'Belize',
'BMU' => 'Bermuda',
'BOL' => 'Bolivia',
'BRA' => 'Brazil',
'BRB' => 'Barbados',
'BRN' => 'Brunei Darussalam',
'BTN' => 'Bhutan',
'BVT' => 'Bouvet Island',
'BWA' => 'Botswana',
'CAF' => 'Central African Republic',
'CAN' => 'Canada',
'CCK' => 'Cocos (Keeling) Islands',
'CHE' => 'Switzerland',
'CHL' => 'Chile',
'CHN' => 'China',
'CIV' => 'Cote d\'Ivoire',
'CMR' => 'Cameroon',
'COD' => 'Congo, the Democratic Republic of the',
'COG' => 'Congo',
'COK' => 'Cook Islands',
'COL' => 'Colombia',
'COM' => 'Comoros',
'CPV' => 'Cape Verde',
'CRI' => 'Costa Rica',
'CUB' => 'Cuba',
'CUW' => 'Curacao',
'CXR' => 'Christmas Island',
'CYM' => 'Cayman Islands',
'CYP' => 'Cyprus',
'CZE' => 'Czech Republic',
'DEU' => 'Germany',
'DJI' => 'Djibouti',
'DMA' => 'Dominica',
'DNK' => 'Denmark',
'DOM' => 'Dominican Republic',
'DZA' => 'Algeria',
'ECU' => 'Ecuador',
'EGY' => 'Egypt',
'ERI' => 'Eritrea',
'ESH' => 'Western Sahara',
'ESP' => 'Spain',
'EST' => 'Estonia',
'ETH' => 'Ethiopia',
'FIN' => 'Finland',
'FJI' => 'Fiji',
'FLK' => 'Falkland Islands (Malvinas)',
'FRA' => 'France',
'FRO' => 'Faroe Islands',
'FSM' => 'Micronesia, Federated States of',
'GAB' => 'Gabon',
'GBR' => 'United Kingdom',
'GEO' => 'Georgia',
'GGY' => 'Guernsey',
'GHA' => 'Ghana',
'GIN' => 'Guinea',
'GIB' => 'Gibraltar',
'GLP' => 'Guadeloupe',
'GMB' => 'Gambia',
'GNB' => 'Guinea-Bissau',
'GNQ' => 'Equatorial Guinea',
'GRC' => 'Greece',
'GRD' => 'Grenada',
'GRL' => 'Greenland',
'GTM' => 'Guatemala',
'GUF' => 'French Guiana',
'GUM' => 'Guam',
'GUY' => 'Guyana',
'HKG' => 'Hong Kong',
'HMD' => 'Heard Island and McDonald Islands',
'HND' => 'Honduras',
'HRV' => 'Croatia',
'HTI' => 'Haiti',
'HUN' => 'Hungary',
'IDN' => 'Indonesia',
'IMN' => 'Isle of Man',
'IND' => 'India',
'IOT' => 'British Indian Ocean Territory',
'IRL' => 'Ireland',
'IRN' => 'Iran, Islamic Republic of',
'IRQ' => 'Iraq',
'ISL' => 'Iceland',
'ISR' => 'Israel',
'ITA' => 'Italy',
'JAM' => 'Jamaica',
'JEY' => 'Jersey',
'JOR' => 'Jordan',
'JPN' => 'Japan',
'KAZ' => 'Kazakhstan',
'KEN' => 'Kenya',
'KGZ' => 'Kyrgyzstan',
'KHM' => 'Cambodia',
'KIR' => 'Kiribati',
'KNA' => 'Saint Kitts and Nevis',
'KOR' => 'Korea, Republic of',
'KWT' => 'Kuwait',
'LAO' => 'Lao People\'s Democratic Republic',
'LBN' => 'Lebanon',
'LBR' => 'Liberia',
'LBY' => 'Libyan Arab Jamahiriya',
'LCA' => 'Saint Lucia',
'LIE' => 'Liechtenstein',
'LKA' => 'Sri Lanka',
'LSO' => 'Lesotho',
'LTU' => 'Lithuania',
'LUX' => 'Luxembourg',
'LVA' => 'Latvia',
'MAC' => 'Macao',
'MAF' => 'Saint Martin (French part)',
'MAR' => 'Morocco',
'MCO' => 'Monaco',
'MDA' => 'Moldova, Republic of',
'MDG' => 'Madagascar',
'MDV' => 'Maldives',
'MEX' => 'Mexico',
'MHL' => 'Marshall Islands',
'MKD' => 'Macedonia, the former Yugoslav Republic of',
'MLI' => 'Mali',
'MLT' => 'Malta',
'MMR' => 'Myanmar',
'MNE' => 'Montenegro',
'MNG' => 'Mongolia',
'MNP' => 'Northern Mariana Islands',
'MOZ' => 'Mozambique',
'MRT' => 'Mauritania',
'MSR' => 'Montserrat',
'MTQ' => 'Martinique',
'MUS' => 'Mauritius',
'MWI' => 'Malawi',
'MYS' => 'Malaysia',
'MYT' => 'Mayotte',
'NAM' => 'Namibia',
'NCL' => 'New Caledonia',
'NER' => 'Niger',
'NFK' => 'Norfolk Island',
'NGA' => 'Nigeria',
'NIC' => 'Nicaragua',
'NOR' => 'Norway',
'NIU' => 'Niue',
'NLD' => 'Netherlands',
'NPL' => 'Nepal',
'NRU' => 'Nauru',
'NZL' => 'New Zealand',
'OMN' => 'Oman',
'PAK' => 'Pakistan',
'PAN' => 'Panama',
'PCN' => 'Pitcairn',
'PER' => 'Peru',
'PHL' => 'Philippines',
'PLW' => 'Palau',
'PNG' => 'Papua New Guinea',
'POL' => 'Poland',
'PRI' => 'Puerto Rico',
'PRK' => 'Korea, Democratic People\'s Republic of',
'PRT' => 'Portugal',
'PRY' => 'Paraguay',
'PSE' => 'Palestinian Territory, Occupied',
'PYF' => 'French Polynesia',
'QAT' => 'Qatar',
'REU' => 'Reunion',
'ROU' => 'Romania',
'RUS' => 'Russian Federation',
'RWA' => 'Rwanda',
'SAU' => 'Saudi Arabia',
'SDN' => 'Sudan',
'SEN' => 'Senegal',
'SGP' => 'Singapore',
'SGS' => 'South Georgia and the South Sandwich Islands',
'SHN' => 'Saint Helena',
'SJM' => 'Svalbard and Jan Mayen',
'SLB' => 'Solomon Islands',
'SLE' => 'Sierra Leone',
'SLV' => 'El Salvador',
'SMR' => 'San Marino',
'SOM' => 'Somalia',
'SPM' => 'Saint Pierre and Miquelon',
'SRB' => 'Serbia',
'SSD' => 'South Sudan',
'STP' => 'Sao Tome and Principe',
'SUR' => 'Suriname',
'SVK' => 'Slovakia',
'SVN' => 'Slovenia',
'SWE' => 'Sweden',
'SWZ' => 'Swaziland',
'SXM' => 'Sint Maarten (Dutch part)',
'SYC' => 'Seychelles',
'SYR' => 'Syrian Arab Republic',
'TCA' => 'Turks and Caicos Islands',
'TCD' => 'Chad',
'TGO' => 'Togo',
'THA' => 'Thailand',
'TJK' => 'Tajikistan',
'TKL' => 'Tokelau',
'TKM' => 'Turkmenistan',
'TLS' => 'Timor-Leste',
'TON' => 'Tonga',
'TTO' => 'Trinidad and Tobago',
'TUN' => 'Tunisia',
'TUR' => 'Turkey',
'TUV' => 'Tuvalu',
'TWN' => 'Taiwan, Province of China',
'TZA' => 'Tanzania, United Republic of',
'UGA' => 'Uganda',
'UKR' => 'Ukraine',
'USA' => 'United States',
'UMI' => 'United States Minor Outlying Islands',
'URY' => 'Uruguay',
'UZB' => 'Uzbekistan',
'VAT' => 'Holy See (Vatican City State)',
'VCT' => 'Saint Vincent and the Grenadines',
'VEN' => 'Venezuela',
'VGB' => 'Virgin Islands, British',
'VIR' => 'Virgin Islands, U.S.',
'VNM' => 'Viet Nam',
'VUT' => 'Vanuatu',
'WLF' => 'Wallis and Futuna',
'WSM' => 'Samoa',
'YEM' => 'Yemen',
'ZAF' => 'South Africa',
'ZMB' => 'Zambia',
'ZWE' => 'Zimbabwe'
);
asort($lists['countries']);

$lists['timezones'] = array(
'-12' => 'GMT -12:00 Eniwetok, Kwajalein',
'-11' => 'GMT -11:00 Midway Island, Samoa',
'-10' => 'GMT -10:00 Hawaii',
'-9' => 'GMT -9:00 Alaska',
'-8' => 'GMT -8:00 Pacific Time (US & Canada)',
'-7' => 'GMT -7:00 Mountain Time (US & Canada)',
'-6' => 'GMT -6:00 Central Time (US & Canada), Mexico City',
'-5' => 'GMT -5:00 Eastern Time (US & Canada), Bogota, Lima',
'-4' => 'GMT -4:00 Atlantic Time (Canada), Caracas, La Paz',
'-3.5' => 'GMT -3:30 Newfoundland',
'-3' => 'GMT -3:00 Brazil, Buenos Aires, Georgetown',
'-2' => 'GMT -2:00 Mid-Atlantic',
'-1' => 'GMT -1:00 Azores, Cape Verde Islands',
'0' => 'GMT Western Europe Time, London, Lisbon, Casablanca',
'1' => 'GMT +1:00 Brussels, Copenhagen, Madrid, Paris',
'2' => 'GMT +2:00 Kaliningrad, South Africa',
'3' => 'GMT +3:00 Baghdad, Riyadh, Moscow, St. Petersburg',
'3.5' => 'GMT +3:30 Tehran',
'4' => 'GMT +4:00 Abu Dhabi, Muscat, Baku, Tbilisi',
'4.5' => 'GMT +4:30 Kabul',
'5' => 'GMT +5:00 Ekaterinburg, Islamabad, Karachi, Tashkent',
'5.5' => 'GMT +5:30 Bombay, Calcutta, Madras, New Delhi',
'6' => 'GMT +6:00 Almaty, Dhaka, Colombo',
'7' => 'GMT +7:00 Bangkok, Hanoi, Jakarta',
'8' => 'GMT +8:00 Beijing, Perth, Singapore, Hong Kong',
'9' => 'GMT +9:00 Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
'9.5' => 'GMT +9:30 Adelaide, Darwin',
'10' => 'GMT +10:00 Eastern Australia, Guam, Vladivostok',
'11' => 'GMT +11:00 Magadan, Solomon Islands, New Caledonia',
'12' => 'GMT +12:00 Auckland, Wellington, Fiji, Kamchatka'
);

$lists['months'] = array(
'01' => 'January',
'02' => 'February',
'03' => 'March',
'04' => 'April',
'05' => 'May',
'06' => 'June',
'07' => 'July',
'08' => 'August',
'09' => 'September',
'10' => 'October',
'11' => 'November',
'12' => 'December'
);
$lists['months_long'] = $lists['months'];
$lists['months'] = array_keys($lists['months']);

$year = gmdate('Y');
$lists['years'] = range($year, $year + 10);

foreach ($lists as $key => $value) {$GLOBALS[$key] = $value;}

function convert_country_codes($country_code3)
{
	$countries = array(
	'AFG' => 'AF',
	'ALA' => 'AX',
	'ALB' => 'AL',
	'DZA' => 'DZ',
	'ASM' => 'AS',
	'AND' => 'AD',
	'AGO' => 'AO',
	'AIA' => 'AI',
	'ATA' => 'AQ',
	'ATG' => 'AG',
	'ARG' => 'AR',
	'ARM' => 'AM',
	'ABW' => 'AW',
	'AUS' => 'AU',
	'AUT' => 'AT',
	'AZE' => 'AZ',
	'BHS' => 'BS',
	'BHR' => 'BH',
	'BGD' => 'BD',
	'BRB' => 'BB',
	'BLR' => 'BY',
	'BEL' => 'BE',
	'BLZ' => 'BZ',
	'BEN' => 'BJ',
	'BMU' => 'BM',
	'BTN' => 'BT',
	'BOL' => 'BO',
	'BES' => 'BQ',
	'BIH' => 'BA',
	'BWA' => 'BW',
	'BVT' => 'BV',
	'BRA' => 'BR',
	'IOT' => 'IO',
	'BRN' => 'BN',
	'BGR' => 'BG',
	'BFA' => 'BF',
	'BDI' => 'BI',
	'KHM' => 'KH',
	'CMR' => 'CM',
	'CAN' => 'CA',
	'CPV' => 'CV',
	'CYM' => 'KY',
	'CAF' => 'CF',
	'TCD' => 'TD',
	'CHL' => 'CL',
	'CHN' => 'CN',
	'CXR' => 'CX',
	'CCK' => 'CC',
	'COL' => 'CO',
	'COM' => 'KM',
	'COG' => 'CG',
	'COD' => 'CD',
	'COK' => 'CK',
	'CRI' => 'CR',
	'CIV' => 'CI',
	'HRV' => 'HR',
	'CUB' => 'CU',
	'CUW' => 'CW',
	'CYP' => 'CY',
	'CZE' => 'CZ',
	'DNK' => 'DK',
	'DJI' => 'DJ',
	'DMA' => 'DM',
	'DOM' => 'DO',
	'ECU' => 'EC',
	'EGY' => 'EG',
	'SLV' => 'SV',
	'GNQ' => 'GQ',
	'ERI' => 'ER',
	'EST' => 'EE',
	'ETH' => 'ET',
	'FLK' => 'FK',
	'FRO' => 'FO',
	'FIJ' => 'FJ',
	'FIN' => 'FI',
	'FRA' => 'FR',
	'GUF' => 'GF',
	'PYF' => 'PF',
	'ATF' => 'TF',
	'GAB' => 'GA',
	'GMB' => 'GM',
	'GEO' => 'GE',
	'DEU' => 'DE',
	'GHA' => 'GH',
	'GIB' => 'GI',
	'GRC' => 'GR',
	'GRL' => 'GL',
	'GRD' => 'GD',
	'GLP' => 'GP',
	'GUM' => 'GU',
	'GTM' => 'GT',
	'GGY' => 'GG',
	'GIN' => 'GN',
	'GNB' => 'GW',
	'GUY' => 'GY',
	'HTI' => 'HT',
	'HMD' => 'HM',
	'VAT' => 'VA',
	'HND' => 'HN',
	'HKG' => 'HK',
	'HUN' => 'HU',
	'ISL' => 'IS',
	'IND' => 'IN',
	'IDN' => 'ID',
	'IRN' => 'IR',
	'IRQ' => 'IQ',
	'IRL' => 'IE',
	'IMN' => 'IM',
	'ISR' => 'IL',
	'ITA' => 'IT',
	'JAM' => 'JM',
	'JPN' => 'JP',
	'JEY' => 'JE',
	'JOR' => 'JO',
	'KAZ' => 'KZ',
	'KEN' => 'KE',
	'KIR' => 'KI',
	'PRK' => 'KP',
	'KOR' => 'KR',
	'KWT' => 'KW',
	'KGZ' => 'KG',
	'LAO' => 'LA',
	'LVA' => 'LV',
	'LBN' => 'LB',
	'LSO' => 'LS',
	'LBR' => 'LR',
	'LBY' => 'LY',
	'LIE' => 'LI',
	'LTU' => 'LT',
	'LUX' => 'LU',
	'MAC' => 'MO',
	'MKD' => 'MK',
	'MDG' => 'MG',
	'MWI' => 'MW',
	'MYS' => 'MY',
	'MDV' => 'MV',
	'MLI' => 'ML',
	'MLT' => 'MT',
	'MHL' => 'MH',
	'MTQ' => 'MQ',
	'MRT' => 'MR',
	'MUS' => 'MU',
	'MYT' => 'YT',
	'MEX' => 'MX',
	'FSM' => 'FM',
	'MDA' => 'MD',
	'MCO' => 'MC',
	'MNG' => 'MN',
	'MNE' => 'ME',
	'MSR' => 'MS',
	'MAR' => 'MA',
	'MOZ' => 'MZ',
	'MMR' => 'MM',
	'NAM' => 'NA',
	'NRU' => 'NR',
	'NPL' => 'NP',
	'NLD' => 'NL',
	'ANT' => 'AN',
	'NCL' => 'NC',
	'NZL' => 'NZ',
	'NIC' => 'NI',
	'NER' => 'NE',
	'NGA' => 'NG',
	'NIU' => 'NU',
	'NFK' => 'NF',
	'MNP' => 'MP',
	'NOR' => 'NO',
	'OMN' => 'OM',
	'PAK' => 'PK',
	'PLW' => 'PW',
	'PSE' => 'PS',
	'PAN' => 'PA',
	'PNG' => 'PG',
	'PRY' => 'PY',
	'PER' => 'PE',
	'PHL' => 'PH',
	'PCN' => 'PN',
	'POL' => 'PL',
	'PRT' => 'PT',
	'PRI' => 'PR',
	'QAT' => 'QA',
	'REU' => 'RE',
	'ROU' => 'RO',
	'RUS' => 'RU',
	'RWA' => 'RW',
	'BLM' => 'BL',
	'SHN' => 'SH',
	'KNA' => 'KN',
	'LCA' => 'LC',
	'MAF' => 'MF',
	'SXM' => 'SX',
	'SPM' => 'PM',
	'VCT' => 'VC',
	'WSM' => 'WS',
	'SMR' => 'SM',
	'STP' => 'ST',
	'SAU' => 'SA',
	'SEN' => 'SN',
	'SRB' => 'RS',
	'SYC' => 'SC',
	'SLE' => 'SL',
	'SGP' => 'SG',
	'SVK' => 'SK',
	'SVN' => 'SI',
	'SLB' => 'SB',
	'SOM' => 'SO',
	'ZAF' => 'ZA',
	'SGS' => 'GS',
	'SSD' => 'SS',
	'ESP' => 'ES',
	'LKA' => 'LK',
	'SDN' => 'SD',
	'SUR' => 'SR',
	'SJM' => 'SJ',
	'SWZ' => 'SZ',
	'SWE' => 'SE',
	'CHE' => 'CH',
	'SYR' => 'SY',
	'TWN' => 'TW',
	'TJK' => 'TJ',
	'TZA' => 'TZ',
	'THA' => 'TH',
	'TLS' => 'TL',
	'TGO' => 'TG',
	'TKL' => 'TK',
	'TON' => 'TO',
	'TTO' => 'TT',
	'TUN' => 'TN',
	'TUR' => 'TR',
	'TKM' => 'TM',
	'TCA' => 'TC',
	'TUV' => 'TV',
	'UGA' => 'UG',
	'UKR' => 'UA',
	'ARE' => 'AE',
	'GBR' => 'GB',
	'USA' => 'US',
	'UMI' => 'UM',
	'URY' => 'UY',
	'UZB' => 'UZ',
	'VUT' => 'VU',
	'VEN' => 'VE',
	'VNM' => 'VN',
	'VGB' => 'VG',
	'VIR' => 'VI',
	'WLF' => 'WF',
	'ESH' => 'EH',
	'YEM' => 'YE',
	'ZMB' => 'ZM',
	'ZWE' => 'ZW'
	);

	if (isset($countries[$country_code3])) {$country_code2 = $countries[$country_code3];} else {$country_code2 = $country_code3;}
	return $country_code2;
}
?>