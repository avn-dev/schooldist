<?php

namespace TsContactLogin\Combination\Handler;

use Ext_Thebing_Format;
use Ext_Thebing_Gui2_Format_Date;
use Ext_Thebing_Inquiry_Document_Version;
use Ext_Thebing_Inquiry_Payment;

class Documents extends HandlerAbstract {
	protected function handle(): void {

		$inquiry = $this->login->getInquiry();
		$currencyId = $inquiry->getCurrency();
		$school = $inquiry->getSchool();
		$schoolId = $school->id;
		$formatDate = new Ext_Thebing_Gui2_Format_Date(false, $schoolId);
		$secureUrl = $this->login->getUrl('getFile') . '&document_id=';
		$inquiryIds = array($inquiry->id);

		$documents = (array)$inquiry->getDocuments('invoice_without_storno', true, true);
		$additional = (array)$inquiry->getDocuments('additional_document', true, true);
		$payments = (array)Ext_Thebing_Inquiry_Payment::searchPaymentsByInquiryArray($inquiryIds);

		$invoiceDocuments = [];
		foreach ($documents as $document) {
			if (!$document->released_student_login) {
				continue;
			}
			$version = $document->getLastVersion();

			if ($version instanceof Ext_Thebing_Inquiry_Document_Version) {
				$amount = $version->getAmount();

				$amount = Ext_Thebing_Format::Number($amount, $currencyId, $schoolId, true, 2);
				$date = $formatDate->format($document->created);
				$filePath = $document->id;
				$url = $secureUrl . $filePath;
				$invoiceDocuments[] = [
					'url' => $url,
					'number' => $document->document_number,
					'amount' => $amount,
					'date' => $date
				];
			}
		}
		$additionDocuments = [];
		foreach ($additional as $document) {
			if (!$document->released_student_login) {
				continue;
			}
			$version = $document->getLastVersion();

			if ($version instanceof Ext_Thebing_Inquiry_Document_Version) {
				$template = $version->getTemplate();
				$type = $template->type;
				$date = $formatDate->format($version->date);
				$documentType = $type;
				$filePath = $document->id;
				$url = $secureUrl . $filePath;
				$additionDocuments[] = [
					'url' => $url,
					'document' => $documentType,
					'date' => $date
				];
			}
		}

		$paymentDocuments = [];
		foreach ($payments as $payment) {
			$paymentDetails = new Ext_Thebing_Inquiry_Payment($payment['id']);
			$documentNumber = $payment['document_number'];
			$date = $formatDate->format($paymentDetails->date);
			$paymentAmount = $paymentDetails->getAmount();
			$amount = Ext_Thebing_Format::Number($paymentAmount, $currencyId, $schoolId, true, 2);
			$customerPaymentDocuments = $inquiry->getDocuments('receipt_customer', true, true);

			$url = '';
			if (!empty($customerPaymentDocuments)) {
				foreach ((array)$customerPaymentDocuments as $customerPaymentDocument) {
					$url = $secureUrl . $customerPaymentDocument->id;
				}
			}
			$paymentDocuments[] = [
				'url' => $url,
				'number' => $documentNumber,
				'date' => $date,
				'amount' => $amount
			];
		}

		$this->assign('invoices', $invoiceDocuments);
		$this->assign('additions', $additionDocuments);
		$this->assign('payments', $paymentDocuments);

		$this->assign('bookings', $this->login->getActiveInquiries());

		$this->login->setTask('showDocuments');
	}

}