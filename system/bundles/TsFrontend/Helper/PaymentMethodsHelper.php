<?php

namespace TsFrontend\Helper;

use Illuminate\Support\Collection;
use TcFrontend\Interfaces\WidgetCombination;
use TsFrontend\Factory\PaymentFactory;
use TsFrontend\Interfaces\PaymentProvider\PaymentProvider;

class PaymentMethodsHelper {

	/**
	 * @var Collection|PaymentProvider[]|null
	 */
	private ?Collection $providers = null;

	private ?array $method = null;

	private WidgetCombination $combination;

	public function __construct(WidgetCombination $combination) {
		$this->combination = $combination;
	}

	public function setMethod(array $data) {
		$this->method = $data;
	}

	public function generatePaymentProviders(Collection $providers, \Ext_Thebing_School $school) {

//		if ($this->providers !== null && $this->providers->isNotEmpty()) {
//			return $this->providers;
//		}

		/** @var Collection|PaymentProvider[] $providers */
		$providers = $providers->mapWithKeys(function (string $provider) use ($school) {
			return [$provider => (new PaymentFactory())->make($provider, $school)];
		});

		$this->providers = $providers->sort(function (PaymentProvider $handler1, PaymentProvider $handler2) {
			return $handler1->getSortOrder() > $handler2->getSortOrder();
		});

		return $this->providers;

	}

	public function generatePaymentMethods(\Ext_TS_Inquiry $inquiry, Collection $items, string $description): Collection {

		$methods = collect();
		foreach ($this->providers as $key => $handler) {

			// TODO Ersetzen mit explizitem preliminary-Flag
			if (empty($this->method)) {
				$handler->createPreliminaryPayment($inquiry, $items, $description);
			}

			foreach ($handler->getPaymentMethods($this->combination->getLanguage()) as $paymentMethod) {
				$paymentMethod['provider'] = $key;
				$paymentMethod['component'] = $handler->getComponentName();
				$paymentMethod['translations'] = $handler->getTranslations($this->combination->getLanguage()) ?: new \stdClass();
				$paymentMethod['url'] = $handler->getScriptUrl();
				$methods[] = $paymentMethod;
			}

		}

		return $methods;

	}

	public function createPaymentHandler(): PaymentProvider {

		$provider = $this->method['provider'];
		$handler = $this->providers->get($provider);

		if (!$handler instanceof PaymentProvider) {
			throw new \RuntimeException('Handler not found: '.$provider);
		}

		$handler->setPaymentMethod($this->method);

		return $handler;

	}

}
