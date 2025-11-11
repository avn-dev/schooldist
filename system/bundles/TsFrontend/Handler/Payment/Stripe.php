<?php

namespace TsFrontend\Handler\Payment;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Stripe\Exception\InvalidRequestException;
use Tc\Service\Language\Frontend;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * KK-Nummern zum Testen:
 * 4242 4242 4242 4242 Succeeds and immediately processes the payment.
 * 4000 0000 0000 3220 3D Secure 2 authentication must be completed for a successful payment.
 * 4000 0000 0000 9995 Always fails with a decline code of insufficient_funds.
 *
 * @link https://stripe.com/docs/payments/accept-a-payment?integration=elements » Custom Payment Flow
 * @link https://stripe.com/docs/payments/accept-a-payment?integration=elements#web-test-integration » Tests
 * @see PaymentStripe.vue
 */
class Stripe implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm {

	use PaymentProviderTrait;

	public function getScriptUrl(): string {
		return 'https://js.stripe.com/v3/';
	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array {

		$client = $this->createClient();

		// https://stripe.com/docs/currencies#zero-decimal
		$amount = $items->sum('amount_with_tax');
		$currency = $inquiry->getCurrency(true);
		$currency->bThinspaceSign = true;
		$contact = $inquiry->getCustomer();

		// https://stripe.com/docs/api/payment_intents/object
		try {
			$intent = \Stripe\PaymentIntent::create([
				'amount' => round($amount, 2) * 100, // Muss ein Integer sein
				'currency' => $currency->getIso(),
				'metadata' => ['inquiry_id' => $inquiry->exist() ? $inquiry->id : null],
				'capture_method' => 'manual',
				'description' => $description,
				'payment_method_types' => ['card']
			]);
		} catch (InvalidRequestException $e) {
			throw $this->createError('Stripe PaymentIntent::create API error', [$e->getStripeCode(), $e->getJsonBody(), $items]);
		}

		return [
			'billing_details' => $this->createBillingDetails($contact),
			'amount' => \Ext_Thebing_Format::Number($amount, $currency, $this->school),
			'api_key' => $client['public'],
			'client_secret' => $intent->client_secret
		];

	}

	/**
	 * Billing-Details erzeugen, da die Zahlung in Stripe ansonsten nicht zuweisbar wäre
	 *
	 * Dies kann nicht direkt PaymentIntent::create() mit angegeben werden, da payment_method_data komplette
	 * Zahlungsdaten erwartet, daher muss das alles übers JS geschliffen werden.
	 *
	 * @link https://stripe.com/docs/api/payment_methods/object#payment_method_object-billing_details
	 *
	 * @param \Ext_TS_Inquiry_Contact_Traveller $contact
	 * @return array
	 */
	private function createBillingDetails(\Ext_TS_Inquiry_Contact_Traveller $contact): array {

		$billingDetails = [];

		$name = $contact->getEverydayName();
		if ($name !== '') {
			// Darf nicht leer gesetzt werden
			$billingDetails['name'] = $name;
		}

		$email = $contact->getFirstEmailAddress()->getEmail();
		if ($email) {
			$billingDetails['email'] = $email;
		}

		$address = $this->getContactAddress($contact);
		if (!$address->isEmpty()) {

			// Stripe ist sehr zickig bei leeren Werten, daher dürfen diese nur gesetzt werden, wenn auch ein Wert existiert
			$stripeAddress = [];
			$setAddress = function ($key, $value) use (&$stripeAddress) {
				if (empty($value)) {
					return;
				}
				$stripeAddress[$key] = $value;
			};

			$setAddress('line1', $address->address);
			$setAddress('line2', $address->address_addon);
			$setAddress('postal_code', $address->zip);
			$setAddress('city', $address->city);
			$setAddress('state', $address->state);
			$setAddress('country', $address->country_iso);

			$billingDetails['address'] = $stripeAddress;

		}

		return $billingDetails;

	}

	public function checkPayment(Collection $data): bool {

		$this->createClient();

		try {
			$intent = new \Stripe\PaymentIntent($data->get('id'));
			$intent->refresh();
		} catch (InvalidRequestException $e) {
			throw $this->createError('checkPayment Stripe API Error: '.$e->getMessage(), [$data->toArray(), $e->getStripeCode(), $e->getJsonBody()]);
		}

		// succeeded ist kein gültiger Status, da die Zahlung dann bereits verwendet (capture) wurde
		if ($intent->status !== 'requires_capture') {
			throw $this->createError('Stripe PaymentIntent status: '.$intent->status, [$intent, $data->toArray()]);
		}

		return true;

	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated {

		$this->createClient();

		if ($data->get('status') !== 'requires_capture') {
			throw $this->createError('Invalid Stripe PaymentIntent status: '.$data->get('status'), [$data->toArray()]);
		}

		try {
			$intent = (new \Stripe\PaymentIntent($data->get('id')))->capture();
		} catch (InvalidRequestException $e) {
			// Z.B.: This PaymentIntent could not be captured because it has already been captured.
			throw $this->createError('Stripe API error: '.$e->getMessage(), [$data->toArray(), $e->getStripeCode(), $e->getJsonBody()]);
		}

		if ($intent->status !== 'succeeded') {
			throw $this->createError('Invalid Stripe PaymentIntent status after capture: '.$intent->status, [$data->toArray(), $intent]);
		}

		// Die billing_details sind nicht im $intent enthalten
		$paymentMethod = new \Stripe\PaymentMethod($intent->payment_method);
		$paymentMethod->refresh();

		$language = new \Tc\Service\Language\Frontend($this->school->getLanguage());
		$currency = \Ext_Thebing_Currency::getByIso($intent->currency);

		$comment = [];
		$comment[] = sprintf('%s: %s', $language->translate('E-mail'), $paymentMethod->billing_details['email']);
		$comment[] = sprintf('%s: %s', $language->translate('ID'), $intent->id);

		$payment = new \Ext_TS_Inquiry_Payment_Unallocated();
		$payment->transaction_code = $intent->id;
		$payment->comment = join("\n", $comment);
		$payment->lastname = $paymentMethod->billing_details['name'];
		$payment->amount = $intent->amount_received / 100;
		$payment->amount_currency = $currency->id;
		$payment->payment_date = Carbon::createFromTimestamp($intent->created)->toDateString();
		$payment->additional_info = json_encode(['type' => 'stripe', 'payment_intent' => $intent, 'payment_method' => $paymentMethod]);

		return $payment;

	}

	public function getTranslations(Frontend $languageFrontend): array {
		return [
			'credit_card' => $languageFrontend->translate('Credit card'),
			//'name_on_card' => $language->translate('Name on card'),
			'card_number' => $languageFrontend->translate('Card number'),
			'expiration_date' => $languageFrontend->translate('Expiration date'),
			'security_code' => $languageFrontend->translate('Security code'),
			'pay_now' => $languageFrontend->translate('Pay now: {amount}')
		];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array {

		return [
			[
				'key' => 'stripe_card',
				'label' => $languageFrontend->translate('Credit Card'),
				'alt' => $languageFrontend->translate('Credit Card'),
				'icon_class' => 'fas fa-credit-card'
			]
		];

	}

	private function createClient(): array {

		$testMode = $this->school->getMeta('stripe_api_test_mode');

		$publicKey = $this->school->getMeta($testMode ? 'stripe_test_api_key_public' : 'stripe_api_key_public');
		$privateKey = $this->school->getMeta($testMode ? 'stripe_test_api_key' : 'stripe_api_key');

		\Stripe\Stripe::setApiKey($privateKey);

		return [
			'public' => $publicKey,
			'private' => $privateKey,
		];

	}

	public function getSortOrder(): int {
		return 1;
	}

}
