<?php

namespace TsFrontend\Handler\Payment;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Tc\Service\Language\Frontend;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * Testdaten für Playground:
 * Direct Debit: DE11520513735120710131
 * Bank transfer: Demo Bank
 * Credit Card: 4111 1111 1111 1111 / 123
 *
 * Alle anderen Werte sind mit irgendwelchen Zahlen aufzufüllen (z.B. OTP). E-Mails werden vom Playground KEINE verschickt.
 *
 * Testdaten für andere Fälle, z.B. DENIED:
 * @link https://docs.klarna.com/resources/test-environment/sample-data/#germany
 *
 * API schmeißt Error 400, wenn irgendein required Feld fehlt. Erst dann gibt es mal eine Response mit Fehlercode.
 *
 * Docs zur Implementierung:
 * @link https://docs.klarna.com/klarna-payments/api-call-descriptions/create-session/
 *
 * API-Docs:
 * @link https://docs.klarna.com/klarna-payments/api/payments-api/
 */
class Klarna implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm {

	use PaymentProviderTrait;

	private array $methods = [];

	public function getScriptUrl(): string {
		return 'https://x.klarnacdn.net/kp/lib/v1/api.js';
	}

	public function createPreliminaryPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description) {

		$contact = $inquiry->getCustomer();
		$address = $this->getContactAddress($contact);

		// Ohne Land oder Items funktioniert das eh nicht
		if (
			$items->isEmpty() ||
			empty($address->country_iso)
		) {
			return;
		}

		try {
			$payment = $this->createPayment($inquiry, $items, $description);
			$this->methods = $payment['payment_method_categories'];
		} catch (\Throwable $e) {
			$data = ['message' => $e->getMessage()];
			if ($e instanceof ClientException) {
				$data['response'] = (string)$e->getResponse()->getBody();
				$data['request'] = (string)$e->getRequest()->getBody();
			}
			$this->createLogger()->warning('Klarna createPreliminaryPayment failed', $data);
		}

	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array {

		$amount = $items->sum('amount_with_tax');
		$currency = $inquiry->getCurrency(true);
		$contact = $inquiry->getCustomer();
		$address = $this->getContactAddress($contact);

		// https://docs.klarna.com/klarna-payments/api/payments-api/#operation/createCreditSession
		$response = $this->createClient()->post('payments/v1/sessions', [
			'json' => [
				'locale' => $contact->corresponding_language.'-'.strtoupper($address->country_iso),
				'order_amount' => round($amount, 2) * 100,
				'order_lines' => $items->map(function (array $item) {
					$amount = round($item['amount_with_tax'], 2) * 100;
					return [
						'name' => $item['description'],
						'quantity' => 1,
						'total_amount' => $amount,
						'unit_price' => $amount
					];
				}),
				'purchase_country' => $address->country_iso,
				'purchase_currency' => $currency->getIso(),
				'billing_address' => [
					'email' => $contact->getFirstEmailAddress()->getEmail(),
					'given_name' => $contact->firstname,
					'family_name' => $contact->lastname,
					'street_address' => $address->address,
					'postal_code' => $address->zip,
					'city' => $address->city,
					'region' => $address->state,
					'country' => $address->country_iso
				],
				'customer' => [
					'date_of_birth' => $contact->birthday,
					'gender' => match ($contact->gender) {
						1 => 'male',
						2 => 'female',
						default => null
					}
				]
			]
		]);

		$payment = json_decode($response->getBody(), true);
		$payment['amount'] = \Ext_Thebing_Format::Number($amount, $currency, $this->school);

		return $payment;

	}

	public function checkPayment(Collection $data): bool {

		try {
			// https://docs.klarna.com/klarna-payments/api/#operation/readCreditSession
			$response = $this->createClient()->get('payments/v1/sessions/'.data_get($data, 'session_id'));
			$json = json_decode($response->getBody(), true);

			// Beim Order Place wechselt der Status auf complete (the credit session will be closed)
			if ($json['status'] !== 'incomplete') {
				throw $this->createError('Klarna checkPayment failed: Wrong status', [$data, $json]);
			}

		} catch (ClientException $e) {
			throw $this->createError('Klarna checkPayment failed:'.$e->getMessage(), [$data, (string)$e->getResponse()->getBody()]);
		}

		// Wirklich prüfen kann man das nicht, weil man den authorization_token nicht vergleichen kann
		if (empty($data['authorization_token'])) {
			throw $this->createError('Klarna checkPayment failed: No authorization_token', [$data, $json]);
		}

		return true;

	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated {

		$client = $this->createClient();

		try {
			// https://docs.klarna.com/klarna-payments/api/#operation/readCreditSession
			$response = $client->get('payments/v1/sessions/'.data_get($data, 'session_id'));
			$session = json_decode($response->getBody(), true);
		} catch (ClientException $e) {
			throw $this->createError('Klarna capturePayment read session failed: '.$e->getMessage(), [
				'data' => $data->toArray(),
				'response' => (string)$e->getResponse()->getBody()
			]);
		}

		// TODO Request per DI
		// Das muss gesetzt werden, obwohl es total irrelevant ist für den SPA-Flow
		$session['merchant_urls']['confirmation'] = app()->make(\Illuminate\Http\Request::class)?->headers->get('referer');

		try {
			// Klarna will die gleichen Daten nochmal
			// Scheinbar dient die Session eher für die Ermittelung der Zahlungsmethoden (fraud) und der Autorisierung eines Maximalbetrags
			// https://docs.klarna.com/klarna-payments/api/#operation/createOrder
			$response = $client->post('/payments/v1/authorizations/'.$session['authorization_token'].'/order', ['json' => $session]);
			$order = json_decode($response->getBody(), true);
		} catch (ClientException $e) {
			throw $this->createError('Klarna capturePayment order failed: '.$e->getMessage(), [
				'data' => $data->toArray(),
				'response' => (string)$e->getResponse()->getBody(),
				'request' => (string)$e->getRequest()->getBody()
			]);
		}

		$currency = \Ext_Thebing_Currency::getByIso($session['purchase_currency']);

		$comment = [];
		$comment[] = sprintf('Klarna payment (%s)', $order['authorized_payment_method']['type']);
		$comment[] = 'Fraud status: '.$order['fraud_status']; // TODO Was tun bei Status PENDING?
		$comment[] = 'Order-ID: '.$order['order_id'];

		$payment = new \Ext_TS_Inquiry_Payment_Unallocated();
		$payment->transaction_code = $order['order_id'];
		$payment->comment = join("\n", $comment);
		$payment->firstname = $session['billing_address']['given_name'];
		$payment->lastname = $session['billing_address']['family_name'];
		$payment->amount = (float)$session['order_amount'] / 100;
		$payment->amount_currency = (int)$currency->id;
		$payment->payment_date = (new \DateTime($data['paid_date']))->format('Y-m-d');
		$payment->additional_info = json_encode(['type' => 'klarna', '' => $data, 'session' => $session, 'order' => $order]);

		return $payment;

	}

	public function getTranslations(Frontend $languageFrontend): array {
		return [
			'pay_now' => $languageFrontend->translate('Pay now: {amount}')
		];
	}

	public function getPaymentMethods(Frontend $language): array {

		return array_map(function (array $method) {
			return [
				'key' => 'klarna_'.$method['identifier'],
				'label' => $method['name'],
				'alt' => 'Klarna: '.$method['name'],
				'icon' => $method['asset_urls']['standard']
			];
		}, $this->methods);

	}

	public function getSortOrder(): int {
		return 3;
	}

	/**
	 * API-URL ist je nach Kontinent und Test-Modus unterschiedlich
	 *
	 * @link https://docs.klarna.com/klarna-payments/api/payments-api/
	 * @return \GuzzleHttp\Client
	 */
	private function createClient(): \GuzzleHttp\Client {

		$url = [];
		$url[] = 'https://api';
		$url[] = match ($this->school->getMeta('klarna_api')) {
			'na' => '-na',
			'oc' => '-oc',
			default => '' // Kein Suffix für EU
		};

		if ($this->school->getMeta('klarna_playground')) {
			$url[] = '.playground';
		}

		$url[] = '.klarna.com/';

		return new \GuzzleHttp\Client([
			'base_uri' => join($url),
			'headers' => [
				'Authorization' => 'Basic '.base64_encode($this->school->getMeta('klarna_username').':'.$this->school->getMeta('klarna_password'))
			],
		]);

	}

}