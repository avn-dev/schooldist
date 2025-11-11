<?php

return [
	// https://transfermateeducation.com/example/api/4.2/references/payer-identification-number/
	// https://en.wikipedia.org/wiki/List_of_national_identity_card_policies_by_country
	'id_validation' => [
		['AR', 'DNI or CUIT', '^(?=[0-9]*$)(?:.{7,9}|.{11})$'], // Argentina
		['BO', 'CI', '^(?=[0-9]*$)(?:.{5,20})$'], // Bolivia
		['BR', 'CPF (NNN.NNN.NNN-NN)', '^(\\d{3}\\.\\d{3}\\.\\d{3}-\\d{2})$'], // Brazil
		['CL', 'CI/RUT', '^(?:.{8,9})$'], // Chile
		['CN', 'Citizen ID number (公民身份号码)', '^([1-9]\\d{5}(19|20)\\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\\d{3}[0-9Xx])$'], // China
		['CO', 'CC (Cédula de Ciudadania)', '^(?=[0-9]*$)(?:.{6,10})$'], // Colombia
		['EC', 'CI (Cédula de Identidad)', '^(?=[0-9]*$)(?:.{5,20})$'], // Ecuador
		['EG', 'ID', '^(?=[0-9]*$)(?:.{14})$'], // Egypt
		['IN', 'PAN (LLLLLNNNNL)', '^[A-Z]{5}[0-9]{4}[A-Z]{1}$'], // India
		['ID', 'NIK', '^(?=[0-9]*$)(?:.{16})$'], // Indonesia
		['MX', 'CURP', '^(?:.{10,18})$'], // Mexico
		['MA', 'CNIE', '^(?:.{5,20})$'], // Morocco
		['NG', 'NIN', '^(?=[0-9]*$)(?:.{11})$'], // Nigeria
		['PY', 'CI', '^(?=[0-9]*$)(?:.{5,20})$'], // Paraguay
		['PE', 'DNI', '^(?=[0-9]*$)(?:.{8,9})$'], // Peru
		['ZA', 'ID', '^(?=[0-9]*$)(?:.{5,20})$'], // South Africa
		['TR', 'T.C. Kimlik No.', '^(?=[0-9]*$)(?:.{5,20})$'], // Turkey
		['US', 'SSN (NNN-NN-NNNN)', '^(?!000|666)[0-8][0-9]{2}-(?!00)[0-9]{2}-(?!0000)[0-9]{4}$'], // USA
		['UY', 'CI (Cédula de Identidad)', '^(?=[0-9]*$)(?:.{6,8})$'] // Uruguay
	],
	'states' => [
		'AU' => [
			'ACT' => 'Australian Capital Territory',
			'NSW' => 'New South Wales',
			'NT' => 'Northern Territory',
			'QLD' => 'Queensland',
			'SA' => 'South Australia',
			'TAS' => 'Tasmania',
			'VIC' => 'Victoria',
			'WA' => 'Western Australia'
		],
		'CA' => [
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QE' => 'Québec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon'
		],
		'US' => [
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AS' => 'American Samoa',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
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
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'VI' => 'U.S. Virgin Islands',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming'
		]
	]
];