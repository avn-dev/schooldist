<?php

use Tc\Handler\Wizard\EmailAccounts\Conditions;
use Tc\Handler\Wizard\EmailAccounts\Steps\Email;

$oauth2 = function (string $type) {
	return [
		'type' => 'block',
		'elements' => [
			'verify' => ['type' => 'step', 'title' => 'OAuth2', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\StepOAuth2::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\ExistingEmailAccount::class, \Communication\Handler\Wizards\EmailAccounts\Conditions\HasOAuth2::class.':'.$type], 'mail_type' => $type],
		]
	];
};

return [
	'type' => 'block',
	'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\BlockEmailAccounts::class,
	'elements' => [
		'list' => ['type' => 'step', 'title' => 'E-Mail-Konten', 'icon' => 'fa fa-at', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\StepList::class],
		'access' => [
			'type' => 'block',
			'elements' => [
				'form' => ['type' => 'step', 'title' => 'Zugriffsrechte', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\StepAccess::class],
			]
		],
		'form' => [
			'type' => 'block',
			'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\BlockEmailAccountEntity::class,
			'elements' => [
				'smtp_settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\Smtp\StepSettings::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\HasAccess::class]],
				'smtp_settings2' => ['type' => 'step', 'title' => 'E-Mail-Ausgang (SMTP)', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\Smtp\StepSettings2::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\HasAccess::class, \Communication\Handler\Wizards\EmailAccounts\Conditions\ExistingEmailAccount::class]],
				'smtp_oauth2' => $oauth2('smtp'),
				'imap_question' => ['type' => 'step', 'title' => 'Automatischen E-Mail-Eingang verwenden?', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\Imap\StepImapQuestion::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\HasAccess::class, \Communication\Handler\Wizards\EmailAccounts\Conditions\ExistingEmailAccount::class]],
				'imap' => [
					'type' => 'block',
					'elements' => [
						'imap_settings' => ['type' => 'step', 'title' => 'E-Mail-Eingang (IMAP)', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\Imap\StepImapSettings::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\HasAccess::class, \Communication\Handler\Wizards\EmailAccounts\Conditions\ExistingEmailAccount::class]],
						'imap_oauth2' => $oauth2('imap'),
						'imap_settings2' => ['type' => 'step', 'title' => 'Synchronisation', 'class' => \Communication\Handler\Wizards\EmailAccounts\Steps\Email\Imap\StepImapSettings2::class, 'conditions' => [\Communication\Handler\Wizards\EmailAccounts\Conditions\HasAccess::class, \Communication\Handler\Wizards\EmailAccounts\Conditions\ExistingEmailAccount::class]],
					]
				],
			]
		]
	]
];