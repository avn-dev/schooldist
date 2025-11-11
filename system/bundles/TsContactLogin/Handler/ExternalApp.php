<?php

namespace TsContactLogin\Handler;

use TcExternalApps\Interfaces\ExternalApp as TcExternalApp;

class ExternalApp extends TcExternalApp
{
	const APP_NAME = 'contact_login';

	public function getTitle(): string
	{
		return \L10N::t('Schülerportal');
	}

	public function getDescription(): string
	{
		return \L10N::t('Schülerportal - Beschreibung');
	}

	public function getIcon()
	{
		return 'fas fa-link';
	}

	public function getCategory(): string
	{
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

}