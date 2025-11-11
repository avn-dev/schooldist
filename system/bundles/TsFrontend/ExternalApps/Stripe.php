<?php

namespace TsFrontend\ExternalApps;

class Stripe extends \Ts\Handler\ExternalAppPerSchool {
	
	public function getTitle(): string {
		return 'Stripe Card';
	}
	
	public function getDescription(): string {
		return $this->t('Stripe - Beschreibung');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getIcon(): string {
		return 'fa fa-cc-stripe';
	}
	
	public function getSettings(): array {
		return [
			'stripe_api_test_mode' => [
				'label' => 'Test Mode',
				'type' => 'checkbox'
			],
			'stripe_api_key_public' => [
				'label' => 'Live API Key (public)',
				'type' => 'input'
			],
			'stripe_api_key' => [
				'label' => 'Live API Key (secret)',
				'type' => 'password'
			],
			'stripe_test_api_key_public' => [
				'label' => 'Test API Key (public)',
				'type' => 'input'
			],
			'stripe_test_api_key' => [
				'label' => 'Test API Key (secret)',
				'type' => 'password'
			],
//			'stripe_item_title' => [
//				'label' => 'Item Title (Form V2)',
//				'type' => 'input'
//			],
//			'stripe_item_description' => [
//				'label' => 'Item Description (Form V2)',
//				'type' => 'input'
//			],
		];
	}

}