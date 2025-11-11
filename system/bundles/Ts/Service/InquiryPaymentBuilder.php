<?php

namespace Ts\Service;

use Illuminate\Support\Arr;

/**
 * Service für die Generierung und das Speichern eines Inquiry-Payments auf Basis von Items und Betrag
 */
class InquiryPaymentBuilder
{
	/**
	 * @var \Ext_TS_Inquiry
	 */
	private \Ext_TS_Inquiry $inquiry;

	/**
	 * @var \Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	private array $items;

	public function __construct(\Ext_TS_Inquiry $inquiry, array $items)
	{
		$this->inquiry = $inquiry;
		$this->items = $items;
	}

	public function execute(\Ext_Thebing_Inquiry_Payment $payment)
	{
		$items = [];
		$allocationService = new \Ext_TS_Payment_Item_AllocateAmount($this->items, $payment->amount_inquiry);
		$allocatedItems = $allocationService->allocateAmounts();
		$factor = abs($payment->amount_inquiry) > 0 && abs($payment->amount_school) > 0 ? $payment->amount_school / $payment->amount_inquiry : 1;

		foreach ($allocatedItems as $itemId => $itemAmount) {

			// Beträge mit 0 ignorieren, da ansonsten alle vorhanden Rechnungen dieser Zahlung zugewiesen werden
			if (bccomp($itemAmount, 0, 5) === 0) {
				continue;
			}

			$paymentItem = new \Ext_Thebing_Inquiry_Payment_Item();
			$paymentItem->payment_id = $payment->getId();
			$paymentItem->item_id = $itemId;
			$paymentItem->amount_inquiry = $itemAmount;
			$paymentItem->amount_school = $itemAmount * $factor;
			$paymentItem->currency_inquiry = $payment->currency_inquiry;
			$paymentItem->currency_school = $payment->currency_school;
			$payment->setJoinedObjectChild('items', $paymentItem);

			$items[] = collect($this->items)->firstWhere('id', $itemId);
		}

		$documents = array_reduce($items, function (array $documents, \Ext_Thebing_Inquiry_Document_Version_Item $item) {
			$document = $item->getVersion()->getDocument();
			$documents[$document->id] = $document;
			return $documents;
		}, []);

		if (!empty($items) && empty($documents)) {
			throw new \RuntimeException('There are no documents for ' . __CLASS__);
		}

		if ($allocationService->hasOverPayment()) {
			$overPayment = new \Ext_Thebing_Inquiry_Payment_Overpayment();
			$overPayment->payment_id = $payment->id;
			$overPayment->inquiry_document_id = !empty($documents) ? Arr::first($documents)->id : null;
			$overPayment->amount_inquiry = $allocationService->getOverPayment();
			$overPayment->amount_school = $allocationService->getOverPayment() * $factor;
			$overPayment->currency_inquiry = $payment->currency_inquiry;
			$overPayment->currency_school = $payment->currency_school;
			$payment->setJoinedObjectChild('overpayments', $overPayment);
		}

		$payment->validate(true);
		$payment->save();

		if (!$payment->checkAmount()) {
			throw new \RuntimeException('InquiryPaymentBuilder: Payment amounts do not match');
		}

		// Bezahlbelege pro Zahlung
		$payment->writePostSaveTask($this->inquiry, true);
		//[$customerReceipts, $agencyReceipts] = \Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($this->inquiry, \Ext_Thebing_Inquiry_Payment::RECEIPT_PAYMENT);
		//if ($customerReceipts) $payment->preparePaymentPdfs();
		//if ($agencyReceipts) $payment->preparePaymentPdfs(true);

		// Bezahlbelege pro Dokument
		foreach ($documents as $document) {
			\Ext_Thebing_Document::refreshPaymentReceipts($this->inquiry, $document);
		}

		return $payment;
	}
}
