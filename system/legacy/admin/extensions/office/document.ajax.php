<?php

$session = Core\Handler\SessionHandler::getInstance();

$_SESSION['office'] = (array)$session->get('office');

/**
 * 
 */
if($strTask == 'load_checklist') {

	$iDocumentID = (int)$objPurifier->purify($_VARS['document_id']);

	$oDocument = new Ext_Office_Document($iDocumentID);

	$aCheckList = $oDocument->getCheckList();

	$arrTransfer['data'] = array('aCheckList' => $aCheckList);

} else if($strTask == 'save_checklist') {

	$iPositionID = (int)$_VARS['position_id'];

	$oEntry = new WDBasic((int)$_VARS['entry_id'], 'office_checklists');
	$oEntry->position_id	= $iPositionID;
	$oEntry->text			= rawurldecode($objPurifier->purify($_VARS['text']));
	$oEntry->save();

	$arrTransfer['data'] = array('aEntry' => $oEntry->aData);
	
} else if($strTask == 'remove_checklist') {
	
	$oEntry = new WDBasic((int)$_VARS['entry_id'], 'office_checklists');
	$oEntry->active	= 0;
	$oEntry->save();
	
}

if($strTask == 'get_block_text') {
	$rTextBlocks = DB::getQueryRows("SELECT * FROM `office_templates` WHERE `id` = ".(int)$objPurifier->purify($_VARS['block_text_id']));
	foreach($rTextBlocks as $aTextBlock) {
		$sTextBlocks = $aTextBlock['text'];
	}

	$arrTransfer['data'] = array(
		'sArea'		=> $objPurifier->purify($_VARS['area']),
		'sText'		=> $sTextBlocks
	);
}

if($strTask == 'prepare_document') {

	// Get document id
	$iDocumentID = (int)$objPurifier->purify($_VARS['document_id']);

	// Get pdf output forms
	$sSQL = "SELECT * FROM `office_forms` WHERE `active` = 1";
	$rResult = DB::getQueryRows($sSQL);

	$aPDFForms = array();
	foreach($rResult as $aResult) {
		$aPDFForms[] = array($aResult['id'], $aResult['name']);
	}

	// Neues Dokument kann keine Positionen haben
	unset($_SESSION['office']['doc_'.$iDocumentID]['positions']);

	// Prepare document types
	$aTypes				= array();
	$sSelectedType		= 'letter';
	foreach((array)$aTypeNames as $sKey => $sValue) {
		$aTypes[] = array($sKey, $sValue);
	}

	// Prepare editors
	$aTmpEditors		= $objOfficeDao->getEditors();
	$aEditors			= array();
	$iSelectedEditor	= $user_data['id'];
	foreach((array)$aTmpEditors as $sKey => $sValue) {
		$aEditors[] = array($sKey, $sValue);
	}

	// Prepare customers
	$aTmpCustomers		= $objOfficeDao->getCustomers();
	$aCustomers			= array(array('', '---'));
	$iSelectedCustomer	= 0;
	foreach((array)$aTmpCustomers as $iKey => $aValue) {
		$aCustomers[] = array($iKey, $aValue['name'], $aValue['language']);
	}

	// prepare clients
	$aTmpClients		= $aClients;
	$aJSClients			= array();
	$iSelectedClient	= 1;
	foreach((array)$aTmpClients as $iKey => $sClientName) {
		$aJSClients[] = array($iKey, $sClientName);
	}

	// prepare currencys
	$aTmpCurrencys		= Data::getCurrencys();
	$aJSCurrencys		= array();
	$iSelectedCurrency	= '';
	foreach((array)$aTmpCurrencys as $iKey => $aCurrencys) {
		if($iKey == 0) {
			$aJSCurrencys[] = array(
				0 => '',
				1 => 'keine Auswahl'
			);
		}
		$aJSCurrencys[] = array(
			0	=> $aCurrencys['iso4217'],
			1	=> $aCurrencys['sign'] . ' - ' . $aCurrencys['name']
		);
	}

	// Produktbereiche
	$oProductArea = new \Office\Entity\ProductArea;
	$aProductAreas = $oProductArea->getArrayList();
	if(!empty($aProductAreas)) {
		$aProductAreas = array_values($aProductAreas);
		foreach($aProductAreas as &$aProductArea) {
			$aProductArea = array(
				$aProductArea['id'],
				$aProductArea['name']
			);
		}
		array_unshift($aProductAreas, array(0, ''));
	}

	// Prepare contact person
	$aContacts			= array(array('', '---'));
	$iSelectedContact	= 0;

	$aTextBlocks = array(array('', '---', array()));
	$rTextBlocks = DB::getQueryRows("SELECT * FROM office_templates ORDER BY name");
	foreach($rTextBlocks as $aTextBlock) {
		$aTextBlocks[] = array($aTextBlock['id'], $aTextBlock['name']);
	}

	// Prepare units
	$aArticleUnits = array();
	foreach((array)$aUnits as $sKey => $sValue) {
		$aArticleUnits[] = array($sKey, $sValue);
	}

	$oRevenueAccounts = \Office\Entity\RevenueAccounts::getInstance();
	$aTmpRevenueAccounts = (array)$oRevenueAccounts->getArrayList(true);	
	$aRevenueAccounts = array(array(0, ''));
	foreach($aTmpRevenueAccounts as $sKey => $sValue) {
		$aRevenueAccounts[] = array($sKey, $sValue);
	}

	// Prepare vat´s
	$aArticleVats = array();
	foreach((array)$aVat as $sKey => $sValue) {
		$aArticleVats[] = array($sKey, $sValue['rate']);
	}

	// Prepare positions
	$aTmpArticles = $objOfficeDao->getArticles();
	$aArticles = array(array('', '---'));
	foreach((array)$aTmpArticles as $iKey => $aValue) {
		$aArticles[] = array($aValue['id'], $aValue['product'], $aValue);
	}

	// Prepare payments
	$aTmpPayments = classExtensionDao_Office::getPaymentTerms(null, true);
	
	$aPayments = array(array('', '---', array('message' => 'Zur Zeit ist keine Zahlungsbedingung ausgewählt...')));
	$iSelectedPayment = 0;
	foreach((array)$aTmpPayments as $iKey => $aValue) {
		$aPayments[] = array($aValue['id'], $aValue['title'], $aValue);
	}

	// Prepare state
	$sState = 'draft';

	// Prepare date
	$sDate = date('d.m.Y');
	$sBookingDate = '';

	// Prepare subject
	$sSubject = '';
	
	$sPurchaseOrderNumber = '';

	// Prepare address
	$sAddress = '';

	// Prepare document texts
	$sStartText = $sEndtext = '';

	// Prepare contract properties
	$sSelectedScale = '';
	$sSelectedContractLast = '';
	$sSelectedInterval = '';

	// Prepare positions
	$aPosiitons = array();

	// Prepare receivables
	$aReceivables = array();

	// Contract scales
	$aContractScales = array(
		array('Wochen', 'Wochen'),
		array('Monate', 'Monate')
	);

	// Prepare fee
	$aFee = array();

	// Prepare document discount
	$iTotalDiscount = 0;
	$iCashDiscount = 0;

	// Document already exists
	if($iDocumentID > 0) {

		// Get document data
		$aDocument = $objOfficeDao->getDocumentData($iDocumentID);

		// Prepare document properties
		$sSelectedType			= $aDocument['type'];
		$iTotalDiscount			= $aDocument['discount'];
		$iCashDiscount			= $aDocument['cash_discount'];
		$iSelectedEditor		= $aDocument['editor_id'];
		$iSelectedClient		= $aDocument['client_id'];
		$iSelectedCurrency		= $aDocument['currency'];
		$iSelectedCustomer		= $aDocument['customer_id'];
		$sAddress				= $aDocument['address'];
		$sState					= $aDocument['state'];
		$sDate					= date('d.m.Y', $aDocument['date']);
		$sBookingDate			= date('d.m.Y', $aDocument['booking_date']); 
		$iSelectedPayment		= $aDocument['payment'];
		$sSubject				= $aDocument['subject'];
		$sPurchaseOrderNumber = $aDocument['purchase_order_number'];
		$sStartText				= \Util::getEscapedString($aDocument['text']);
		$sEndtext				= \Util::getEscapedString($aDocument['endtext']);
		$sSelectedScale			= $aDocument['contract_scale'];
		$sSelectedContractLast	= $aDocument['contract_last'] != 0 ? date('d.m.Y', $aDocument['contract_last']) : '';
		$sSelectedInterval		= $aDocument['contract_interval'];

		$oCustomer = new Ext_Office_Customer(null, $iSelectedCustomer);
		$bCalculateVat = $oCustomer->checkCalculateVat();
		
		// Wenn Buchungsdatum = Rechnungsdatum
		if($sDate == $sBookingDate) {
			$sBookingDate = '';
		}

		// Prepare fee
		if($aDocument['fee'] != '') {
			$aFee = unserialize($aDocument['fee']);
		}

		// Get document items
		$aPosiitons = $objOfficeDao->getDocumentItems($iDocumentID);
		if(is_null($aPosiitons)) {
			$aPosiitons = array();
		}

		// Define position counter
		$i = 1;
		foreach((array)$aPosiitons as $iKey => $aValue) {

			$aPosiitons[$iKey]['position']		= $i++;
			$aPosiitons[$iKey]['totalprice']	= $aPosiitons[$iKey]['amount'] * $aPosiitons[$iKey]['price'] * (1 - $aPosiitons[$iKey]['discount_item'] / 100);
			$aPosiitons[$iKey]['amount']		= $aPosiitons[$iKey]['amount'];
			$aPosiitons[$iKey]['price']			= $aPosiitons[$iKey]['price'];
			$aPosiitons[$iKey]['discount_item']	= $aPosiitons[$iKey]['discount_item'];
			$aPosiitons[$iKey]['vat']			= $aPosiitons[$iKey]['vat'] * 100;

			// Write position in the session
			$_SESSION['office']['doc_'.$iDocumentID]['positions'][] = $aPosiitons[$iKey];
		}

		// Get receivable documents
		$aTmpReceivables = $objOfficeDao->getReceivables();
		$aReceivables = array();
		foreach((array)$aTmpReceivables as $iKey => $aValue) {
			if($aValue['customer_id'] == $aDocument['customer_id']) {
				$aValue['date'] = date('d.m.Y', $aValue['date']);
				$aValue['due_date'] = date('d.m.Y', $aValue['due_date']);
				$aValue['price'] = number_format($aValue['price'], 2, ',', '.');
				$aValue['receivable'] = number_format($aValue['receivable'], 2, ',', '.');

				$aReceivables[] = $aValue;
			}
		}

		// Get document contact persons
		$aTmpContacts = $objOfficeDao->getContacts($aDocument['customer_id']);
		$iSelectedContact = $aDocument['contact_person_id'];
		foreach((array)$aTmpContacts as $sKey => $aValue) {
			$aContacts[] = array($sKey, $aValue['firstname'].' '.$aValue['lastname']);
		}
		
		// Get Settlement-List
		$aSettlementListItems = $oCustomer->getSettlementListItems();
		
		if(!empty($aSettlementListItems)) {
			foreach($aSettlementListItems as &$mSettlementListItem) {
				$mSettlementListItem = $mSettlementListItem->aData;
			}
		}
		
	} else {
		$bCalculateVat = true;
	}

	$arrTransfer['data'] = array(
		'id'					=> $iDocumentID,

		'aProductAreas' => $aProductAreas,
		'aTypes'				=> $aTypes,
		'aEditors'				=> $aEditors,
		'aClients'				=> $aJSClients,
		'aCurrencys'			=> $aJSCurrencys,
		'aCustomers'			=> $aCustomers,
		'aContacts'				=> $aContacts,
		'aTextBlocks'			=> $aTextBlocks,
		'aArticles'				=> $aArticles,
		'aUnits'				=> $aArticleUnits,
		'aVats'					=> $aArticleVats,
		'aRevenueAccounts'		=> $aRevenueAccounts,
		'aPayments'				=> $aPayments,
		'aPositions'			=> $aPosiitons,
		'aReceivables'			=> $aReceivables,
		'aContractScales'		=> $aContractScales,
		'aReminders'			=> $aFee,
		'aPDFForms'				=> $aPDFForms,

		'sState'				=> $sState,
		'sDate'					=> $sDate,
		'sBookingDate'			=> $sBookingDate,
		'sSubject'				=> $sSubject,
		'sPurchaseOrderNumber' => $sPurchaseOrderNumber,
		'sAddress'				=> $sAddress,
		'sStartText'			=> $sStartText,
		'sEndText'				=> $sEndtext,
		'iTotalDiscount'		=> $iTotalDiscount,
		'iCashDiscount'		    => $iCashDiscount,

		'selectedType'			=> $sSelectedType,
		'selectedEditor'		=> $iSelectedEditor,
		'selectedClient'		=> $iSelectedClient,
		'selectedCurrency'		=> $iSelectedCurrency,
		'selectedCustomer'		=> $iSelectedCustomer,
		'selectedContact'		=> $iSelectedContact,
		'selectedPayment'		=> $iSelectedPayment,
		'selectedScale'			=> $sSelectedScale,
		'selectedContractLast'	=> $sSelectedContractLast,
		'selectedInterval'		=> $sSelectedInterval,
		'selectedCustomerVatCheck' => $bCalculateVat,
		'settlement_list_items'		=> $aSettlementListItems,

		'form_id' => $aDocument['form_id'],
		'product_area_id' => $aDocument['product_area_id']
	);

} elseif($strTask == 'prepare_ticket') {

	// Get the tickets object
	$oTickets = new Ext_Office_TicketsLight();

	// Prepare customers
	$aTmpCustomers		= $oTickets->getCustomers();
	$aCustomers			= array();
	foreach((array)$aTmpCustomers as $iKey => $sValue)
	{
		$aCustomers[] = array($iKey, $sValue);
	}

	// Prepare priorities
	$aTmpPriorities		= $oTickets->getPriorities();
	$aPriorities		= array();
	foreach((array)$aTmpPriorities as $iKey => $sValue)
	{
		$aPriorities[] = array($iKey, $sValue);
	}

	$sSQL = "SELECT `customer_id` FROM `office_documents` WHERE `id` = :iID";
	$aSelectedCust = DB::getPreparedQueryData($sSQL, array('iID' => (int)$_VARS['document_id']));

	// Prepare editors
	$aTmpUsers		= $oTickets->getUserList();
	$aUsers			= array();
	$iSelectedUser	= $user_data['id'];
	foreach((array)$aTmpUsers as $iKey => $sValue)
	{
		$aUsers[] = array($iKey, $sValue);
	}

	// Create the transfer array
	$arrTransfer['data'] = array(
		'aCustomers'	=> $aCustomers,
		'aPriorities'	=> $aPriorities,
		'aStates'		=> $aStates,
		'aUsers'		=> $aUsers,
		'selectedUser'	=> $iSelectedUser,
		'selectedCust'	=> $aSelectedCust[0]['customer_id'],
		'documentID'	=> (int)$_VARS['document_id']
	);

} elseif($strTask == 'save_ticket') {
	
	// Get the tickets object
	$oTickets = new Ext_Office_TicketsLight();

	// Prepare values
	$_VARS['due_date']		= rawurldecode($objPurifier->purify($_VARS['due_date']));
	$_VARS['headline']		= rawurldecode($objPurifier->purify($_VARS['headline']));
	$_VARS['description']	= rawurldecode($objPurifier->purify($_VARS['description']));

	$iTicketID = $oTickets->saveTicket($_VARS);
	$oTickets->sendMessage($iTicketID);

} elseif($strTask == 'getClientData') {

	// Get client ID
	$iClientID = (int)$objPurifier->purify($_VARS['document_client_id']);

	// get all customer to selected client
	$aTmpCustomers = $objOfficeDao->getCustomers('', $iClientID);
	$aCustomers = array(array('', '---', array()));
	foreach((array)$aTmpCustomers as $sKey => $aValue)
	{
		$aCustomers[] = array($sKey, $aValue['name'], $aValue);
	}

	$arrTransfer['data'] = array(
		'aCustomers'	=> $aCustomers
	);

} elseif($strTask == 'getCustomerData') {

	// Get customer ID
	$iCustomerID = (int)$_VARS['customer_id'];

	$aContacts = array(array('', '---', array()));
	$iSelectedContact = 0;

	$sAddress = '';
	$bCalculateVat = true;
	if($iCustomerID > 0) {

		// Prepare contacts
		$aTmpContacts = $objOfficeDao->getContacts($iCustomerID);
		foreach((array)$aTmpContacts as $sKey => $aValue) {
			$aContacts[] = array($sKey, $aValue['firstname'].' '.$aValue['lastname'], $aValue);
			
			if(
				$iSelectedContact === 0 &&
				$aValue['invoice_contact'] == 1
			) {
				$iSelectedContact = $sKey;
			}			
		}

		// Get customer data
		$oCustomer = new Ext_Office_Customer(0, $iCustomerID);

		// Create customer address
		$sAddress = $oCustomer->getAddress();

		$bCalculateVat = $oCustomer->checkCalculateVat();
		
		// Get Settlement-List
		$aSettlementListItems = $oCustomer->getSettlementListItems();
		
		if(!empty($aSettlementListItems)) {
			foreach($aSettlementListItems as &$mSettlementListItem) {
				$mSettlementListItem = $mSettlementListItem->aData;
			}
		}

	}

	// Get receivable documents
	$aTmpReceivables = $objOfficeDao->getReceivables();
	$aReceivables = array();
	foreach((array)$aTmpReceivables as $iKey => $aValue) {

		if($aValue['customer_id'] == $iCustomerID) {
			$aValue['date'] = date('d.m.Y', $aValue['date']);
			$aValue['due_date'] = date('d.m.Y', $aValue['due_date']);
			$aValue['price'] = number_format($aValue['price'], 2, ',', '.');
			$aValue['receivable'] = number_format($aValue['receivable'], 2, ',', '.');

			$aReceivables[] = $aValue;
		}

	}

	// Get customer specified payment conditions
	$aTmpPayments = $objOfficeDao->getCustomerPayments($iCustomerID);

	$arrTransfer['data'] = array(
		'iCustomerID'				=> $iCustomerID,
		'aContacts'					=> $aContacts,
		'selectedContact'			=> $iSelectedContact,
		'sAddress'					=> $sAddress,
		'aReceivables'				=> $aReceivables,
		'selectedPaymentInvoice'	=> $aTmpPayments['selectedPaymentInvoice'],
		'selectedPaymentMisc'		=> $aTmpPayments['selectedPaymentMisc'],
		'selectedCustomerVatCheck'	=> $bCalculateVat,
		'settlement_list_items'		=> $aSettlementListItems
	);

} elseif($strTask == 'getPositionData') {

	$iArticleID = (int)$objPurifier->purify($_VARS['article_id']);

	if($iArticleID > 0) {
		$aArticle = $objOfficeDao->getArticle($iArticleID);
	}

	$arrTransfer['data'] = array('aArticle' => $aArticle);

} elseif($strTask == 'add_position') {

	$iDocumentID = (int)$objPurifier->purify($_VARS['document_id']);

	if(!isset($_SESSION['office']['doc_'.$iDocumentID]['positions_counter'])) {
		$_SESSION['office']['doc_'.$iDocumentID]['positions_counter'] = -1;
		$iPositionID = $_SESSION['office']['doc_'.$iDocumentID]['positions_counter'];
	} else {
		$iPositionID = --$_SESSION['office']['doc_'.$iDocumentID]['positions_counter'];
	}

	$aPosition = array(
		'document_id'	=> $iDocumentID,
		'id'			=> $iPositionID,
		'position'		=> count((array)$_SESSION['office']['doc_'.$iDocumentID]['positions']) + 1,
		'amount'		=> rawurldecode($objPurifier->purify($_VARS['amount'])),
		'number'		=> rawurldecode($objPurifier->purify($_VARS['number'])),
		'product'		=> rawurldecode($objPurifier->purify($_VARS['product'])),
		'price'			=> rawurldecode($objPurifier->purify($_VARS['price'])),
		'discount_item'	=> rawurldecode($objPurifier->purify($_VARS['discount_item'])),
		'unit'			=> rawurldecode($objPurifier->purify($_VARS['unit'])),
		'revenue_account' => (int)$_VARS['revenue_account'],
		'vat'			=> rawurldecode($objPurifier->purify($_VARS['vat'])),
		'description'	=> rawurldecode($objPurifier->purify($_VARS['description'])),
		'only_text'		=> rawurldecode($objPurifier->purify($_VARS['only_text'])),
		'groupsum'		=> rawurldecode($objPurifier->purify($_VARS['groupsum'])),
		'settlement_list_item' => (int)$_VARS['settlement_list_item'],
        'group_display' => rawurldecode($objPurifier->purify($_VARS['group_display']))
	);

	// Format position
	$aPosition['totalprice']	= (float)(floatval($aPosition['amount']) * floatval($aPosition['price']) * (1 - floatval($aPosition['discount_item']) / 100));
	$aPosition['amount']		= floatval($aPosition['amount']);
	$aPosition['price']			= floatval($aPosition['price']);
	$aPosition['discount_item']	= floatval($aPosition['discount_item']);
	$aPosition['vat']			= floatval($aPosition['vat']);

	$_SESSION['office']['doc_'.$iDocumentID]['positions'][] = $aPosition;

	$arrTransfer['data']['aPositions'] = $_SESSION['office']['doc_'.$iDocumentID]['positions'];

} elseif($strTask == 'edit_position') {

	$iDocumentID = (int)$_VARS['document_id'];
	$iPositionID = (int)$_VARS['position_id'];

	foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'] as $iKey => $aValue) {

		if($aValue['id'] == $iPositionID) {

			$aValue['groupsum']			= rawurldecode($objPurifier->purify($_VARS['groupsum']));
			$aValue['only_text']		= rawurldecode($objPurifier->purify($_VARS['only_text']));
			$aValue['amount']			= rawurldecode($objPurifier->purify($_VARS['amount']));
			$aValue['number']			= rawurldecode($objPurifier->purify($_VARS['number']));
			$aValue['product']			= rawurldecode($objPurifier->purify($_VARS['product']));
			$aValue['amount']			= rawurldecode($objPurifier->purify($_VARS['amount']));
			$aValue['price']			= rawurldecode($objPurifier->purify($_VARS['price']));
			$aValue['discount_item']	= rawurldecode($objPurifier->purify($_VARS['discount_item']));
			$aValue['unit']				= rawurldecode($objPurifier->purify($_VARS['unit']));
			$aValue['revenue_account']	= (int)$_VARS['revenue_account'];
			$aValue['vat']				= rawurldecode($objPurifier->purify($_VARS['vat']));
			$aValue['description']		= rawurldecode($objPurifier->purify($_VARS['description']));
            $aValue['group_display']	= rawurldecode($objPurifier->purify($_VARS['group_display']));

			// Format position
			$aValue['totalprice']		= (float)(floatval($aValue['amount']) * floatval($aValue['price']) * (1 - floatval($aValue['discount_item']) / 100));
			$aValue['amount']			= floatval($aValue['amount']);
			$aValue['price']			= floatval($aValue['price']);
			$aValue['discount_item']	= floatval($aValue['discount_item']);
			$aValue['vat']				= floatval($aValue['vat']);

			// Replace the position
			$_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey] = $aValue;
		}
	}

	$arrTransfer['data']['aPositions'] = $_SESSION['office']['doc_'.$iDocumentID]['positions'];

} elseif($strTask == 'delete_position') {

	$iDocumentID = (int)$_VARS['document_id'];
	$iPositionID = (int)$_VARS['position_id'];

	$bFlag = false;
	foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'] as $iKey => $aValue) {

		if($aValue['id'] == $iPositionID) {
			// Delete the position
			unset($_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey]);
			$bFlag = true;
		}

		// Move positions forward (for javascript functions)
		if($bFlag) {

			if($_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey+1]) {
				$_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey] = $_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey+1];
				$_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey]['position']--;
			} else {
				unset($_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey]);
			}
		}
	}
	ksort($_SESSION['office']['doc_'.$iDocumentID]['positions']);
	$arrTransfer['data']['aPositions'] = $_SESSION['office']['doc_'.$iDocumentID]['positions'];

} elseif($strTask == 'sort_positions') {

	$aSorted = json_decode($_VARS['sort_array']);

	$iDocumentID = (int)$_VARS['document_id'];

	$aPositions = array();
	$i = 0;
	while(!empty($aSorted)) {
		foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'] as $iKey => $aValue) {
			if($aValue['id'] == $aSorted[$i]) {
				$_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey]['position'] = $i + 1;
				$aPositions[] = $_SESSION['office']['doc_'.$iDocumentID]['positions'][$iKey];

				unset($aSorted[$i]);
				$i++;
				break;
			}
		}
	}

	$_SESSION['office']['doc_'.$iDocumentID]['positions'] = $aPositions;

} elseif($strTask == 'manageReminder') {

	$iDocumentID 	= (int)$_VARS['document_id'];
	$iFee			= (float)$_VARS['fee'];
	$iInterest		= (float)$_VARS['interest'];
	$iReminderID	= (int)$_VARS['reminder_id'];

	// Add or edit a reminder
	if(
		$objPurifier->purify($_VARS['modus']) == 'set'
			||
		$objPurifier->purify($_VARS['modus']) == 'edit'
	) {
		$_SESSION['office']['doc_'.$iDocumentID]['reminders'][$iReminderID] = array(
			'fee'		=> $iFee,
			'interest'	=> $iInterest
		);
	}
	// Delete (unset) a reminder
	else if($objPurifier->purify($_VARS['modus']) == 'unset') {
		unset($_SESSION['office']['doc_'.$iDocumentID]['reminders'][$iReminderID]);
	}

	$arrTransfer['data']['aReminders'] = $_SESSION['office']['doc_'.$iDocumentID]['reminders'];

} elseif($strTask == 'get_document_data') {

	// Get document ID
	$iDocumentID = (int)$_VARS['document_id'];

	// Get document data
	$oAPI = new Ext_Office_Document($iDocumentID);

	// Prepare document data
	$aDocument = array(
		'id'		=> $oAPI->id,
		'type'		=> $oAPI->type,
		'state'		=> $oAPI->state,
	);

	$arrTransfer['data']['aDocument'] = $aDocument;
	
} elseif($strTask == 'copy_document') {
		
	$oDocument = new Ext_Office_Document($_VARS['document_id']);
	$bSuccess = $oDocument->copyContent($_VARS['copy_document_id'], (int)$_VARS['document_truncate_items']);

	$arrTransfer['success'] = $bSuccess;

} elseif($strTask == 'check_copy_document') {
	
	$mDocument = Ext_Office_Document::getByTypeAndId($_VARS['document_copy_type'], $_VARS['document_copy_id']);

	if(
		$mDocument !== false &&
		$mDocument['id'] != $_VARS['document_id']
	) {
		$arrTransfer['check'] = true;
		$arrTransfer['copy_id'] = $mDocument['id'];
		$arrTransfer['document_truncate_items'] = (int)$_VARS['document_truncate_items'];
	} else {
		$arrTransfer['check'] = false;
	}

} elseif($strTask == 'save_document_copy') {

	// Get document ID
	$iDocumentID = (int)$_VARS['document_id'];

	// Get document data
	$oAPI = new Ext_Office_Document($iDocumentID);

	// Placeholders
	$aPlaceHolders = array('{Number}', '{OfferDate}', '{Date}', '{Percent}');

	// Placeholder values
	$aAcceptedTS = $objOfficeDao->getAcceptedTimestamp($iDocumentID);
	$aPlaceHoldersValues = array(
		$oAPI->number,
		date('d.m.Y', $oAPI->date),
		date('d.m.Y', $aAcceptedTS['date']),
		$_VARS['procent']
	);

	// Log
	$aLog = array(
		'id'			=> 0,
		'customer_id'	=> $oAPI->customer_id,
		'contact_id'	=> $oAPI->contact_person_id,
		'editor_id'		=> $user_data['id'],
		'document_id'	=> $oAPI->id,
		'topic'			=> $oAPI->type,
		'subject'		=> 'Weitergeführt',
		'state'			=> ''
	);
	$objOfficeDao->manageProtocols($aLog);

	$sNewType = $objPurifier->purify($_VARS['new_type']);
	
	$bReverseAmounts = false;
	if($sNewType == 'cancellation_invoice') {
		$bReverseAmounts = true;
	}

	// Copy the document
	$iNewDocumentID = $oAPI->copyDocument($sNewType, $bReverseAmounts);

	// Get new document
	$oAPI = new Ext_Office_Document($iNewDocumentID);

	// Log
	$aLog = array(
		'id'			=> 0,
		'customer_id'	=> $oAPI->customer_id,
		'contact_id'	=> $oAPI->contact_person_id,
		'editor_id'		=> $user_data['id'],
		'document_id'	=> $oAPI->id,
		'topic'			=> $oAPI->type,
		'subject'		=> 'Angelegt',
		'state'			=> $oAPI->state
	);

	// Prepare the document as discount amount document
	if((int)$_VARS['checkbox'] == 1) {

		// Create the array as 'vat => price_net - discount_item'
		$aVats = array();
		foreach((array)$oAPI->aItems as $iKey => $aValue) {
			if(!$aVats[$aValue['vat']]) {
				$aVats[$aValue['vat']] = 0;
			}
			$aVats[$aValue['vat']] += ($aValue['price'] * $aValue['amount']) * (1 - $aValue['discount_item'] / 100) * (1 - $oAPI->discount / 100);

			$oAPI->removeItem($aValue['id']);
		}

		// Create new positions
		$iPosition = 0;
		foreach((array)$aVats as $fKey => $fValue) {
			$aItem = array(
				'id'			=> 0,
				'position'		=> ++$iPosition,
				'number'		=> '',
				'product'		=> str_replace($aPlaceHolders, $aPlaceHoldersValues, $arrConfigData['discount_amount_title']),
				'amount'		=> 1,
				'unit'			=> $arrConfigData['discount_amount_unit'],
				'description'	=> str_replace($aPlaceHolders, $aPlaceHoldersValues, $arrConfigData['discount_amount_text']),
				'price'			=> (float)$fValue,
				'discount_item'	=> 100 - (float)$_VARS['procent'],
				'vat'			=> (float)$fKey,
				'only_text'		=> 0,
				'groupsum'		=> 0,
				'revenue_account' => 0,
                'group_display' => ''
			);
			$oAPI->addItems($aItem);
		}
		$oAPI->discount = 0;

		$oAPI->save();

	}

} elseif($strTask == 'saveDocument') {

	// Get (temporary) document id
	$iDocumentID = (int)urldecode($_VARS['document_id']);

	// Create an API object
	if($iDocumentID > 0) {
		$oAPI = new Ext_Office_Document($iDocumentID);

		// Log
		$sLogSubject = 'Bearbeitet';
	} else {

		$oAPI = new Ext_Office_Document();

		// Set the document type if is it a new document
		$oAPI->type = urldecode($_VARS['type']);

		// Log
		$sLogSubject = 'Angelegt';

	}

	// Set the document properties
	$oAPI->currency				= urldecode($objPurifier->purify($_VARS['currency']));
	$oAPI->client_id			= (int)$_VARS['document_client_id'];
	$oAPI->customer_id			= (int)$_VARS['customer'];
	$oAPI->contact_person_id	= (int)$_VARS['contact_person'];
	$oAPI->editor_id			= (int)$_VARS['editor'];
	$oAPI->form_id = (int)$_VARS['form_id'];
	$oAPI->product_area_id = (int)$_VARS['product_area_id'];
	$oAPI->address				= urldecode($objPurifier->purify($_VARS['address']));
	$oAPI->purchase_order_number = urldecode($objPurifier->purify($_VARS['purchase_order_number']));

	// get date
	$sDate = urldecode($_VARS['date']);
	if(WDDate::isDate($sDate, WDDate::DATES)) {
		$oDate = new WDDate(rawurldecode($_VARS['date']), WDDate::DATES);
	} else {
		$oDate = new WDDate();
	}
	$sTimeStamp =  $oDate->get(WDDate::TIMESTAMP);
	$oAPI->date	= $sTimeStamp;

	// Buchungsdatum
	$sBookingDate = $_VARS['booking_date'];
	if(WDDate::isDate($sBookingDate, WDDate::DATES)) {
		$oBookingDate = new WDDate($sBookingDate, WDDate::DATES);
		$oAPI->booking_date = $oBookingDate->get(WDDate::DB_DATE);
	} else {
		$oAPI->booking_date = $oDate->get(WDDate::DB_DATE);
	}

	$oAPI->subject				= urldecode($objPurifier->purify($_VARS['subject']));
	$sTmpText					= urldecode($objPurifier->purify($_VARS['starttext']));
	$oAPI->text					= clean_text($sTmpText);

	switch(urldecode($_VARS['type'])) {
		case 'letter':
		case 'fax': 
			// Do nothing
			break;
		case 'contract':
			// Set the document specified properties
			$oAPI->contract_last		= urldecode($objPurifier->purify($_VARS['contract_last']));
			$oAPI->contract_start		= urldecode($_VARS['date']);
			$oAPI->contract_interval	= urldecode($objPurifier->purify($_VARS['contract_interval']));
			$oAPI->contract_scale		= urldecode($objPurifier->purify($_VARS['contract_scale']));

			// Do not break!!!
		case 'offer':
		case 'confirmation':
		case 'account':
		case 'credit':
		case 'cancellation_invoice':
			
			if(empty($_SESSION['office']['doc_'.$iDocumentID]['positions'])) {
				throw new RuntimeException('Items missing!');
			}

			// If is it credit: price = price * -1;
			$iMultiplicator = 1;
			if(urldecode($_VARS['type']) == 'cancellation_invoice') {
				$iMultiplicator = -1;
			}

			// Add items to document
			foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'] as $iKey => $aValue) {
				
				// Insert new item
				if((int)$aValue['id'] <= 0) {
					$aItem = array(
						'position'			=> $iKey+1,
						'only_text'			=> $aValue['only_text'],
						'groupsum'			=> $aValue['groupsum'],
						'group_display'		=> $aValue['group_display'],
						'number'			=> $aValue['number'],
						'product'			=> $aValue['product'],
						'amount'			=> $aValue['amount'],
						'unit'				=> $aValue['unit'],
						'revenue_account'	=> $aValue['revenue_account'],
						'description'		=> $aValue['description'],
						'price'				=> $aValue['price'] * $iMultiplicator,
						'discount_item'		=> $aValue['discount_item'],
						'vat'				=> ($aValue['vat'] / 100),
					);
					$oAPI->addItems($aItem);
				}
				// Update item
				else {

					foreach((array)$oAPI->aItems as $iItemKey => $aItemValue) {
						if($aItemValue['id'] == (int)$aValue['id']) {
							$oAPI->updateItem($iItemKey, 'position', 		$iKey+1);
							$oAPI->updateItem($iItemKey, 'only_text',		$aValue['only_text']);
							$oAPI->updateItem($iItemKey, 'groupsum',		$aValue['groupsum']);
                            $oAPI->updateItem($iItemKey, 'group_display',	$aValue['group_display']);
							$oAPI->updateItem($iItemKey, 'number',			$aValue['number']);
							$oAPI->updateItem($iItemKey, 'product',			$aValue['product']);
							$oAPI->updateItem($iItemKey, 'amount',			$aValue['amount']);
							$oAPI->updateItem($iItemKey, 'unit',			$aValue['unit']);
							$oAPI->updateItem($iItemKey, 'revenue_account',	$aValue['revenue_account']);
							$oAPI->updateItem($iItemKey, 'description',		$aValue['description']);
							$oAPI->updateItem($iItemKey, 'price',			($aValue['price'] * $iMultiplicator));
							$oAPI->updateItem($iItemKey, 'discount_item',	$aValue['discount_item']);
							$oAPI->updateItem($iItemKey, 'vat',				($aValue['vat'] / 100));
							break;
						}
					}
				}				
				
				if(
					isset($aValue['settlement_list_item']) &&
					(int)$aValue['settlement_list_item'] > 0
				) {
					$oSettlementListItem = \Office\Entity\Settlementlist\Item::getInstance($aValue['settlement_list_item']);
					$oSettlementListItem->cleared = date('Y-m-d H:i:s');
					$oSettlementListItem->save();
				}

			}

			// Set the document specified properties
			$oAPI->discount = (float)$_VARS['discount'];
			$oAPI->cash_discount = (float)$_VARS['cash_discount'];
			$oAPI->payment = (int)$_VARS['payment'];
			$sTmpText = urldecode($objPurifier->purify($_VARS['endtext']));
			$oAPI->endtext = clean_text($sTmpText);
			$oAPI->price = (float)$_VARS['price'] * $iMultiplicator;
			$oAPI->price_net = (float)$_VARS['price_net'] * $iMultiplicator;
			break;
		case 'reminder':

			// Prepare checked reminders
			$aReminders = array();
			foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['reminders'] as $iKey => $aValue) {
				// Set the state of reminded document on 'reminded' 
				$oTmpAPI = new Ext_Office_Document($iKey);
				$oTmpAPI->state = 'reminded';
				$oTmpAPI->save();

				$aReminder = array(
					'fee'		=> $aValue['fee'],
					'interest'	=> $aValue['interest']
				);
				$aReminders[$iKey] = $aReminder;
			}

			// Set the document specified properties
			$oAPI->fee			= serialize($aReminders);
			$oAPI->payment		= (int)$_VARS['payment'];
			$sTmpText			= urldecode($objPurifier->purify($_VARS['endtext']));
			$oAPI->endtext		= html_entity_decode($sTmpText, ENT_NOQUOTES, 'UTF-8');
			$oAPI->price		= (float)$_VARS['price'] * $iMultiplicator;
			$oAPI->price_net	= (float)$_VARS['price_net'] * $iMultiplicator;
			break;
	}

	// Delete items from document if required
	if(count((array)$oAPI->aItems) != count((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'])) {

		foreach((array)$oAPI->aItems as $iItemKey => $aItemValue) {

			// Set delete flag
			$bDelete = true;

			// Check if the item is in the items list
			foreach((array)$_SESSION['office']['doc_'.$iDocumentID]['positions'] as $iKey => $aValue) {
				if(isset($aItemValue['id']) && $aValue['id'] == $aItemValue['id']) {
					// The item is in the items list, do not delete it
					$bDelete = false;
					break;
				}
			}

			// Delete item from data base
			if($bDelete === true) {
				$oAPI->removeItem($aItemValue['id']);
			}
		}
	}

	unset($_SESSION['office']['doc_'.$iDocumentID]);

	// Save the document
	$oAPI->save();

	// Update the data in the SESSION
	if($oAPI->id > 0) {

		// Get document data
		$aDocument = $objOfficeDao->getDocumentData($oAPI->id);

		// Get reminders
		$aFee = array();
		if($aDocument['fee'] != '') {
			$aFee = unserialize($aDocument['fee']);

			foreach((array)$aFee as $iKey => $aValue) {
				$_SESSION['office']['doc_'.$oAPI->id]['reminders'][$iKey] = array(
					'fee'		=> $aValue['fee'],
					'interest'	=> $aValue['interest']
				);
			}
		}

		// Get document items
		$aPosiitons = $objOfficeDao->getDocumentItems((int)$oAPI->id);
		if(is_null($aPosiitons)) {
			$aPosiitons = array();
		}

		foreach((array)$aPosiitons as $iKey => $aValue) {
			$aPosiitons[$iKey]['totalprice']	= $aPosiitons[$iKey]['amount'] * $aPosiitons[$iKey]['price'] * (1 - $aPosiitons[$iKey]['discount_item'] / 100);
			$aPosiitons[$iKey]['amount']		= $aPosiitons[$iKey]['amount'];
			$aPosiitons[$iKey]['price']			= $aPosiitons[$iKey]['price'];
			$aPosiitons[$iKey]['discount_item']	= $aPosiitons[$iKey]['discount_item'];
			$aPosiitons[$iKey]['vat']			= $aPosiitons[$iKey]['vat'] * 100;

			// Correcture for JS functions
			if($aPosiitons[$iKey]['price'] < 0) {
				$aPosiitons[$iKey]['price']			*= -1;
				$aPosiitons[$iKey]['totalprice']	*= -1;
			}

			// Write position in the session
			$_SESSION['office']['doc_'.$oAPI->id]['positions'][] = $aPosiitons[$iKey];
		}

		$arrTransfer['data'] = array(
			'id'					=> $oAPI->id,
			'aPositions'			=> $aPosiitons,
			'aReminders'			=> $aFee
		);
	}

	// Generate PDF
	$oAPI->createFile();

	// Log
	$aLog = array(
		'id'			=> 0,
		'customer_id'	=> $oAPI->customer_id,
		'contact_id'	=> $oAPI->contact_person_id,
		'editor_id'		=> $user_data['id'],
		'document_id'	=> $oAPI->id,
		'topic'			=> $oAPI->type,
		'subject'		=> $sLogSubject,
		'state'			=> $oAPI->state
	);

}

$session->set('office', $_SESSION['office']);
