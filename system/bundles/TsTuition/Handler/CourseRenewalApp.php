<?php

namespace TsTuition\Handler;

use TcExternalApps\Interfaces\ExternalApp;

class CourseRenewalApp extends ExternalApp
{
	const APP_NAME = 'ts_tuition_course_renewal';

	public function getTitle(): string
	{
		return \L10N::t('Kursverträge');
	}

	public function getDescription(): string
	{
		return \L10N::t('Automatische Verlängerung von Kursen.');
	}

	public function getCategory(): string
	{
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public function getIcon()
	{
		return 'fas fa-file-signature';
	}
}