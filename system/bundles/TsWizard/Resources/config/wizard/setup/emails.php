<?php

return require base_path('system/bundles/Communication/Resources/config/wizards/email_accounts.php');

/*use TcCommunication\Wizard\EmailAccounts\Conditions;
use TcCommunication\Wizard\EmailAccounts\Steps\Email;

return [
	'type' => 'block',
	'class' => Email\BlockEmailAccounts::class,
	'elements' => [
		'list' => ['type' => 'step', 'title' => 'E-Mail-Konten', 'icon' => 'fa fa-at', 'class' => Email\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => Email\BlockEmailAccountEntity::class,
			'elements' => [
				'smtp_settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => \TcCommuniation\Wizard\EmailAccounts\Steps\Email\Smtp\StepSmtpSettings::class],
				'smtp_settings2' => ['type' => 'step', 'title' => 'E-Mail-Ausgang (SMTP)', 'class' => \TcCommuniation\Wizard\EmailAccounts\Steps\Email\Smtp\StepSmtpSettings2::class, 'conditions' => [Conditions\ExistingEmailAccount::class]],
				'oauth2' => ['type' => 'step', 'title' => 'OAuth2', 'class' => Email\StepOAuth2::class, 'conditions' => [Conditions\ExistingEmailAccount::class]],
				'imap_question' => ['type' => 'step', 'title' => 'Automatischen E-Mail-Eingang verwenden?', 'class' => \TcCommuniation\Wizard\EmailAccounts\Steps\Email\Imap\StepImapQuestion::class, 'conditions' => [Conditions\ExistingEmailAccount::class]],
				'imap_settings' => ['type' => 'step', 'title' => 'Imap', 'class' => \TcCommuniation\Wizard\EmailAccounts\Steps\Email\Imap\StepImapSettings::class, 'conditions' => [Conditions\ExistingEmailAccount::class]],
			]
		]
	]
];*/