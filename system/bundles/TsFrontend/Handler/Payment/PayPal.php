<?php

namespace TsFrontend\Handler\Payment;

use Illuminate\Support\Collection;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Tc\Service\Language\Frontend;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * PayPal API V2
 * https://developer.paypal.com/docs/checkout/
 */
class PayPal implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm {

	use PaymentProviderTrait;

	/**
	 * @inheritdoc
	 * @link https://developer.paypal.com/docs/checkout/reference/customize-sdk
	 */
	public function getScriptUrl(): string {

		$currency = \Ext_Thebing_Currency::getInstance($this->school->getCurrency());

		$params = [
			'client-id' => $this->school->getMeta('paypal_client_id'),
			'currency' => $currency->getIso(),
			'intent' => 'capture',
			// https://developer.paypal.com/docs/checkout/reference/customize-sdk/#disable-funding
			// credit: PayPal Ratenzahlung; Pass credit in disable-funding […]: Non-US merchants who do not have the correct licenses and approvals to display the Credit button
//			'disable-funding' => 'credit',
			'integration-date' => '2021-02-01',
			'debug' => 'true'
		];

//		if ($this->option === 'paypal_card') {
//			$params['enable-funding'] = 'card';
//			$params['disable-funding'] = 'credit,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sepa,sofort,venmo';
//		} else {
			$params['disable-funding'] = 'credit';
//		}

		return 'https://www.paypal.com/sdk/js?'.http_build_query($params);

	}

	/**
	 * @inheritdoc
	 * @link https://developer.paypal.com/docs/checkout/integrate/#4-set-up-the-transaction Integration
	 * @link https://developer.paypal.com/docs/checkout/reference/server-integration/set-up-transaction/ Integration
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_create API
	 * @throws \PayPalHttp\HttpException
	 */
	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array {

		$currencyIso = $inquiry->getCurrency(true)->getIso();

		$total = 0;
		$itemTotal = 0;
		$totalDiscount = 0;
		$items = $items->map(function (array $item) use ($currencyIso, &$total, &$totalDiscount, &$itemTotal) {
			// PayPal nimmt nicht mehr als zwei Nachkommastellen an und die Summe ($total) muss am Ende aufgehen
			$amount = round($item['amount_with_tax'], 2);
			if ($amount < 0) { // discount
				$totalDiscount += $amount;
				return null;
			} else {
				$total += $amount;
				$itemTotal += $amount;
				return [
					'name' => $item['description'],
					'unit_amount' => [
						'currency_code' => $currencyIso,
						'value' => $amount,
					],
					'quantity' => 1
				];
			}
		})->filter();
		$total += $totalDiscount;
		$contact = $inquiry->getCustomer();

		$payer = [
			'name' => [
				'given_name' => $contact->firstname,
				'surname' => $contact->lastname
			]
		];

		// E-Mail vorausfüllen (PayPal-Login oder andere Zahlungsart)
		// Darf nicht leer gesetzt werden
		$email = $contact->getFirstEmailAddress()->getEmail();
		if ($email) {
			$payer['email_address'] = $email;
		}

		$address = $this->getContactAddress($contact);

		// Adresse vorbefüllen für andere Zahlungsarten
		// Darf nicht ohne country_code gesetzt werden
		if (!empty($address->country_iso)) {
			$payer['address'] = [
				'address_line_1' => $address->address,
				'address_line_2' => $address->address_addon,
				'admin_area_2' => $address->city,
				'admin_area_1' => $address->state,
				'postal_code' => $address->zip,
				'country_code' => $address->country_iso
			];
		}

		$body = [
			'intent' => 'CAPTURE',
			'payer' => $payer,
			'purchase_units' => [
				[
					'amount' => [
						'currency_code' => $currencyIso,
						'value' => $total,
						'breakdown' => [
							'item_total' => [
								'currency_code' => $currencyIso,
								'value' => $itemTotal
							]
						]
					],
					'description' => $description,
					'items' => $items->toArray()
				]
			],
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
				'user_action' => 'PAY_NOW' // TODO Sollte fürs RegForm vlt. CONTINUE sein, aber irgendwie sieht beides gleich aus
			]
		];

		if ($totalDiscount < 0) {
			$body['purchase_units'][0]['amount']['breakdown']['discount'] = [
				'currency_code' => $currencyIso,
				'value' => round(abs($totalDiscount), 2)
			];
		}

		$client = $this->createClient();

		$request = new OrdersCreateRequest();
		$request->prefer('return=representation');
		$request->body = $body;
		$response = $client->execute($request);

		return [
			'order_id' => $response->result->id
		];

	}

	/**
	 * @TODO Evtl. einbauen, dass PayPalHttp\HttpException abgefangen wird (z.B. bei $data = EMPTY)
	 *
	 * @inheritdoc
	 * @link https://developer.paypal.com/docs/checkout/reference/server-integration/get-transaction/#on-the-server Integration
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_get API
	 * @throws \PayPalHttp\HttpException
	 * @throws PaymentError
	 */
	public function checkPayment(Collection $data): bool {

		$client = $this->createClient();

		$request = new OrdersGetRequest($data['orderID']);
		$response = $client->execute($request);

		// status === COMPLETED ist kein gültiger Wert hier, da die Bezahlung dann bereits verwendet wurde
		// Das dürfte eigentlich nur vorkommen, wenn man das Formular beim Debugging nach Submit wieder submittet
		if ($response->result->status !== 'APPROVED') {
			throw $this->createError('PayPal check payment status: '.$response->result->status, [$request, $response]);
		}

		return true;

	}

	/**
	 * @inheritdoc
	 * @link https://developer.paypal.com/docs/checkout/reference/server-integration/capture-transaction/ Integration
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_capture API
	 * @throws \PayPalHttp\HttpException
	 * @throws PaymentError
	 */
	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated {

		$client = $this->createClient();

		$request = new OrdersCaptureRequest($data['orderID']);
		$request->prefer('return=representation');
		$response = $client->execute($request);

		if ($response->result->status !== 'COMPLETED') {
			throw $this->createError('PayPal capture payment status: '.$response->result->status, [$request, $response]);
		}

		$currency = \Ext_Thebing_Currency::getByIso($response->result->purchase_units[0]->amount->currency_code);
		$language = new \Tc\Service\Language\Frontend($this->school->getLanguage());

		// Das ist der gleiche Code, den man auch in PayPal unter Transaktionsdetails sehen kann
		$transactionCode = $response->result->purchase_units[0]->payments->captures[0]->id;

		$comment = [];
		$comment[] = sprintf('%s: %s', $language->translate('E-mail'), $response->result->payer->email_address);
		$comment[] = sprintf('%s: %s', $language->translate('Transaction'), $transactionCode);
		$comment[] = sprintf('%s: %s', $language->translate('Payer-ID'), $response->result->payer->payer_id);

		$payment = new \Ext_TS_Inquiry_Payment_Unallocated();
		$payment->transaction_code = $transactionCode;
		$payment->comment = join("\n", $comment);
		$payment->firstname = $response->result->payer->name->given_name;
		$payment->lastname = $response->result->payer->name->surname;
		$payment->amount = $response->result->purchase_units[0]->amount->value;
		$payment->amount_currency = $currency->id;
		$payment->payment_date = (new \DateTime($response->result->update_time))->format('Y-m-d');
		$payment->additional_info = json_encode(['type' => 'paypal', 'order' => $response->result]);

		return $payment;

	}

	/**
	 * Objekt für PayPal-Authorisierung erzeugen
	 *
	 * @return PayPalHttpClient
	 */
	private function createClient(): PayPalHttpClient {

		if ($this->school->getMeta('paypal_client_sandbox')) {
			$env = new SandboxEnvironment($this->school->getMeta('paypal_client_id'), $this->school->getMeta('paypal_client_secret'));
		} else {
			$env = new ProductionEnvironment($this->school->getMeta('paypal_client_id'), $this->school->getMeta('paypal_client_secret'));
		}

		return new PayPalHttpClient($env);

	}

	public function getTranslations(Frontend $languageFrontend): array {
		return [];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array {
		return [
			[
				'key' => 'paypal',
				'label' => $languageFrontend->translate('PayPal'),
				'alt' => $languageFrontend->translate('PayPal'),
				'icon' => 'https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png',
				'icon_only' => true
			],
		];
	}

	public function getSortOrder(): int {
		return 2;
	}

}