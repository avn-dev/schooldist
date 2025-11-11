<?php

namespace TsFrontend\Factory;

use TsFrontend\Handler\Payment;
use TsFrontend\Interfaces\PaymentProvider\PaymentProvider;

class PaymentFactory {

	private const HANDLERS = [
		Payment\Skip::KEY => [
			'handler' => Payment\Skip::class
		],
		'paypal' => [
			'external_app' => \TsFrontend\ExternalApps\PayPal::class,
			'handler' => Payment\PayPal::class
		],
		'stripe' => [
			'external_app' => \TsFrontend\ExternalApps\Stripe::class,
			'handler' => Payment\Stripe::class
		],
		\TsFrontend\ExternalApps\TransferMate::KEY => [
			'external_app' => \TsFrontend\ExternalApps\TransferMate::class,
			'handler' => Payment\TransferMate::class
		],
		'klarna' => [
			'external_app' => \TsFrontend\ExternalApps\Klarna::class,
			'handler' => Payment\Klarna::class
		],
		'moneris' => [
			'external_app' => \TsFrontend\ExternalApps\Moneris::class,
			'handler' => Payment\Moneris::class
		],
		'redsys' => [
			'external_app' => \TsFrontend\ExternalApps\Redsys::class,
			'handler' => Payment\Redsys::class
		],
		\TsFlywire\Handler\ExternalAppWidget::APP_NAME => [
			'external_app' => \TsFlywire\Handler\ExternalAppWidget::class,
			'handler' => Payment\Flywire::class
		]
	];

	public function make(string $handler, \Ext_Thebing_School $school = null): PaymentProvider {

		if (isset(self::HANDLERS[$handler])) {

			/** @var PaymentProvider $instance */
			$instance = app()->make(self::HANDLERS[$handler]['handler']);
			$instance->setKey($handler);

			if ($school) {
				$instance->setSchool($school);
			}

			return $instance;

		}

		throw new \InvalidArgumentException('Unknown handler: ' . $handler);

	}

	/**
	 * @param string|null $interface Interface-Klasse
	 * @return PaymentProvider[]
	 */
	public function getOptions(string $interface = null) {

		$options = [];

		foreach (self::HANDLERS as $key => $config) {
			if (
				(empty($interface) || is_subclass_of($config['handler'], $interface)) &&
				\TcExternalApps\Service\AppService::hasApp($key)
			) {
				$title = $key;
				if (class_exists($config['external_app'])) {
					/** @var \TcExternalApps\Interfaces\ExternalApp $class */
					$class = new $config['external_app']();
					$title = $class->getTitle();
				}
				$options[$key] = $title;
			}
		}

		return $options;

	}

}