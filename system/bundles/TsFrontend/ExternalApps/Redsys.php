<?php

namespace TsFrontend\ExternalApps;

class Redsys extends \Ts\Handler\ExternalAppPerSchool {
	
	public function getTitle(): string {
		return 'Redsys';
	}
	
	public function getDescription(): string {
		return $this->t('Redsys - Beschreibung');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getIcon(): string {
		return 'fas fa-credit-card';
	}
	
	public function getSettings(): array {
		return [
			'redsys_client_key' => [
				'label' => 'Client Key',
				'type' => 'input'
			],
			'redsys_merchant_code' => [
				'label' => 'Merchant Code',
				'type' => 'input'
			],
//			'redsys_merchant_name' => [
//				'label' => 'Merchant Name',
//				'type' => 'input'
//			],
			'redsys_merchant_terminal' => [
				'label' => 'Merchant Terminal',
				'type' => 'input'
			],
//			'redsys_product_description' => [
//				'label' => 'Product Description',
//				'type' => 'input'
//			],
			'redsys_testing' => [
				'label' => 'Test environment',
				'type' => 'checkbox'
			]
		];
	}

}