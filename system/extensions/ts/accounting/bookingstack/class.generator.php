<?php

use \Carbon\Carbon;

abstract class Ext_TS_Accounting_Bookingstack_Generator {
	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $_oInquiry;
	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool;
	/**
	 * @var Ext_Thebing_Client_Inbox
	 */
	protected $_oInbox;
	/**
	 * @var \TsAccounting\Entity\Company
	 */
	protected $_oCompany;

	protected $testMode = false;

	/**
	 * @var \TsAccounting\Service\Interfaces\AbstractInterface
	 */
	protected $interface;
	
	protected array $aIgnoreErrors = [];

	abstract public function getEntityName(): string;

	abstract protected function generateStackEntries(): array;

	abstract protected function generateQBNumber(array $aEntry): string;

	abstract protected function getEarliestServiceFrom(): ?Carbon;

	public function setInterface(\TsAccounting\Service\Interfaces\AbstractInterface $interface) {
		$this->interface = $interface;
	}
	
	public function getCompany(): \TsAccounting\Entity\Company {
		return $this->_oCompany;
	}

	/**
	 * @see \Ext_TS_Accounting_Bookingstack_Generator_Document::generateDocumentItemStackEntry()
	 */
	public function createStack($bReturnIds=false) {
		
		$aEntries = $this->generateStackEntries();

		if($bReturnIds === true) {
			$aReturn = [];
		}

		$dEarliestCommencement = $this->getEarliestServiceFrom();
		$oCompany = $this->getCompany();

		foreach($aEntries as $aEntry) {

			// Die QB Rechnungsnummer soll nur generiert werden wenn das Interface Quickbooks ist
			$sQBNumber = '';
			if(strpos($oCompany->interface, 'quickbooks') !== false) {
				$sQBNumber = $this->generateQBNumber($aEntry);
			}

			// Pflichtfeld bei XERO
			if(
				$oCompany->interface == 'xero' &&
				empty($aEntry['tax_key'])
			) {
				$aEntry['tax_key'] = '0';
			}
			
			$oStack = new Ext_TS_Accounting_BookingStack();
			foreach ($aEntry as $sField => $mValue) {
				if (isset($oStack->$sField)) {
					$oStack->$sField = $mValue;
				}
			}

			/*
			 * Sonderfälle
			 * Hier wurden früher alle Zuweisungen manuell vorgenommen. Wegen der automatische Zuweisung per Foreach (s.o.), 
			 * die aber nicht vollständig korrekt war, müssen hier noch ein paar Werte manuell gesetzt werden bei denen
			 * die Keys nicht übereinstimmen. Aber da ich Probleme nicht mehr ersehen kann wg. der foreach, muss hier jetzt 
			 * jeweils ein isset drum :-(
			 */
			if(isset($aEntry['receipt_text'])) {
				$oStack->stack_description = (string)$aEntry['receipt_text'];
			}
			
			if(isset($aEntry['description'])) {
				$oStack->position_description = (string)($aEntry['description'] ?? '');
			}
			
			if(isset($aEntry['amount'])) {
				$oStack->amount = round((float)$aEntry['amount'], 5);
			}
			
			if(isset($aEntry['amount_default_currency'])) {
				$oStack->amount_default_currency = round((float)$aEntry['amount_default_currency'], 5);
			}
			
			if(isset($aEntry['currency'])) {
				$oStack->currency_iso = (string)$aEntry['currency'];
			}
			
			if(isset($aEntry['date'])) {
				$oStack->booking_date = $aEntry['date'];
			}
			
			if(isset($aEntry['from'])) {
				$oStack->service_from = $aEntry['from'];	
			}
			
			if(isset($aEntry['until'])) {
				$oStack->service_until = $aEntry['until'];
			}
			
			if(isset($aEntry['address_type_id'])) {
				$oStack->address_type_id = (int)($aEntry['address_type_id'] ?? 0);	
			}
			
			$oStack->qb_number = $sQBNumber;

			if($dEarliestCommencement) {
				$oStack->earliest_commencement = $dEarliestCommencement->toDateString();
			}

			// Buchungsschlüssel schreiben
			// Muss hier passieren, weil der Betrag ggf. konvertiert wird
			if($oStack->amount >= 0) {
				$oStack->posting_key = $oCompany->posting_key_positive;
			} else {
				$oStack->posting_key = $oCompany->posting_key_negative;
			}

			$mValidate = $oStack->validate();

			if($mValidate !== true){
				throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('save_error', $this, $mValidate);
			}

			$oStack->save();

			if($bReturnIds === true) {
				$aReturn[] = $oStack->id;
			}

		}

		if($bReturnIds === true) {
			return $aReturn;
		}

		return true;
	}

	/**
	 * ermittelt die Kundennummer
	 * wird benötigt da die nummer ggf das konto ist
	 * das kann die Kundennummer sein oder der nummerkreis den man bei den Firmen anlegen kann
	 * @return string
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getCustomerNumber(){
		$sCustomerNumber    = '';
		$bCustomerNumber    = (bool)$this->_oCompany->customer_account_use_number;
		if($this->_oInquiry){
			$oCustomer          = $this->_oInquiry->getFirstTraveller();

			if($bCustomerNumber){
				$sCustomerNumber    = $oCustomer->getCustomerNumber();
			} else {
				$iNumberRange        = (int)$this->_oCompany->customer_account_numberrange_id;
				$oNumberrnge         = new Ext_TS_Accounting_Bookingstack_Numberrange_Contact($iNumberRange, $this->_oCompany, $oCustomer, 'customer');
				$sCustomerNumber     = $oNumberrnge->createNumber();

				if($sCustomerNumber === false) {
					throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('numberrange_locked', $this);
				}
			}
		}
		return $sCustomerNumber;
	}

	/**
	 * @return mixed
	 */
	public function getSponsorNumber(){
		return $this->_oInquiry->getSponsor()->number;
	}

	/**
	 * ermittelt die Agenturnummer die als Konto benutzt weden soll.
	 * das kann die Agenturnummer an sich sein oder der nummernkreis den man unter firmen angeben kann
	 * @param $bPassiv
	 * @return string
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getAgencyNumber($oAgency, $bPassiv){

		if(!$bPassiv){
			$bAgencyNumber              = (bool)$this->_oCompany->agency_active_account_use_number;
			$sType                      = 'agency_active';
		} else {
			$bAgencyNumber              = (bool)$this->_oCompany->agency_activepassive_account_use_number;
			$sType                      = 'agency_active_passiv';
		}

		$sNumber = '';

		if($bAgencyNumber){
			// Wenn die Agentur nicht mehr vorhanden ist darf keine Nummer zurückgegeben werden
			// #5744
			if($oAgency) {
				$sNumber    = $oAgency->getNumber();
			}
		} else if($this->_oInquiry) {
			$oCustomer          = $this->_oInquiry->getFirstTraveller();
			if(!$bPassiv){
				$iNumberRange   = (int)$this->_oCompany->agency_active_account_numberrange_id;
			} else {
				$iNumberRange   = (int)$this->_oCompany->agency_activepassive_account_numberrange_id;
			}
			$oNumberrange        = new Ext_TS_Accounting_Bookingstack_Numberrange_Contact($iNumberRange, $this->_oCompany, $oCustomer, $sType);
			$sNumber            = $oNumberrange->createNumber();

			if($sNumber === false) {
				throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('numberrange_locked', $this);
			}
		}

		return $sNumber;
	}

	public function setTestMode(bool $mode) {
		$this->testMode = $mode;
	}	
	
	/**
	 * @return \TsAccounting\Entity\Company\TemplateReceiptText
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	protected function getReceiptText() {

		$oReceiptText = \TsAccounting\Entity\Company\TemplateReceiptText::searchByCombination($this->_oCompany, $this->_oSchool, $this->_oInbox);

		if(
			!$oReceiptText && 
			!in_array('no_receipt_text_found', $this->aIgnoreErrors)
		) {
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_receipt_text_found', $this);
		}

		return $oReceiptText;
	}
	
	/**
	 * Zeilenumbrüche ersetzen, da diese Probleme machen beim Import in DATEV und Excel (aber nicht bei LibreOffice)
	 *
	 * @param $sString
	 * @return string
	 */
	protected function removeLineBreaks($sString) {
		return preg_replace('/[\r\n]+/', ' ', $sString);
	}

}
