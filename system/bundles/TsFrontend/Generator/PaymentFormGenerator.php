<?php

namespace TsFrontend\Generator;

use Carbon\Carbon;
use Core\Entity\ParallelProcessing\Stack;
use Illuminate\Support\Collection;
use Tc\Service\Language\Frontend as FrontendLanguage;
use TcFrontend\Dto\WidgetPath;
use TcFrontend\Factory\WidgetPathHashedFactory;
use TcFrontend\Interfaces\WidgetCombination;
use TcFrontend\Traits\WidgetCombinationTrait;
use Ts\Entity\Payment\PaymentProcess;
use TsFrontend\Interfaces\PaymentProvider\PaymentProvider;

/**
 * @see \TsFrontend\Controller\PaymentFormController
 */
class PaymentFormGenerator extends \Ext_TC_Frontend_Combination_Abstract implements WidgetCombination {

	use WidgetCombinationTrait;

	public function initCombination(\Illuminate\Http\Request $request, string $language = null) {

		\Core\Handler\SessionHandler::disableSession();

		if (empty($language)) {
			$language = \Ext_Thebing_Client::getFirstSchool()->getLanguage();
		}

		parent::initCombination($request, $language);

	}

	public function getWidgetPaths(): array {
		return [
			'api' => new WidgetPath('api/1.0/ts/frontend/payment', '', 'ts-payment-form:api')
		];
	}

	public function getWidgetScripts(): array {

//		// Stripe-Implementierung
//		$polyfills = ['Object.entries', 'Object.values', 'Promise'];

		return [
//			'https://polyfill.io/v3/polyfill.min.js?features='.urlencode(join(',', $polyfills)),
			(new WidgetPathHashedFactory('assets/tc-frontend', 'js/payment-form.js', 'ts-payment-form', 'TcFrontend:assets/'))->create()
		];

	}

	public function getWidgetStyles(): array {

		$styles = [];

		if ($this->isUsingBundle() || $this->isUsingIframe()) {
			$styles[] = (new WidgetPathHashedFactory('assets/tc-frontend', 'css/payment-form-bootstrap4.css', 'ts-payment-form', 'TcFrontend:assets/'))->create();
		}

		$styles[] = (new WidgetPathHashedFactory('assets/tc-frontend', 'css/payment-form.css', 'ts-payment-form', 'TcFrontend:assets/'))->create();

		return $styles;

	}

	public function getWidgetData($checkCacheIgnore = false): array {

		$language = $this->getLanguage();

		return [
			'key' => $this->getCombination()->key,
			'language' => $language->getLanguage(),
			'translations' => [
				'internal_error' => $language->translate('Internal Server Error'),
				'loading' => $language->translate('Loading'),
				'payment' => $language->translate('Payment'),
				'customer' => $language->translate('Customer'),
				'invoice' => $language->translate('Invoice'),
				'payment_details' => $language->translate('Payment Details'),
				'amount_total' => $language->translate('Amount total'),
				'amount_payed' => $language->translate('Payed'),
				'amount_due' => $language->translate('Due amount'),
				'date_due' => $language->translate('Due to'),
				'pay_full_amount' => $language->translate('Pay full amount instead'),
			]
		];
	}

	public function getLanguage(): FrontendLanguage {

		$language = new FrontendLanguage(\System::getInterfaceLanguage());
		$language->setContext('Fidelo » Payment Form');
		return $language;

	}

	public function buildCustomerAddress(\Ext_TS_Contact $contact): array {

		$address = $contact->getAddress('billing');
		if ($address->isEmpty()) {
			$address = $contact->getAddress('contact');
		}

		$country = '';
		if (!empty($address->country_iso)) {
			$countries = (new \Core\Service\LocaleService())->getCountries(\System::getInterfaceLanguage());
			$country = $countries[$address->country_iso];
		}

		$address = array_filter([
			$contact->firstname.' '.$contact->lastname,
			$address->address,
			$address->address_addon,
			$address->state,
			$address->zip.' '.$address->city,
			strtoupper($country)
		], function ($line) {
			return !empty($line);
		});

		return $address;

	}

	public function getWidgetPassParams(): array {
		return [
			'query:payment'
		];
	}

}