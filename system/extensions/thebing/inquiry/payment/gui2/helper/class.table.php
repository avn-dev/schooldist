<?php

/**
 * Diese Klasse generiert die angezeigte Tabelle im Bezahldialog
 */
class Ext_Thebing_Inquiry_Payment_Gui2_Helper_Table
{
	protected
		$_oDialog,
		$_sL10N,
		$_bGroup = false,
		$_oSchool,
		$_oAgencyPayment,
		$_iCurrencyInquiryId,
		$_iCurrencySchoolId,
		$_aRenderedInquiryIds = array(),
		$bDisableOverpayment = false;

	// Benötigte Variablen, die aus Ext_Thebing_Inquiry_Payment kommen
	protected
		$_fStaticOverpayAmount,
		$_iStaticOverpayCurrency,
		$_iStaticOverpaySchool;

	// Benötigt für Gutschriften-Felder (an Agentur)
	private
		$_oLastAmountInput,
		$_oLastAmountSchoolInput;

	// Generierung der Zeilen
	private
		$_fAmountTotal = 0,
	 	$_fAmountTotalBalance = 0;

	/**
	 * @var array Ext_Thebing_Inquiry_Payment::buildPaymentDataArray()['inquries']
	 */
	protected $_aPaymentData;

	/**
	 * @var Ext_TS_Inquiry Erste Buchung aus Ext_Thebing_Inquiry_Payment::buildPaymentDataArray()
	 */
	protected $_oFirstInquiry;

	/**
	 * @var bool|string
	 */
	private $sAdditional;

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aPaymentData
	 * @param bool|string $sAdditional
	 */
	public function __construct(Ext_Gui2_Dialog $oDialog, array $aPaymentData, $sAdditional = false) {
		$this->_oDialog = $oDialog;
		$this->_sL10N = Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH;
		$this->_aPaymentData = $aPaymentData;
		$this->sAdditional = $sAdditional;

		// Erste Buchung holen
		$aFirstData = reset($aPaymentData['inquries']);
		$this->_oFirstInquiry = $aFirstData['inquiry'];
		$this->_oSchool = $this->_oFirstInquiry->getSchool();
		$this->_bGroup = $this->_oFirstInquiry->hasGroup();

		// Währungen holen
		$oSchool = $this->_oFirstInquiry->getSchool();
		$this->_iCurrencyInquiryId = $this->_oFirstInquiry->getCurrency();
		$this->_iCurrencySchoolId = $oSchool->getCurrency();

		$this->bDisableOverpayment = $this->_oAgencyPayment instanceof Ext_Thebing_Agency_Payment || $this->_aPaymentData['multiple_inquiries_selected'];
	}

	/**
	 * Diese Methode setzt diese benötigten Statics aus Ext_Thebing_Inquiry_Payment
	 *
	 * @param float $fOverpayAmount
	 * @param int $iOverpayCurrency
	 * @param int $iOverpaySchool
	 */
	public function setStaticVariables($fOverpayAmount, $iOverpayCurrency, $iOverpaySchool) {
		$this->_fStaticOverpayAmount = $fOverpayAmount;
		$this->_iStaticOverpayCurrency = $iOverpayCurrency;
		$this->_iStaticOverpaySchool = $iOverpaySchool;
	}

	/**
	 * Übergabe einer Zahlung aktiviert manuelle Gutschriften (an Agentur)
	 * @param Ext_Thebing_Agency_Payment $oPayment
	 */
	public function setAgencyPayment(Ext_Thebing_Agency_Payment $oPayment) {
		$this->_oAgencyPayment = $oPayment;
	}

	/**
	 * Liefert die IDs aller Inquirys, die in der Tabelle generiert wurden
	 * @return array
	 */
	public function getRenderedInquiryIds() {
		return $this->_aRenderedInquiryIds;
	}

	/**
	 * Alles final generieren
	 * @return Ext_Gui2_Html_Div
	 */
	public function render() {
		$oDiv = new Ext_Gui2_Html_Div();

		$aHeader = $this->_getHeader();

		$oTable = new Ext_Gui2_Html_Table();
		$oTable->class = 'table tblDocumentTable highlightRows';
		$oTable->id = 'saveid[payment_items]';

		// Tabellenkopf generieren
		$oTr = new Ext_Gui2_Html_Table_Tr();
		foreach($aHeader as $aData) {
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement($aData['l10n']);
			$oTh->style = 'width:' . $aData['width'] . '; display: ' . $aData['display'];
			$oTr->setElement($oTh);
		}
		$oTable->setElement($oTr);

		// Positionen generieren
		$this->_renderPositions($aHeader, $oTable);

		// Summenzeile
//		$oTr = $this->_getPaymentInfoRow($aHeader, null, L10N::t('Beträge manuell verteilen', $this->_sL10N));
//		$oTr = $this->_getPaymentInfoRow($aHeader, \Illuminate\Support\Arr::first($this->_aPaymentData['inquries'])['inquiry'], '');
		$oTr = $this->getPaymentCheckboxAllRow($aHeader);
		$oTable->setElement($oTr);

		// Überbezahlung, Anzeige der »bewussten« Überbezahlung
		$oTr = $this->_getOverPaymentRow($aHeader, 1);
		$oTable->setElement($oTr);

		// Überbezahlung wird durch JS umgeschaltet
		// Anzeige der aktuellen Überbezahlung
		$oTr = $this->_getOverPaymentRow($aHeader, 0);
		$oTable->setElement($oTr);

		$oTr = $this->_getPaymentRow($aHeader);
		$oTable->setElement($oTr);

		// Wenn Agenturbezahlung übergeben, dann Felder für Gutschriften anzeigen
//		if($this->_oAgencyPayment instanceof Ext_Thebing_Agency_Payment) {
			$this->_renderCreditnoteFields($aHeader, $oTable);
//		}

		$oDiv->setElement($oTable);

		// Feld beinhaltet alle InquiryIds aller Kunden in dem Dialog (notwendig für Gruppen)
		// @TODO Sollte entfernt werden (siehe auch saveDialogPaymentTab())
		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = 'hidden';
		$oHidden->name = $oHidden->id = 'save[payment_dialog][all_inquiries]';
		$oHidden->value = implode(',', $this->getRenderedInquiryIds());
		$oDiv->setElement($oHidden);

		// Feld definiert, ob es sich um eine Gruppenbezahlung handelt oder nicht.
		// Dies ist wichtig, da die Positionen bei Gruppen erst beim Speichern aufgeteilt werden.
		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = 'hidden';
		$oHidden->name = $oHidden->id = 'save[payment_dialog][group_payment_merged]';
		$oHidden->value = (int)$this->_bGroup;
		$oDiv->setElement($oHidden);

		// Feld definiert, ob mehrere IDs ($aSelectedIdsOriginal) ausgewählt wurden
		// Das ist relevant fürs Overpayment, da dieses bei Mehrfachauswahl nicht befüllt werden darf
		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = 'hidden';
		$oHidden->name = 'save[payment_dialog][multiple_inquiries_selected]';
		$oHidden->value = (int)$this->_aPaymentData['multiple_inquiries_selected'];
		$oDiv->setElement($oHidden);

		// Beträge müssen gerundet werden, da ansonsten JS Fehler auslöst (und da wird eh nur mit gerundeten Werten gerechnet) #9574
		$this->_oDialog->setOptionalAjaxData('payment_amount_total', round($this->_fAmountTotal, 2));
		$this->_oDialog->setOptionalAjaxData('payment_amount_total_open', round($this->_fAmountTotalBalance, 2));

		return $oDiv;
	}

	protected function _renderPositions($aHeader, $oTable) {
		$aPaymentData = $this->_aPaymentData['inquries'];

		// Wenn Gruppe, dann nur eine Position pro Schüler
		if($this->_bGroup) {
			$aPaymentData = $this->_mergePositionsForGroup();
		}

		foreach($aPaymentData as $aData) {
			$oInquiry = $aData['inquiry'];
			$this->_aRenderedInquiryIds[] = $oInquiry->id;

			if(!$this->_bGroup) {

				// Kundennamen
				$oCustomer = $oInquiry->getCustomer();
				$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
				$aTemp = array();
				$aNameData = array('lastname' => $oCustomer->lastname, 'firstname' => $oCustomer->firstname);

				foreach((array)$aData['documents'] as $aDocuments) {
					$oDocument = $aDocuments['document'];
					$oTr = $this->getPaymentInfoRow($aHeader, $oInquiry, $oDocument->document_number);
					$oTable->setElement($oTr);
					foreach((array)$aDocuments['items'] as $oItem) {
						$oTr = $this->_getPaymentRow($aHeader, $oInquiry, true, $oItem, $oDocument);
						$oTable->setElement($oTr);
					}
				}

			} else {
				$oTr = $this->_getPaymentRow($aHeader, $oInquiry, false, $aData);
				$oTable->setElement($oTr);
			}
		}
	}

	/**
	 * Alle Dokumente und Items pro Schüler mergen
	 * @return array
	 */
	protected function _mergePositionsForGroup() {
		$aPaymentData = array();
		foreach($this->_aPaymentData['inquries'] as $aInquiryPayments) {
			$oInquiry = $aInquiryPayments['inquiry'];
			$fAmount = 0;
			$fAmountPayed = 0;

			// Kundenname
			$oCustomer = $oInquiry->getCustomer();
			$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
			$aTmp = array();
			$aNameData = array('lastname' => $oCustomer->lastname, 'firstname' => $oCustomer->firstname);
			$sCustomerName = $oFormat->format($aTmp, $aTmp, $aNameData);

			$aDocumentDumber = array(); 
			
			foreach((array)$aInquiryPayments['documents'] as $aDocuments) {
				foreach((array)$aDocuments['items'] as $oItem) {
					/** @var $oItem Ext_Thebing_Inquiry_Document_Version_Item */
					$fAmount += (float)$oItem->getTaxDiscountAmount($this->_oSchool->id, '', false);
					$fAmountPayed += $oItem->getPayedAmount($oInquiry->getCurrency());
				}
				
				$oDocument = $aDocuments['document'];
				$aDocumentDumber[$oInquiry->id][] = $oDocument->document_number;
			}

			$aPaymentData[] = array(
				'inquiry' => $oInquiry,
				'amount' => $fAmount,
				'amount_payed' => $fAmountPayed,
				'description' => $sCustomerName,
				'document_number' => $aDocumentDumber
			);
		}

		return $aPaymentData;
	}

	/**
	 * Felder für Gutschriften anzeigen
	 */
	protected function _renderCreditnoteFields($aHeader, $oTable) {
		// War vorher, am Ende der foreach()-Schleife, die Währung der letzten Buchung

		if (
			$this->sAdditional === 'commission_payout' ||
			!$this->_oFirstInquiry->hasAgency()
		) {
			return;
		}

		$oAgency = $this->_oFirstInquiry->getAgency();
		$aCreditNotes = $oAgency->getOpenCreditNotes($this->_iCurrencyInquiryId);
		// @TODO Keine Ahnung, wie das hier vorher funktioniert hat:
		// 	1. Ext_Thebing_Agency_Payment hat kein Property »currency_id«
		//	2. Der Query in der Methode liefert »Column 'currency_id' in where clause is ambiguous«
		#$aManualCreditNotes = $oAgency->getOpenManualCreditNotes($this->_oAgencyPayment->currency_id);
		$aManualCreditNotes = $oAgency->getOpenManualCreditNotes($this->_iCurrencyInquiryId);

		$aAllCreditNotes = [...(array)$aCreditNotes, ...(array)$aManualCreditNotes];

		$oTr = new Ext_Gui2_Html_Table_Tr();
		$oTr->class = 'creditnotes-toggle';
		$oTr->style = 'cursor: pointer;';
		$oTd = new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->setElement('<i class="fa fa-chevron-down"></i>');
		$oTr->setElement($oTd);
		$oTd = new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->colspan = count($aHeader) - 1;
		$oTd->setElement(sprintf(
			'<strong>%s</strong> <span class="badge">%d</span>',
			L10N::t('Creditnotes', $this->_sL10N),
			count($aAllCreditNotes)
		));
		$oTr->setElement($oTd);
		$oTable->setElement($oTr);

		foreach($aAllCreditNotes as $oCreditNote) {
			// Payment wird durch JS getoggelt
			$oTr = $this->_getCreditNoteRow($aHeader, $oCreditNote);
			$oTable->setElement($oTr);
		}

		$oTr = $this->_getCreditNoteTotalRow($aHeader, $aAllCreditNotes);

		$oTable->setElement($oTr);

		$oTr = $this->_getCreditNoteSumRow($aHeader);

		$oTable->setElement($oTr);
	}

	/**
	 * Tabellenkopf
	 * @return array
	 */
	protected function _getHeader() {

		$aHeader = array();

		if(!$this->_bGroup) {
			$sPositionTranslations = L10N::t('Positionen', $this->_sL10N);
		} else {
			$sPositionTranslations = L10N::t('Schüler', $this->_sL10N);
		}

		$aHeader['checkbox']['l10n'] = '';
		$aHeader['checkbox']['width'] = '20px';
		$aHeader['checkbox']['display'] = '';

		$sDocumentNrLabel = 'R.-Nr.';
		if($this->sAdditional == 'commission_payout') {
			$sDocumentNrLabel = 'Gu.-Nr.';
		}

		$aHeader['document_number']['l10n'] = L10N::t($sDocumentNrLabel, $this->_sL10N);
		$aHeader['document_number']['width'] = '110px';
		$aHeader['document_number']['display'] = '';

		$aHeader['description']['l10n'] = $sPositionTranslations;
		$aHeader['description']['width'] = 'auto';
		$aHeader['description']['display'] = '';
		$aHeader['total']['l10n'] = L10N::t('Total', $this->_sL10N);
		$aHeader['total']['width'] = '100px';
		$aHeader['total']['display'] = '';
		$aHeader['balance']['l10n'] = L10N::t('Offen', $this->_sL10N);
		$aHeader['balance']['width'] = '100px';
		$aHeader['balance']['display'] = '';
		$aHeader['amount']['l10n'] = L10N::t('Währung', $this->_sL10N);
		$aHeader['amount']['width'] = '107px';
		$aHeader['amount_school']['l10n'] = L10N::t('Schulwährung', $this->_sL10N);
		$aHeader['amount_school']['width'] = '107px';
		$aHeader['amount_school']['display'] = '';

		// Schulwährungs-Betragsfeld verstecken bei gleichen Währungen (im Debugmodus aber anzeigen)
		if(
			System::d('debugmode') !== 2 &&
			$this->_iCurrencyInquiryId == $this->_iCurrencySchoolId
		) {
			$aHeader['amount_school']['display'] = 'none';
		}

		return $aHeader;
	}

	/**
	 * Erzeugt eine Positionszeile
	 *
	 * @param $aHeader
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param bool $bUseObjects Objekt-Struktur oder Array-Struktur
	 * @param array|Ext_Thebing_Inquiry_Document_Version_Item $mItem
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return Ext_Gui2_Html_Table_Tr
	 */
	protected function _getPaymentRow($aHeader, $oInquiry = null, $bUseObjects = true, $mItem = null, $oDocument = null) {

		$aCheckboxTd = array();

		if(
			$oDocument != null ||
			!$bUseObjects
		) {

			if($bUseObjects) {

				$sItemId = $mItem->id;

				$fAmountNotRounded = 0;
				if(
					$this->sAdditional !== 'commission_payout' ||
					abs($mItem->amount_provision) > 0
				) {
					$fAmountNotRounded = (float)$mItem->getTaxDiscountAmount($this->_oSchool->id, '', false);
				}

				$fPayedAmount = $mItem->getPayedAmount($oInquiry->getCurrency());
				$sItemDescription = $mItem->description;
				$sDocumentNumber = $oDocument->document_number;

				if(System::d('debugmode') > 0) {
					$sItemDescription = '<span style="font-size: 0.8em;">ID: '.$mItem->id.'</span><br>'.$sItemDescription;
				}

			} else {

				// Wird benutzt bei Gruppen, wo nur eine Position pro Schüler angezeigt wird
				// Die Beträge müssen vorher erst zusammengerechnet werden, daher Array-Struktur
				$sItemId = $oInquiry->id;
				$fAmountNotRounded = $mItem['amount'];
				$fPayedAmount = $mItem['amount_payed'];
				$sItemDescription = $mItem['description'];

				$sDocumentNumber = '';
				if(
					is_array($mItem['document_number']) &&
					isset($mItem['document_number'][$oInquiry->id])
				) {
					$sDocumentNumber = $mItem['document_number'][$oInquiry->id];					
					if(
						$this->_bGroup &&
						is_array($sDocumentNumber)
					) {
						$sDocumentNumber = implode(', ', $sDocumentNumber);
					}
				}

			}

			// Bei Creditnote-Payout muss der bezahlte Betrag umgedreht werden, da CN-Rechnungsbeträge positiv sind
			if($this->sAdditional === 'commission_payout') {
				$fPayedAmount *= -1;
			}

			// Inquiry der Buchung
			$iInquiryId = $oInquiry->id;

			$fAmount = round($fAmountNotRounded, 2);

			// Achtung, Wert kann mehrere Nachkommastellen haben, da ungerundet!
			$this->_fAmountTotal += $fAmountNotRounded;

			// Differenz zu schon bezahltem Betrag
			$fBalanceNotRounded = $fAmountNotRounded - $fPayedAmount;
			$fBalance = round($fBalanceNotRounded, 2);

			// Achtung, Wert kann mehrere Nachkommastellen haben, da ungerundet!
			$this->_fAmountTotalBalance += $fBalanceNotRounded;

			// amount Name
			$aInputAmountName = 'save[items][' . $sItemId . '][amount_inquiry]';

			// school amount Name
			$aInputAmountSchoolName = 'save[items][' . $sItemId . '][amount_school]';

			$sIDNamesAmount = $aInputAmountName;
			$sIDNamesAmountSchool = $aInputAmountSchoolName;

			// Checkbox pro Position
			$oCheckbox = $this->_oDialog->create('input');
			$oCheckbox->name = 'save[items][' . $sItemId . '][checked]';
			$oCheckbox->type = 'checkbox';
			$oCheckbox->value = 1;
			$oCheckbox->class = 'payment_item_checkbox';
			$oCheckbox->checked = true;
			$oCheckbox->setDataAttribute('item-id', $sItemId);
			$aCheckboxTd[] = $oCheckbox;

			// Hiddenfelder mitschicken
			// Inquiry Währung
			$sName = 'save[items][' . $sItemId . '][currency_inquiry]';
			$oHiddenCurrency = $this->_oDialog->create('input');
			$oHiddenCurrency->type = 'hidden';
			$oHiddenCurrency->value = $this->_iCurrencyInquiryId;
			$oHiddenCurrency->name = $sName;
			$oHiddenCurrency->id = $sName;
			$oHiddenCurrency->class = 'currency_input_payment';
			$oHiddenCurrency->__set('data-item-id', $sItemId);
			$aCheckboxTd[] = $oHiddenCurrency;

			// Schulwährung
			$sName = 'save[items][' . $sItemId . '][currency_school]';
			$oHiddenSchoolCurrency = $this->_oDialog->create('input');
			$oHiddenSchoolCurrency->type = 'hidden';
			$oHiddenSchoolCurrency->value = $this->_iCurrencySchoolId;
			$oHiddenSchoolCurrency->name = $sName;
			$oHiddenSchoolCurrency->id = $sName;
			$oHiddenSchoolCurrency->class = 'school_currency_input_payment';
			$oHiddenSchoolCurrency->__set('data-item-id', $sItemId);
			$aCheckboxTd[] = $oHiddenSchoolCurrency;

			// Offener Betrag
			$sName = 'save[items][' . $sItemId . '][open_amount]';
			$oHiddenOpenAmount = $this->_oDialog->create('input');
			$oHiddenOpenAmount->type = 'hidden';
			$oHiddenOpenAmount->value = $fBalance;
			$oHiddenOpenAmount->name = ''; //$sName;
			$oHiddenOpenAmount->id = $sName;
			$oHiddenOpenAmount->class = 'open_payment_amount';
			$oHiddenOpenAmount->__set('data-item-id', $sItemId);
			$aCheckboxTd[] = $oHiddenOpenAmount;

			// Bezahlter Betrag
			$sName = 'save[items][' . $sItemId . '][payed_amount]';
			$oHiddenPayedAmount = $this->_oDialog->create('input');
			$oHiddenPayedAmount->type = 'hidden';
			$oHiddenPayedAmount->value = $fPayedAmount;
			$oHiddenPayedAmount->name = ''; //$sName;
			$oHiddenPayedAmount->id = $sName;
			$oHiddenPayedAmount->class = 'payed_amount';
			$oHiddenPayedAmount->__set('data-item-id', $sItemId);
			$aCheckboxTd[] = $oHiddenPayedAmount;

			// der Schüler, der zur diesem item gehört
			$sName = 'save[items][' . $sItemId . '][inquiry]';
			$oHiddenInquiry = $this->_oDialog->create('input');
			$oHiddenInquiry->type = 'hidden';
			$oHiddenInquiry->value = $iInquiryId;
			$oHiddenInquiry->name = $sName;
			$oHiddenInquiry->id = $sName;
			$oHiddenInquiry->class = 'item_inquiry_id';
			$oHiddenInquiry->__set('data-item-id', $sItemId);
			$aCheckboxTd[] = $oHiddenInquiry;

			$sClassNamesAmount = 'txt amount form-control input-sm payment_amount_input';
			$sClassNamesAmountSchool = 'txt amount form-control input-sm payment_amount_school_input';

		} else {

			$fAmountTotal = round($this->_fAmountTotal, 2);
			$fAmountTotalBalance = round($this->_fAmountTotalBalance, 2);

			$sItemDescription = '';
			$fAmount = $fAmountTotal;
			$fBalance = $fAmountTotalBalance;
			$aInputAmountName = 'save[payment][amount_inquiry]';
			$aInputAmountSchoolName = 'save[payment][amount_school]';
			$sDocumentNumber = L10N::t('Summe');
			$sItemId = '';

			$sClassNamesAmount = 'txt amount form-control input-sm payment_amount_input_all';
			$sClassNamesAmountSchool = 'txt amount form-control input-sm payment_amount_school_input_all';
			$sIDNamesAmount = ''; //'payment_amount_input_all';
			$sIDNamesAmountSchool = ''; //'payment_amount_school_input_all';

			// Total Balance setzt sich aus Balance der einzelnen Items zusammen abzüglich der Überbezahlung
			if(!$this->bDisableOverpayment) {
				$fBalance = $fBalance - $this->_fStaticOverpayAmount;
			}

		}

		$oCurrency = Ext_Thebing_Currency::getInstance($this->_iCurrencyInquiryId);
		$sAmountCurrency = '<span class="input-group-addon">'.$oCurrency->getSign().'</span>';

		$oCurrency = Ext_Thebing_Currency::getInstance($this->_iCurrencySchoolId);
		$sAmountSchoolCurrency = '<span class="input-group-addon">'.$oCurrency->getSign().'</span>';

		// Für jede Position ein TD
		$oTr = new Ext_Gui2_Html_Table_Tr();
		$oTr->class = 'payment_toggle_row';
		foreach((array)$aHeader as $sKey => $aData) {

			$mValue = '';

			switch($sKey) {
				case 'checkbox':
					$mValue = $aCheckboxTd;
					$sClass = 'tdCheckbox';
					break;

				case 'document_number':
					if ($this->_bGroup) {
						$mValue = $sDocumentNumber ?? '-';
					}
					$sClass = '';
					break;

				case 'description':
					$mValue = $sItemDescription;
					$sClass = '';
					break;

				case 'total':
					$mValue = Ext_Thebing_Format::Number($fAmount, $this->_iCurrencyInquiryId, $this->_oSchool->id);
					$sClass = 'amount';
					break;

				case 'balance':
					$mValue = Ext_Thebing_Format::Number($fBalance, $this->_iCurrencyInquiryId, $this->_oSchool->id);
					$sClass = 'amount';
					break;

				case 'amount':
					$oInputDiv = $this->_oDialog->create('div');
					$oInputDiv->class = 'form-group-sm input-group';
					$oInput = $this->_oDialog->create('input');
					$oInput->type = 'text';
					$oInput->name = $aInputAmountName;
					$oInput->style = 'width: 80px';
					$oInput->class = $sClassNamesAmount;
					$oInput->id = $sIDNamesAmount;
					$oInput->value = '';

					if(!empty($sItemId)) {
						$oInput->setDataAttribute('item-id', $sItemId);
					}

					$oInputDiv->setElement($oInput);
					$oInputDiv->setElement($sAmountCurrency);

					// Summenzeile überspringen und Input cachen, um es in Zeile der Creditnotes schreiben zu können
					if(
//						$this->_oAgencyPayment instanceof Ext_Thebing_Agency_Payment &&
						($this->_oFirstInquiry->hasAgency() && $this->sAdditional !== 'commission_payout') &&
						$oInquiry == null
					) {
						$this->_oLastAmountInput = $oInputDiv;
						$oInputDiv = '';
					}

					$mValue = $oInputDiv;
					$sClass = 'amount_td';
					break;

				case 'amount_school':
					$oInputDiv = $this->_oDialog->create('div');
					$oInputDiv->class = 'form-group-sm input-group';
					$oInput = $this->_oDialog->create('input');
					$oInput->type = 'text';
					$oInput->name = $aInputAmountSchoolName;
					$oInput->style = 'width: 80px';
					$oInput->class = $sClassNamesAmountSchool;
					$oInput->id = $sIDNamesAmountSchool;
					$oInput->value = '';
					$oInput->bReadOnly = true;
					$oInput->bDisabledByReadonly = false;
					$oInputDiv->setElement($oInput);
					$oInputDiv->setElement($sAmountSchoolCurrency);

					// Summenzeile überspringen und Input cachen, um es in Zeile der Creditnotes schreiben zu können
					if(
//						$this->_oAgencyPayment instanceof Ext_Thebing_Agency_Payment &&
						($this->_oFirstInquiry->hasAgency() && $this->sAdditional !== 'commission_payout') &&
						$oInquiry == null
					) {
						$this->_oLastAmountSchoolInput = $oInputDiv;
						$oInputDiv = '';
					}

					$mValue = $oInputDiv;
					$sClass = 'amount_school_td';
					break;

			}

			// Zeile schreiben
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			if(is_array($mValue)) {
				foreach((array)$mValue as $oValue) {
					$oTd->setElement($oValue);
				}
			} else {
				$oTd->setElement($mValue);
			}

			$oTd->class = $sClass;
			$oTd->style = 'display: ' . $aData['display'];
			$oTr->setElement($oTd);

		}

		return $oTr;
	}

	protected function getPaymentCheckboxAllRow($header) {

		$checkbox = $this->_oDialog->create('input');
		$checkbox->type = 'checkbox';
		$checkbox->checked = 1;
		$checkbox->class = 'payment_checkbox_all';

		$colGroup = count($header) - count(array_filter($header, fn ($data) => $data['display'] == 'none'));

		$tr = new Ext_Gui2_Html_Table_Tr();
		$td = new Ext_Gui2_Html_Table_Tr_Td();
		$td->setElement($checkbox);
		$td->class = 'payment_customer_td tdCheckbox';
		$tr->setElement($td);
		$td = new Ext_Gui2_Html_Table_Tr_Td();
		$td->setElement($this->_oDialog->oGui->t('Alle auswählen'));
		$td->class = 'payment_customer_td';
		$td->colspan = $colGroup;
		$tr->setElement($td);

		return $tr;
	}

	protected function getPaymentInfoRow($header, $inquiry, $text) {

		$customer = $inquiry->getCustomer();
		$checkbox = $this->_oDialog->create('input');
		$checkbox->type = 'checkbox';
		$checkbox->checked = 1;
		$checkbox->class = 'payment_checkbox payment_checkbox_customer payment_checkbox_customer_' . $customer->id;

		$colGroup = count($header) - count(array_filter($header, fn ($data) => $data['display'] == 'none'));

		$tr = new Ext_Gui2_Html_Table_Tr();
		$tr->class = 'payment_clickable_row';
		$td = new Ext_Gui2_Html_Table_Tr_Td();
		$td->setElement($checkbox);
		$td->class = 'payment_customer_td tdCheckbox';
		$tr->setElement($td);
		$td = new Ext_Gui2_Html_Table_Tr_Td();
		$td->setElement($text);
		$td->class = 'payment_customer_td';
		$tr->setElement($td);
		$td = new Ext_Gui2_Html_Table_Tr_Td();
		$td->setElement($customer->getName());
		$td->class = 'payment_customer_td';
		$td->colspan = $colGroup - 1;
		$tr->setElement($td);

		return $tr;
	}

	protected function _getOverPaymentRow($aHeader, $iOverpaymentType = 0) {

		// $iOverpaymentType -> 0 = Payments, anzeige der aktuellen überbezahlung
		// $iOverpaymentType -> 1 = Überbezahlung, anzeige der "bewusten" überbezahlung
		// $iOverpaymentType -> 2 = Überbezahlung, anzeige der "änderungs" Überbezahlung

		$sCurrencyFromSign = Ext_Thebing_Currency::getInstance($this->_iCurrencyInquiryId)->getSign();
		$sSchoolCurrencyFromSign = Ext_Thebing_Currency::getInstance($this->_iCurrencySchoolId)->getSign();

		if($iOverpaymentType == 0) {
			$sClassTr = 'over_pay_tr_0';

			$oInputAmount = $this->_oDialog->create('input');
			$oInputAmount->type = 'text';
			$oInputAmount->style = 'width: 80px';
			$oInputAmount->class = 'txt amount form-control input-sm overpayment';
			$oInputAmount->value = '';
			$oInputAmount->name = 'save[overpay][amount_inquiry]';
			$oInputAmount->setDataAttribute('current-overpayment-amount', $this->_fStaticOverpayAmount);
			$oInputAmountDiv = $this->_oDialog->create('div');
			$oInputAmountDiv->class = 'form-group-sm input-group';
			$oInputAmountDiv->setElement($oInputAmount);
			$oInputAmountDiv->setElement('<span class="input-group-addon">'.$sCurrencyFromSign.'</span>');

			// Bei Agenturzahlungen oder Mehrfachauswahl gibt es kein Overpayment
			if($this->bDisableOverpayment) {
				$oInputAmount->setDataAttribute('always-ro', 'true'); // Darf nicht readonly heißen, da GUI irgendwas mit strpos macht
			}

			$oInputAmountSchool = $this->_oDialog->create('input');
			$oInputAmountSchool->type = 'text';
			$oInputAmountSchool->style = 'width: 80px';
			$oInputAmountSchool->class = 'txt amount form-control input-sm overpayment_school';
			$oInputAmountSchool->value = '';
			$oInputAmountSchool->name = 'save[overpay][amount_school]';
			$oInputAmountSchool->bReadOnly = true;
			$oInputAmountSchool->bDisabledByReadonly = false; // bReadOnly setzt immer auch disabled?
			$oInputAmountSchoolDiv = $this->_oDialog->create('div');
			$oInputAmountSchoolDiv->class = 'form-group-sm input-group';
			$oInputAmountSchoolDiv->setElement($oInputAmountSchool);
			$oInputAmountSchoolDiv->setElement('<span class="input-group-addon">'.$sSchoolCurrencyFromSign.'</span>');

		} else {
			$sClassTr = 'over_pay_tr_1';
			$oInputAmountSchoolDiv = '';
			$oInputAmountDiv = '';
		}

		// Für jede Position ein TD
		$oTr = new Ext_Gui2_Html_Table_Tr();
		$oTr->class = $sClassTr;

		foreach((array)$aHeader as $sKey => $aData) {

			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$mValue = '';

			switch($sKey) {

				case 'document_number':
					$mValue = L10N::t('Überbezahlung');
					$sClass = '';
					break;

				case 'amount':
					$mValue = $oInputAmountDiv;
					$sClass = 'amount_td';
					break;

				case 'amount_school':
					$mValue = $oInputAmountSchoolDiv;
					$sClass = 'amount_school_td';
					break;

				case 'balance':
					if(!$this->bDisableOverpayment) {
						$mValue = $this->_fStaticOverpayAmount * -1;
						$mValue = Ext_Thebing_Format::Number($mValue, $this->_iStaticOverpayCurrency, $this->_iCurrencySchoolId);
					} else {
						$mValue = '- '.$sCurrencyFromSign;
						$oTd->title = $this->_oDialog->getDataObject()->getGui()->t('Eine Überbezahlung ist nicht möglich.');
					}

					$sClass = 'amount';
					break;

				case 'total':
				case 'checkbox':
				case 'description':
					$sClass = '';
					break;

			}

			$oTd->setElement($mValue);
			$oTd->class = $sClass;
			$oTd->style = 'display: ' . $aData['display'];
			$oTr->setElement($oTd);

		}

		return $oTr;

	}

	protected function _getCreditNoteRow($aHeader, $oCreditNote) {

		if($oCreditNote instanceof Ext_Thebing_Inquiry_Document) {
			$oInquiry = $oCreditNote->getInquiry();
			$iCurrencyId = $oInquiry->getCurrency();
			$oSchool = $oInquiry->getSchool();
		} else {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
			$iCurrencyId = $oCreditNote->currency_id;
		}

		// Für jede Position ein TD
		$oTr = new Ext_Gui2_Html_Table_Tr();
		$oTr->class = 'creditnote-row';
		$oTr->style = 'display: none;';

		$fCommission = (float)$oCreditNote->getCommissionAmount();
		$fBalance = $fCommission - $oCreditNote->getAllocatedAccountingAmount();

		foreach((array)$aHeader as $sKey => $aData) {

			$mValue = '';

			switch($sKey) {
				case 'document_number':
					$mValue = L10N::t('CreditNote', Ext_Gui2::$sAllGuiListL10N) . ': ' . $oCreditNote->document_number;
					if(
						array_key_exists('comment', $oCreditNote->getData()) &&
						strlen($oCreditNote->comment) > 0
					) {
						$mValue .= ' (' . $oCreditNote->comment . ')';
					}

					if($oCreditNote instanceof Ext_Thebing_Inquiry_Document) {
						$oParentDocument = $oCreditNote->getParentDocument();
						if ($oParentDocument instanceof Ext_Thebing_Inquiry_Document) {
							$fOpenAmount = $oParentDocument->getOpenAmount();
							if ($fOpenAmount != 0) {
								$sColor = $oParentDocument->getPayedAmount() ? 'warning' : 'danger';
								$sTitle = L10N::t('Offener Betrag', Ext_Gui2::$sAllGuiListL10N).': '.Ext_Thebing_Format::Number($fOpenAmount, $iCurrencyId, $oSchool->id);
							} else {
								$sColor = 'success';
								$sTitle = '';
							}
							$mValue .= ' '.sprintf('<span class="label label-%s" title="%s">%s</span>', $sColor, $sTitle, $oParentDocument->document_number);
						}
					}

					$sClass = '';
					break;

				case 'total':
					$mValue = $fCommission;
					$mValue = Ext_Thebing_Format::Number($mValue, $iCurrencyId, $oSchool->id);
					$sClass = 'amount';
					break;

				case 'amount':
					$mValue = '';
					$sClass = '';
					break;

				case 'amount_school':
					$mValue = '';
					$sClass = '';
					break;

				case 'balance':
					$mValue = $fBalance;
					$mValue = Ext_Thebing_Format::Number($mValue, $iCurrencyId, $oSchool->id);
					$sClass = 'amount';
					break;

				case 'checkbox':

					$sName = 'creditnote';
					if($oCreditNote instanceof Ext_Thebing_Agency_Manual_Creditnote) {
						$sName = 'manual_creditnote';
					}

					#$fBalanceConverted = Ext_Thebing_Format::ConvertAmount($fBalance, $iCurrencyId, $this->_iCurrencyInquiryId);
					$fBalanceConverted = Ext_Thebing_Format::roundBySchoolSettings($fBalance);

					$oCheckbox = $this->_oDialog->create('input');
					$oCheckbox->type = 'checkbox';
					$oCheckbox->value = $oCreditNote->id;
					$oCheckbox->class = 'checkbox_creditnote';
					$oCheckbox->name = 'save[creditnote]['.$sName.'][]';
					$oCheckbox->setDataAttribute('amount-open', $fBalanceConverted);

					$mValue = $oCheckbox;
					break;

				case 'description':
					$sClass = '';
					break;

			}

			$oTd = new Ext_Gui2_Html_Table_Tr_Td();

			// Zelle »Beschreibung« überspringen
			if($sKey == 'document_number') {
				$oTd->colspan = 2;
			} else if($sKey == 'description') {
				continue;
			}

			$oTd->setElement($mValue);
			$oTd->class = $sClass;
			$oTd->style = 'display: ' . $aData['display'];
			$oTr->setElement($oTd);

		}

		return $oTr;
	}

	protected function _getCreditNoteTotalRow(array $aHeader, array $aCreditNotes)
	{
		$oTr = new Ext_Gui2_Html_Table_Tr();
		$oTr->class = 'creditnotes-total';
		$oTr->style = 'display: none;';

		$aCommission = $aBalances = [];
		foreach ($aCreditNotes as $oCreditNote) {

			if($oCreditNote instanceof Ext_Thebing_Inquiry_Document) {
				$iCurrencyId = $oCreditNote->getInquiry()->getCurrency();
			} else {
				$iCurrencyId = $oCreditNote->currency_id;
			}

			$fCommission = (float)$oCreditNote->getCommissionAmount();
			$fBalance = $fCommission - $oCreditNote->getAllocatedAccountingAmount();

			$aCommission[$iCurrencyId] += $fCommission;
			$aBalances[$iCurrencyId] += $fBalance;
		}

		$aCommission = array_map(fn ($iCurrencyId) => Ext_Thebing_Format::Number($aCommission[$iCurrencyId], $iCurrencyId), array_keys($aCommission));
		$aBalances = array_map(fn ($iCurrencyId) => Ext_Thebing_Format::Number($aBalances[$iCurrencyId], $iCurrencyId), array_keys($aBalances));

		foreach((array)$aHeader as $sKey => $aData) {

			$mValue = '';

			switch($sKey) {
				case 'document_number':
					$mValue = L10N::t('Total (Creditnotes)', Ext_Gui2::$sAllGuiListL10N);
					$sClass = '';
					break;
				case 'total':
					$mValue = implode(', ', $aCommission);
					$sClass = 'amount';
					break;
				case 'amount':
					$mValue = '';
					$sClass = '';
					break;
				case 'amount_school':
					$mValue = '';
					$sClass = '';
					break;
				case 'balance':
					$mValue = implode(', ', $aBalances);
					$sClass = 'amount';
					break;
				case 'checkbox':
					$oCheckbox = $this->_oDialog->create('input');
					$oCheckbox->type = 'checkbox';
					$oCheckbox->class = 'checkbox_creditnotes_total';
					$mValue = $oCheckbox;
					break;
				case 'description':
					$sClass = '';
					break;
			}

			$oTh = new Ext_Gui2_Html_Table_Tr_Th();

			// Zelle »Beschreibung« überspringen
			if($sKey == 'document_number') {
				$oTh->colspan = 2;
			} else if($sKey == 'description') {
				continue;
			}

			$oTh->setElement($mValue);
			$oTh->class = $sClass;
			$oTh->style = 'background-color: #f7f7f7; display: ' . $aData['display'];
			$oTr->setElement($oTh);

		}

		return $oTr;
	}

	protected function _getCreditNoteSumRow($aHeader) {

		// Für jede Position ein TD
		$oTr = new Ext_Gui2_Html_Table_Tr();
		#$oTr->class = $sClassTr;

		foreach((array)$aHeader as $sKey => $aData) {
			$mValue = '';

			switch($sKey) {

				case 'document_number':
					$mValue = L10N::t('Verfügbare Summe', Ext_Gui2::$sAllGuiListL10N);
					$sClass = '';
					break;

				case 'total':
					$mValue = '';
					$sClass = 'amount';
					break;
				case 'amount':
					$mValue = $this->_oLastAmountInput;
					$sClass = '';
					break;
				case 'amount_school':
					$mValue = $this->_oLastAmountSchoolInput;
					$sClass = '';
					break;

				case 'balance':
					$oCurrency = Ext_Thebing_Currency::getInstance($this->_iStaticOverpayCurrency);
					$mValue = '<span class="amount_for_use_total"></span>&nbsp;' . $oCurrency->getSign();
					$sClass = 'amount';
					break;

				case 'description':
				case 'checkbox':
					$mValue = '';
					$sClass = '';
					break;
			}

			$oTd = new Ext_Gui2_Html_Table_Tr_Td();

			// Zelle »Beschreibung« überspringen
			if($sKey == 'document_number') {
				$oTd->colspan = 2;
			} else if($sKey == 'description') {
				continue;
			}

			$oTd->setElement($mValue);
			$oTd->class = $sClass;
			$oTd->style = 'display: ' . $aData['display'];
			$oTr->setElement($oTd);
		}

		return $oTr;
	}

}