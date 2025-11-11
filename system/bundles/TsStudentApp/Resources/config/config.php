<?php

use TsStudentApp\Command;
use TsStudentApp\Messenger\Notifications;
use TsStudentApp\Messenger\Thread;
use TsStudentApp\Pages;
use TsStudentApp\Properties;

return [

	'external_apps' => [
		TsStudentApp\Handler\ExternalApp::APP_NAME => [
			'class' => TsStudentApp\Handler\ExternalApp::class
		]
	],

	'auth' => [
		'customer_db' => 77,
		'token_lifetime' => 14400, // 4 Stunden
	],

	/**
	* Uploads
	*/

	'uploads' => [
		'images' => [
			'quality' => 75
		]
	],

	/**
	* Messenger Einstellungen
	*/

	'messenger' => [

		/**
		 * Notifications
		 */

		'notifications' => [
			'default' => 'firebase',
			'services' => [
				'firebase' => [
					'class' => Notifications\Firebase::class
				],
				'apns' => [
					'class' => Notifications\Apns::class
				]
			]
		],

		/**
		 * Threads
		 *
		 * - "icon" -> see https://ionicons.com/ (nur wenn es kein Bild für den Thread gibt)
		 * - "in" -> Nachricht vom Schüler
		 * - "out" -> Nachricht aus der Software
		 */

		'threads' => [
			[
				'icon' => 'home-outline',
				'label' => 'Schule',
				'entity' => \Ext_Thebing_School::class,
				'class' => Thread\School::class,
				'relation' => [\Ext_Thebing_User::class, '*'],
				'directions' => ['in', 'out']
			],
			[
				'icon' => 'person-circle-outline',
				'label' => 'Lehrer',
				'entity' => \Ext_Thebing_Teacher::class,
				'class' => Thread\Teacher::class,
				'directions' => ['in', 'out']
			]
		]
	],

	/**
	 * App pages
	 *
	 * - "title" -> Seiten-Titel (wird übersetzt)
	 * - "icon" -> aus https://ionicons.com/
	 * - "data" -> Klasse der Seite aus der die Daten kommen
	 * - "tab" -> als Tab darstellen
	 * - "refresh_after" -> wenn die Seite eine refresh()-Methode hat kann man hier festlegen, nach wie vielen Sekunden
	 *                      die Seite aktualisiert werden muss (wenn nicht angegeben wird immer aktualisiert)
	 */

	'pages' => [
		'home' => [
			'title' => 'Home',
			'icon' => 'home',
			'data' => Pages\Home::class,
			'tab' => true
		],
		'messenger' => [
			'title' => 'Messages',
			'icon' => 'chatbox-outline',
			'data' => Pages\Messenger::class,
			'badge_property' => Properties\NumberOfUnseenMessages::PROPERTY,
			'tab' => true,
			'deactivatable' => true
		],
		'messenger-thread' => [
			'title' => 'Messages (thread)',
			'data' => Pages\Messenger\Thread::class
		],
		'timetable' => [
			'title' => 'Schedule',
			'icon' => 'time-outline',
			'data' => Pages\Schedule::class,
			'refresh_after' => 300, // in sek => 5min
			'tab' => true,
			'deactivatable' => true
		],
		'schedule-info' => [
			'title' => 'Schedule (event view)',
			'data' => Pages\EventInfo::class,
		],
		'activities' => [
			'title' => 'Activities',
			'icon' => 'bicycle',
			'data' => Pages\Activities::class,
			'refresh_after' => 300, // in sek => 5min
			'tab' => true,
			'deactivatable' => true
		],
		'personal' => [
			'title' => 'Personal data',
			'icon' => 'person-outline',
			'data' => Pages\PersonalData::class,
			'refresh_after' => 300, // in sek => 5min
			'deactivatable' => true
		],
		'booking' => [
			'title' => 'Booking',
			'icon' => 'today-outline',
			'data' => Pages\Booking::class,
			'refresh_after' => 300, // in sek => 5min
			'deactivatable' => true
		],
		'attendance' => [
			'title' => 'Attendance',
			'icon' => 'pie-chart-outline',
			'data' => Pages\Attendance::class,
			'deactivatable' => true
		],
		'accommodation' => [
			'title' => 'Accommodation',
			'icon' => 'home-outline',
			'data' => Pages\Accommodation::class,
			'refresh_after' => 300, // in sek => 5min
			'deactivatable' => true
		],
		'documents' => [
			'title' => 'Documents',
			'icon' => 'document-outline',
			'data' => Pages\Documents::class,
			'refresh_after' => 300, // in sek => 5min
			'deactivatable' => true
		],
		'faq' => [
			'title' => 'FAQ',
			'icon' => 'help-circle-outline',
			'data' => Pages\FAQ::class,
			'refresh_after' => 3600, // in sek => 1h
			'deactivatable' => true
		],
		'about' => [
			'title' => 'About us',
			'icon' => 'information-circle-outline',
			'data' => Pages\About::class,
		],
		'change-password' => [
			'title' => 'Change password',
			'icon' => 'lock-closed-outline',
			'data' => Pages\ChangePassword::class,
		],
		'settings' => [
			'title' => 'Settings',
			'icon' => 'settings-outline',
			'data' => Pages\Settings::class,
		],
		'more' => [
			'title' => 'More',
			'icon' => 'menu',
			'tab' => true
		],
	],

	'properties' => [
		Properties\NumberOfUnseenMessages::PROPERTY => [
			'class' => Properties\NumberOfUnseenMessages::class,
			'refresh_after' => 60, // in sek => 1min
		],
		Properties\LastMessages::PROPERTY => [
			'class' => Properties\LastMessages::class,
			'default' => ['limit' => 3],
			'refresh_after' => 60, // in sek => 1min
			//'on_request' => true
		],
		/*Properties\MessageStatus::PROPERTY => [
			'class' => Properties\MessageStatus::class,
			'refresh_after' => 30, // in sek
			'onRequest' => true
		]*/
	],

	'event_manager' => [
		'listen' => [
			[\TsStudentApp\Events\AppMessageReceived::class, ['access' => 'app:'.\TsStudentApp\Handler\ExternalApp::APP_NAME]]
		],
	],

	/**
	 * Commands
	 */

	'commands' => [
		Command\SendNicknameMessage::class,
		Command\SendStudentMessage::class,
		Command\SendStudentNotification::class,
		Command\SendDeviceNotification::class,
	]
];
