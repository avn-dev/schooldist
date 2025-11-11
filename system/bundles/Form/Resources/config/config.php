<?php

return [
	'hooks' => [
		Core\Command\Scheduler::HOOK_NAME => [
			'class' => Form\Hook\SchedulerHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		]
	],
	'commands' => [
		Form\Command\MailSend::class
	]
];
