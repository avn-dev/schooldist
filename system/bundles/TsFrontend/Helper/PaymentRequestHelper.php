<?php

namespace TsFrontend\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TcFrontend\Interfaces\WidgetCombination;
use TsFrontend\Exceptions\PaymentChallenge;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Handler\Payment\Skip;
use TsRegistrationForm\Generator\CombinationGenerator;

class PaymentRequestHelper {

	private string $requestType = 'create';

	private WidgetCombination $combination;

	private Request $request;

	private \Ext_TS_Inquiry $inquiry;

	private ?Collection $invoices = null;

	private Collection $providers;

	private Collection $items;

	private \Closure $itemBuilder;

	public function __construct(WidgetCombination $combination, Request $request, \Ext_TS_Inquiry $inquiry, Collection $providers) {

		$this->combination = $combination;
		$this->request = $request;
		$this->inquiry = $inquiry;
		$this->providers = $providers;

		if ($request->filled('preliminary')) {
			$this->requestType = 'preliminary';
		} elseif ($request->filled('check')) {
			$this->requestType = 'check';
		}

	}

	public function setInvoices(Collection $invoices): static
	{
		$this->invoices = $invoices;
		return $this;
	}

	public function setItemBuilder(\Closure $closure): void {

		$this->itemBuilder = $closure;

	}

	public function handle(): array {

		$methods = [];
		$payment = null;
		$this->items = ($this->itemBuilder)();

		try {

			$paymentMethodsHelper = $this->createPaymentHelper();
			$methods = $this->createMethods($paymentMethodsHelper);

			switch ($this->requestType) {
				case 'preliminary':
					$this->combination->log('payment::methods', ['methods' => $methods], false);
					break;
				case 'check':
					$handler = $paymentMethodsHelper->createPaymentHandler();
					$status = $handler->checkPayment(collect($this->request->input('payment')));
					break;
				default:
					$handler = $paymentMethodsHelper->createPaymentHandler();
					$payment = $handler->createPayment($this->inquiry, $this->items, $this->inquiry->getSchool()->ext_1, $this->invoices);
					$this->combination->log('payment::created', ['payment' => $payment, 'method' => $this->request->input('method')], false);
			}

		} catch (PaymentChallenge $e) {
			return ['payment' => ['status' => 'challenge', 'challenge' => $e->getAdditional()]];
		} catch (PaymentError $e) {
			$this->combination->log('payment::create::error', ['message' => $e->getMessage(), 'additional' => $e->getAdditional()], true);
			if ($this->requestType === 'check') {
				throw $e;
			}
		}

		if ($this->requestType === 'check') {
			return ['payment' => ['status' => $status ?? false]];
		}

		return [
			'methods' => $methods,
			'payment' => $payment
		];

	}

	private function createPaymentHelper(): PaymentMethodsHelper {

		$helper = new PaymentMethodsHelper($this->combination);
		$helper->setMethod($this->request->input('method') ?? []);
		$helper->generatePaymentProviders($this->providers, $this->inquiry->getSchool());

		return $helper;

	}

	private function createMethods(PaymentMethodsHelper $helper) {

		$methods = $helper->generatePaymentMethods($this->inquiry, $this->items, $this->inquiry->getSchool()->ext_1);

		// Wenn es keinen Betrag oder keine verfügbaren Zahlungsmethoden gibt, muss die Skip-Option angeboten werden
		if (
			$this->requestType === 'preliminary' && (
				$this->items->isEmpty() ||
				$methods->isEmpty()
			)
		) {
			$this->combination->log('payment::providing_skip', [], false);
			$helper->generatePaymentProviders(collect(Skip::KEY), $this->inquiry->getSchool());
			$methods = $helper->generatePaymentMethods($this->inquiry, $this->items, $this->inquiry->getSchool()->ext_1);
		}

		// Übersetzung ersetzen bei Skip und Registration Form
		$methods->transform(function (array $method) {
			if (
				$method['key'] === Skip::KEY &&
				$this->combination instanceof CombinationGenerator
			) {
				$method['translations']['book_now'] = $this->combination->getForm()->getTranslation('sendbtn', $this->combination->getLanguage());
			}
			return $method;
		});

		return $methods;

	}

}