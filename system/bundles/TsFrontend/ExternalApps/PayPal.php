<?php

namespace TsFrontend\ExternalApps;

class PayPal extends \Ts\Handler\ExternalAppPerSchool {
	
	public function getTitle(): string {
		return 'PayPal';
	}
	
	public function getDescription(): string {
		return $this->t('PayPal - Beschreibung');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getIcon(): string {
		return 'fa fa-paypal';
	}
	
	public function getSettings(): array {
		return [
			'paypal_client_sandbox' => [
				'label' => $this->t('Sandbox aktivieren'),
				'type' => 'checkbox'
			],
			'paypal_client_id' => [
				'label' => 'Client-ID',
				'type' => 'input'
			],
			'paypal_client_secret' => [
				'label' => 'Client-Secret',
				'type' => 'password'
			]
		];
	}

}