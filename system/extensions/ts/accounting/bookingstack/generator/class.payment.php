<?php

class Ext_TS_Accounting_Bookingstack_Generator_Payment extends Ext_TS_Accounting_Bookingstack_Generator {
	/**
	 * @var Ext_Thebing_Inquiry_Payment
	 */
	private $_oPayment;

	public function __construct(Ext_Thebing_Inquiry_Payment $oPayment, array $aIgnoreErrors = []) {
		$this->_oPayment = $oPayment;
		$this->aIgnoreErrors = $aIgnoreErrors;
		$this->_oInquiry = $oPayment->getInquiry();
		$this->_oInbox = $this->_oInquiry->getInbox();
		$this->_oSchool = $this->_oInquiry->getSchool();

		$this->loadCompany();
	}

	public function getEntityName(): string {

		$name = collect($this->_oPayment->getAllDocuments())
			->map(function($document) {
				return $document->document_number;
			})
			->implode(', ');

		return $name;
	}

	protected function generateQBNumber(array $aEntry): string {
		return '';
	}

	/**
	 * load the Company for the current payment
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function loadCompany(){
		$oCompany = $this->_oPayment->getCompany();
		if (!$oCompany){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_company_found', $this);
		}
		$this->_oCompany = $oCompany;
	}

	protected function getEarliestServiceFrom(): ?\Carbon\Carbon {
		return new \Carbon\Carbon($this->_oPayment->date);
	}

	protected function getReceipt(): ?Ext_Thebing_Inquiry_Document {
		return $this->_oPayment->getReceipts($this->_oInquiry, $this->_oInquiry->hasAgency() ? 'receipt_agency' : 'receipt_customer')->first();
	}
	
	protected function generateStackEntries(): array {

		$aDocuments = $this->_oPayment->getAllDocuments();
		$aItems = [];

		$oCurrency = Ext_Thebing_Currency::getInstance($this->_oPayment->getFirstItem()->currency_inquiry);

		$aAccountNameData = $this->_oPayment->getAccountNameData();

		$oAccountIncome = $this->getIncomeAccount($aAccountNameData);
		$oAccountExpense = null;

		if ($this->_oCompany->isDoubleAccounting()) {
			$oAccountExpense = $this->getExpenseAccount();
		}

		
		$receipt = $this->getReceipt();
		
		$aGlobalEntry = [];
		$aGlobalEntry['payment_id'] = $this->_oPayment->getId();
		$aGlobalEntry['document_id'] = $receipt?->id ?? '';
		$aGlobalEntry['document_number'] = $receipt?->document_number ?? '';
		$aGlobalEntry['receipt_text'] = '';
		$aGlobalEntry['description'] = $this->_oPayment->getMethod()->name;
		$aGlobalEntry['account_number_expense'] = ($oAccountExpense) ? $oAccountExpense->account_number : '';
		$aGlobalEntry['account_number_income'] = $oAccountIncome->account_number;
		$aGlobalEntry['account_automatic_expense']= $oAccountExpense->automatic_account;
		$aGlobalEntry['account_automatic_income'] = $oAccountIncome->automatic_account;
		$aGlobalEntry['cost_center'] = '';
		$aGlobalEntry['item_id'] = 0;
		$aGlobalEntry['currency'] = $oCurrency->getIso();
		$aGlobalEntry['document_date'] = $this->_oPayment->date;
		$aGlobalEntry['date'] = $this->_oPayment->date;
		$aGlobalEntry['from'] = $this->_oInquiry->getServiceFrom();
		$aGlobalEntry['until'] = $this->_oInquiry->getServiceUntil();
		$aGlobalEntry['position_type'] = 'payment';
		$aGlobalEntry['double_accounting'] = (int)$this->_oCompany->isDoubleAccounting();
		$aGlobalEntry['debit_credit'] = 'S';
		$aGlobalEntry['account_type'] = 'S';
		$aGlobalEntry['address_type'] = $aAccountNameData['type'];
		$aGlobalEntry['address_type_id'] = $aAccountNameData['id'];
		$aGlobalEntry['address_type_object_name'] = '';
		$aGlobalEntry['address_firstname'] = '';
		$aGlobalEntry['address_lastname'] = '';

		if(isset($aAccountNameData['type'])) {
			$aGlobalEntry['address_type'] = (string)$aAccountNameData['type'];
		}
		if(isset($aAccountNameData['id'])) {
			$aGlobalEntry['address_type_id'] = (string)$aAccountNameData['id'];
		}
		if(isset($aAccountNameData['object_name'])) {
			$aGlobalEntry['address_type_object_name'] = (string)$aAccountNameData['object_name'];
		}
		if(isset($aAccountNameData['firstname'])) {
			$aGlobalEntry['address_firstname'] = (string)$aAccountNameData['firstname'];
		}
		if(isset($aAccountNameData['lastname'])) {
			$aGlobalEntry['address_lastname'] = (string)$aAccountNameData['lastname'];
		}
	
		$paymentParts = [];
		
		if (!empty($aDocuments)) {

			foreach($aDocuments as $oDocument) {
				// Buchung kann pro Dokument abweichen bei Gruppen
				$documentInquiry = $oDocument->getInquiry();

				if(!isset($paymentParts[$oDocument->document_number])) {
					$paymentParts[$oDocument->document_number] = [
						'amount' => 0.0,
						'document' => $oDocument,
						'inquiry' => $documentInquiry
					];
				}

				$paymentParts[$oDocument->document_number]['amount'] += $this->_oPayment->getAmount($documentInquiry->getId(), $oDocument->getId());;
			}

		} else {

			// Zahlungen ohne Dokument
			$paymentParts[] = [
				'amount' => $this->_oPayment->getAmount(),
				'document' => null,
				'inquiry' => $this->_oInquiry
			];

		}

		foreach($paymentParts as $paymentPart) {

			$aEntry = $aGlobalEntry;
			$aEntry['main_document_number'] = '';
			$aEntry['invoice_document_number'] = '';
			$aEntry['amount'] = $paymentPart['amount'];
			$aEntry['amount_default_currency'] = $paymentPart['amount'];

//			if($aEntry['amount'] < 0) {
//				$aEntry['amount'] *= -1;
//				$aEntry['amount_default_currency'] *= -1;
//				$aEntry['debit_credit'] = 'H';
//			}

			if ($paymentPart['document']) {

				$aEntry['invoice_document_id'] = $paymentPart['document']->id;
				$aEntry['main_document_number'] = $paymentPart['document']->document_number;
				$aEntry['invoice_document_number'] = $paymentPart['document']->document_number;

				if ($this->_oCompany->debitor_by_invoice) {
					$aAccountNameData = $paymentPart['document']->getLastVersion()->getAddressNameData();

					if (isset($aAccountNameData['type'])) {
						$aEntry['address_type'] = (string)$aAccountNameData['type'];
					}
					if (isset($aAccountNameData['id'])) {
						$aEntry['address_type_id'] = (string)$aAccountNameData['id'];
					}
					if (isset($aAccountNameData['object_name'])) {
						$aEntry['address_type_object_name'] = (string)$aAccountNameData['object_name'];
					}
					if (isset($aAccountNameData['firstname'])) {
						$aEntry['address_firstname'] = (string)$aAccountNameData['firstname'];
					}
					if (isset($aAccountNameData['lastname'])) {
						$aEntry['address_lastname'] = (string)$aAccountNameData['lastname'];
					}

					$oAccountIncome = $this->getIncomeAccount($aAccountNameData);

					$aEntry['account_number_income'] = $oAccountIncome->account_number;
					$aEntry['account_automatic_income'] = $oAccountIncome->automatic_account;
				}

				$oParentDocument = $paymentPart['document']->getParentDocument();

				if ($oParentDocument) {
					$aEntry['main_document_number'] = $oParentDocument->document_number;
				}
			}

			// Sage > Zahlung auf jeweils zwei Einträge splitten (#19955)
			if($this->_oCompany->payment_entries_split) {
				
				$aEntryMethod = $aEntryAccount = $aEntry;
				
				$aEntryMethod['amount_if_claim'] = $aEntryMethod['amount'];
				$this->addStackEntry($aItems, $aEntryMethod);
				
				$aEntryAccount['account_number_expense'] = '';
				$aEntryAccount['amount_if_position'] = $aEntryAccount['amount'];
				$this->addStackEntry($aItems, $aEntryAccount);
				
			} else {
				
				$aEntry['amount_if_claim'] = $aEntry['amount'];
				$aEntry['amount_if_position'] = $aEntry['amount'];
				
				$this->addStackEntry($aItems, $aEntry);
				
			}
			
		}

		return $aItems;
	}

	protected function getReceiptTextForEntry(array $entry) {

		$oReceiptText = $this->getReceiptText();

		$text = '';

		if ($oReceiptText) {
			
			$text = $oReceiptText->findText(null, 'payment');

			$oPlaceholder = new \TsAccounting\Service\Placeholder\Company\TemplateReceiptText();
			$oPlaceholder->setInquiry($this->_oInquiry);
			$oPlaceholder->setEntry($entry);
			$text = $oPlaceholder->replace($text);

			if (empty($text) && !in_array('no_receipt_text_found', $this->aIgnoreErrors)) {
				throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_receipt_text_found', $this);
			}

			$text = $this->removeLineBreaks($text);
		}

		if ($this->testMode) {
			$text .= ' -- TEST EXPORT --';
		}

		return $text;
	}
	
	protected function addStackEntry(array &$entries, array $entry) {
		
		$entry['receipt_text'] = $this->getReceiptTextForEntry($entry);
		
		$hookData = array(
			'entry'     => &$entry,
			'entity'   	=> $this->_oPayment,
			'company'   => $this->_oCompany,
			'school'    => $this->_oSchool,
			'item'      => null
		);

		\System::wd()->executeHook('ts_accounting_bookingstack_generator_item_stack_entry', $hookData);

		$entry['company_id'] = (int)$this->_oCompany->getId();
		$entry['school_id'] = (int)$this->_oSchool->getId();
		$entry['inbox_id'] = ($this->_oInbox) ? (int)$this->_oInbox->getId() : 0;

		if ($this->_oInquiry->hasAgency()) {
			$entry['agency_id'] = (int)$this->_oInquiry->agency_id;
		}

		$entries[] = $entry;
		
	}


	protected function getExpenseAccount() {
		$oAccount = $this->_oCompany->getAllocationsObject()->getPaymentMethodAccount($this->_oPayment->getMethod());

		if(!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_paymentmethod_account', $this);
		}

		return $oAccount;
	}

	protected function getIncomeAccount(array $aAccountData) {

		$sAddressType = $aAccountData['type'];

		if(
			// Kunde, Schule
			$sAddressType === 'contact'
		) {

			$oAccount = new stdClass();
			$oAccount->account_number = $this->getCustomerNumber();
			$oAccount->automatic_account = -1; // keine Angabe
			
		} else if(
			// Sponsor
			$sAddressType === 'sponsor'
		) {

			$oAccount = new stdClass();
			$oAccount->account_number = $this->getSponsorNumber();
			$oAccount->automatic_account = -1;
			
		} else if(
			// Agentur
			$sAddressType === 'agency'
		){

			$oAccount                       = new stdClass();
			$oAccount->account_number       = $this->getAgencyNumber($this->_oInquiry->getAgency(), false);
			$oAccount->automatic_account    = -1; // keine Angabe

		}

		if (!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('unknown_booking_case', $this);
		}

		return $oAccount;
	}
}
