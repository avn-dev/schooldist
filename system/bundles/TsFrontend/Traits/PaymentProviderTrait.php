<?php

namespace TsFrontend\Traits;

use Illuminate\Support\Collection;
use Ts\Entity\Payment\PaymentProcess;
use TsFrontend\Exceptions\PaymentError;

trait PaymentProviderTrait
{
	protected string $key;

	/**
	 * @var \Ext_Thebing_School
	 */
	protected \Ext_Thebing_School $school;

	/**
	 * Ausgewählte Zahlungsmethode (alle Daten)
	 *
	 * @var array
	 */
	protected array $method;

	/**
	 * @var PaymentProcess
	 */
	protected PaymentProcess $process;

	public function setKey(string $key)
	{
		$this->key = $key;
	}

	public function setSchool(\Ext_Thebing_School $school)
	{
		$this->school = $school;
	}

	public function getComponentName(): string
	{
		$class = (new \ReflectionClass($this))->getShortName();
		return 'Payment' . $class;
	}

	public function setPaymentMethod(array $method)
	{
		$this->method = $method;
	}

	public static function getKey(): string
	{
		return strtolower((new \ReflectionClass(get_called_class()))->getShortName());
	}

//	public function setProcess(PaymentProcess $process) {
//		$this->process = $process;
//	}

	public function createPreliminaryPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description)
	{

	}

	protected function createError(string $message, array $data): PaymentError
	{
		// Wird später in der Kombination auch nochmal geloggt, ich weiß...
		$this->createLogger()->error('Payment error '.get_class($this), ['message' => $message, 'data' => $data]);
		
		$error = new PaymentError($message);
		$error->setAdditional($data);

		return $error;
	}

	protected function getContactAddress(\Ext_TS_Inquiry_Contact_Traveller $contact): \Ext_TC_Address
	{
		$address = $contact->getAddress('billing');
		if (!$address->isFilled()) {
			$address = $contact->getAddress('contact');
		}

		return $address;
	}

	public function createLogger(): \Monolog\Logger
	{
		return \Log::getLogger('frontend', 'payment');
	}

	public function getAccountingPaymentMethod(): \Ext_Thebing_Admin_Payment
	{
		$type = \Ext_Thebing_Admin_Payment::TYPE_PROVIDER_PREFIX . $this->key;

		return \Ext_Thebing_Admin_Payment::findFirstWithType($type);
	}

	public function searchPaymentByTransactionCode(string $transactionCode): \Ext_TS_Inquiry_Payment_Unallocated|\Ext_Thebing_Inquiry_Payment|null
	{
		/** @var \Ext_TS_Inquiry_Payment_Unallocated $payment */
		$payment = $this->searchUnallocatedPaymentByTransactionCode($transactionCode);

		if ($payment === null) {
			$payment = \Ext_Thebing_Inquiry_Payment::query()
				->where('method_id', $this->getAccountingPaymentMethod()->id)
				->where('transaction_code', $transactionCode)
				->first();
		}

		return $payment;
	}

	
	public function searchUnallocatedPaymentByTransactionCode(string $transactionCode): \Ext_TS_Inquiry_Payment_Unallocated|null
	{
		/** @var \Ext_TS_Inquiry_Payment_Unallocated $payment */
		$payment = \Ext_TS_Inquiry_Payment_Unallocated::query()
			->where('payment_method_id', $this->getAccountingPaymentMethod()->id)
			->where('transaction_code', $transactionCode)
			->first();

		return $payment;
	}
	
	public function getSortOrder(): int
	{
		return 10;
	}
}