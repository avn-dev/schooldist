<?php

namespace TsFrontend\ExternalApps;

class Moneris extends \Ts\Handler\ExternalAppPerSchool {

	public function getTitle(): string {
		return 'Moneris';
	}

	public function getDescription(): string {
		return $this->t('Moneris - Beschreibung');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getIcon(): string {
		return 'fas fa-credit-card';
	}

	public function getSettings(): array {
		return [
			'moneris_testing' => [
				'label' => 'Testing',
				'type' => 'checkbox'
			],
			'moneris_store_id' => [
				'label' => 'Store ID',
				'type' => 'input'
			],
			'moneris_api_token' => [
				'label' => 'API Token',
				'type' => 'password',
				'encrypted' => true
			],
			'moneris_checkout_id' => [
				'label' => 'Moneris Checkout Configuration ID',
				'type' => 'input'
			]
		];
	}

}