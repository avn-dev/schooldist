<?php

use TsAccounting\Entity\Company;

/**
 * Example:
 * Quadratus:
 * Fix length
 *
 * M: Static
 * 9C00003: Compte / Account
 * CS/70: Journal?
 * 000: Fill with 0
 * 011219: Date / Receipt date 2019-12-01
 * E/F: Type Monnaie / Type of amount?
 * C+/D+: Sens / Direction
 * 000000000500: Montant / Amount i cent?
 * Sothi: ?
 * 3825: No Ecrit / Receipt number
 * EUR: Currency
 * CS/70: Journal?
 * RECEPTION novembre 2019: Receipt text
 * 000070536/19-11-0001: No Piece / Zeichnungsnummer
 * M9C00003 CS000011219E                    C+000000000500                   Sothi                    3825    EURCS    RECEPTION novembre 2019 Recept  000070536
 * M9C00003 70000011119F                    D+000000054500                   Sothi                    3859    EUR70    RECEPTION novembre 2019         19-11-0001
 */
class Ext_TS_Accounting_Bookingstack_Generator_Document extends Ext_TS_Accounting_Bookingstack_Generator {

	/**
	 * @var Ext_Thebing_Inquiry_Document
	 */
	protected $_oDocument;

	/**
	 * @var Company
	 */
	protected $_oCompany;

	/**
	 * @var Ext_Thebing_Inquiry_Document_Version
	 */
	protected $_oVersion;

	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool;

	/**
	 * @var Ext_Thebing_Client_Inbox
	 */
	protected $_oInbox;

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $_oInquiry;


	public function __construct(Ext_Thebing_Inquiry_Document $oDocument, array $aIgnoreErrors = []) {
		$this->_oDocument   = $oDocument;
		$this->_oInquiry    = $this->_oDocument->getInquiry();
		$this->aIgnoreErrors = $aIgnoreErrors;
		$this->loadInbox();
		$this->loadSchool();
		$this->loadVersion();
		$this->loadCompany();
	}

	public function getEntityName(): string {
		return $this->_oDocument->document_number;
	}

	/**
	 * load the inbox for the current Document
	 */
	public function loadInbox(){
		$oInbox     = $this->_oDocument->getInbox(true);
		if($oInbox){
			$this->_oInbox = $oInbox;
		}
	}

	/**
	 * load the School for the current Document
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function loadSchool(){
		$oSchool     = $this->_oDocument->getSchool();
		if(!$oSchool){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_school_found', $this);
		}
		$this->_oSchool = $oSchool;
	}

	/**
	 * load the Company for the current Document
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function loadCompany() {
		
		$oCompany = $this->_oDocument->getCompany();
		if(!$oCompany) {
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_company_found', $this);
		}
		$this->_oCompany = $oCompany;
		
		$service = \TsAccounting\Factory\AccountingInterfaceFactory::get($this->_oCompany);
		if($service) {
			$this->interface = $service;
		}
		
	}

	/**
	 * load the last version of the current document
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function loadVersion(){
		$oVersion  = $this->_oDocument->getLastVersion();
		if(!$oVersion){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_version_found', $this);
		}
		$this->_oVersion    = $oVersion;
	}

	protected function getEarliestServiceFrom(): ?\Carbon\Carbon {
		return $this->_oVersion->getEarliestServiceFrom();
	}

	/**
	 * generate the complete Stack for the current document
	 */
	protected function generateStackEntries(): array{

		$bReverseSign = (
			$this->_oCompany->service_account_book_reverse_sign == 3 ||
			($this->_oCompany->service_account_book_reverse_sign && $this->_checkDocumentReversion())
		);

		$aEntries = array();
		if($this->_oDocument->type === 'manual_creditnote') {

			$aEntries = $this->generateManualCreditnoteStackEntries();

		} else {

			$aItems = $this->getDocumentItems();
			$oItem = reset($aItems);

			if(!$oItem) {
				$fAmount = (float)$this->_oVersion->getAmount();
				if(bccomp($fAmount, 0) === 0) {
					// Wenn in einer Gruppe ein Guide alles frei hat, hat er auch keine Positionen #5116
					return [];
				} else {
					throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_version_item_found', $this);
				}
			}

			if ((int)$this->_oCompany->create_claim_debt !== Company::NO_CLAIM_DEBT_POSITIONS) {

				// Datum vom Leistungszeitraum muss alle Leistungen umfassen
				$aFromDates = $aUntilDates = array();
				foreach($aItems as $oTmpItem) {
					$aFromDates[] = $oTmpItem->getFrom();
					$aUntilDates[] = $oTmpItem->getUntil();
				}

				$dFromDate = min($aFromDates);
				$dUntilDate = max($aUntilDates);

				$paymentTerms = array_filter($this->_oVersion->getPaymentTerms(), fn ($paymentTerm) => abs($paymentTerm->amount) > 0);

				if (!empty($paymentTerms)) {
					if ((int)$this->_oCompany->create_claim_debt === Company::SEPARATE_CLAIM_DEBT_POSITIONS) {
						// Pro Anzahlung und Restzahlung eigene Forderungsposition erzeugen
						foreach($paymentTerms as $oPaymentTerm) {
							$aEntry = $this->generateDocumentItemStackEntry($oItem, $oPaymentTerm->amount, $dFromDate, $dUntilDate, 'claim_debt');
							$aEntry['date'] = $oPaymentTerm->date;
							$aEntries[] = $aEntry;
						}
					} else if ((int)$this->_oCompany->create_claim_debt === Company::SINGLE_CLAIM_DEBT_POSITION) {
						// Eine einzelne Gesamtposition aus allen erzeugen
						$amounts = array_map(fn ($paymentTerm) => $paymentTerm->amount, $paymentTerms);
						$dates = array_map(fn ($paymentTerm) => \Carbon\Carbon::parse($paymentTerm->date), $paymentTerms);

						$aEntry = $this->generateDocumentItemStackEntry($oItem, array_sum($amounts), $dFromDate, $dUntilDate, 'claim_debt');
						$aEntry['date'] = min($dates)->toDateString();
						$aEntries[] = $aEntry;
					}
				}
			}

			foreach($aItems as $oItem){
				if($oItem->calculate == 1){

					$bAddItem = true;
					if(
						(
							$this->_oDocument->type == 'creditnote' &&
							Ext_TC_Util::compareFloat($oItem->amount_provision, 0) === 0
						) ||
						(
							Ext_TC_Util::compareFloat($this->getItemAmount($oItem), 0) === 0 && // Betrag mit Rabatt = 0
							(
								Ext_TC_Util::compareFloat($this->getItemAmount($oItem, false), 0) === 0 || // Betrag ohne Rabatt = 0
								!$this->_oCompany->additional_booking_record_for_discount
							)
						)
					) {
						$bAddItem = false;
					}

					if($bAddItem) {
						$aItemEntries = $this->generateDocumentItemStackEntries($oItem);
						$aEntries = array_merge($aEntries, $aItemEntries);
					}
				}
			}
		}

		// Ist dieser Flag gesetzt, muss das Vorzeichen von allen Werten umgekehrt werden
		if($bReverseSign === true) {
			foreach($aEntries as &$aEntry) {
				if(
					$this->_oCompany->service_account_book_reverse_sign == 1 ||
					$this->_oCompany->service_account_book_reverse_sign == 3 ||
					(
						$this->_oCompany->service_account_book_reverse_sign == 2 &&
						$aEntry['position_type'] !== 'claim_debt'
					)
				) {
					$aEntry['amount'] *= -1;
					$aEntry['amount_default_currency'] *= -1;
				}
			}
			unset($aEntry);
		}

		\System::wd()->executeHook('ts_accounting_bookingstack_generator_stack_entries', $aEntries, $this->_oCompany, $this->_oSchool);

		// Hook für spezielles Verhalten bei Wahl von Interface
		if($this->interface) {
			$this->interface->generateDocumentStackEntries($aEntries);
		}

		// Achtung: 2x return in dieser Methode
		return $aEntries;
	}

	/**
	 * generiet einen Stack Eintrag für eine manuelle Creditnote
	 * hier ist das besondere das es KEINE ITEMS gibt!
	 * die position muss daher komplett manuell zusammen gebaut werden,
	 * dafür gibt es aber auch immer nur 1-2 Einträge (einfache/doppeltebuchhaltung) der den gesamt Betrag enthält und es gibt keine Steuern!
	 * @return array
	 */
	public function generateManualCreditnoteStackEntries() {
		$aEntries = array();

		$oCreditnote = $this->_oDocument->getManualCreditnote();
		$oDate = new DateTime($oCreditnote->getCreatedForIndex());

		// Position des Typs manual_creditnote (wird bei Belegtexten und Konteneinstellungen beachtet)
		$oItem = $this->_oVersion->newItem();
		$oItem->type = 'manual_creditnote';
		$oItem->amount = $oCreditnote->amount;
		$oItem->description = L10N::t('Manuelle Agenturgutschrift', 'Thebing » Accounting » Booking Stack');;
		$oItem->index_from = $oDate->format('Y-m-d');
		$oItem->index_until = $oDate->format('Y-m-d');

		if((int)$this->_oCompany->create_claim_debt !== Company::NO_CLAIM_DEBT_POSITIONS) {
			$aEntries[] = $this->generateDocumentItemStackEntry($oItem, $oCreditnote->amount, $oDate, $oDate, 'claim_debt');
		}

		$aEntry = $this->generateDocumentItemStackEntries($oItem);
		$aEntries = array_merge($aEntries, $aEntry);

		return $aEntries;
	}

	/**
	 * Generiert alle Buchungsstapel-Einträge für ein Item (Aufteilung auf Zeitraum)
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return array
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function generateDocumentItemStackEntries(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {

		$bAgencyCreditNote = $this->isAgencyCreditNote();

		$aEntries = array();
		$aSplitDataArrival = array();

		// Bei einem Transferpaket muss diese Position in 2 Positionen gesplittet werden
		if($oItem->type === 'transfer') {
			if(
				isset($oItem->additional_info['transfer_arrival_id']) &&
				isset($oItem->additional_info['transfer_departure_id']) &&
				$oItem->additional_info['transfer_arrival_id'] > 0 &&
				$oItem->additional_info['transfer_departure_id'] > 0
			) {
				// Alle Beträge halbieren, damit Provision auch von brutto/netto wieder korrekt ausgerechnet werden kann
				// TODO Gibt es hier Probleme mit dem Discount? Da wurde in #5662 mal was eingebaut (siehe Legacy)
				$oItem->amount /= 2;
				$oItem->amount_net /= 2;
				$oItem->amount_provision /= 2;

				$oArrivalItem = clone $oItem;
				$oArrivalItem->index_until = $oArrivalItem->index_from;
				$aSplitDataArrival = $this->splitDocumentItem($oArrivalItem);

				$oItem->index_from = $oItem->index_until;
			}
		}

		// Item aufsplitten um die Positioen über den Zeitraum und ggf. für die Steuern getrennt zu haben
		$aSplitData = $this->splitDocumentItem($oItem);

		if(
			$oItem->type === 'transfer' &&
			!empty($aSplitDataArrival)
		) {
			$aSplitData = array_merge($aSplitData, $aSplitDataArrival);
		}

		// Einzelne Splittungen durchgehen und die Buchungsstack Einträge dafür erzeugen
		foreach($aSplitData as $aSplit) {

			$aSplitEntries = [];

			if(
				$this->_oDocument->isNetto() &&
				$this->_oCompany->book_net_with_gross_and_commission &&
				$oItem->amount_provision != 0
			) {
				// Prozentsatz ermitteln
				$fFactor = $aSplit['amount'] / $oItem->getAmount('netto');

				// Brutto
				$aSplitEntries[] = ['amount'=> $oItem->getAmount('brutto')*$fFactor, 'factor' => $fFactor, 'commission_from_net_invoice'=> false, 'is_commission' => false, 'discount'=> false];
				// Provision
				$aSplitEntries[] = ['amount'=> $oItem->getAmount('commission')*$fFactor*-1, 'factor' => $fFactor*-1, 'commission_from_net_invoice'=> true, 'is_commission' => true, 'discount'=> false];

			} else {

				$isCommission = false;
				if($bAgencyCreditNote) {
					$isCommission = true;
				}

				$aSplitEntries[] = ['amount'=> $aSplit['amount'], 'factor' => 1, 'commission_from_net_invoice'=> false, 'is_commission' => $isCommission, 'discount'=> false];
				
			}

			$amountDiscount = (float)$oItem->amount_discount;

			if(
				$this->_oCompany->additional_booking_record_for_discount &&
				!empty($amountDiscount)
			) {

				$aTmpSplitEntries = $aSplitEntries;
				$aSplitEntries = [];
				foreach($aTmpSplitEntries as &$aSplitEntry) {
					
					if(
						$this->_oCompany->additional_booking_record_for_discount === 'all' ||
						(
							$this->_oCompany->additional_booking_record_for_discount === 'not_commission' &&
							!$aSplitEntry['is_commission']
						)
					) {

						$fFactor = $aSplitEntry['factor'];

						$discountedAmount = $oItem->getAmount('brutto', true);
						$originalAmount = $oItem->getAmount('brutto', false);

						if($discountedAmount) {
							$fDiscountFactor = $oItem->getAmount('brutto', false) / $discountedAmount;
							$fWithoutDiscount = $aSplitEntry['amount'] * $fDiscountFactor;
							$fWithDiscount = $aSplitEntry['amount'];
						} else {
							// 100 % Rabatt
							$fDiscountFactor = 0;
							$fWithoutDiscount = $originalAmount * $fFactor;
							$fWithDiscount = $discountedAmount * $fFactor;
						}

						$fDiscount = $fWithDiscount - $fWithoutDiscount;

						// Betrag
						$aSplitEntries[] = ['amount'=> $fWithoutDiscount, 'commission_from_net_invoice'=> $aSplitEntry['commission_from_net_invoice'], 'discount'=> $aSplitEntry['discount']];
						// Rabatt
						$aSplitEntries[] = ['amount'=> $fDiscount, 'commission_from_net_invoice'=> $aSplitEntry['commission_from_net_invoice'], 'discount'=> true];
					
					} else {
						
						$aSplitEntries[] = $aSplitEntry;
						
					}
				}

			}

			foreach($aSplitEntries as $aSplitEntry) {
				$aEntries[] = $this->generateDocumentItemStackEntry($oItem, $aSplitEntry['amount'], $aSplit['from'], $aSplit['until'], $aSplit['position_type'], $aSplitEntry['commission_from_net_invoice'], $aSplitEntry['discount'], $aSplit['accounting_type']);
			}
			
		}

		$aHookData = array(
			'entries' => &$aEntries,
			'company' => $this->_oCompany,
			'school' => $this->_oSchool,
			'item' => $oItem
		);

		\System::wd()->executeHook('ts_accounting_bookingstack_generator_item_stack_entries', $aHookData);

		// Hook für spezielles Verhalten bei Wahl von Interface
		if($this->interface) {
			$this->interface->generateDocumentItemStackEntries($aEntries, $oItem);
		}

		return $aEntries;
	}

	/**
	 * Gibt die Nummer des passiven Rechnungsabgrenzungskonto zurück
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param int $iTax
	 * @return stdClass
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getPassivAccountNumber($oItem, $iTax){
		$oAccount = $this->_oCompany->getAllocationsObject()->getContinuanceAccount($oItem, $iTax, 'accrual_account_passive');
		if(!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('unknown_passiv_account', $this);
		}
		return $oAccount->account_number;
	}

	/**
	 * Gibt die Nummer des aktiven Rechnungsabgrenzungskonto zurück
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param int $iTax
	 * @return stdClass
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getActiveAccountNumber($oItem, $iTax){
		$oAccount = $this->_oCompany->getAllocationsObject()->getContinuanceAccount($oItem, $iTax, 'accrual_account_active');
		if(!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('unknown_active_account', $this);
		}
		return $oAccount->account_number;
	}

	/**
	 * gibt den Adresstypen zurück
	 * muss bei manuellen creditnotes umgeschrieben werden da man dort keine adresse speichern kann und die version methode dann immer "address" zurück geben würde
	 * @return string
	 */
	public function getAddressType(){
		$sAddressType       = $this->_oVersion->getAddressType();
		return $sAddressType;
	}

	public function getAddressTypeId(){
		$iAddressTypeId         = (int)$this->_oVersion->getAddressTypeId();
		return $iAddressTypeId;
	}

	/**
	 * ermittelt das Konto für die Forerung
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param type $sAccountType
	 * @return \stdClass
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getClaimAccount(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $sAccountType, $iTax){

		$bDoubleAccounting = $this->isDoubleAccounting();
		$sAddressType       = $this->getAddressType();
		$bAgencyCreditNote = $this->isAgencyCreditNote();
		$bReduction         = $this->isReducing();
		$bPassivAndActive   = $this->isActiveAndPassive();

		// Bei einfacher Buchhaltung, aber mit Forderungsposition, gibt es kein Sollkonto
		if(!$bDoubleAccounting) {
			$sAccountType = 'expense';
		}

		// Kundennummer wird als Konto genommen ...
		if(
			// bei einer Forderung einer normalen Rechnung an den Kunden für das Sollkonto
		(
			!$bAgencyCreditNote && // kein gutschrift
			$sAccountType == 'expense' && // Sollkonto
			strpos($sAddressType, 'agency') === false &&
			strpos($sAddressType, 'sponsor') === false
		)
		) {
			$oAccount = new stdClass();
			if(empty($this->_oCompany->fixed_expense_claim_debt_account_number)) {
				$oAccount->account_number = $this->getCustomerNumber();
			} else {
				$oAccount->account_number = $this->_oCompany->fixed_expense_claim_debt_account_number;
			}
			$oAccount->automatic_account = -1; // keine Angabe

			// Sponsor
		} elseif(
			!$bAgencyCreditNote && // kein gutschrift
			$sAccountType == 'expense' && // Sollkonto
			strpos($sAddressType, 'sponsor') !== false
		) {

			$oAccount = new stdClass();

			if(empty($this->_oCompany->fixed_expense_claim_debt_account_number)) {
				$oAccount->account_number = $this->getSponsorNumber();
			} else {
				$oAccount->account_number = $this->_oCompany->fixed_expense_claim_debt_account_number;
			}

			$oAccount->automatic_account = -1;

			// Agenturnummer wird als Konto genommen ...
		} else if(
			// Für die Forderung bei einer Gutschrift an die Agentur mit Reduktion für das Sollkonto
			(
				// gutschrift + reduktion
				// oder keine gutschrift
				(
					(
						$bAgencyCreditNote && // gutschrift
						$bReduction  // gutschrift als Reduktion
					) ||
					!$bAgencyCreditNote
				) &&
				// und sollkonto sowei an die agentur
				$sAccountType == 'expense' && // Sollkonto
				strpos($sAddressType, 'agency') === 0 // agency_xxx
			)  ||
			// oder bei einer Forderung einer Gutschrift an die Agentur ohne Reduktion für das Habenkonto
			(
				$bAgencyCreditNote && // gutschrift
				$sAccountType == 'income' && // Hbenkonto
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				!$bReduction // Gutschrift nicht als Reduktion
			)
		){
			$bPassiv                        = false;
			// bei Haben ohne Reduktion muss der passive kreis genommen werden
			if(
				$sAccountType == 'income' &&
				$bPassivAndActive &&
				!$bReduction
			){
				$bPassiv                    = $bPassiv;
			}
			$oAccount                       = new stdClass();

			if(empty($this->_oCompany->fixed_expense_claim_debt_account_number)) {
				$oAccount->account_number = $this->getAgencyNumber($this->_oDocument->getAgency(), $bPassiv);
			} else {
				$oAccount->account_number = $this->_oCompany->fixed_expense_claim_debt_account_number;
			}

			$oAccount->automatic_account    = -1; // keine Angabe
			// Passives Rechnungsabgrenzungskonto wird genommen ...
		} else if(
			// bei einer Forderung einer Gutschrift an die Agentur ohne Reduktion für das Sollkonto
			// wenn die Verbuchungsart auf nur Aktiv steht
			(
				$bDoubleAccounting &&
				!$bPassivAndActive && // nur aktiv
				$bAgencyCreditNote && // gutschrift
				$sAccountType == 'expense' && // Sollkonto
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				!$bReduction // Gutschrift nicht als Reduktion
			)  ||
			// bei einer Forderung einer Gutschrift an die Agentur mit Reduktion für das Habenkonto
			// wenn Verbuchungsart nur Aktiv
			(
				$bDoubleAccounting &&
				!$bPassivAndActive && // nur aktiv
				$bAgencyCreditNote && // gutschrift
				$sAccountType == 'income' && // Habenkonto
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				$bReduction // Gutschrift als Reduktion
			) ||
			// bei einer Forderung einer normalen Rechnung an den Kunden für das Habenkonto
			(
				$bDoubleAccounting &&
				!$bAgencyCreditNote && // kein gutschrift
				$sAccountType == 'income' // Sollkonto
			)
		){
			$oAccount                   = new stdClass();
			$oAccount->account_number   = $this->getPassivAccountNumber($oItem, $iTax);
			$oAccount->automatic_account    = -1; // keine Angabe
			// Aktives Rechnungsabgrenzungskonto
		} else if(
			// bei einer Forderung einer Gutschrift an die Agentur ohne Reduktion für das Sollkonto
			// wenn die Verbuchungsart auf nur passiv und aktiv steht
			(
				$bDoubleAccounting &&
				$bPassivAndActive && // passiv und aktiv
				$bAgencyCreditNote && // gutschrift
				$sAccountType == 'expense' && // Sollkonto
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				!$bReduction  // Gutschrift nicht als Reduktion
			) ||
			// bei einer Forderung einer Gutschrift an die Agentur mit Reduktion für das Habenkonto
			// wenn Verbuchungsart Aktiv und Passiv ist
			(
				$bDoubleAccounting &&
				$bPassivAndActive && // passiv und aktiv
				$bAgencyCreditNote && // gutschrift
				$sAccountType == 'income' && // Habenkonto
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				$bReduction  // Gutschrift als Reduktion
			)
		){
			$oAccount = new stdClass();
			$oAccount->account_number   = $this->getActiveAccountNumber($oItem, $iTax);
			$oAccount->automatic_account    = -1; // keine Angabe
		}

		if(!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('unknown_claim_booking_case', $this);
		}

		return $oAccount;
	}

	public function getTaxAccount(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $sAccountType = 'income'){
		$oAccount   = $this->_oCompany->getAllocationsObject()->getTaxAccount($oItem);
		// In der einfachen Buchhaltung gibt es keine Bestandskonten, daher Fehlermeldung ignorieren
		/*if(!$oAccount){
			throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_tax_account_found', $this);
		}*/
		return $oAccount;
	}

	/**
	 * ermittelt das Konto für normale Positionen ( keine Forderungen! )
	 * 
	 * @todo Das muss sauber umgesetzt werden. Diese vielen Fälle sind so nicht wartbar. Da gibt es bessere Möglichkeiten das umzusetzen
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param type $sAccountType
	 * @return type
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getPositionAccount(
		Ext_Thebing_Inquiry_Document_Version_Item $oItem, 
		$sAccountType, 
		$iTaxCategory = 0, 
		$sType = 'position', 
		$bCommissionFromNetInvoice=false
	) {

		$bDoubleAccounting = $this->isDoubleAccounting();
		
		$sAddressType = $this->getAddressType();
		
		// Wenn ein Nettobetrag aufgeteilt wird, muss für den Provisionsbetrag das Agenturkonto und für den Bruttobetrag der Kunde verwendet werden
//		if(
//			$bCommissionFromNetInvoice === true &&
//			strpos($sAddressType, 'agency') === 0
//		) {
//			$sAddressType = 'address';
//		}
		
		$bAgencyCreditNote = $this->isAgencyCreditNote();
		$bReduction = $this->isReducing();
		$bPassivAndActive = $this->isActiveAndPassive();

		if(
			(int)$this->_oCompany->create_claim_debt !== Company::NO_CLAIM_DEBT_POSITIONS &&
			(
				// wenn normale Rechnung und Sollkonto
				// dann nehme das Passive Rechnungsabgrenzungskonto
				(
					$bDoubleAccounting && // Muss immer doppelt sein (kein PRAP bei einfacher Buchhaltung)
					!$bAgencyCreditNote  && // kein gutschrift
					$sAccountType == 'expense' // Sollkonto
				) ||
				// wenn gutschrift an agentur und nur aktive verbuchung sowie geduktion
				// für das sollkonto dann nehme das Passive Rechnungsabgrenzungskonto
				(
					$bDoubleAccounting && // Muss immer doppelt sein (kein PRAP bei einfacher Buchhaltung)
					$bAgencyCreditNote &&
					!$bPassivAndActive &&
					$bReduction &&
					strpos($sAddressType, 'agency') === 0 &&// agency_xxx
					$sAccountType == 'expense'
				) ||
				// wenn gutschrift an agentur und nur aktive verbuchung sowie nicht reduktion
				// für das habenkonto dann nehme das Passive Rechnungsabgrenzungskonto
				(
					$bDoubleAccounting && // Muss immer doppelt sein (kein PRAP bei einfacher Buchhaltung)
					$bAgencyCreditNote &&
					!$bPassivAndActive &&
					!$bReduction &&
					strpos($sAddressType, 'agency') === 0 &&// agency_xxx
					$sAccountType == 'income'
				)
			)
		) {

			$oAccount                       = new stdClass();
			$oAccount->account_number       = $this->getPassivAccountNumber($oItem, $iTaxCategory);
			$oAccount->automatic_account    = -1;

		} else if(
			(int)$this->_oCompany->create_claim_debt !== Company::NO_CLAIM_DEBT_POSITIONS &&
			(
				// wenn gutschrift an agentur und verbuchungsart aktiv und passive sowie reduktion für das Sollkonto
				// nehme das Aufwandskonto das zugewiesen ist
				(
					$bDoubleAccounting && // Muss immer doppelt sein (kein ARAP bei einfacher Buchhaltung)
					$bAgencyCreditNote  &&
					$sAccountType == 'expense' &&
					strpos($sAddressType, 'agency') === 0 &&// agency_xxx
					$bPassivAndActive &&
					$bReduction
				) ||
				// wenn gutschrift an agentur und verbuchungsart aktiv und passive sowie keine reduktion für das Habenkonto
				// nehme das Aufwandskonto das zugewiesen ist
				(
					$bDoubleAccounting && // Muss immer doppelt sein (kein ARAP bei einfacher Buchhaltung)
					$bAgencyCreditNote  &&
					$sAccountType == 'income' &&
					strpos($sAddressType, 'agency') === 0 &&// agency_xxx
					$bPassivAndActive &&
					!$bReduction
				)
			)
		) {

			$oAccount                   = new stdClass();
			$oAccount->account_number   = $this->getActiveAccountNumber($oItem, $iTaxCategory);
			$oAccount->automatic_account    = -1;
		} else if(
			// wenn normale Rechnung und Habenkonto
			// dann nehme das Ertragskonto das zugewiesen ist
			(
				!$bAgencyCreditNote  && // kein gutschrift
				$sAccountType == 'income' // Sollkonto
				//strpos($sAddressType, 'agency') === false
			) ||
			// Wenn Gutschrift an Agentur und nur Aktive Verbuchung sowie Redukiton für das Habenkonto
			// dann nehme das zugewiesene Ertragskonto
			(
				$bAgencyCreditNote  &&
				$sAccountType == 'income' &&
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				!$bPassivAndActive &&
				$bReduction
			) ||
			// Wenn Gutschrift an Agentur und nur Aktive Verbuchung sowie keine Redukiton für das Sollkonto
			// dann nehme das zugewiesene Ertragskonto
			(
				$bAgencyCreditNote  &&
				$sAccountType == 'expense' &&
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				!$bPassivAndActive &&
				$bReduction
			)
		){

			$sTmpAccountType = 'income';

			/*
			 * Manuelle Gutschrift: Entsprechendes Konto steht bei den Aufwandskonten
			 * Position vom Typ Provision (Quickbooks Basic): Provisionskonto steht bei den Aufwandskonten
			 */
			if(
				$this->_oDocument->type == 'manual_creditnote' ||
				$oItem->type === 'commission'
			) {
				$sTmpAccountType = 'expense';
			}

			if($sType == 'vat') {
				$oAccount = $this->getTaxAccount($oItem, $sTmpAccountType);
			} else {
				// nichter verwirren lassen der 3te paramter heißt gleich wie $sAccountType ist aber nicht das gleiche!
				$oAccount = $this->getAccount($oItem, $iTaxCategory, $sTmpAccountType);
			}

		} else if(
			!$bAgencyCreditNote && // kein gutschrift
			!$bReduction
		) {

			if($sType == 'vat') {
				$oAccount = $this->getTaxAccount($oItem, $sTmpAccountType);
			} else {
				$oAccount = $this->getAccount($oItem, $iTaxCategory, $sAccountType);
			}

		} else if(
			// wenn gutschrift an agentur und verbuchungsart aktiv und passive sowie reduktion für das Habenkonto
			// nehme das Aufwandskonto das zugewiesen ist
			(
				$bDoubleAccounting &&
				$bAgencyCreditNote  &&
				$sAccountType == 'income' &&
				strpos($sAddressType, 'agency') === 0 &&// agency_xxx
				$bPassivAndActive
			) ||
			// wenn gutschrift an agentur sowie keine reduktion für das Sollkonto
			// nehme das Aufwandskonto das zugewiesen ist
			(
				$bDoubleAccounting &&
				$bAgencyCreditNote  &&
				$sAccountType == 'expense' &&
				strpos($sAddressType, 'agency') === 0
			) || (
				// S0030 3.2.2: Wenn Gutschrift an Agentur, EINFACHE Buchhaltung, Sollkonto und keine Reduktion:
				// Nehme das zugewiesene Aufwandskonto #6106
				!$bDoubleAccounting &&
				$bAgencyCreditNote &&
				$sAccountType === 'income' &&
				strpos($sAddressType, 'agency') === 0 &&
				!$bReduction
			)
		){

			if($sType == 'vat') {
				$oAccount = $this->getTaxAccount($oItem, 'expense');
			} else {

				$sTmpAccountType = ($sAccountType == 'income')?'expense':'income';

				// nicht verwirren lassen der 3te paramter heißt gleich wie $sAccountType ist aber nicht das gleiche!
				$oAccount = $this->getAccount($oItem, $iTaxCategory, $sTmpAccountType);
			}

		} else {
			$oAccount = $this->getAccount($oItem, $iTaxCategory, $sAccountType);
		}

		return $oAccount;
	}

	/**
	 * ermittelt das konto über die firma mit hilfe der parameter und einstellungen
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param int $iTaxCategory
	 * @param string $sAccountType
	 * @return stdClass
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getAccount(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $iTaxCategory, $sAccountType){

		$oAccount   = $this->_oCompany->getAllocationsObject()->getAccount($oItem, $iTaxCategory, $sAccountType);
		if(!$oAccount){
			//throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_'.$sAccountType.'_account_found', $this);
		}

		return $oAccount;
	}

	/**
	 * ermittelt das Konto für die Übergebene Pos in kombination der Positions art ( forderung/normal/steuer ) und Haben oder Sollkonto
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param string $sType
	 * @param string $sAccountType
	 * @return stdClass
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function getDocumentItemAccount(
		Ext_Thebing_Inquiry_Document_Version_Item $oItem, 
		$sType = 'position', 
		$sAccountType = 'income', 
		$bCommissionFromNetInvoice=false
	) {

		//Forderungen
		if($sType == 'claim_debt') {
			$oAccount = $this->getClaimAccount($oItem, $sAccountType, 0);
		}  else {
			$oAccount = $this->getPositionAccount($oItem, $sAccountType, $oItem->tax_category, $sType, $bCommissionFromNetInvoice);
		}

		if(!$oAccount) {
			//throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_'.$sAccountType.'_account_found', $this);
		}

		return $oAccount;
	}

	/**
	 * check if the document is a creditnote ( creditnote for brutto agency invoices )
	 * @return bool
	 */
	public function isAgencyCreditNote(){
		if(
			$this->_oDocument->type == 'creditnote' ||
			$this->_oDocument->type == 'manual_creditnote'
		){
			return true;
		}
		return false;
	}

	/**
	 * check if reducing is enabled (company option)
	 * @return boolean
	 */
	public function isReducing(){
		if($this->_oCompany->service_account_book_credit_as_reduction == 1) {
			return true;
		}
		return false;
	}

	/**
	 * prüft, ob für ein Dokument die Vorzeichen umgekehrt werden müssen
	 *
	 * Aus der Buchhaltungslogik entfallen folgende Dokumenttypen:
	 * - Gutschriften an die Agentur (basierend auf Bruttorechnung)
	 * - Stornos der Gutschriften an die Agentur
	 * - Manuelle Gutschriften der Agentur
	 * - Stornos der manuellen Gutschriften der Agentur
	 *
	 * Redmine: #5337
	 *
	 * @return boolean
	 */
	protected function _checkDocumentReversion() {

		$sType = $this->_oDocument->type;

		$bReverse = true;

		switch($sType) {
			// Gutschriften an die Agentur (basierend auf Bruttorechnung)
			case 'creditnote':
				// schauen, ob die Gutschrift auf einer Bruttorechnung oder Storno basiert
				$oParentDocument = $this->_oDocument->getParentDocument();

				// Pendant zu isNetto()
				$sType = $oParentDocument->type;
				if($sType === 'storno') {
					$sType = $oParentDocument->getParentDocument()->type;
				}

				if(strpos($sType, 'brutto') !== false) {
					$bReverse = false;
				}

				break;

			// - Manuelle Gutschriften der Agentur
			// - Stornos der manuellen Gutschriften der Agentur
			case 'manual_creditnote':
				if(count($this->_oDocument->manual_creditnotes) > 0) {
					$bReverse = false;
				}
				break;

			default:
				$bReverse = true;
		}

		return $bReverse;
	}

	/**
	 * check if accounting is active and passiv
	 * ( passive kann only be with agency, customer have only active )
	 * @return boolean
	 */
	public function isActiveAndPassive(){
		$bCheck = false;
		$oAgency = $this->_oDocument->getAgency();
		if($oAgency && $oAgency->getId() > 0){
			if($this->_oCompany->agency_account_booking_type == 2){
				$bCheck = true;
			}
		}
		return $bCheck;
	}

	/**
	 * Generiert einen Eintrag für den Buchungsstapel mit allen nötigen Informationen
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param float $fAmount
	 * @param DateTime $oFrom
	 * @param DateTime $oUntil
	 * @param string $sType
	 * @return array
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function generateDocumentItemStackEntry(
		Ext_Thebing_Inquiry_Document_Version_Item $oItem, 
		$fAmount, 
		DateTime $oFrom, 
		DateTime $oUntil, 
		$sType = 'position', 
		$bCommissionFromNetInvoice=false, 
		$bDiscount=false,
		$accountingType = null
	) {

		$aEntry = [
			'item' => $oItem // Wird nicht gespeichert. Ist nur für eventuell nachträgliche Manipulation
		];

		if($bCommissionFromNetInvoice === false) {
			$sIncomeAccountType = 'income';
		} else {
			$sIncomeAccountType = 'expense_net';
		}

		$sExpenseAccountType = 'expense';

		if(empty($accountingType)) {
			$accountingType = $sType;
		}
		
		$oAccountIncome = $this->getDocumentItemAccount($oItem, $accountingType, $sIncomeAccountType, $bCommissionFromNetInvoice);
		
		if($bDiscount) {
			$sIncomeAccountNumber = $oAccountIncome->account_number_discount;
		} else {
			$sIncomeAccountNumber = $oAccountIncome->account_number;
		}
		
		if($this->isDoubleAccounting()) {
			$oAccountExpense = $this->getDocumentItemAccount($oItem, $accountingType, $sExpenseAccountType, $bCommissionFromNetInvoice);
		}

		$sAddressType                       = $this->getAddressType();
		$sAddressTypeId                     = $this->getAddressTypeId();
		$aEntry['account_number_income']    = $sIncomeAccountNumber;
		$aEntry['account_number_expense']   = (isset($oAccountExpense)) ? $oAccountExpense->account_number : '';
		$aEntry['account_automatic_income'] = $oAccountIncome->automatic_account;
		$aEntry['account_automatic_expense']= $oAccountExpense->automatic_account;
		$aEntry['item_id'] = null;
		if($sType != 'claim_debt'){
			$aEntry['description']          = $this->removeLineBreaks($oItem->description);
			$aEntry['item_id'] = $oItem->id;
		}
		$aEntry['document_id']              = $this->_oDocument->getId();
		$aEntry['document_number']          = $this->_oDocument->document_number;
		$aEntry['document_date']            = $this->_oVersion->date;
		$aEntry['currency']                 = $this->_oDocument->getCurrency()->getIso();
		$aEntry['amount']                   = $fAmount;
		$aEntry['amount_default_currency'] = $fAmount;
		$aEntry['date']                     = $oFrom->format('Y-m-d');
		$aEntry['from']                     = $oFrom->format('Y-m-d');
		$aEntry['until']                    = $oUntil->format('Y-m-d');
		$aEntry['position_type']            = $sType;
		$aEntry['address_type']             = $sAddressType;
		$aEntry['address_type_id']          = $sAddressTypeId;
		$aEntry['double_accounting']        = (int)$this->isDoubleAccounting();
		$aEntry['cost_center']              = '';
		$aEntry['main_document_number'] = $this->_oDocument->document_number;
		$aEntry['invoice_document_number'] = $this->_oDocument->document_number;
		$aEntry['commission_from_net_invoice'] = $bCommissionFromNetInvoice;

		$sSchoolCurrency = Ext_Thebing_Currency::getInstance($this->_oSchool->currency)->iso4217;
		
		// Fremdwährung?
		if($aEntry['currency'] != $sSchoolCurrency) {
			$oExchangeRateTable = Ext_TC_Exchangerate_Table::getRepository()->findOneBy([]);
			if(
				$oExchangeRateTable instanceof Ext_TC_Exchangerate_Table &&
				$oExchangeRateTable->exist()
			) {
				try {
					$aEntry['amount_default_currency'] = $oExchangeRateTable->calculateAmount($aEntry['amount'], $aEntry['currency'], $sSchoolCurrency, $this->_oVersion->date);
				} catch(Ext_TC_Exchangerate_Exception $e) {
					// Kein Wechselkurs gefunden!
					$aEntry['amount_default_currency'] = null;
				}
			}
		}
		
		$oParentDocument = $this->_oDocument->getMainParentDocument();

		if($oParentDocument) {

			$sParentDocumentNumber = $oParentDocument->document_number;
			$aEntry['main_document_number'] = $sParentDocumentNumber;

			// Nur wenn das Dokument Storno oder Gutschrift ist
			if(
				$this->_oDocument->type === 'storno' ||
				$this->_oDocument->is_credit == 1
			) {
				$aEntry['invoice_document_number'] = $sParentDocumentNumber;
			}

		}

		if($sType === 'claim_debt') {
			$aEntry['debit_credit'] = 'H';
			$aEntry['account_type'] = 'D';
		} else {
			if($bCommissionFromNetInvoice === true) {
				$aEntry['debit_credit'] = 'H';
			} else {
				$aEntry['debit_credit'] = 'S';
			}
			$aEntry['account_type'] = 'S';
		}

		// Betrag ggf. manipulieren
		$aEntry['amount'] = $this->manipulateAmount($aEntry);

		// Bescheuerter Sage-Case
		if ($sType === 'claim_debt') {
			$aEntry['amount_if_claim'] = $aEntry['amount'];
		} else {
			$aEntry['amount_if_position'] = $aEntry['amount'];
		}

		if (
			$oItem->tax_category > 0 &&
			$sType === 'position'
		) {
			$oVat = Ext_TC_Vat::getInstance($oItem->tax_category);
			$aEntry['tax'] = $oVat->name;
			$aEntry['tax'] .= ' ('.(new Ext_TC_Gui2_Format_Percent(3))->formatByValue($oItem->tax).')';
			$aEntry['tax_key'] = $oVat->short;
			$aEntry['tax_category'] = $oItem->tax_category;
			$aEntry['amount_tax'] = $oItem->getOnlyTaxAmount($aEntry['amount']);
		}

		// Ermittlung der Kostenstelle über Item kann bei Forderungspositionen nicht klappen
		if($sType != 'claim_debt') {
			$aEntry['cost_center'] = $this->getCostCenter($oItem);
		}

		$aEntry['receipt_text'] = $this->getReceiptTextForItem($oItem, $sType, $aEntry);
		
		$aHookData = array(
			'entry'     => &$aEntry,
			'company'   => $this->_oCompany,
			'school'    => $this->_oSchool,
			'item'      => $oItem
		);

		\System::wd()->executeHook('ts_accounting_bookingstack_generator_item_stack_entry', $aHookData);

		$aEntry['company_id']				= (int)$this->_oCompany->id;
		$aEntry['school_id']				= (int)$this->_oSchool->id;

		if(
			$this->_oInquiry &&
			$this->_oInquiry->hasAgency()
		) {
			$aEntry['agency_id'] = (int)$this->_oInquiry->agency_id;
		}
		
		$iInboxId							= 0;

		if($this->_oInbox instanceof Ext_Thebing_Client_Inbox) {
			$iInboxId = (int)$this->_oInbox->id;
		}

		$aEntry['inbox_id']					= $iInboxId;

		$aAddressNameData = $this->_oVersion->getAddressNameData();

		$aEntry['address_type_object_name']	= '';
		$aEntry['address_firstname']		= '';
		$aEntry['address_lastname']			= '';

		if(isset($aAddressNameData['object_name'])) {
			$aEntry['address_type_object_name'] = (string)$aAddressNameData['object_name'];
		}

		if(isset($aAddressNameData['firstname'])) {
			$aEntry['address_firstname'] = (string)$aAddressNameData['firstname'];
		}

		if(isset($aAddressNameData['lastname'])) {
			$aEntry['address_lastname'] = (string)$aAddressNameData['lastname'];
		}

		return $aEntry;
	}

	protected function getCostCenter(\Ext_Thebing_Inquiry_Document_Version_Item $oItem) {

		if($oItem->tax_category > 0) {
				
			$oVat =\Ext_TC_Vat::getInstance($oItem->tax_category);
			
			/*
			 * Wert der Kostenstelle nimmt den Wert einer der folgenden Entitäten, priorisiert absteigend
			 ** Steuer
			 ** Zusatzgebühren/Versicherungen/Kurskategorien
			 ** Firma
			 ** Schule
			 * */
			if(
				!empty($oVat) &&
				!empty($oVat->cost_center)
			) {

				return $oVat->cost_center;

			}
			
		}

		$oService = $oItem->getService();

		if($oItem->type === 'special') {

			$oSpecialBlock = Ext_Thebing_Special_Block_Block::getInstance($oItem->type_id);
			if($oSpecialBlock->exist()) {
				$oSpecial = $oSpecialBlock->getSpecial();
				return $oSpecial->cost_center;
			}

		} elseif($oItem->parent_type === 'cancellation') {

			$iCancellationFeeId = $oItem->additional_info['cancellation_fee_id'] ?? null;

			if(!empty($iCancellationFeeId)) {
				$oCancellationFee = Ext_Thebing_Cancellation_Fee::getInstance($iCancellationFeeId);
				$oCancellationGroup = $oCancellationFee->getCancellationGroup();
				return $oCancellationGroup->cost_center;
			}

		} elseif($oService instanceof Ext_Thebing_Tuition_Course) {

			$oCourseCategory = $oService->getCategory();

			if(
				$oCourseCategory instanceof Ext_Thebing_Tuition_Course_Category &&
				!empty($oCourseCategory->cost_center)
			) {
				return $oCourseCategory->cost_center;
			}

		} elseif(
			$oService instanceof Ext_Thebing_School_Additionalcost
		) {

			/*
			 * ACHTUNG: Performanceintensiv!
			 */
			if(
				(
					$oService->type == Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION ||
					$oService->type == Ext_Thebing_School_Additionalcost::TYPE_COURSE
				) &&
				$oService->use_service_category_cost_center
			) {

				if($oService->type == Ext_Thebing_School_Additionalcost::TYPE_COURSE) {
					$journeyItemIds = $oItem->additional_info['inquiry_journey_course_ids'];
					$itemType = 'course';
				} else {
					$journeyItemIds = $oItem->additional_info['inquiry_journey_accommodation_ids'];
					$itemType = 'accommodation';
				}
				$version = $oItem->getVersion();

				if(
					is_array($journeyItemIds) && 
					count($journeyItemIds) > 1
				) {
					
					// Nach Startdatum und Betrag sortieren
					usort($journeyItemIds, function($a, $b) use($version, $itemType) {

						$itemA = $version->getJoinedObjectChildByValue('items', ['type' => $itemType, 'type_id' => (int)$a]);
						$itemB = $version->getJoinedObjectChildByValue('items', ['type' => $itemType, 'type_id' => (int)$b]);

						$fromA = new \Carbon\Carbon($itemA->index_from);
						$fromB = new \Carbon\Carbon($itemB->index_from);

						if($fromA < $fromB) {
							return -1;
						} elseif($fromA > $fromB) {
							return 1;
						} else {
							return ($itemA->amount > $itemB->amount) ? -1 : 1;
						}
						
					});
					
					$journeyItemId = reset($journeyItemIds);
					
				} else {
					$journeyItemId = (int)$oItem->parent_booking_id;
				}
				
				$journeyItem = $oItem->getInquiry()->getServiceObject($itemType, $journeyItemId);
				
				if(
					$journeyItem &&
					$journeyItem->exist()
				) {

					if($oService->type == Ext_Thebing_School_Additionalcost::TYPE_COURSE) {
						$course = $journeyItem->getCourse();				
						$costCenterObject = $course->getCategory();
					} else {
						$costCenterObject = $journeyItem->getCategory();
					}

					if(
						$costCenterObject instanceof \WDBasic &&
						!empty($costCenterObject->cost_center)
					) {
						return $costCenterObject->cost_center;
					}
					
				}
				
			} elseif(!empty($oService->cost_center)) {
				return $oService->cost_center;
			}
			
		} elseif(
			(
				$oService instanceof Ext_Thebing_Accommodation_Category ||
				$oService instanceof TsActivities\Entity\Activity ||
				$oService instanceof Ext_Thebing_Insurance
			) &&
			!empty($oService->cost_center)
		) {

			return $oService->cost_center;

		} else if(
			$oItem->type === 'transfer' &&
			!empty($oItem->additional_info['transfer_package_id'])
		) {

			$oPackage = Ext_Thebing_Transfer_Package::getInstance($oItem->additional_info['transfer_package_id']);
			if($oPackage->exist()) {
				return $oPackage->cost_center;
			}

		} else if(!empty($this->_oCompany->cost_center)) {

			return $this->_oCompany->cost_center;

		} else {

			return $this->_oSchool->cost_center;

		}

	}

	/**
	 * Belegtext suchen und Platzhalter ersetzen
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param $sType
	 * @return mixed|string
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	protected function getReceiptTextForItem(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $sType, array $aEntry) {

		if(
//			$this->_oCompany->interface === 'sage' &&
			$this->_oCompany->accounting_records === 'single'
		) {
			return '';
		}

		$oReceiptText = $this->getReceiptText();

		$sText = '';

		if ($oReceiptText) {
			$sText = $oReceiptText->findText($oItem, $sType);

			$oPlaceholder = new \TsAccounting\Service\Placeholder\Company\TemplateReceiptText();
			$oPlaceholder->setObject($oItem);
			$oPlaceholder->setEntry($aEntry);
			$sText = $oPlaceholder->replace($sText);

			if (empty($sText) && !in_array('no_receipt_text_found', $this->aIgnoreErrors)) {
				throw new Ext_TS_Accounting_Bookingstack_Generator_Exception('no_receipt_text_found', $this);
			}

			$sText = $this->removeLineBreaks($sText);
		}

		if ($this->testMode) {
			$sText .= ' -- TEST EXPORT --';
		}

		return $sText;

	}

	/**
	 * splittet eine Position in mehrere Eintrage falls nötig
	 * (verteilung auf die monate und ggf steuerpositionen)
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return string
	 */
	public function splitDocumentItem(Ext_Thebing_Inquiry_Document_Version_Item $oItem){

		$aData = array();

		$oAccount = $this->getDocumentItemAccount($oItem, 'position', 'income');

		// wenn -1 heißt das PRAP oder ARA und diese haben keine Informationen ob automatik oder nicht
		// in so einem Fall "muss" die andere Seite jedoch ein normales Konto sein daher holen wir uns das
		if($oAccount->automatic_account === -1){
			$oAccount = $this->getDocumentItemAccount($oItem, 'position', 'expense');
		}

		$oGenerateSplittingData = function($dFrom, $dUntil, $fAmount, $fFactor=1) use ($oItem, $oAccount) {
			
			$aSplittingData = [];
			$iSplittingData = 0;
			
			$aSplittingData[0] = array(
				'from' => $dFrom,
				'until' => $dUntil,
				'amount' => $fAmount,
				'factor' => $fFactor,
				'position_type' => 'position'
			);

			// Wenn kein Automatikkonto und Steuern vorhanden sind müssen 2 getrennte Positionen erzeugt werden
			if(
				(
					$this->_oCompany->automatic_account_setting == 'none' ||
					$oAccount->automatic_account == 0
				) &&
				$oItem->tax_category > 0
			) {
				$this->addTaxToSplittingDataByRef($oItem, $aSplittingData, $iSplittingData);
			}

			return $aSplittingData;
		};

		$oFrom      = $oItem->getFrom();
		$oUntil     = $oItem->getUntil();

		// Bei zusatzkosten müssen die Zeiträume der Schuleinstellung angeglichen werden
		// erster bzw. letzer Leistungsstart
		if(
			$oItem->type == 'additional_course' ||
			$oItem->type == 'additional_accommodation' ||
			$oItem->type == 'additional_general'
		){
			$oAdditional    = Ext_Thebing_School_Additionalcost::getInstance($oItem->type_id);
			$iTimepoint     = (int)$oAdditional->timepoint;
			$iCharge		= (int)$oAdditional->calculate;

			// Dürfen nur auf gleichen Tag gesetzt werden, wenn nicht wöchentlich
			// Gleiche Logik existiert auch in den Statistiken!
			if($iCharge != \Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK) {
				if($iTimepoint == 1){
					$oUntil = clone $oFrom;
				} else if($iTimepoint == 2){
					$oFrom  = clone $oUntil;
				}
			}
		} elseif($oItem->type === 'extraPosition') {
			// Eigene Positionen werden immer am ersten Tag der Leistung verbucht und nicht gesplittet
			$oUntil = clone $oFrom;
		}

		$fAmount = $this->getItemAmount($oItem);

		// Bei Sage/Quickbooks Basic: KEINE Aufteilung der Positionen
		if($this->_oCompany->accounting_records !== 'deferred_income') {
			return $oGenerateSplittingData($oFrom, $oUntil, $fAmount);
		}

		//$fTax       = Ext_TS_Vat::getTaxRate($oItem->tax_category, $this->_oSchool->id);

		// Differenz in Tagen ermitteln und den Betrag teilen um ihn dann anteilig verteilen zu können
		$oDiff      = $oFrom->diff($oUntil, true);
		$iDaysTotal = $oDiff->days;
		$iDaysTotal = $iDaysTotal + 1; // da der starttag nicht mitgezählt wird und bei transfer das zu Problemen führt
		if($iDaysTotal == 0){
			$iDaysTotal = 1;
		}
		$fDayAmount = $fAmount / $iDaysTotal;

		do {

			// Aktuelles von merken
			$oCurrentFrom = clone $oFrom;
			// ein monat hinzuaddieren da wir pro monat splitten
			$iMonth = (int) $oFrom->format('m') + 1;
			//$oFrom->modify('+1 month');
			// funktionierte beispielsweise nicht, wenn der Kursstart der 31.03 war. Dann ging er auf den 01.05 und
			// hat für den April keine Position generiert sondern eine für 31.03. - 30.04.

			// auf den ersten setzten da wir immer am anfang des Monats verbuchen
			$oFrom->setDate($oFrom->format('Y'), $iMonth, 1);

			// Solange das nächste From noch vor oder an dem Ende liegt gehts weiter
			if($oFrom <= $oUntil){
				$bContinue = true;
				// wenn das nächste From noch gültig ist dann ist das aktuelle ende dieses neue From abzüglich 1 tag
				$oCurrentUntil = clone $oFrom;
				$oCurrentUntil->modify('-1 day');
				// wenn das neue From nicht mehr gültig ist dann ist das Ende für den aktuellen Part das Ende des Items ansich
			} else {
				$bContinue      = false;
				$oCurrentUntil  = $oUntil;
			}

			// Tage ermitteln für anteilige Betragserrechnung
			$oDiff = $oCurrentFrom->diff($oCurrentUntil, true);
			$iDays = $oDiff->days;
			$iDays = $iDays + 1; // da der starttag nicht mitgezählt wird und bei transfer das zu Problemen führt

			// Daten setzen
			$aData = array_merge($aData, $oGenerateSplittingData($oCurrentFrom, $oCurrentUntil, $fDayAmount * $iDays, $iDays/$iDaysTotal));

		} while($bContinue);

		// rundungsdiff. bilden pro POSITION daher hier und nicht am ende aller Pos.
		$fSubTotal      = 0;
		foreach($aData as $key => $aEntry){
			if($aEntry['position_type'] != 'claim_debt'){
				$aData[$key]['amount']      = round($aEntry['amount'], 2);
				$fSubTotal                  += $aData[$key]['amount'];
			}
		}

		$fRoundDiff                 = $fAmount - $fSubTotal;
		$aData[$key]['amount']      += $fRoundDiff;

		return $aData;
	}

	/**
	 * fügt die steuern als weiteren eintrag hinzu und reduziert den betrag um den steuersatz
	 * @param array $aData
	 * @param int $i
	 */
	public function addTaxToSplittingDataByRef(Ext_Thebing_Inquiry_Document_Version_Item $oItem, &$aData, &$i) {

		// Steuern abziehen vom haupteintrag
		$fAmountTax = $oItem->getOnlyTaxAmount();

		if($fAmountTax != 0) {

			// Bei aufgeteilten Beträgen (auf Monate) gibt es einen Faktor
			if(
				!empty($aData[$i]['factor']) && 
				$aData[$i]['factor'] !== 1
			) {
				$fAmountTax *= $aData[$i]['factor'];
			}

			// Steuern müssen immer abgezogen werden, da der Betrag immer mit Steuern im Array steht
			$aData[$i]['amount'] = $aData[$i]['amount'] - $fAmountTax;

			$aData[$i]['accounting_type']   = 'position';

			// Steuereintrag ergänzen
			$aData[($i+1)] = $aData[$i];
			$i++;
			$aData[$i]['amount'] = $fAmountTax;
			$aData[$i]['accounting_type'] = 'vat';
			$aData[$i]['amount'] = $this->manipulateAmount($aData[$i]);

		}

	}

	/**
	 * Betrag manipulieren
	 *
	 * @param array $aEntry
	 * @return float
	 */
	public function manipulateAmount(array $aEntry) {

		$sAddressType = $this->getAddressType();
		$bAgencyCreditnote = $this->isAgencyCreditNote();
		$bReduction = $this->isReducing();
		$bPassivAndActive = $this->isActiveAndPassive();
		$bDoubleAccounting = $this->isDoubleAccounting();

		if(
			(
				// S0009 2.5.1: Forderungsposition, Agenturgutschrift, (nur) Aktiv, Reduktion: ODER
				// S0009 2.5.3: Forderungsposition, Agenturgutschrift, Aktiv & Passiv, Reduktion:
				// Betragszeichen der Forderungsposition umkehren
				$bDoubleAccounting &&
				$aEntry['position_type'] === 'claim_debt' &&
				$bAgencyCreditnote &&
				strpos($sAddressType, 'agency') === 0 &&
				$bReduction
			) || (
				// S0009 2.5.1: Normale Position, Agenturgutschrift, (nur) Aktiv, Reduktion:
				// Betrag umkehren
				$bDoubleAccounting &&
				$aEntry['position_type'] === 'position' &&
				$bAgencyCreditnote &&
				strpos($sAddressType, 'agency') === 0 &&
				$bReduction &&
				!$bPassivAndActive
			) || (
				// S0009 2.5.4: Normale Position, Agenturgutschrift, Aktiv & Passiv, keine Reduktion:
				// Betrag umkehren
				$bDoubleAccounting &&
				$aEntry['position_type'] === 'position' &&
				$bAgencyCreditnote &&
				strpos($sAddressType, 'agency') === 0 &&
				!$bReduction &&
				$bPassivAndActive
			) || (
				// S0030 3.1: Wenn normale Position auf Agenturgutschrift, EINFACHE Buchhaltung und Reduktion:
				// Beträge umkehren #6106
				!$bDoubleAccounting &&
				$bAgencyCreditnote &&
				$aEntry['position_type'] === 'position' &&
				strpos($sAddressType, 'agency') === 0 &&
				$bReduction
			) || (
				// #14418: Forderungsposition bei entsprechender Einstellung immer umkehren
				!$bDoubleAccounting &&
				$aEntry['position_type'] === 'claim_debt' &&
				!$bAgencyCreditnote &&
				(int)$this->_oCompany->create_claim_debt !== Company::NO_CLAIM_DEBT_POSITIONS
			)
		) {
			$aEntry['amount'] = $aEntry['amount'] * -1;
		}

		return $aEntry['amount'];

	}


	/**
	 * prüft ob die doppelte Buchhaltung aktiv ist
	 * @return boolean
	 */
	public function isDoubleAccounting() {
		if($this->_oCompany->accounting_type == 'double') {
			return true;
		}

		return false;
	}

	/**
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument() {
		return $this->_oDocument;
	}

	/**
	 * Liefert, je nach Inquiry und Dokumententyp, den Brutto- oder Nettobetrag der Position
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return float
	 */
	protected function getItemAmount(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $withDiscount=true) {

		$sType = 'brutto';

		if($this->_oDocument->type === 'creditnote') {
			// Bei Creditnote entsprechend Typ Creditnote (wird in der getDiscountAmount() behandelt)
			$sType = 'commission';
		} elseif($this->_oDocument->isNetto()) {
			$sType = 'netto';
		}

		$fAmount = $oItem->getAmount($sType, $withDiscount);

		// Eventuelle minimale Beträge (~E-15) runden
		$fAmount = round($fAmount, 5);

		return $fAmount;
	}

	/**
	 * @return Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	protected function getDocumentItems() {

		$aDocumentItems = $this->_oVersion->getItemObjects(true, false);
		$aItems = [];

		// Dokumente ohne Items: Nichts machen, einfach return
		if(empty($aDocumentItems)) {
			return [];
		}

		// Sage Basic hat nur eine Position pro Dokument
		if($this->_oCompany->accounting_records === 'single') {

			$oDocumentItem = new Ext_Thebing_Inquiry_Document_Version_Item();
			$oDocumentItem->type = 'document';
			$oDocumentItem->index_from = $this->_oInquiry->service_from;
			$oDocumentItem->index_until = $this->_oInquiry->service_until;
			$oDocumentItem->onPdf = $oDocumentItem->calculate = 1;

			foreach($aDocumentItems as $oItem) {
				$oDocumentItem->amount += $oItem->getAmount('brutto');
				$oDocumentItem->amount_net += $oItem->getAmount('netto');
				$oDocumentItem->amount_provision += $oItem->getAmount('commission');
			}

			return [$oDocumentItem];
// quickbooks_basic wurde nicht mehr verwendet
//		} elseif(
//			// Bei Quickbooks Basic gibt es bei Netto und CN eine Provisionsposition
//			$this->_oCompany->interface === 'quickbooks_basic' && (
//				$this->_oDocument->isNetto() ||
//				$this->isAgencyCreditNote()
//			)
//		) {
//
//			// Bei Netto: Alle vorhanden Positionen übernehmen (CNs haben nur eine Position)
//			if(!$this->isAgencyCreditNote()) {
//				$aItems = $aDocumentItems;
//			}
//
//			// Item des Typs Commission, welches bei Belegtexten und Konteneinstellungen entsprechend beachtet wird
//			$oCommissionItem = new Ext_Thebing_Inquiry_Document_Version_Item();
//			$oCommissionItem->type = 'commission';
//			$oCommissionItem->version_id = $this->_oDocument->latest_version; // Für Beleg-Platzhalter
//			$oCommissionItem->description = L10N::t('Provision', 'Thebing » Accounting » Booking Stack');
//			$oCommissionItem->index_from = $this->_oInquiry->service_from; // Absoluter Leistungszeitraum
//			$oCommissionItem->index_until = $this->_oInquiry->service_until; // Absoluter Leistungszeitraum
//			$oCommissionItem->onPdf = $oCommissionItem->calculate = 1; // Damit der Buchungsstapel das Item nicht ignoriert
//
//			// Alle Items durchlaufen und die Provisionsbeträge abzüglich Discount holen
//			foreach($aDocumentItems as $oItem) {
//				$fAmount = $oItem->getAmount('commission') * -1;
//
//				// Direkt in Bruttobetrag packen, da getItemAmount() bei »quickbooks_basic« nur Bruttobeträge liefert
//				$oCommissionItem->amount += $fAmount;
//				$oCommissionItem->amount_provision += $fAmount; // Auch schreiben, da sonst Position ignoriert wird
//			}
//
//			$aItems[] = $oCommissionItem;
//
//			return $aItems;

		} else {
			return $aDocumentItems;
		}

	}

	/**
	 * Generiert eine QB Nummer anhand des eingestellten
	 * Formates in der Firma
	 *
	 * @param array $aEntry
	 * @return string
	 */
	protected function generateQBNumber(array $aEntry): string {

		$sAgencyName = '';
		$sAgencyNumber = '';
		$sAddressNumber = '';
		$oAgency = $this->_oDocument->getAgency();

		if($oAgency) {
			$sAgencyName = $oAgency->getName(true);
			$sAgencyNumber = $oAgency->getNumber();
			$sAddressNumber = $sAgencyNumber;
		} elseif($this->_oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$sAddressNumber = $this->_oInquiry->getCustomer()->getCustomerNumber();
		}

		$aAddressData = $this->_oDocument->getLastVersion()->getAddressNameData();
		$oBookingDate = new DateTime($aEntry['date']);

		$sRetVal = $this->_oCompany->qb_number_format;
		$sRetVal = str_replace('%document_number', $this->_oDocument->document_number, $sRetVal);
		$sRetVal = str_replace('%agency_number', $sAgencyNumber, $sRetVal);
		$sRetVal = str_replace('%agency', $sAgencyName, $sRetVal);

		if($this->_oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$sRetVal = str_replace('%firstname', $this->_oInquiry->getCustomer()->firstname, $sRetVal);
			$sRetVal = str_replace('%surname', $this->_oInquiry->getCustomer()->lastname, $sRetVal);
			$sRetVal = str_replace('%customernumber', $this->_oInquiry->getCustomer()->getCustomerNumber(), $sRetVal);
		}

		$sRetVal = str_replace('%addresse_number', $sAddressNumber, $sRetVal);
		$sRetVal = str_replace('%addresse', $aAddressData['firstname'] . ' ' . $aAddressData['lastname'], $sRetVal);
		$sRetVal = str_replace('%d', $oBookingDate->format('d'), $sRetVal);
		$sRetVal = str_replace('%m', $oBookingDate->format('m'), $sRetVal);
		$sRetVal = str_replace('%y', $oBookingDate->format('y'), $sRetVal);
		$sRetVal = str_replace('%Y', $oBookingDate->format('Y'), $sRetVal);

		return $sRetVal;
	}

}
