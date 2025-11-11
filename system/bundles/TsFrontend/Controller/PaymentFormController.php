<?php

namespace TsFrontend\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Ts\Entity\Payment\PaymentProcess;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Generator\PaymentFormGenerator;
use TsFrontend\Helper\PaymentRequestHelper;
use TsFrontend\Helper\PaymentMethodsHelper;

/**
 * @see \TsFrontend\Generator\PaymentFormGenerator
 */
class PaymentFormController extends \Illuminate\Routing\Controller {

	public function load(Request $request, PaymentFormGenerator $combination): Response {

		$process = $this->getProcess($request);

		$combination->log(__CLASS__.'::load::begin', [], false);

		if ($process === null) {
			return $this->createErrorResponse($combination, 'This payment does not exist.');
		}

		$inquiry = $process->getInquiry();
		$terms = $inquiry->getDueTerms();

		// Hash/Prozess bereits verwendet, aber es gibt noch Fälligkeiten: Neuen Prozess generieren oder bereits vorhandenen verwenden
		if (
			$process->payed !== null &&
			$terms->isNotEmpty()
		) {
			$process = $this->handlePayedProcess($combination, $process, $inquiry);
			if ($process === null) {
				return $this->createErrorResponse($combination, 'A payment is being processed. Please contact your language school if this status persists.');
			}
		}

		if (
			$terms->isEmpty() &&
			$process->payed
		) {
			return $this->createErrorResponse($combination, 'This payment has already been made.');
		}

		if ($terms->isEmpty()) {
			return $this->createErrorResponse($combination, 'This payment has no amount. Please contact your language school.');
		}

		$amount = $amountDue = $terms->sum(fn(\Ext_TS_Document_Version_PaymentTerm $term) => $term->getOpenAmount());
		$documents = $terms->map(fn(\Ext_TS_Document_Version_PaymentTerm $term) => $term->getVersion()->getDocument())->unique();
		$documentNumbers = $documents->map(fn(\Ext_Thebing_Inquiry_Document $document) => $document->document_number)->join(', ');
		$openAmount = $inquiry->getOpenPaymentAmount();

		// Schüler kann auch direkt den vollen Betrag zahlen, solange nicht due = open total
		$fullAmountOption = false;
		if (abs($amount - $openAmount) > PHP_FLOAT_EPSILON) {
			$fullAmountOption = true;
		}

		// Option ausgewählt: Vollen Betrag bezahlen
		if ($request->boolean('pay_full_amount')) {
			$amount = $openAmount;
		}

		$contact = $inquiry->getCustomer();
		$currency = $inquiry->getCurrency(true);
		$currency->bThinspaceSign = true;
		$school = $inquiry->getSchool();
		$dateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $school->id);
		$description = $school->ext_1.': '.$documentNumbers;

		// Zahlungsanbieter funktionieren generell nicht mit einem Betrag von 0
		if ($amount <= 0) {
			return $this->createErrorResponse($combination, 'This payment has no amount. Please contact your language school.');
		}

		$providers = collect($combination->getCombination()->payment_providers);

		$helper = new PaymentRequestHelper($combination, $request, $inquiry, $providers);
		$helper->setInvoices($documents);
		$helper->setItemBuilder(function () use ($amount, $description): Collection {
			return collect([[
				'amount_with_tax' => round($amount, 2),
				'description' => $description
			]]);
		});

		$data = $helper->handle();

		return response([
			'invoice' => $documentNumbers,
			'address' => $combination->buildCustomerAddress($contact),
			'customer_number' => $contact->getCustomerNumber(),
			'amount_total' => \Ext_Thebing_Format::Number($inquiry->getTotalAmount(), $currency, $school, true, 2),
			'amount_payed' => \Ext_Thebing_Format::Number($inquiry->getTotalPayedAmount(), $currency, $school, true, 2),
			'amount_due' => \Ext_Thebing_Format::Number($amountDue, $currency, $school, true, 2),
			'date_due' => $dateFormat->format($terms->first()->date),
			'is_due' => Carbon::parse($terms->first()->date)->lt(Carbon::now()),
			'full_amount_option' => $fullAmountOption,
			'methods' => $data['methods'],
			'payment' => $data['payment'],
			'process' => $process->hash
		]);

	}

	public function submit(Request $request, PaymentFormGenerator $combination): Response {

		$combination->log(__CLASS__.'::submit::begin', [], false);

		$process = $this->getProcess($request);

		if ($process === null) {
			return response(['message' => $combination->getLanguage()->translate('This payment does not exist.')], 400);
		}

		$inquiry = $process->getInquiry();
		$school = $inquiry->getSchool();

		try {

			$paymentMethodsHelper = new PaymentMethodsHelper($combination);
			$paymentMethodsHelper->setMethod($request->input('method'));
			$paymentMethodsHelper->generatePaymentProviders(collect($combination->getCombination()->payment_providers), $school);

			$handler = $paymentMethodsHelper->createPaymentHandler();

			$payment = $handler->capturePayment($inquiry, collect($request->input('payment')));
			if ($payment === null) {
				throw new \DomainException('Can not capture a null payment in PaymentForm.');
			}

			$payment->comment .= "\n".sprintf($combination->getLanguage()->translate('Paid by payment form (combination ID %d)'), $combination->getCombination()->id);
			$payment->payment_method_id = $handler->getAccountingPaymentMethod()->id;
			$payment->process_id = $process->id;
			$payment->inquiry_id = $inquiry->id;
			$payment->save();

			$process->payed = Carbon::now()->toDateTimeString();
			$process->save();

			if ($payment->status === \Ext_TS_Inquiry_Payment_Unallocated::STATUS_REGISTERED) {
				$message = $combination->getLanguage()->translate('The payment has been registered successfully. Please perform the payment as indicated.');
			} else {
				$message = $combination->getLanguage()->translate('The payment has been completed successfully.');
				$payment->writeFormPaymentTask();
			}

			if ($payment->hasValidInstructions()) {
				$message .= "<br><br>".$payment->instructions;
			}

			$combination->logUsage('submit', false);

			$combination->log(__CLASS__.'::submit::finish', ['payment' => $payment->getData(), 'process' => $process->getData()], false);

			return response(['message' => $message]);

		} catch (PaymentError $e) {

			$combination->logUsage('submit_error', false);

			$combination->log(__METHOD__.': PaymentError in submit ', $e->getAdditional());

			return response(['message' => $combination->getLanguage()->translate('There was an error while processing the payment. Please try again.')], 400);

		}

	}

	private function getProcess(Request $request): ?PaymentProcess {

		/** @var PaymentProcess $process */
		$process = PaymentProcess::query()
			->where('hash', $request->input('process'))
			->first();

		// Wiederverwendbarer Hash, aber intern anderen Prozess verwenden
		if ($request->filled('process_following')) {
			$process = PaymentProcess::query()
				->where('hash', $request->input('process_following'))
				->where('inquiry_id', $process->inquiry_id)
				->first();
		}

		if ($process === null) {
			return null;
		}

		if ($process->seen === null) {
			$process->seen = Carbon::now()->toDateTimeString();
			$process->save();
		}

		return $process;

	}

	private function handlePayedProcess(PaymentFormGenerator $combination, PaymentProcess $process, \Ext_TS_Inquiry $inquiry): ?PaymentProcess {

		$openProcess = PaymentProcess::query()
			->where('inquiry_id', $process->inquiry_id)
			->whereNull('payment_id')
			->whereNotNull('payed')
			->count();

		// Wenn direkt wieder die Seite aufgerufen wird und noch Beträge offen sind, wurde das letzte Payment ggf. noch nicht vom PP verarbeitet
		if ($openProcess > 0) {
			return null;
		}

		$newProcess = PaymentProcess::createPaymentProcess($inquiry, $process);
		$newProcess->seen = Carbon::now()->toDateTimeString();
		$newProcess->save();

		$combination->log('PaymentFormController::process_following_creation', [$process->getData(), $newProcess->getData()], false);

		return $newProcess;

	}

	private function createErrorResponse(PaymentFormGenerator $combination, string $message): Response {

		$combination->log(__CLASS__.'::load::error', ['message' => $message]);

		return response(['message' => $combination->getLanguage()->translate($message)], 400);

	}

}