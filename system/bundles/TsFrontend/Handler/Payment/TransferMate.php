<?php

namespace TsFrontend\Handler\Payment;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\Language\Frontend;
use Ts\Events\Inquiry\PaymentFailed;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Service\TransferMateApi;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * @link https://redmine.fidelo.com/projects/ts-frontend/wiki/TransferMate
 *
 * Testdaten:
 * VISA Credit Card: 4444333322221111
 * MC Credit Card: 5555555555554444
 *
 * Enter AUTHORISED as Name for successful CC payment.
 * Enter REFUSED for declined CC.
 *
 * CVV – any 3/4 digit code
 * Exp. Date – any date in future.
 *
 * Webhooks werden nur geschickt, wenn eine Default Webhook URL bei TransferMate vorhanden ist (muss beantragt werden)!
 * Es gibt allerdings auch ein Webhook Sandbox Interface (siehe KeePass), wo man diese manuell auslösen kann.
 *
 * @see TransferMateApi
 */
class TransferMate implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm, PaymentProvider\WebhookCapture {

	use PaymentProviderTrait;

	private array $preliminaryPayload = [];

	public function getScriptUrl(): string {
		return '#';
	}

	public function createPreliminaryPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description) {

		#$this->createLogger()->info(__METHOD__);
		
		$config = (new \Core\Helper\Bundle())->readBundleFile('TsFrontend', 'transfermate');

		$api = $this->createApi();
		$contact = $inquiry->getCustomer();
		$address = $this->getContactAddress($contact);
		$countries = $api->getCountries();

		// Daten ans Frontend für das spezielle TransferMate-Formular
		$this->preliminaryPayload = [
			'config' => [
				'id_validation' => $config['id_validation'],
				'countries' => $countries,
				'states' => $config['states'],
				'tos_url' => 'https://transfermateeducation.com/student-terms-of-use.aspx'
			],
			'values' => [
				'payer_type' => null, // Nicht von TransferMate
				'country_pay_from' => null,
				'india_fee_exceed' => 0,
				'state' => $address->state,
				'tos' => false, // Nicht von API
				'who_is_making_the_payment' => null,
				'payer_name' => null,
				'payer_chinese_name' => null,
				'payer_nationality' => null,
				'payer_city' => null,
				'payer_address' => null,
				'payer_postal_code' => null,
				'payer_phone_number' => null,
				'payer_email' => null,
				'payer_unique_id' => null,
				'payer_document' => null,
				'student_name' => $contact->getEverydayName(),
				'student_chinese_name' => null,
				'student_country' => $address->country_iso,
				'student_city' => $address->city,
				'student_address' => trim($address->address.' '.$address->address_addon),
				'student_postal_code' => $address->zip,
				'student_phone_number' => $contact->getFirstPhoneNumber(),
				'student_dob' => $contact->birthday,
				'student_email' => $contact->getFirstEmailAddress()->getEmail(),
				'student_document' => $inquiry->social_security_number
			]
		];

	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array {

		#$this->createLogger()->info(__METHOD__);
		
		$amount = round($items->sum('amount_with_tax'), 2);

		try {
			if (empty($this->method['page'])) {
				return ['methods' => $this->createTransferMateMethods($amount)];
			}

			// document_upload_url wird nicht beachtet, aber es wird eh nicht mehr zurückgeleitet (angeblich auch nur für flow=redirect)
			$payment = $this->createTransferMatePayment($inquiry, $amount, $description);

			/*
			 * Zahlung hier schon lokal speichern, da es durch Benutzerfehler 
			 * passieren kann, dass es keine Rückmeldung mehr an Fidelo über die 
			 * durchgeführte Zahlung gibt.
			 */
			$paymentUnallocated = $this->createUnallocatedPayment($inquiry, (float)$payment['source_amount'], $payment['source_currency'], $payment, \Ext_TS_Inquiry_Payment_Unallocated::STATUS_INITIALIZED);
			$paymentUnallocated->save();
			
		} catch (PaymentError $e) {
			return $this->createErrorResponse($e);
		}

		return $payment;
	}

	private function createTransferMateMethods(float $amount): Collection {

		#$this->createLogger()->info(__METHOD__);
		
		$api = $this->createApi();

		return $api->getPaymentMethods($this->school->getMeta('transfermate_bank_account_id'), $amount, $this->method['additional']['country_pay_from'])
			->transform(function (array $method) {
				$method['key'] = 'transfermate_'.$method['payment_method_id'];
				$method['label'] = $method['type'];
				$method['alt'] = $method['type'];
				$method['subtitle'] = sprintf('%s %01.2f', $method['converted_currency'], $method['converted_amount']);
				if ($method['locked']) {
					$method['subtitle'] = $method['lock_reason'];
				}
				return $method;
			});

	}

	private function createTransferMatePayment(\Ext_TS_Inquiry $inquiry, float $amount, string $description): array {

		#$this->createLogger()->info(__METHOD__);
		
		$api = $this->createApi();
		$contact = $inquiry->getCustomer();
		$data = $this->method['additional'];

		// Order-ID wird für Webhooks benötigt und muss bei _jedem_ MakeAPayment einmalig sein (also auch beim Wechsel der Zahlungsmethode)
		$orderId = 'FIDELO-'.(int)$inquiry->id.'-';
		$orderId = $orderId.Str::random(32 - strlen($orderId));

		$payload = [
			'payment_amount' => $amount,
			'country_pay_from' => $data['country_pay_from'],
			'state' => $data['state'],
			'payment_purpose' => $description,
			'payment_method' => $data['method']['payment_method_id'],
			'edu_institute_name' => $this->school->ext_1,
			'order_id' => $orderId,
			'webhook_url' => sprintf('https://%s/api/1.0/ts/frontend/webhook/payment/transfermate', \Util::getHost()), // Immer überschreiben, aber Default Webhook URL muss bei TransferMate gesetzt sein
			'who_is_making_the_payment' => $data['who_is_making_the_payment'],
			'india_fee_exceed' => $data['india_fee_exceed'],
			'payer_name' => $data['payer_name'],
			'payer_chinese_name' => $data['payer_chinese_name'],
			'payer_nationality' => $data['payer_nationality'],
			'payer_city' => $data['payer_city'],
			'payer_address' => $data['payer_address'],
			'payer_postal_code' => $data['payer_postal_code'],
			'payer_phone_number' => $data['payer_phone_number'],
			'payer_email' => $data['payer_email'],
			'payer_unique_id' => $data['payer_type'] === 'student' && $contact->exist() ? $contact->id : '',
			'payer_document' => $data['payer_document'],
			'student_name' => $data['student_name'],
			'student_chinese_name' => $data['student_chinese_name'],
			'student_country' => $data['student_country'],
			'student_city' => $data['student_city'],
			'student_address' => $data['student_address'],
			'student_postal_code' => $data['student_postal_code'],
			'student_phone_number' => $data['student_phone_number'],
			'student_dob' => $data['student_dob'],
			'student_number' => $contact->getCustomerNumber() ?? 'NONE',
			'student_email' => $data['student_email'],
			'student_unique_id' => $contact->exist() ? $contact->id : '',
			'student_document' => $data['student_document']
		];

		$payment = $api->createPayment($this->school->getMeta('transfermate_bank_account_id'), $payload);

		// Styles direkt anwenden, damit das auch nicht nur im Payment-Kontext im Frontend funktioniert
		if (!empty($payment['instructions'])) {
			$payment['instructions'] = <<<css
			<style>
				#pts-payment-instructions { margin-bottom: 1rem; }
				#pts-payment-instructions .emphasis { font-style: italic; }
				#pts-payment-instructions .center { text-align: center; }
				#pts-payment-instructions .important-color { color: #dc3545; }
				#pts-payment-instructions .bank-details { width: 100%; }
			</style>
			{$payment['instructions']}
			css;
		}

		$this->createLogger()->info('TransferMate createPayment', ['payload' => $payload, 'payment' => $payment, 'method' => $this->method]);

		return $payment;

	}

	public function checkPayment(Collection $data): bool {

		$this->createLogger()->info('checkPayment', $data->toArray());

		$api = $this->createApi();
		$payment = $api->findPaymentById((int)$data['id']);

		return $this->checkTransfermatePayment($payment);

	}

	private function checkTransfermatePayment(array $payment): bool {

		return
			// 0 bedeutet Bank Transfer und das kann hier niemals PAID sein
			(int)$payment['method_id'] === 0 ||
			(int)$payment['third_party_status_id'] === TransferMateApi::STATUS_THIRDPARTY_SUCCESSFUL ||
			(int)$payment['status_id'] === TransferMateApi::STATUS_PAID;

	}

	/**
	 * $data = MakeAPayment Response + method
	 *
	 *
		$data = Array
		(
			[id] => 1116109
			[type] => VISA Credit
			[flow] => redirect
			[logo] => https://transfermateeducation.com/images/pm/logo/visa.png
			[redirect_url] => https://fidelo-xml-service.transfermateeducation.com/payments-getaway-api.aspx?token=hSAPHG-YqiGRxFM1pdyxdg
			[ref_number] => FIDELO-XML-SERVICECPS-1300
			[source_amount] => 15
			[source_currency] => EUR
			[converted_amount] => 30.71
			[converted_currency] => BGN
			[fx_rate] => 0.49255106823459
			[taxes_total_amount] => 0.25
			[document_upload_url] => ''
			[taxes] => Array
				(
					[@attributes] => Array
						(
							[total] => 1
						)
					[tax] => Array
						(
							[name] => Merchant Fee
							[amount] => 0.25
							[percentage] => 0.80
							[description] => Merchant fee for credit card payments and APM.
						)
				)
			[bank_account_details] => ''
			[instructions] => ''
			[method] => Array
				(
					[type] => VISA Credit
					[payment_method_id] => 2
					[payment_method_flow] => redirect
					[locked] =>
					[lock_reason] =>
					[converted_amount] => 30.71
					[converted_currency] => BGN
					[icon_class] => fas fa-credit-card
					[key] => transfermate_2
					[label] => VISA Credit
					[alt] => VISA Credit
					[provider] => transfermate
					[component] => PaymentTransferMate
					[translations] => Array
						(
							[cancel] => Cancel
							[additional_taxes] => Additional taxes may apply.
							[redirect_description] => Please click here to proceed with your payment.
						)

					[url] => #
				)

		)
	 */
	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated {

		$this->createLogger()->info(__METHOD__.': Incoming payment', ['data' => $data, 'inquiry' => $inquiry->getData()]);

		$api = $this->createApi();
		$payment = $api->findPaymentById((int)$data['id']);
		
		if (!$this->checkTransfermatePayment($payment)) {
			throw $this->createError(__METHOD__.': Status is not paid', ['data' => $data->toArray(), 'payment' => $payment]);
		}

		// Hinweis im Payment Form steuern
		// Außerdem bei Bank Transfer nicht zuweisen, da sich Betrag verändern kann
		$status = \Ext_TS_Inquiry_Payment_Unallocated::STATUS_REGISTERED;
		if ((int)Arr::get($payment, 'third_party_status_id') === TransferMateApi::STATUS_THIRDPARTY_SUCCESSFUL) {
			$status = \Ext_Thebing_Inquiry_Payment::STATUS_PENDING;
		}

		// Es müsste bereits ein initialisiertes $paymentUnallocated geben
		$paymentUnallocated = $this->searchUnallocatedPaymentByTransactionCode($payment['id']);
				
		if($paymentUnallocated === null) {
			$paymentUnallocated = $this->createUnallocatedPayment($inquiry, (float)$data['source_amount'], $data['source_currency'], $payment, $status);
		}

		$paymentUnallocated->status = $status;
		$paymentUnallocated->payment_date = (new \DateTime($data['paid_date']))->format('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'transfermate', 'payment' => $payment, 'data' => $data]);
		$this->updateUnallocatedPaymentComment($paymentUnallocated, $payment);
		
		if ($data['flow'] === 'direct') {
			$paymentUnallocated->instructions = $data['instructions'];
		}

		return $paymentUnallocated;
	}

	protected function updateUnallocatedPaymentComment(\Ext_TS_Inquiry_Payment_Unallocated $paymentUnallocated, $payment) {
	
		$comment = [];
		$comment[] = 'TransferMate payment (asynchronous)';
		$comment[] = 'Transaction ID: '.$payment['id'];
		$comment[] = 'Reference number: '.$payment['reference_number']; // ref_number in MakeAPayment
		$comment[] = 'Source amount: '.$payment['source_amount'];
		$comment[] = 'Foreign amount: '.$payment['foreign_amount'].($payment['foreign_amount'] !== $payment['payable_amount'] ? '('.$payment['payable_amount'].')' : '');
		if(!empty($payment['method'])) {
			$comment[] = 'Payment method: '.$payment['method']; // type in MakeAPayment
		}
		
		$paymentUnallocated->comment = join("\n", $comment);
		
	}

	protected function createUnallocatedPayment(\Ext_TS_Inquiry $inquiry, float $amount, string $sourceCurrency, $payment, string $status) {
		
		$contact = $inquiry->getCustomer();
		
		$currency = \Ext_Thebing_Currency::getByIso($sourceCurrency); // source_currency existert nicht in $payment/PaymentHistory

		$paymentUnallocated = new \Ext_TS_Inquiry_Payment_Unallocated();
		$paymentUnallocated->payment_method_id = $this->getAccountingPaymentMethod()->id;
		$paymentUnallocated->status = $status;
		$paymentUnallocated->transaction_code = $payment['id'];
		$paymentUnallocated->firstname = $contact->firstname;
		$paymentUnallocated->lastname = $contact->lastname;
		$paymentUnallocated->amount = $amount;
		$paymentUnallocated->amount_currency = (int)$currency->id;
		$paymentUnallocated->payment_date = date('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'transfermate', 'payment' => $payment]);
		$paymentUnallocated->inquiry_id = $inquiry->id;
	
		$this->updateUnallocatedPaymentComment($paymentUnallocated, $payment);
		
		return $paymentUnallocated;
	}


	/**
	 * @inheritdoc
	 *
	 * Alle Responses liefern 200, damit der Webhook nicht jedes Mal fünffach gesendet wird.
	 *
	 * Webhook-Format von TransferMate ist nirgends dokumentiert.
	 *
	 * Array
		(
			[cc_status] => Deprecated! Use: third_party_status.
			[cc_status_id] => Deprecated! Use: third_party_status_id.
			[cc_status_updated_at] => Deprecated! Use: third_party_status_updated_at.
			[third_party_status] => Successful
			[third_party_status_id] => 2
			[third_party_status_updated_at] => 2022-09-15T23:31:47+00:00
			[order_id] => FIDELO-3062-CGvtfi1F3MEIuYd9vsaj
			[response_id] => 1663284707
			[response_context] => 3RDPTY
			[event_type] => status_update
			[payment_method] => APM
			[transaction_id] => 1114766
			[ref_number] => FIDELO-XML-SERVICECPS-1300
			[transaction_status_id] => 0
			[transaction_status] => Registered
			[payable_amount] => 65.00
			[payable_currency] => EUR
			[paid_amount] => 0.00
			[paid_currency] => EUR
			[registered_date] => 2022-09-15T00:16:41+00:00
			[pending_date] =>
			[paid_date] =>
			[inactive_date] =>
			[status_updated_at] => 2022-09-15T00:16:41+00:00
			[response_sent_at] => 2022-09-15T23:31:47+00:00
			[hmac_signature] => 6dc218d8f7ca4b1f6ae76e43065be58aae5f3df8e7bfcb872635f50b2624f839
		)
	 *
	 * @param Collection $data
	 * @return Response
	 */
	public function captureByWebhook(Collection $data): Response {

		$this->createLogger()->info('captureByWebhook', $data->toArray());
		
		// Registered kommt rein, sobald die TM-Zahlung durch Auswahl der Zahlungsmethode erstellt wurde
		// Registered kommt aber auch (erneut) rein, sobald eine CC-Zahlung eingegeben wurde
		if ((int)$data->get('transaction_status_id') === TransferMateApi::STATUS_REGISTERED) {
			// Immer ignorieren, da capturePayment hier bereits Registered übernimmt
			return response('OK (Payment status ignored)');
		}

		$payment = $this->searchPaymentByTransactionCode((int)$data->get('transaction_id'));
		if ($payment === null) {
			// TODO Überprüfen welche Fälle hier noch reinlaufen (z.B. INACTIVE bei nie abgeschlossener Zahlung?)
			$this->createLogger()->info(__METHOD__.': Could not find any payment in system', $data->toArray());
			return response('OK (Payment not found)');
		}

		$this->setSchool($payment->getInquiry()->getSchool()); // HMAC

		if (!$this->school->exist()) {
			$this->createLogger()->error(__METHOD__.': Booking or school not found', ['data' => $data, 'inquiry' => $payment->getInquiry()->getData(), 'school' => $payment->getInquiry()->getSchool()]);
			return response('ERROR (Booking or school not found)', 422);
		}

		\System::setInterfaceLanguage($this->school->getLanguage()); // Events

		// HMAC-Signatur überprüfen
		if (!$this->checkSignature($data)) {
			$this->createLogger()->error(__METHOD__.': HMAC signature mismatch', ['data' => $data, 'hmac_school' => $this->school->getMeta('transfermate_hmac_secret'), 'school' => $payment->getInquiry()->getSchool()]);
			return response('ERROR (Signature mismatch)', 400);
		}

		if ((int)$data->get('transaction_status_id') === TransferMateApi::STATUS_PENDING) {
			$payment->status = \Ext_Thebing_Inquiry_Payment::STATUS_PENDING;
			$payment->save();
		} elseif ((int)$data->get('transaction_status_id') === TransferMateApi::STATUS_PAID) {
			$payment->status = \Ext_Thebing_Inquiry_Payment::STATUS_PAID;
			$payment->save();
		} elseif ((int)$data->get('transaction_status_id') === TransferMateApi::STATUS_INACTIVE) {
			PaymentFailed::dispatch($payment->getInquiry());
			$payment->delete();
			$payment = null;
		}

		$this->createLogger()->info(sprintf(__METHOD__.': Set payment status to "%s" (incoming status "%s") ', $payment->status, $data->get('transaction_status')), $data->toArray());

		if ($payment instanceof \Ext_TS_Inquiry_Payment_Unallocated) {
			$payment->writeFormPaymentTask();
		}

		return response('OK (Payment processed)');

	}

	/**
	 * HMAC-Signatur überprüfen (Schule notwendig!)
	 */
	private function checkSignature(Collection $data): bool {

		$sortedPayload = $data->reject(fn($value, $key) => $value === '' || $key === 'hmac_signature')->sortKeys();
		$hmac = hash_hmac('sha256', $sortedPayload->join(':'), $this->school->getMeta('transfermate_hmac_secret'));

		return hash_equals($data['hmac_signature'], $hmac);

	}

	public function getTranslations(Frontend $languageFrontend): array {

		$languageFrontend = (clone $languageFrontend)->setContext('TransferMate');

		return [
			'backdrop_description' => $languageFrontend->translate('Don\'t see the payment window?'),
			'redirect_description' => $languageFrontend->translate('Please click here to proceed with your payment.'),
			'focus_description' => $languageFrontend->translate('Please switch tabs to reactivate the payment window.'),
			'bank_transfer_button' => $languageFrontend->translate('Pay now by Bank Transfer'),
			'bank_transfer_description' => $languageFrontend->translate('Please read the instructions carefully and click the button at the end to submit. You\'ll also get a copy of these payment instructions afterwards.'),
			'next' => $languageFrontend->translate('Next'),
			'back' => $languageFrontend->translate('Back'),
			'yes' => $languageFrontend->translate('Yes'),
			'no' => $languageFrontend->translate('No'),
			'your_payment' => $languageFrontend->translate('Your payment'),
			'student_details' => $languageFrontend->translate('Student details'),
			'payer_details' => $languageFrontend->translate('Payer details'),
			'completion' => $languageFrontend->translate('Completion'),
			'student' => $languageFrontend->translate('Student'),
			'payer' => $languageFrontend->translate('Payer'),
			'parent_of_student' => $languageFrontend->translate('Parent of student'),
			'relative_of_student' => $languageFrontend->translate('Relative of student'),
			'other' => $languageFrontend->translate('Other'),
			'select_payment_method' => $languageFrontend->translate('Please select a payment method:'),
			'additional_fees_and_taxes' => $languageFrontend->translate('Additional fees and taxes may apply.'),
			'country_pay_from' => $languageFrontend->translate('What country are you paying from?'),
			'payer_type' => $languageFrontend->translate('Who is making the payment?'),
			'paying_from' => $languageFrontend->translate('I\'m paying from {country}.'),
			'relationship_to_student' => $languageFrontend->translate('Relationship to student'),
			'name' => $languageFrontend->translate('Name'),
			'address' => $languageFrontend->translate('Address'),
			'postal_code' => $languageFrontend->translate('Postal Code'),
			'city' => $languageFrontend->translate('City'),
			'state' => $languageFrontend->translate('State'),
			'country' => $languageFrontend->translate('Country'),
			'nationality' => $languageFrontend->translate('Nationality'),
			'phone_number' => $languageFrontend->translate('Phone Number'),
			'date_of_birth' => $languageFrontend->translate('Date of birth'),
			'email' => $languageFrontend->translate('E-mail'),
			'india_fee_exceed' => $languageFrontend->translate('Will you exceed the US$ 250,000 foreign exchange limit in the calendar year 01 April – 31 March?'),
			'agree_tos' => $languageFrontend->translate('I agree to the {link}Terms & Conditions{/link}.')
		];

	}

	public function getPaymentMethods(Frontend $languageFrontend): array {

		return [
			[
				'key' => 'transfermate',
				'label' => $languageFrontend->translate('TransferMate'),
				'alt' => $languageFrontend->translate('TransferMate'),
				'icon_class' => 'fas fa-landmark',
				'additional' => $this->preliminaryPayload
			]
		];

	}

	private function createApi(): TransferMateApi {

		return new TransferMateApi($this->school->getMeta('transfermate_client'), $this->school->getMeta('transfermate_username'), $this->school->getMeta('transfermate_password'));

	}

	private function createErrorResponse(PaymentError $e): array {

		$logger = $this->createLogger();
		$message = sprintf('TransferMate Error: %s (%s)', $e->getAdditional()['error']['error_message'], $e->getAdditional()['error']['field_id']);
		$logger->error($message, $e->getAdditional());

		// Fehler ans Frontend weitergeben (z.B. Invalid or unsupported country)
		return ['error' => $message];

	}

}
