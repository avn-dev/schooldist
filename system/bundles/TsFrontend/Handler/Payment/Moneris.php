<?php

namespace TsFrontend\Handler\Payment;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Tc\Service\Language\Frontend;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * @link https://developer.moneris.com/livedemo/checkout/overview/guide/php
 * @link https://developer.moneris.com/en/More/Testing/Testing%20a%20Solution
 */
class Moneris implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm
{

	use PaymentProviderTrait;

	public function getScriptUrl(): string
	{
		if ($this->school->getMeta('moneris_testing')) {
			return 'https://gatewayt.moneris.com/chktv2/js/chkt_v2.00.js';
		}

		return 'https://gateway.moneris.com/chktv2/js/chkt_v2.00.js';
	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array
	{
		$amount = $items->sum('amount_with_tax');
		$contact = $inquiry->getCustomer();

		$payload = [
			'txn_total' => number_format($amount, 2, '.', ''), // Explizit als "0.00"
			'action' => 'preload',
			'contact_details' => [
				'first_name' => $contact->firstname,
				'last_name' => $contact->lastname,
				'email' => $contact->getFirstEmailAddress()->getEmail(),
				'phone' => $contact->getFirstPhoneNumber()
			]
		];

		if ($contact->exist()) {
			$payload['cust_id'] = $contact->getCustomerNumber();
		}

		$address = $this->getContactAddress($contact);
		if ($address->isFilled()) {
			$payload['billing_details'] = [
				'address_1' => $address->address,
				'address_2' => $address->address_addon,
				'city' => $address->city,
				'province' => strlen($address->state) === 2 ? $address->country_iso : '',
				'country' => strlen($address->country_iso) === 2 ? $address->country_iso : '',
				'postal_code' => $address->zip
			];
		}

		$response = $this->requestCheckoutApi($payload);

		if (empty($response['response']['ticket'])) {
			throw $this->createError('Moneris createPayment: No ticket', $response);
		}

		return [
			'mode' => $this->school->getMeta('moneris_testing') ? 'qa' : 'prod',
			'ticket' => $response['response']['ticket']
		];

	}

	public function checkPayment(Collection $data): bool
	{
		$response = $this->requestCheckoutApi([
			'ticket' => $data->get('ticket'),
			'action' => 'receipt'
		]);

		if (
			empty($response['response']['receipt']['result']) ||
			$response['response']['receipt']['result'] !== 'a'
		) {
			throw $this->createError('Moneris checkPayment receipt failed', $response);
		}

		return true;
	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated
	{
		$response = $this->requestCheckoutApi([
			'ticket' => $data->get('ticket'),
			'action' => 'receipt'
		]);

		if (
			empty($response['response']['receipt']['result']) ||
			$response['response']['receipt']['result'] !== 'a'
		) {
			throw $this->createError('Moneris capturePayment receipt failed', $response);
		}

		$orderId = data_get($response, 'response.receipt.cc.order_no');
		$amount = data_get($response, 'response.receipt.cc.amount');
		$transactionNumber = data_get($response, 'response.receipt.cc.transaction_no');

		$response2 = $this->requestXmlCapture($orderId, $amount, $transactionNumber);

		if (!str_contains($response2['Message'], 'APPROVED')) {
			throw $this->createError('Moneris capturePayment XML capture failed', [$response, $response2]);
		}

		$comment = [];
		$comment[] = 'Moneris payment';
		$comment[] = 'Checkout ticket ID: '.data_get($response, 'response.request.ticket');
		$comment[] = 'Order ID: '.$orderId;

		$paymentUnallocated = new \Ext_TS_Inquiry_Payment_Unallocated();
//		$paymentUnallocated->inquiry_id = $inquiry->id;
		$paymentUnallocated->payment_method_id = $this->getAccountingPaymentMethod()->id;
		$paymentUnallocated->status = \Ext_Thebing_Inquiry_Payment::STATUS_PAID;
		$paymentUnallocated->transaction_code = data_get($response, 'response.request.ticket');
		$paymentUnallocated->comment = join("\n", $comment);
		$paymentUnallocated->firstname = data_get($response, 'response.request.cust_info.first_name');
		$paymentUnallocated->lastname = data_get($response, 'response.request.cust_info.last_name');
		$paymentUnallocated->amount = (float)$amount;
		$paymentUnallocated->amount_currency = $inquiry->getCurrency();
		$paymentUnallocated->payment_date = (new \DateTime($data['paid_date']))->format('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'moneris', 'payment' => $response['response'], 'payment_capture' => $response2, 'data' => $data]);

		return $paymentUnallocated;
	}

	public function getTranslations(Frontend $languageFrontend): array
	{
		return [];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array
	{
		return [
			[
				'key' => 'moneris',
				'label' => $languageFrontend->translate('Moneris'),
				'icon_class' => 'fas fa-credit-card'
			]
		];
	}

	private function requestCheckoutApi(array $payload): array
	{
		$payload = [
			'store_id' => $this->school->getMeta('moneris_store_id'),
			'api_token' => Crypt::decrypt($this->school->getMeta('moneris_api_token')),
			'checkout_id' => $this->school->getMeta('moneris_checkout_id'),
			'environment' => $this->school->getMeta('moneris_testing') ? 'qa' : 'prod',
			...$payload
		];

		$url = 'https://gateway.moneris.com/chktv2/request/request.php';
		if ($this->school->getMeta('moneris_testing')) {
			$url = 'https://gatewayt.moneris.com/chktv2/request/request.php';
		}

		$response = (new \GuzzleHttp\Client())->post($url, [
			'connect_timeout' => 30,
			'json' => $payload
		]);

		$json = json_decode($response->getBody(), true);

		$this->createLogger()->info(__METHOD__.' API', ['request' => $payload, 'response' => $json]);

		return $json;
	}

	private function requestXmlCapture(string $orderId, string $amount, string $transactionNumber): array
	{
		$xml = new \SimpleXMLElement("<request/>");
		$xml->addChild('store_id', $this->school->getMeta('moneris_store_id'));
		$xml->addChild('api_token', Crypt::decrypt($this->school->getMeta('moneris_api_token')));

		$type = $xml->addChild('completion');
		$type->addChild('order_id', $orderId);
		$type->addChild('comp_amount', $amount);
		$type->addChild('txn_number', $transactionNumber);
		$type->addChild('crypt_type', 7); // SSL-enabled merchant

		$url = 'https://www3.moneris.com/gateway2/servlet/MpgRequest';
		if ($this->school->getMeta('moneris_testing')) {
			$url = 'https://esqa.moneris.com/gateway2/servlet/MpgRequest';
		}

		$body = $xml->asXML();
		$response = (new \GuzzleHttp\Client())->post($url, [
			'connect_timeout' => 30,
			'headers' => [
				'Content-Type' => 'text/xml; charset=utf-8',
			],
			'body' => $body
		]);

		$xml = simplexml_load_string($response->getBody());
		$json = json_decode(json_encode($xml->receipt), true);

		$this->createLogger()->info(__METHOD__.' XML API', ['request' => $body, 'response' => $json]);

		return $json;
	}
}