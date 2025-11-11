<?php

/**
 * @TODO Ultimativ sollten Ext_Thebing_Inquiry_Payment und Ext_TS_Inquiry_Payment_Unallocated (und \Ts\Entity\Payment\PaymentProcess) gemerged werden
 *   (Zahlungen mit Status, denn alle sind in Ext_Thebing_Inquiry_Payment paid und brauchen Items)
 *
 * @property string|int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property ?string $inquiry_id
 * @property string $payment_method_id
 * @property string $process_id
 * @property string $status ENUM
 * @property string $transaction_code
 * @property string $comment
 * @property string $firstname
 * @property string $lastname
 * @property string|float $amount
 * @property string|int $amount_currency
 * @property string $payment_date (DATE)
 * @property ?string $instructions
 * @property string $additional_info
 */
class Ext_TS_Inquiry_Payment_Unallocated extends Ext_Thebing_Basic {

	const STATUS_REGISTERED = 'registered';
	const STATUS_INITIALIZED = 'initialized';

    protected $_sTable = 'ts_inquires_payments_unallocated';

	protected $_sPlaceholderClass = \Ext_TS_Inquiry_Payment_Unallocated_Placeholder::class;

	/**
	 * Diese Zahlung zu einer richtigen Zahlung inkl. Zuweisungen konvertieren
	 *
	 * In dieser Methode wird nicht geprüft, ob diese Zahlung bereits existiert!
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param int $iPaymentMethodId $this->payment_method_id
	 * @return Ext_Thebing_Inquiry_Payment
	 */
	public function createInquiryPayment(Ext_TS_Inquiry $oInquiry, $iPaymentMethodId, string $source = 'backend'): Ext_Thebing_Inquiry_Payment {

		if ($oInquiry->getCurrency() != $this->amount_currency) {
			throw new \LogicException(sprintf('Wrong inquiry currency (%d) for allocating payment: %d, inquiry: %d, unallocated payment: %d', $oInquiry->getCurrency(), $this->amount_currency, $oInquiry->id, $this->id));
		}

		$oPayment = new Ext_Thebing_Inquiry_Payment();
		$oPayment->inquiry_id = $oInquiry->id;
		$oPayment->status = $this->status;
		$oPayment->date = $this->payment_date;
		$oPayment->transaction_code = $this->transaction_code;
		$oPayment->comment = htmlspecialchars($this->comment);
		$oPayment->method_id = $iPaymentMethodId;
		$oPayment->type_id = 1;
		$oPayment->sender = 'customer';
		$oPayment->receiver = 'school';
		$oPayment->amount_inquiry = $this->amount;
		$oPayment->amount_school = $this->amount;
		$oPayment->currency_inquiry = $oInquiry->getCurrency();
		$oPayment->currency_school = $oInquiry->getSchool()->getCurrency();
		$oPayment->additional_info = $this->additional_info;
		$oPayment->setMeta('source', $source);

		// Übliche Sonderbehandlung für Gruppen
		if($oInquiry->hasGroup()) {
			$aInquiryIds = $oInquiry->getGroup()->getInquiries(false, false, false);
		} else {
			$aInquiryIds = [$oInquiry->id];
		}

		$aPaymentData = $oPayment->buildPaymentDataArray($aInquiryIds);

		if(empty($aPaymentData[0]['documents'])) {
			#throw new \LogicException('INQUIRY_HAS_NO_INVOICE');
		}

		// Alle Items aller Rechnungen
		$aItems = [];
		foreach($aPaymentData as $aInquiry) {
			foreach($aInquiry['documents'] as $aDocument) {
				foreach($aDocument['items'] as $oItem) {
					$aItems[] = $oItem;
				}
			}
		}

		$oBuilder = new \Ts\Service\InquiryPaymentBuilder($oInquiry, $aItems);
		$oBuilder->execute($oPayment);

		$this->delete();

		// Kundennummer generieren, wenn noch nicht vorhanden
		$oCustomerNumberService = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
		$oCustomerNumberService->saveCustomerNumber(false, false); // Wird von calculatePayedAmount() gespeichert

		$oInquiry->calculatePayedAmount();

		return $oPayment;

	}

	public function getInquiry(): ?Ext_TS_Inquiry {

		if ($this->inquiry_id) {
			return Ext_TS_Inquiry::getInstance($this->inquiry_id);
		}

		return null;

	}

	public function hasValidInstructions(): bool {

		return !empty($this->instructions) && $this->status !== \Ext_Thebing_Inquiry_Payment::STATUS_PAID;

	}

	public function writeFormPaymentTask() {

		\Core\Entity\ParallelProcessing\Stack::getRepository()->writeToStack('ts-frontend/form-payment', [
			'object' => \Ext_TS_Inquiry::class,
			'object_id' => $this->inquiry_id,
			'payment_process_id' => $this->process_id, // Das muss vorhanden sein, damit der Status im Prozess korrekt gesetzt wird für fortlaufende Prozesse
			'unallocated_payment_id' => $this->id
		], 2);

	}

}