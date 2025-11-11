<?php

return [
	// Mapping zwischen Block und Vue-Components inkl. Props
	'block_type_mapping' => [
		Ext_Thebing_Form_Page_Block::TYPE_COLUMNS => ['block-columns'],
		Ext_Thebing_Form_Page_Block::TYPE_HEADLINE2 => ['block-static', 'type' => 'h2'],
		Ext_Thebing_Form_Page_Block::TYPE_HEADLINE3 => ['block-static', 'type' => 'h3'],
		Ext_Thebing_Form_Page_Block::TYPE_STATIC_TEXT => ['block-static', 'type' => 'text'],
		Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD => ['block-download'],
		Ext_Thebing_Form_Page_Block::TYPE_INPUT => ['block-input', 'type' => 'input'],
		Ext_Thebing_Form_Page_Block::TYPE_SELECT => ['block-input', 'type' => 'select'],
		Ext_Thebing_Form_Page_Block::TYPE_DATE => ['block-input', 'type' => 'datepicker'],
		Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX => ['block-input', 'type' => 'checkbox'],
		Ext_Thebing_Form_Page_Block::TYPE_UPLOAD => ['block-input', 'type' => 'upload'],
		Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA => ['block-input', 'type' => 'textarea'],
		Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT => ['block-input', 'type' => 'multiselect'],
		Ext_Thebing_Form_Page_Block::TYPE_YESNO => ['block-input', 'type' => 'select'],
		Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS => ['block-nav-steps'],
		Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS => ['block-nav-buttons'],
		Ext_Thebing_Form_Page_Block::TYPE_NOTIFICATIONS => ['block-notifications'],
		Ext_Thebing_Form_Page_Block::TYPE_HORIZONTAL_RULE => ['block-static', 'type' => 'hr'],
		Ext_Thebing_Form_Page_Block::TYPE_HONEYPOT => ['block-honeypot'],
		Ext_Thebing_Form_Page_Block::TYPE_COURSES => ['block-service-container', 'type' => 'course', 'max' => 3, 'min' => 1],
		Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS => ['block-service-container', 'type' => 'accommodation', 'max' => 1, 'min' => 1],
		Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS => ['block-transfer', 'type' => 'transfer'],
		Ext_Thebing_Form_Page_Block::TYPE_INSURANCES => ['block-service-blocks', 'type' => 'insurance'],
		Ext_Thebing_Form_Page_Block::TYPE_PRICES => ['block-prices'],
		Ext_Thebing_Form_Page_Block::TYPE_FEES => ['block-service-blocks', 'type' => 'fee'],
		Ext_Thebing_Form_Page_Block::TYPE_PAYMENT => ['block-payment'],
		Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY => ['block-service-blocks', 'type' => 'activity', 'view' => 'checkbox'],
	],

	// Blöcke im Preisblock => Translation
	'price_block_blocks' => [
		'courses' => 'priceCourse',
		'accommodations' => 'priceAccommodation',
		'extras' => 'priceExtra',
		'fees' => 'priceCostsGeneral'
	],

	// Document Items => Block in Preisanzeige
	'price_block_mapping' => [
		'course' => 'courses',
		'accommodation' => 'accommodations',
		'transfer' => 'extras',
		'insurance' => 'extras',
		'extra_nights' => 'accommodations',
		'extra_weeks' => 'accommodations',
		'additional_course' => 'fees',
		'additional_accommodation' => 'fees',
		'additional_general' => 'extras',
		'activities' => 'extras',
		'special' => 'extras'
	],

	// Aktionen, die bei Änderung des Feldes ausgeführt werden (ACTION_SETTINGS)
	'field_actions' => [
		'services' => [
			'courses' => [
				'$change' => ['dates', 'prices'],
				'course' => ['dates', 'prices'],
				'language' => ['prices'],
				'level' => ['dates', 'prices'], // Da Validator das Feld prüft, muss eine Änderung die Aktionen auslösen
				'start' => ['dates', 'prices'],
				'duration' => ['dates', 'prices'],
				'units' => ['prices'], // Da Validator das Feld prüft, muss eine Änderung die Aktionen auslösen
				'program' => ['dates', 'prices'],
				'additional_services' => ['prices']
			],
			'accommodations' => [
				'$change' => ['dates', 'prices'],
				'accommodation' => ['dates', 'prices'],
				'roomtype' => ['prices'],
				'board' => ['prices'],
				'start' => ['dates', 'prices'],
				'end' => ['dates', 'prices'],
				'additional_services' => ['prices']
			],
			'transfers' => [
				'$change' => ['prices'],
				'mode' => ['prices'],
				'type' => ['prices'],
				'origin' => ['prices'],
				'destination' => ['prices'],
				'date' => ['prices'],
				'time' => ['prices']
			],
			'insurances' => [
				'$change' => ['prices'],
				'insurance' => ['prices'],
				'start' => ['prices'],
				'duration' => ['prices'],
				'end' => ['prices']
			],
			'fees' => [
				'$change' => ['prices'],
				'fee' => ['prices']
			],
			'activities' => [
				'$change' => ['prices'],
				'activity' => ['dates', 'prices'],
				'start' => ['prices'],
				'duration' => ['prices'],
				'units' => ['prices']
			],
		],
		'fields' => [
			Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL => ['school_change'],
			Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE => ['prices'],
			Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_NATIONALITY => ['prices'],
			Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_PROMOTION_CODE => ['prices'],
//			Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_TRANSFER_MODE => ['prices']
		]
	],

	// Statische Icons für <i class="ICON"></i>
	'icons' => [
		'bookmark' => 'fas fa-bookmark',
		'calendar' => 'fas fa-calendar',
		'chevron-right' => 'fas fa-chevron-right',
		'chevron-left' => 'fas fa-chevron-left',
		'download' => 'fas fa-download',
		'info' => 'fas fa-info-circle',
		'plus' => 'fas fa-plus',
		'question-circle' => 'fas fa-question-circle',
		'redo' => 'fas fa-redo',
		'shopping-cart' => 'fas fa-shopping-cart',
		'spinner' => 'fas fa-spinner fa-spin',
		'times' => 'fas fa-times',
		'trash' => 'fas fa-trash',
		'warning' => 'fas fa-exclamation-triangle'
	]
];
