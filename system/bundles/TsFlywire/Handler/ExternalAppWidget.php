<?php

namespace TsFlywire\Handler;

class ExternalAppWidget extends \Ts\Handler\ExternalAppPerSchool
{
	const APP_NAME = 'flywire_widget';

	public function getIcon(): string
	{
		return 'fas fa-landmark';
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return \L10N::t('Flywire - Payment Widget');
	}

	public function getDescription(): string
	{
		return \L10N::t('Embed Flywire in registration form and payment form.');
	}

	public function getCategory(): string
	{
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getSettings(): array
	{
		return [
			'flywire_demo_mode' => [
				'label' => 'Demo Environment',
				'type' => 'checkbox'
			],
			'flywire_prefix' => [
				'label' => 'Prefix',
				'type' => 'input'
			],
			'flywire_destination' => [
				'label' => 'Destination',
				'type' => 'input'
			]
		];
	}
}