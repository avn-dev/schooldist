<?php

return [

	'event_manager' => [
		'listen' => [
			[\TsActivities\Events\ActivityBooked::class, ['access' => ['ts_event_manager_activities', 'booked']]],
			[\TsActivities\Events\ActivityCancelled::class, ['access' => ['ts_event_manager_activities', 'cancelled']]],
		],
	],

	'communication' => [
		'recipients' => [
		],
		'applications' => [
			'activity' => \TsActivities\Communication\Application\Activities::class,
		],
	],

	'webpack' => [
		['entry' => 'js/scheduling.ts', 'output' => '&', 'config' => 'backend']
	]
];
