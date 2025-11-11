<?php

namespace TsFrontend\ExternalApps;

class Klarna extends \Ts\Handler\ExternalAppPerSchool {

	public function getTitle(): string {
		return 'Klarna';
	}

	public function getDescription(): string {
		return $this->t('Klarna - Beschreibung');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getIcon(): string {
		return 'fas fa-file-invoice';
	}
	
	public function getSettings(): array {
		return [
			'klarna_playground' => [
				'label' => 'Playground',
				'type' => 'checkbox'
			],
			'klarna_api' => [
				'label' => 'API',
				'type' => 'select',
				'options' => [
					'eu' => 'Europe',
					'na' => 'North America',
					'oc' => 'Oceania'
				]
			],
			'klarna_username' => [
				'label' => 'API Username (UID)',
				'type' => 'input'
			],
			'klarna_password' => [
				'label' => 'API Password',
				'type' => 'password'
			]
		];
	}

}