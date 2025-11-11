<?php

namespace TsContactLogin\Combination\Handler;

use Exception;

class Billing extends HandlerAbstract {
	/**
	 * Ext_Thebing_Format::Number() throws exceptions
	 * @throws Exception
	 */

	protected function handle(): void {
		$dataForTemplate = [
			'invoices' => [],
			'overpayment' => [],
		];
		$currencyId = null;
		$schoolId = null;
		$amountTotal = 0;
		$payedAmountTotal = 0;
		$balanceTotal = 0;
		$inquiries = $this->login->getActiveInquiries();
		foreach ($inquiries as $inquiry) {
			$customer = $inquiry->getCustomer();
			$school = $inquiry->getSchool();
			$currencyId = $inquiry->getCurrency();
			$group = $inquiry->getGroup();
			$documents = array();
			$overpayments = [];
			if (is_object($group)) {
				$groupInquiries = $group->getInquiries();
				foreach ($groupInquiries as $inquiryTemp) {
					$groupInquiryDocuments = $inquiryTemp->getDocuments('invoice_without_proforma', true, true);
					$documents = array_merge($documents, $groupInquiryDocuments);
					foreach ($inquiryTemp->getOverpayments('invoice') as $overpayment) {
						$overpayments[$overpayment->id] = $overpayment;
					}
				}
			} else {
				$documents = $inquiry->getDocuments('invoice_without_proforma', true, true);
				$overpayments = $inquiry->getOverpayments('invoice');
			}

			foreach ($documents as $inquiryDocument) {
				$tempInquiry = $inquiryDocument->getInquiry();
				$currencyId = $tempInquiry->getCurrency();
				$amount = $inquiryDocument->getAmount();
				$payedAmount = $inquiryDocument->getPayedAmount($currencyId);
				$balance = $amount - $payedAmount;
				$amountTotal += $amount;
				$payedAmountTotal += $payedAmount;
				$balanceTotal += $balance;

				$dataForTemplate['invoices'][] = [
					'number' => $inquiryDocument->document_number,
					'label' => $inquiryDocument->getLabel(),
					'booking_created' => $inquiry->created,
					'customer' => $customer->firstname." ".$customer->lastname,
					'amount' => \Ext_Thebing_Format::Number($amount, $currencyId, $school, true, 2),
					'payed' => \Ext_Thebing_Format::Number($payedAmount, $currencyId, $school, true, 2),
					'balance' => \Ext_Thebing_Format::Number($balance, $currencyId, $school, true, 2),
				];
			}

			if (!empty($overpayments)) {
				$overpaymentAmount = 0;
				foreach ($overpayments as $overpayment) {
					$overpaymentAmount += $overpayment->amount_inquiry;
				}

				$balanceTotal -= $overpaymentAmount;
				$dataForTemplate['overpayment'] = [
					'number' => 'Overpayment',
					'label' => '',
					'amount' => 0,
					'payed' => 0,
					'balance' => \Ext_Thebing_Format::Number($overpaymentAmount * -1, $currencyId, $school, true, 2),
				];
			}
		}

		$dataForTemplate['total'] = [
			'number' => 'Total',
			'label' => '',
			'amount' => \Ext_Thebing_Format::Number($amountTotal, $currencyId, $schoolId, true, 2),
			'payed' => \Ext_Thebing_Format::Number($payedAmountTotal, $currencyId, $schoolId, true, 2),
			'balance' => \Ext_Thebing_Format::Number($balanceTotal, $currencyId, $schoolId, true, 2),
		];

		$this->assign('data', $dataForTemplate);

		$this->login->setTask('showBillingData');
	}
}