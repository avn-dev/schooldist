<?php

/**
 * 
 */
class Ext_Office_PDF extends wdPDF_ExtendedDocument {

	private $_aFormSettings;
	private $_aFormItems;
	private $_aTableKeys;
	private $_iFormID;
	private $_iDocID;
	private $_aDocumentData;
	private $_aFonts;
	private $_oOfficeDao;
	private $_aCustomer;
	private $_oCustomer;

	public function __construct($iFormID, $iDocID = 0, $bWithBgPDF = true)
	{
		parent::__construct();

		global $strPdfPath, $aTypeNames;

		$objOffice = new classExtension_Office;
		$arrConfigData = $objOffice->getConfigData();
		$this->_oOfficeDao = new classExtensionDao_Office($arrConfigData);

		$this->_iFormID = $iFormID;
		$this->_aFormItems = $this->_getFormItems();
		$this->_aFormSettings = $this->_getFormSettings();

		// if isset global form currency
		if(!empty($this->_aFormSettings['currency']))
		{
			$this->currency = $this->_aFormSettings['currency'];
		}
		
		if(!empty($this->_aFormSettings['disable_minus']))
		{
			$this->switchSign = $this->_aFormSettings['disable_minus'];
		}

		if(!empty($this->_aFormSettings['translations']))
		{
			$this->translations = $this->_aFormSettings['translations'];
		}

		// set signature directory
		$sSignaturesDir = \Util::getDocumentRoot().'storage/office/signatures/';
		$this->_aFormSettings['signature'] = $sSignaturesDir;

		$this->_iDocID = $iDocID;
		$this->startPosition = $this->_aFormSettings['start_position'];
		$this->_aTableKeys = $this->_getTableKeys();

		$this->_aFonts = $this->_getFonts();

		$this->_aDocumentFont = array(
			'font' => $this->_aFonts[$this->_aFormSettings['font_id']]['file'],
			'size' => $this->_aFormSettings['font_size'],
			'size_table' => $this->_aFormSettings['font_size_table'],
			'style' => $this->_aFonts[$this->_aFormSettings['font_id']]['style'],
			'color' => $this->_aFormSettings['font_color'],
			'line_color' => $this->_aFormSettings['line_color'],
			'head_bg_color' => $this->_aFormSettings['head_bg_color']
		);

		$this->_oFPDI->addFonts($this->_aFonts);

		$this->_addDocument();
		$this->_setMargins();
		$this->_displayElements();
		$this->_addItems();
		$this->_addTableRows();
		if($this->_aDocumentData['type'] == 'reminder')
		{
			$this->_addReminderItems();
		}

		// if document got own currency set, use this
		if(!empty($this->_aDocumentData['currency']))
		{
			// get all currencys
			$aSQL = array(
				'sCurrency'	=> $this->_aDocumentData['currency']
			);
			$sSQL = "
				SELECT
					`sign`
				FROM
					`data_currencies`
				WHERE
					`iso4217` = :sCurrency
			";
			$sCurrency = DB::getQueryOne($sSQL, $aSQL);
			if(!empty($sCurrency))
			{
				$this->currency = $sCurrency;
			}
		} 

		$aContactFull	= $this->_getContactFull();
		$aCustomersFull	= $this->_getCustomersFull();
		$aEditorData	= $this->_getEditorData();

		$this->_aCustomer = $aCustomersFull;
		$this->_oCustomer = new Ext_Office_Customer(null, $this->_aDocumentData['customer_id']);
		
		// Highlight only the net price on offers
		if(
			$this->_aDocumentData['type'] == 'offer' && 
			$this->_aFormSettings['price_highlighting'] == 'net_total'
		) {
			$this->highlightOfferNetPrice = true;
		}

		// get original document subject
		$sDocumentOriginalSubject = $this->_aDocumentData['subject']; 

		switch($this->_aDocumentData['type']) {
			case "account":
			case "offer":
			case "confirmation":
			case "credit":
			case "cancellation_invoice":
			case "reminder":
				$sSubject = $this->_aFormSettings['translations']['type_'.$this->_aDocumentData['type']];
				if(empty($sSubject)) {
					$sSubject = $aTypeNames[$this->_aDocumentData['type']]." Nr.";
				}
				$this->_aDocumentData['subject'] = $sSubject." ".$this->_aDocumentData['number'];
				break;
			default:
				break;
		}

		$sType				= '';
		$sClientName		= '';
		$sClientNameShort	= '';

		// get client id
		$aSQL = array(
			'iFromID'	=> $this->_iFormID
		);
		$sSQL = "
			SELECT
				`client_id`
			FROM
				`office_forms`
			WHERE
				`id` = :iFromID
		";
		$iClientID = DB::getQueryOne($sSQL, $aSQL);

		if((int)$iClientID > 0) {
	
			// get type
			$aSQL = array(
				'iClientID'	=> $iClientID,
				'sType'		=> 'filename_' . $this->_aDocumentData['type']
			);
			$sSQL = "
				SELECT
					`value`
				FROM
					`office_config`
				WHERE
					`client_id`	= :iClientID AND
					`key` 		= :sType
				LIMIT 1
			";
			$sType = DB::getQueryOne($sSQL, $aSQL);
			
			// get client name and shortname
			$sSQL = "
				SELECT
					`title`,
					`shortcut`
				FROM
					`office_clients`
				WHERE
					`id` = :iClientID
			";
			$aClientData = DB::getQueryRow($sSQL, $aSQL);
			
			$sClientName 		= $aClientData['title'];
			$sClientNameShort	= $aClientData['shortcut'];
		}

		if(
			!$sType || 
			$sType == ''
		) {
			$sType = $aTypeNames[$this->_aDocumentData['type']];
		}

		$sSalutation = Ext_Office_Config::getDefaultSalutation($this->_aCustomer['language'], $aContactFull['sex']);
		$sSalutation = str_replace(
			array(
				'{CustomerContactFirstname}', 
				'{CustomerContactLastname}'
			), 
			array(
				trim($aContactFull['firstname']),
				trim($aContactFull['lastname'])
			), 
			$sSalutation
		);

		$aBlockPlaceholders = array(
			'DocumentYear'				=> date('Y'),
			'DocumentNumber'			=> $this->_aDocumentData['number'],
			'DocumentDate'				=> $this->_aDocumentData['date'],
			'DocumentType'				=> $sType,
			'DocumentCurrency'			=> $this->_aDocumentData['currency'],
			'DocumentSubject'			=> $this->_aDocumentData['subject'],
			'DocumentCustom'			=> $this->_aDocumentData['custom'],
			'DocumentClient'			=> $sClientName,
			'DocumentClientShort'		=> $sClientNameShort,
			'DocumentOriginalSubject'	=> $sDocumentOriginalSubject,
			'DocumentPurchaseOrderNumber' => (string)$this->_aDocumentData['purchase_order_number'],
			'ContactName'				=> trim($aEditorData['firstname']).' '.trim($aEditorData['lastname']),
			'ContactEmail'				=> $aEditorData['email'],
			'ContactPhone'				=> $aEditorData['phone'],
			'CustomerName'				=> $aCustomersFull['company'],
			'CustomerNumber'			=> $aCustomersFull['number'],
			'CustomerContact'			=> trim($aContactFull['firstname']).' '.trim($aContactFull['lastname']),
			'CustomerContactSalutation'	=> $sSalutation,
			'CustomerContactFirstname'	=> $aContactFull['firstname'],
			'CustomerContactLastname'	=> $aContactFull['lastname'],
			'CustomerContactEmail'		=> $aContactFull['email'],
			'CustomerContactPhone'		=> $aContactFull['phone'],
			'CustomerAddress'			=> nl2br($this->_aDocumentData['address']),
			'CustomerVatID'				=> $aCustomersFull['vat_id_nr'],
			'CustomerEU'				=> $this->_oCustomer->isEU(),
			'CustomerNotEU'				=> !$this->_oCustomer->isEU(),
			'CreditCardPaymentLink'		=> $this->_buildCreditcardPaymentLink()
		);
		$this->addVariables($aBlockPlaceholders);

		$this->invoiceItemsPreTax = 1;

		if($bWithBgPDF) {
			$aDocumentTemplates = array(
				'first'		=> $strPdfPath.$this->_iFormID.'_first.pdf',
				'following'	=> $strPdfPath.$this->_iFormID.'_next.pdf'
			);
			$this->_setTemplates($aDocumentTemplates);
		}

		$this->_addTexts();

		$this->fLineHeightFactor = 1.1;
		
	}

	private function _addReminderItems() {

		foreach((array)(unserialize($this->_aDocumentData['fee'])) as $iKey => $aValue) {

			$aAccount = DB::getQueryRow("SELECT *, UNIX_TIMESTAMP(`date`) as date_ts FROM office_documents WHERE id = '".$iKey."'");
			$interest[$aAccount['id']] = (($aValue['interest'] / 100) / 360) * $aAccount['price'] * floor((mktime() - strtotime("+".$aAccount['payment']." days", $aAccount['date_ts'])) / 86400);
			$oPaymentTerm = new Ext_Office_PaymentTerm($aAccount['payment']);

			$aData['position'] = date("d.m.Y", $aAccount['date_ts']);
			$aData['quantity'] = $aAccount['number'];
			$aData['number'] = date("d.m.Y", strtotime("+".$oPaymentTerm->days." days", $aAccount['date_ts']));
			$aData['amount'] = number_format($aAccount['price'], 2, ",", ".")." ".$this->currency;
			$aData['discount'] = ($aValue['fee'] > 0.01)?number_format(str_replace(".", ",", $aValue['fee']), 2, ",", ".")." ".$this->currency:"0,00 ".$this->currency;
			$aData['vat'] = ($aValue['interest'] > 0.01)?number_format(str_replace(".", ",", $aValue['interest']), 2, ",", ".")." %":"0,00 %";

			$dZins = $aAccount['price'] / 100 * $aValue['interest'];
			$aData['totalamount'] = number_format($aAccount['price'] + $dZins + $aValue['fee'], 2, ",", ".")." ".$this->currency;

			$this->addInvoiceItem($aData);
		}
	}

	private function _displayElements()
	{
		$sSignatureFile= '';

		$this->_aDisplayElements = array(
			'starttext'	=> $this->_aFormSettings['display_starttext'],
			'endtext'	=> $this->_aFormSettings['display_endtext'],
			'table'		=> $this->_aFormSettings['display_table'],
			'tablefoot'	=> $this->_aFormSettings['display_tablefoot'],
			
			// set signature file name to signature directory
			'signature'	=> $this->_aFormSettings['signature'].$this->getSignatureFile()
		);
	}

	private function getSignatureFile()
	{
		$sSQL = "
			SELECT
				*
			FROM
				`office_users`
			WHERE
				`id` = :iID
		";
		$aSQL = array('iID' => $this->_aDocumentData['editor_id']);
		$aReturn = DB::getPreparedQueryData($sSQL, $aSQL);

		// return file name of signature
		return $aReturn[0]['signature'];
	}

	private function _setMargins()
	{

		$aMargins = array(
			'left' => $this->_aFormSettings['margin_left'],
			'top' => $this->_aFormSettings['margin_top'],
			'right' => $this->_aFormSettings['margin_right'],
			'bottom' => $this->_aFormSettings['margin_bottom']
		);
		$this->margin = $aMargins;

	}

	private function _getContactFull()
	{
		$aContactFull = $this->_oOfficeDao->getContact($this->_aDocumentData['contact_person_id']);
		return $aContactFull;
	}

	private function _getCustomersFull()
	{
		$aCustomersFull = $this->_oOfficeDao->getCustomer($this->_aDocumentData['customer_id']);
		$aAdditional = $this->_oOfficeDao->getCustomerAdditionals($this->_aDocumentData['customer_id']);
		
		if(!empty($aAdditional)) {
			$aCustomersFull = array_merge((array)$aAdditional, (array)$aCustomersFull);
		}
		
		return $aCustomersFull;
	}

	private function _getEditorData()
	{
		$sSQL = "
			SELECT 
				`id`, `firstname`, `lastname`, `email`, `phone`
			FROM
				system_user
			WHERE
				`id` = :iEditorID
			LIMIT
				1
		";
		$aParams = array('iEditorID' => $this->_aDocumentData['editor_id']);
		$aReturn = DB::getPreparedQueryData($sSQL, $aParams);
		return $aReturn[0];
	}

	private function _addTexts()
	{
		
		$this->footText = "";
		
		if(
			$this->_aDocumentData['type'] == 'account' ||
			$this->_aDocumentData['type'] == 'offer' ||
			$this->_aDocumentData['type'] == 'credit' ||
			$this->_aDocumentData['type'] == 'cancellation_invoice' ||
			$this->_aDocumentData['type'] == 'reminder'
		) {

			// Zahlungshinweis bzw. Angebotsgueltigkeit
			$oPaymentTerm = new Ext_Office_PaymentTerm($this->_aDocumentData['payment']);
			$strPaymentAdvice  = str_replace("<#date#>", strftime("%x", strtotime("+".$oPaymentTerm->days." days", $this->_aDocumentData['date'])), $oPaymentTerm->getMessage($this->_aCustomer['language']));

			$strPaymentAdvice = nl2br(trim($strPaymentAdvice));
			if(!empty($strPaymentAdvice)) {
				$this->footText .= $strPaymentAdvice."<br><br>";
			}
		}
		
		if(
			$this->_aDocumentData['type'] == 'account'
		) {

			// VAT-Hinweis
			if(!$this->_oCustomer->checkCalculateVat()) {
				
				if($this->_oCustomer->isEU()) {
					$sVatHint = Ext_Office_Config::get('vat_hint_eu', $this->_aCustomer['language']);
				} else {
					$sVatHint = Ext_Office_Config::get('vat_hint_thirdcountries', $this->_aCustomer['language']);
				}

				$sVatHint = nl2br(trim($sVatHint));
				if(!empty($sVatHint)) {
					$this->footText .= $sVatHint."<br><br>";
				}
			}

		}

		$this->footText .= $this->_aDocumentData['endtext'];

		$this->footText = $this->_convertUTF8StringAndUmlauts($this->footText);

		$this->headText = $this->_aDocumentData['text'];
		$this->headText = $this->_convertUTF8StringAndUmlauts($this->headText);

	}

	private function _getFormItems()
	{
		$sSQL = "
			SELECT 
				*
			FROM
				office_forms_items
			WHERE	
				form_id = :iID
			AND
				`active` = 1
		";
		$aParams = array('iID' => $this->_iFormID);
		$aItems = DB::getPreparedQueryData($sSQL, $aParams);

		return $aItems; 
	}

	private function _getFormSettings() {
		$sSQL = "
			SELECT 
				*
			FROM
				office_forms
			WHERE
				`active` = 1
			AND
				`id` = :iFormID
			LIMIT
				1
		";
		$aParams = array('iFormID' => $this->_iFormID);
		$aResult = DB::getPreparedQueryData($sSQL, $aParams);
		$aResult = $aResult[0];
		$aResult['translations'] = json_decode($aResult['translations'], true);

		return $aResult;
	}

	private function _getFonts() {
		$sSQL = "
			SELECT 
				*
			FROM
				office_fonts
			WHERE
				`active` = 1
			
		";
		$aResult = DB::getQueryData($sSQL);
		$aFonts = array();
		foreach((array)$aResult as $aItem) {
			$aFonts[$aItem['id']] = $aItem;
		}
		return $aFonts;
	}

	private function _getTableKeys()
	{
		$sSql = "
			SELECT 
				* 
			FROM 
				office_forms_tables
			WHERE 
				form_id = :iFormID
			AND
				`active` = 1
			ORDER BY 
			    `position`
			";
		$aSql = array('iFormID' => $this->_iFormID);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		
		$aTableKeys = array();
		foreach((array)$aData as $aTableKey) {
			$aTableKeys[$aTableKey['key']] = $aTableKey;
		}
		
		return $aTableKeys;
	}

	private function _addItems()
	{
		foreach((array)$this->_aFormItems as $aData)
		{
			
			$aDocumentItem = array(
						'x'			=> $aData['position_x'],
						'y'			=> $aData['position_y'],
						'width'		=> $aData['width'],
						'font_size'	=> $aData['font_size'],
						'font'		=> $this->_aFonts[$aData['font_id']]['file'],
						'font_style'=> $this->_aFonts[$aData['font_id']]['style'],
						'font_color'=> $aData['font_color'],
						'display'	=> $aData['display'],
						'alignment'	=> $aData['alignment'],
						'content'	=> $aData['content']
					);
			$this->_addDocumentItem($aDocumentItem);
		}
	}

	private function _addTableRows() {

		$sSql = "
			SELECT 
				* 
			FROM 
				office_document_items
			WHERE 
				`document_id` = :iDocID AND
				`active` = 1
			ORDER BY
				`position` ASC
			";
		$aSql = array('iDocID' => $this->_iDocID);
		$aRows = DB::getPreparedQueryData($sSql, $aSql);

		$intPosition = 1;

        $aDelayedGroup = null;
        $aDelayedPositions = array();
        $bHideGroupPositions = false;
        $bDelayedGroupHasPosition = false;

		$bWithDiscount = false;
				
		foreach((array)$aRows as $aRow) {
            /// get row data
			$aData['title'] = trim($aRow['product']);
			$aData['number'] = $aRow['number'];
			$aData['position'] = $intPosition;
			$aData['description'] = trim($aRow['description']);
			$aData['unit'] = $aRow['unit'];
			$aData['discount'] = $aRow['discount_item'];
			$aData['quantity'] = $aRow['amount'];
			$aData['amount'] = $aRow['price'];
			$aData['only_text'] = $aRow['only_text'];
			$aData['groupsum'] = $aRow['groupsum'];
            $aData['group_display'] = $aRow['group_display'];
			$aData['vat'] = $aRow['vat']*100;

			if(bccomp($aData['discount'], 0, 5) !== 0) {
				$bWithDiscount = true;
			}
			
            // add delayed group if we start a new group
            if (
                $aDelayedGroup !== null &&
                $aData['only_text'] &&
                $aData['groupsum'] &&
                in_array($aData['group_display'], array('only_text_positions', 'hide_positions'))
            ) {
                $this->addInvoiceItem($aDelayedGroup);
                foreach ($aDelayedPositions as $aDelayedPosition) {
                    $this->addInvoiceItem($aDelayedPosition);
                }
                $aDelayedGroup = null;
                $aDelayedPositions = array();
                $bHideGroupPositions = false;
                $bDelayedGroupHasPosition = false;
            }
            // delay group and gather data of positions
            if (
                $aData['only_text'] &&
                $aData['groupsum'] &&
                in_array($aData['group_display'], array('only_text_positions', 'hide_positions'))
            ) {
                $aData['display_amount'] = 1;
                $aData['quantity'] = 1;
                $aData['discount'] = 0;
                $aData['amount'] = 0;
                $aDelayedGroup = $aData;
                if ($aData['group_display'] == 'hide_positions') {
                    $bHideGroupPositions = true;
                }
            }
            // add amount of position to delayed group
            elseif (
                $aDelayedGroup !== null
            ) {
                // only a single vat rate per group
                if (!$bDelayedGroupHasPosition) {
                    $aDelayedGroup['vat'] = $aData['vat'];
                    $bDelayedGroupHasPosition = true;
                } elseif ($aDelayedGroup['vat'] != $aData['vat']) {
                    throw new Exception('Cannot combine positions with different vat values into one group.');
                }
                // combine discounts of all positions in this group
                $fCurrentDiscountedAmount = ($aDelayedGroup['amount'] * (100 - ($aDelayedGroup['discount'] / 100)));
                $fNewDiscountedAmount = (($aData['quantity'] * $aData['amount'])  * (100 - ($aData['discount'] / 100)));
                $fTotalDiscountedAmount = ($fCurrentDiscountedAmount + $fNewDiscountedAmount);
                $fTotalAmount = ($aDelayedGroup['amount'] + ($aData['quantity'] * $aData['amount']));
                $aDelayedGroup['discount'] = ((100 - ($fTotalDiscountedAmount / $fTotalAmount)) * 100);
                // sum amounts of all positions in this group
                $aDelayedGroup['amount'] += ($aData['quantity'] * $aData['amount']);
                // only show position when it should not be hidden
                if (!$bHideGroupPositions) {
                    $aData['hide_amount'] = 1;
                    $aDelayedPositions[] = $aData;
                    $intPosition++;
                }
            }
            // add position if no delayed group found
            else {
                $this->addInvoiceItem($aData);
                if (!$aRow['only_text']) {
                    $intPosition++;
                }
            }
		}
        // add the last delayed group 
        if ($aDelayedGroup !== null) {
            $this->addInvoiceItem($aDelayedGroup);
            foreach ($aDelayedPositions as $aDelayedPosition) {
                $this->addInvoiceItem($aDelayedPosition);
            }
        }

		$this->_setInvoiceTableConfig($bWithDiscount);
		
	}

	private function _setInvoiceTableConfig($bWithDiscount=true) {
		global $aTableKeys;

		$aInvoiceTableConfig = array();
		
		// Manuelle Sortierung der Tabellenfelder (nur bei Mahnungen)
		if($this->_aDocumentData['type'] == 'reminder')
		{
			$aInvoiceTableConfig['position'] = array(
					'title'	=> 'Datum',
					'width'	=> $this->_aTableKeys['inv_date']['width'],
					'align'	=> 'L',
					'format' => 0
				);
			$aInvoiceTableConfig['quantity'] = array(
					'title'	=> 'Belegnr.',
					'width'	=> $this->_aTableKeys['inv_number']['width'],
					'align'	=> 'L',
					'format' => 0
				);
			$aInvoiceTableConfig['number'] = array(
					'title'	=> 'Fälligkeit',
					'width'	=> $this->_aTableKeys['inv_pay_date']['width'],
					'align'	=> 'L',
					'format' => 0
				);
			$aInvoiceTableConfig['amount'] = array(
					'title'	=> 'Betrag',
					'width'	=> $this->_aTableKeys['inv_amount']['width'],
					'align'	=> 'R',
					'format' => 0
				);
			$aInvoiceTableConfig['text'] = array(
					'title'	=> '',
					'width'	=> '1',
					'align'	=> 'R',
					'format' => 0
				);
			$aInvoiceTableConfig['discount'] = array(
					'title'	=> 'Gebühr',
					'width'	=> $this->_aTableKeys['inv_fee']['width'],
					'align'	=> 'R',
					'format' => 0
				);
			$aInvoiceTableConfig['vat'] = array(
					'title'	=> 'Zinsen',
					'width'	=> $this->_aTableKeys['inv_zins']['width'],
					'align'	=> 'R',
					'format' => 0
				);
			$aInvoiceTableConfig['totalamount'] = array(
					'title'	=> 'Gesamt',
					'width'	=> $this->_aTableKeys['inv_total']['width'],
					'align'	=> 'R',
					'format' => 0
				);
		}
		else
		{
			$iHighestPosition = max(array_column($this->_aTableKeys, 'position'));

			if ($iHighestPosition !== null) {
				$aTableKeysKeys = array_keys($aTableKeys);
				usort($aTableKeysKeys, function ($sKey1, $sKey2) {
					return ($this->_aTableKeys[$sKey1]['position'] < $this->_aTableKeys[$sKey2]['position']) ? -1 : 1;
				});

				$aTableKeys = array_merge(array_flip($aTableKeysKeys), $aTableKeys);
			}

			// Für automatische Sortierung
			foreach((array)$aTableKeys as $sKey => $aTableKey) {
				if($this->_aTableKeys[$sKey]['active'] == 1) {
					$aInvoiceTableConfig[$sKey] 					= array();
					$aInvoiceTableConfig[$sKey]['title']			= $this->_aTableKeys[$sKey]['title'];
					$aInvoiceTableConfig[$sKey]['width']			= $this->_aTableKeys[$sKey]['width'];
					$aInvoiceTableConfig[$sKey]['align']			= $aTableKey['align'];
					$aInvoiceTableConfig[$sKey]['decimal_places']	= (int)$this->_aTableKeys[$sKey]['decimal_places'];
				}
			}

			// Discount-Spalte entfernen, falls kein Discount da ist
			if($bWithDiscount === false) {
				if(isset($aInvoiceTableConfig['discount'])) {
					$aInvoiceTableConfig['text']['width'] += $aInvoiceTableConfig['discount']['width'];
					unset($aInvoiceTableConfig['discount']);
				}
			}

		}

		$this->setInvoiceTableConfig($aInvoiceTableConfig);
		
	}

	private function _addDocument()
	{
		$sSql = "
			SELECT 
				*, UNIX_TIMESTAMP(`date`) as `date`
			FROM 
				office_documents
			WHERE 
				`id` = :iDocID
			AND
				`active` = 1
			LIMIT
				1
			";
		$aSql = array('iDocID' => $this->_iDocID);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aDocumentData = $aData[0];
		$this->_iInvoiceDiscount = $aData[0]['discount'];
	}

	private function _convertUTF8StringAndUmlauts($sString) {

		$sString = clean_text($sString);

		// return the string
		return $sString;
	}

	/* ==================================================================================================== */

	public function showCheckListPDF($sFileName)
	{
		$aVars = $this->variables;

		$this->_aDisplayElements['starttext'] = 
		$this->_aDisplayElements['endtext'] = 
		$this->_aDisplayElements['table'] = 
		$this->_aDisplayElements['tablefoot'] = 0;

		$aVariables = array('DocumentSubject' => "Checkliste zum " . $aVars['DocumentSubject']);
		$this->addVariables($aVariables);

		$this->_writeCheckListItems($sFileName);

		//$this->showPDFFile($sFileName);
	}

	protected function _writeCheckListItems($sFileName)
	{
		$this->_oFPDI->AddPage();

		$iLeft	= $this->_aPageMargins['left'];
		$iRight	= $this->_aPageMargins['right'];
		$iLineH = $this->_oFPDI->getCurrentLineHeight();

		$this->_oFPDI->SetY($this->startPosition);

		$this->_oFPDI->SetY($this->_oFPDI->GetY() + $iLineH);

		$this->_oFPDI->setWDFont($this->_aDocumentFont['font'], $this->_aDocumentFont['size'], $this->_aDocumentFont['style']);
		$this->_oFPDI->SetTextColor(hexdec(substr($this->_aDocumentFont['color'], 0, 2)), hexdec(substr($this->_aDocumentFont['color'], 2, 2)), hexdec(substr($this->_aDocumentFont['color'], 4, 2)));
		$this->_oFPDI->SetDrawColor(hexdec(substr($this->_aDocumentFont['line_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 4, 2)));

		$oDocument = new Ext_Office_Document($this->_iDocID);
		$aItems = $oDocument->getCheckList();

		foreach((array)$aItems as $iKey => $aItem)
		{
			if(empty($aItem['checks']))
			{
				continue;
			}

			// Write item title
			$this->_oFPDI->SetFont($this->_aDocumentFont['font'], 'B', $this->_aDocumentFont['size'] + 1);
			$this->_oFPDI->MultiCell(210, $iLineH + 2, $this->convertUTF8String('# ' . $aItem['product']), 0, 'L');

			// Reset font style
			$this->_oFPDI->SetFont($this->_aDocumentFont['font'], '', $this->_aDocumentFont['size']);

			foreach((array)$aItem['checks'] as $iKey => $aCheck)
			{
				$this->_oFPDI->SetDrawColor(hexdec(substr($this->_aDocumentFont['line_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 4, 2)));

				// Checkbox
				$this->_oFPDI->Line($iLeft + 3, $this->_oFPDI->GetY(), $iLeft + 8, $this->_oFPDI->GetY());
				$this->_oFPDI->Line($iLeft + 3, $this->_oFPDI->GetY(), $iLeft + 3, $this->_oFPDI->GetY() + 5);
				$this->_oFPDI->Line($iLeft + 3, $this->_oFPDI->GetY() + 5, $iLeft + 8, $this->_oFPDI->GetY() + 5);
				$this->_oFPDI->Line($iLeft + 8, $this->_oFPDI->GetY(), $iLeft + 8, $this->_oFPDI->GetY() + 5);

				$iWidth = $iLeft + 10;

				$aTexts = $this->getArrayFromText($aCheck['text'], 210 - $iWidth - $iRight);

				$this->_oFPDI->SetY($this->_oFPDI->GetY() - 1);

				foreach($aTexts['lines'] as $iLineKey => $sLineValue)
				{
					$this->_oFPDI->SetX($iWidth);
					$this->_oFPDI->Cell(210 - $iWidth - $iRight, $iLineH, $sLineValue, 0, 'L');
					$this->_oFPDI->SetY($this->_oFPDI->GetY() + $iLineH);
				}

				$this->_oFPDI->SetDrawColor(hexdec('dd'), hexdec('dd'), hexdec('dd'));

				// Notice box
				$this->_oFPDI->Line($iLeft + 11, $this->_oFPDI->GetY(), 210 - $iRight, $this->_oFPDI->GetY());
				$this->_oFPDI->Line($iLeft + 11, $this->_oFPDI->GetY(), $iLeft + 11, $this->_oFPDI->GetY() + 20);
				$this->_oFPDI->Line($iLeft + 11, $this->_oFPDI->GetY() + 20, 210 - $iRight, $this->_oFPDI->GetY() + 20);
				$this->_oFPDI->Line(210 - $iRight, $this->_oFPDI->GetY(), 210 - $iRight, $this->_oFPDI->GetY() + 20);

				$this->_oFPDI->SetY($this->_oFPDI->GetY() + 23);
			}

			$this->_oFPDI->SetY($this->_oFPDI->GetY() + $iLineH);
		}

		$this->_oFPDI->Output($sFileName, 'I');
	}
	
	
	/**
	 * Erstellt den Link um per Kreditkarte zu zahlen.
	 * 
	 * @return string Den <b>Link zur Kreditkartenzahlung</b>.
	 */
	protected function _buildCreditcardPaymentLink() {

		// Konfigurationsdaten holen
		$sUrl = Ext_Office_Config::get('paymill_target_url', $this->_aCustomer['language']);
		$sLabel = Ext_Office_Config::get('paymill_link_label', $this->_aCustomer['language']);

		// Einzigartigen hash holen
		$sHash = $this->_aDocumentData['hash'];

		// Link zum Kreditkartenzahlungs-Dokument zusammenbauen.
		$sCreditCardPaymentURL = $sUrl . '?hash=' . $sHash;

		$sLink = '<a href="' . $sCreditCardPaymentURL . '">' . $sLabel . '</a>';

		return $sLink;
	}

}
