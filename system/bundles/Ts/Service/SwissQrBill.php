<?php

namespace Ts\Service;

use Pdf\Service\Tcpdf;
use \Sprain\SwissQrBill as QrBill;
use Ts\Handler\SwissQrBill\ExternalApp;

class SwissQrBill {

	public $pdf;
	public $inquiry;
	public $school;
	public $documentVersion;
	public $qrBill;
	public $pageCount;

	public function __construct(\Ext_Thebing_Pdf_Fpdi|TCPDF $pdf, \Ext_TS_Inquiry $inquiry, \Ext_Thebing_Inquiry_Document_Version $documentVersion){
		$this->pdf = $pdf;
		$this->inquiry = $inquiry;
		$this->school = $inquiry->getSchool();
		$this->documentVersion = $documentVersion;
	}

	public function handle() {
		try {
			$this->add();
		} catch (\Throwable) {
			$logger = \Log::getLogger();
			foreach ($this->qrBill->getViolations() as $violation) {
				$logger->error('Adding Swiss QR-Bill Code failed', [$violation->getMessage(), $violation->getPropertyPath()]);
			}
		}
	}

	public function add() {

		// Noch Platz auf der letzten Seite?
		$remainingHeight = $this->pdf->getPageHeight() - $this->pdf->GetY();
		// 105mm plus Abstand von 5mm
		if($remainingHeight <= 110) {
			$this->pdf->endPage();
			$this->pdf->addPage();
		}

		$paymentTerms = $this->documentVersion->getPaymentTerms();

		$this->pageCount = $this->pdf->getNumPages();

		$moreThanOnePaymentTerm = count($paymentTerms) > 1;

		if (
			!$moreThanOnePaymentTerm ||
			empty($this->school->getMeta(ExternalApp::KEY_NO_FIRST_PAGE))
		) {
			if ($moreThanOnePaymentTerm) {
				// Die erste(n) Seite(n) bleibt/bleiben die Seite(n) ohne QR-Code zum kopieren
				$this->copyPages();
			}
			// Erste Seite mit gesamter Summe
			$this->addQrBill($this->documentVersion->getAmount());
		}

		if ($moreThanOnePaymentTerm) {
			foreach ($paymentTerms as $paymentTerm) {
				// Für jedes PaymentTerm die gleiche(n) Seite(n) mit unterschiedlichem QR-Code
				$this->copyPages();
				$this->addQrBill($paymentTerm->amount);
			}

			// Die Seite(n) die benutzt wurde(n), wieder löschen
			$this->deletePages();
		}
	}

	public function addQrBill($amount) {

		// Create a new instance of QrBill, containing default headers with fixed values
		$this->qrBill = QrBill\QrBill::create();

		$school = $this->school;

		$schoolName = $school->getMeta(ExternalApp::KEY_ACCOUNT_HOLDER_NAME);
		if (empty($schoolName)) {
			$schoolName = $school->name;
		}

		$schoolAdress = $school->getMeta(ExternalApp::KEY_ACCOUNT_HOLDER_ADRESS);
		if (empty($schoolAdress)) {
			$schoolAdress = $school->address;
		}

		$schoolZip = $school->getMeta(ExternalApp::KEY_ACCOUNT_HOLDER_ZIP);
		if (empty($schoolZip)) {
			$schoolZip = $school->zip;
		}

		$schoolCity = $school->getMeta(ExternalApp::KEY_ACCOUNT_HOLDER_CITY);
		if (empty($schoolCity)) {
			$schoolCity = $school->city;
		}

		$schoolIBAN = $school->getMeta(ExternalApp::KEY_ACCOUNT_HOLDER_IBAN);
		if (empty($schoolIBAN)) {
			$schoolIBAN = $school->iban;
		}

		$this->qrBill->setCreditor(
			QrBill\DataGroup\Element\CombinedAddress::create(
				$schoolName,
				$schoolAdress,
				$schoolZip.' '.$schoolCity,
				$school->country_id,
			)
		);

		$this->qrBill->setCreditorInformation(
			QrBill\DataGroup\Element\CreditorInformation::create(
				$schoolIBAN // This is a special QR-IBAN. Classic IBANs will not be valid here.
			)
		);

		$addressTypeAndId = $this->documentVersion->getAddress();
		$languageObject = new \Tc\Service\Language\Frontend($school->getLanguage());

		$documentAddress = new \Ext_Thebing_Document_Address($this->inquiry);
		$addressData = $documentAddress->getAddressData($addressTypeAndId, $languageObject);

		$studentZipAndCity = $addressData['document_zip'] . ' ' . $addressData['document_city'];

		$this->qrBill->setUltimateDebtor(
			QrBill\DataGroup\Element\CombinedAddress::create(
				$addressData['document_firstname'] . ' ' . $addressData['document_lastname'],
				$addressData['document_address'],
				$studentZipAndCity,
				$addressData['document_country_iso']
			)
		);

		$this->qrBill->setPaymentAmountInformation(
			QrBill\DataGroup\Element\PaymentAmountInformation::create(
				$this->documentVersion->getCurrency()->iso4217,
				$amount
			)
		);

		$invoiceNumber = $this->documentVersion->getDocument()->document_number;


		// Add payment reference
		// This is what you will need to identify incoming payments.
		$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
			$school->getMeta(ExternalApp::KEY_BESR),  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
			$invoiceNumber // Darf nur numerisch sein
		);

		$this->qrBill->setPaymentReference(
			QrBill\DataGroup\Element\PaymentReference::create(
				QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
				$referenceNumber
			)
		);

		// Optionally, add some human-readable information about what the bill is for.
		$this->qrBill->setAdditionalInformation(
			QrBill\DataGroup\Element\AdditionalInformation::create(
				$invoiceNumber
			)
		);

		$output = new QrBill\PaymentPart\Output\TcPdfOutput\TcPdfOutput($this->qrBill, $school->getLanguage(), $this->pdf);
		$output->setPrintable(false);
		$output->getPaymentPart();
	}

	public function copyPages() {

		$pageCount = $this->pageCount;
		$pageCountForAction = 1;
		while ($pageCount != 0) {
			$this->pdf->copyPage($pageCountForAction);
			$pageCountForAction++;
			$pageCount--;
		}
	}

	public function deletePages() {

		$pageCount = $this->pageCount;
		while ($pageCount != 0) {
			$this->pdf->deletePage(1);
			$pageCount--;
		}
	}

}