<?php

namespace TsFrontend\Handler\Payment;

use Core\Facade\Cache;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\Language\Frontend;
use Ts\Events\Inquiry\PaymentFailed;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

class Flywire implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm, PaymentProvider\WebhookCapture
{
	use PaymentProviderTrait;

	private const LOCALES = [
		'en',
		'ja',
		'zh-CN',
		'es-ES',
		'fr-FR',
		'ko',
		'pt-PT',
		'cy-GB',
		'ar',
		'id',
		'vi'
	];

	public function getScriptUrl(): string
	{
		return '#';
	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array
	{
		if ($this->school->getMeta('flywire_demo_mode')) {
			$url = 'https://gateway.demo.flywire.com/v1/transfers.json';
		} else {
			$url = 'https://gateway.flywire.com/v1/transfers.json';
		}

		// Pseudo-ID generieren zur Identifikation, da es über den API-Request keine gibt
		$orderId = $this->school->getMeta('flywire_prefix').Str::random(8);
		$amount = round($items->sum('amount_with_tax'), 2);
		$contact = $inquiry->getCustomer();
		$address = $this->getContactAddress($contact);
		$locale = collect(self::LOCALES)->first(fn($l) => str_starts_with($l, $contact->corresponding_language), 'en');

		$payload = [
			//'provider' => $this->school->getMeta('flywire_prefix'),
			// Für die Ermittlung der Provision braucht Flywire hier für alle Kunden den Inhalt "Fidelo"
			'provider' => 'Fidelo',
			'payment_destination' => $this->school->getMeta('flywire_destination'),
			'amount' => $amount * 100,
			'country' => $address->country_iso,
			'locale' => $locale,
			'days_to_expire' => 1,
			'sender_email' => $contact->getFirstEmailAddress()->getEmail(),
			'sender_first_name' => $contact->firstname,
			'sender_last_name' => $contact->lastname,
			'sender_address1' => $address->address,
			'sender_address2' => $address->address_addon,
			'sender_city' => $address->city,
			'sender_state' => $address->state,
			'sender_zip' => $address->zip,
			'sender_phone' => $contact->getFirstPhoneNumber(),
			'allow_to_edit_payer_information' => true,
			'callback_version' => '2',
			'callback_url' => sprintf('https://%s/api/1.0/ts/frontend/webhook/payment/flywire_widget', \Util::getHost()),
			'callback_id' => $orderId,
			'dynamic_fields' => [
				'invoice_number' => $invoices?->map(fn (\Ext_Thebing_Inquiry_Document $invoice) => $invoice->document_number)->join(', ') ?? '',
				'student_id' => $contact->getCustomerNumber(),
				'student_first_name' => $contact->firstname,
				'student_last_name' => $contact->lastname,
				'student_email' => $contact->getFirstEmailAddress()->getEmail()
			]
		];

		$response = (new \GuzzleHttp\Client())->post($url, [
			'connect_timeout' => 30,
			'json' => $payload
		]);

		$json = json_decode($response->getBody(), true);

		$expiration = 3600 * 24 * $payload['days_to_expire'];

		// TODO Was ist wenn jemand den Cache leert? ::forever()
		// Da die Zahlung bei Flywire erst im weiteren Verlauf (nach diesem API-Request) erzeugt wird, muss der Status hier getrackt werden
		// Es gibt außerdem keine Möglichkeit eine Zahlung per API abzufragen, sondern es existieren nur die Webhooks
		Cache::put(self::buildCacheKey($orderId), $expiration, [
			'order_id' => $orderId,
			'inquiry_id' => $inquiry->id,
			'amount_raw' => $amount,
			'status' => 'pending',
			'url' => $json['url'],
			'expiration' => $expiration,
			'flywire' => $payload,
			'webhook' => null
		]);

		return [
			'order_id' => $orderId,
			'url' => $json['url'],
		];
	}

	public function checkPayment(Collection $data): bool
	{
		$cache = Cache::get($this->buildCacheKey($data->get('order_id')));
		if (empty($cache)) {
			throw $this->createError('Flywire payment expired or does not exist', $data->toArray());
		}

		return $cache['status'] === 'approved';
	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated
	{
		$cache = Cache::get($this->buildCacheKey($data->get('order_id')));
		if (empty($cache)) {
			throw $this->createError('Flywire payment expired or does not exist', $data->toArray());
		}

		if ($cache['status'] !== 'approved') {
			throw $this->createError('Flywire payment not approved', $data->toArray());
		}

		// Es müsste bereits ein initialisiertes $paymentUnallocated geben
		$paymentUnallocated = $this->searchUnallocatedPaymentByTransactionCode(data_get($cache, 'webhook.data.payment_id'));

		if($paymentUnallocated === null) {
			$paymentUnallocated = $this->createUnallocatedPayment($inquiry, $cache, $data, \Ext_TS_Inquiry_Payment_Unallocated::STATUS_REGISTERED);
		}

		$paymentUnallocated->status = \Ext_TS_Inquiry_Payment_Unallocated::STATUS_REGISTERED;
		$paymentUnallocated->payment_date = (new \DateTime($data['paid_date']))->format('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'flywire', 'payment' => $cache, 'data' => $data]);

		$this->updateUnallocatedPayment($inquiry, $paymentUnallocated, $cache, $data);

		Cache::forget($this->buildCacheKey($data->get('order_id')));

		return $paymentUnallocated;
	}

	protected function createUnallocatedPayment(\Ext_TS_Inquiry $inquiry, array $cache, Collection $data, string $status) {

		$currencyTo = \Ext_Thebing_Currency::getByIso(data_get($cache, 'webhook.data.currency_to')); // source_currency existert nicht in $payment/PaymentHistory

		$paymentUnallocated = new \Ext_TS_Inquiry_Payment_Unallocated();
		$paymentUnallocated->payment_method_id = $this->getAccountingPaymentMethod()->id;
		$paymentUnallocated->status = $status;
		$paymentUnallocated->transaction_code = data_get($cache, 'webhook.data.payment_id');
		$paymentUnallocated->firstname = $inquiry->getCustomer()->firstname;
		$paymentUnallocated->lastname = $inquiry->getCustomer()->lastname;
		$paymentUnallocated->amount = (float)$cache['amount_raw'];
		$paymentUnallocated->amount_currency = (int)$currencyTo->id;
		$paymentUnallocated->payment_date = date('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'flywire', 'payment' => $cache, 'data' => $data->toArray()]);
		$paymentUnallocated->inquiry_id = $inquiry->id;

		$this->updateUnallocatedPayment($inquiry, $paymentUnallocated, $cache, $data);

		return $paymentUnallocated;
	}

	protected function updateUnallocatedPayment(\Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Payment_Unallocated $paymentUnallocated, array $cache, Collection $data) {

		// "amount_from":"34400","currency_from":"CAD","amount_to":"20000","currency_to":"EUR"
		$currencyFrom = \Ext_Thebing_Currency::getByIso(data_get($cache, 'webhook.data.currency_from'));
		$currencyTo = \Ext_Thebing_Currency::getByIso(data_get($cache, 'webhook.data.currency_to'));
		$amountFrom = (float)(data_get($cache, 'webhook.data.amount_from') / 100);
		$amountTo = (float)(data_get($cache, 'webhook.data.amount_to') / 100);
		
		$comment = [];
		$comment[] = 'Flywire payment (Payment widget)';
		$comment[] = 'Payment ID: '.data_get($cache, 'webhook.data.payment_id');
		
		if($currencyFrom !== $currencyTo) {
			$comment[] = 'Original amount: '.\Ext_Thebing_Format::Number($amountFrom, $currencyFrom, $inquiry->getSchool());
		}
		
		$comment[] = 'Amount: '.\Ext_Thebing_Format::Number($amountTo, $currencyTo, $inquiry->getSchool());
		$comment[] = 'Method: '.data_get($cache, 'webhook.data.payment_method.type');

		$paymentUnallocated->comment = join("\n", $comment);
		
		// Betrag verändert?
		if (bccomp($paymentUnallocated->amount, $amountTo, 2) !== 0) {
			$paymentUnallocated->amount = $amountTo;
			$paymentUnallocated->amount_currency = (int)$currencyTo->id;
		}

	}

	public function getTranslations(Frontend $languageFrontend): array
	{
		return [
			'backdrop_description' => $languageFrontend->translate('Don\'t see the payment window?'),
			'redirect_description' => $languageFrontend->translate('Please click here to proceed with your payment.'),
			'focus_description' => $languageFrontend->translate('Please switch tabs to reactivate the payment window.'),
			'bank_transfer_button' => $languageFrontend->translate('Pay now by Bank Transfer')
		];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array
	{
		return [
			[
				'key' => 'flywire',
				'label' => $languageFrontend->translate('Flywire'),
				'icon_class' => 'fas fa-landmark'
			],
		];
	}

	public function captureByWebhook(Collection $data): Response
	{
		$type = $data->get('event_type');

		$this->createLogger()->info('Flywire webhook incoming', $data->toArray());

		$retrieveCache = function () use ($data) {
			$orderId = data_get($data, 'data.external_reference');
			$cacheKey = $this->buildCacheKey($orderId);

			$cache = Cache::get($cacheKey);
			if (empty($cache)) {
				throw $this->createError('No cached Flywire payment found for initiated payment', $data->toArray());
			}
			return [$cache, $cacheKey];
		};

		switch ($type) {
			// initiated wird gefeuert sobald im Fenster Next (Banktransfer) oder Pay ausgewählt wird; vorher gibt es keine registrierte Zahlung
			// Die Fidelo-Zahlung wird hier bereits als pending abgeschlossen, weil jede Zahlung von Flywire delayed ist
			case 'initiated':
				[$cache, $cacheKey] = $retrieveCache();
				$cache['status'] = 'approved';
				$cache['webhook'] = $data; // Webhook-Daten setzen, da man anders nicht an die Payment-Daten kommt
				Cache::put($cacheKey, $cache['expiration'], $cache);

				break;
			case 'processed':
				[$cache, ] = $retrieveCache();

				$inquiry = \Ext_TS_Inquiry::getInstance($cache['inquiry_id']);

				/*
				 * TODO oder schon bei "initiated"? createPreliminaryPayment()?
				 * Zahlung hier schon lokal speichern, da es durch Benutzerfehler
				 * passieren kann, dass es keine Rückmeldung mehr an Fidelo über die
				 * durchgeführte Zahlung gibt.
				 */
				$paymentUnallocated = $this->createUnallocatedPayment($inquiry, $cache, $data, \Ext_TS_Inquiry_Payment_Unallocated::STATUS_INITIALIZED);
				$paymentUnallocated->save();

				break;
			case 'guaranteed':
			case 'delivered':
			case 'failed':
			case 'cancelled':
				$payment = $this->searchPaymentByTransactionCode(data_get($data, 'data.payment_id'));
				if ($payment === null) {
					$this->createLogger()->error(__METHOD__.': Could not find any Flywire payment in system', $data->toArray());
					return response('ERROR (Invalid payment)', 422);
				}

				if (
					$type === 'guaranteed' ||
					$type === 'delivered'
				) {
					$payment->status = \Ext_Thebing_Inquiry_Payment::STATUS_PAID;
					$payment->save();

					if ($payment instanceof \Ext_TS_Inquiry_Payment_Unallocated) {
						$payment->writeFormPaymentTask();
					}
				} else {
					\System::setInterfaceLanguage($payment->getInquiry()->getSchool()->getLanguage());
					PaymentFailed::dispatch($payment->getInquiry());
					$payment->delete();
				}

				break;
			default:
				throw $this->createError('Unknown Flywire webhook', $data->toArray());
		}

		return response('OK');
	}

	private function buildCacheKey(string $id): string
	{
		return __CLASS__.$id;
	}
}
