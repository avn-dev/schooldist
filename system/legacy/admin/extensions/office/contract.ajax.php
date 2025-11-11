<?php

require_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
require_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

Access_Backend::checkAccess("office");

DB::setResultType(MYSQL_ASSOC);

if($_VARS['task'] == 'matching') {
	
	$aTransfer = array();	
	
	$sItems = $_VARS['matching_items'];
	$aItems = explode("\n", $sItems);

	$aTransfer['result'] = Ext_Office_Contract::matchItems($aItems);
	
	$sJson = json_encode($aTransfer);

	echo $sJson;
	
}

if($strTask == 'get_contracts' || $strTask == 'get_actually_contracts')
{
	$oContract = new Ext_Office_Contract();

	$arrTransfer['data'] = array();

	$arrTransfer['order'] = $_VARS['order'];

	if($strTask == 'get_contracts')
	{
		$aContracts = $oContract->getContractsList($_VARS['order'], $_VARS['search']);
		$arrTransfer['data']['bShowDueContracts'] = false;
	}
	else
	{
		$aContracts = $oContract->getDueList('company', false, (int)$_VARS['contract_days_inadvance']);
		$arrTransfer['data']['bShowDueContracts'] = true;
	}

	// Prepare contracts
	foreach((array)$aContracts as $iKey => $aValue)
	{
		$aContracts[$iKey]['start'] = strftime('%x', $aValue['start']);
		$aContracts[$iKey]['end'] = '---';
		if($aValue['end'] != 0)
		{
			$aContracts[$iKey]['end'] = strftime('%x', $aValue['end']);
		}
		if($aValue['end'] != 0 && $aValue['end'] < time())
		{
			$aContracts[$iKey]['active'] = 0;
		}
	}

	$arrTransfer['data']['aContracts'] = $aContracts;
}

/* ==================================================================================================== */

if($strTask == 'create_contract_invoices') {

	$aPayedCounter = array();

	$oContract = new Ext_Office_Contract();
	$aContracts = $oContract->getDueList('company', false, (int)$_VARS['contract_days_inadvance']);

	$aExecuteContracts = array();
	foreach((array)$aContracts as $aContract) {

		if(in_array($aContract['id'], $_VARS['contract_id'])) {

			$oDateS = new WDDate($aContract['due_date']);
			$oDateE = new WDDate();

			$aExecuteContracts[$aContract['id']]['data'] = $aContract;
			$aExecuteContracts[$aContract['id']]['from'][] = $oDateS->get(WDDate::TIMESTAMP);
			$oDateS->add($aContract['interval'], WDDate::MONTH);
			$aExecuteContracts[$aContract['id']]['till'][] = $oDateS->get(WDDate::TIMESTAMP) - 86400;

		}

	}

	$oContract = new Ext_Office_Contract();
	$oContract->createInvoices($aExecuteContracts);

}

/* ==================================================================================================== */

if($strTask == 'delete_contract') {

	$iContractID = (int)$_VARS['contract_id'];

	$oContract = new Ext_Office_Contract($iContractID);
	$oContract->active = 0;
	$oContract->save();

}

/* ==================================================================================================== */

if($strTask == 'load_contract_contacts')
{
	$oContract = new Ext_Office_Contract((int)$_VARS['contract_id']);

	// Get contract contact persons
	$aTmpContacts		= $objOfficeDao->getContacts((int)$_VARS['customer_id']);
	$iSelectedContact	= 0;
	$aContacts			= array(array('', '---'));
	foreach((array)$aTmpContacts as $sKey => $aValue)
	{
		$aContacts[] = array($sKey, $aValue['firstname'].' '.$aValue['lastname']);
	}

	if((int)$_VARS['contract_id'] > 0)
	{
		$iSelectedContact = $oContract->contact_id;
	}

	$arrTransfer['data'] = array(
		'aContacts'			=> $aContacts,
		'selectedContact'	=> $iSelectedContact
	);
}

/* ==================================================================================================== */

if($strTask == 'get_contracts_stats') {

	$oContract = new Ext_Office_Contract();

	if(!isset($_VARS['selected_year'])) {
		$iSelectedYear = date('Y');
	} else {
		$iSelectedYear = (int)$_VARS['selected_year'];
	}

	/* ================================================== */ // Prepare years

	$aTmpYears = Ext_Office_Contract::getAvailableYears();
	$aYears = array();
	foreach((array)$aTmpYears as $iValue) {
		$aYears[] = array($iValue, $iValue);
	}

	/* ================================================== */ // Prepare stats

	$oLocale = new WDLocale(System::d('systemlanguage'), 'date');

	$aTmpStats = $oContract->getContractsStats($iSelectedYear);
	$aStats = array();
	$oDate = new WDDate();
	$iTotal = 0;
	$iTotalCleared = 0;
	for($i = 1; $i <= 12; $i++)
	{
		$oDate->set($i, WDDate::MONTH);

		$iPrice = 0;
		$iCleared = 0;
		if(array_key_exists($i, $aTmpStats))
		{
			$iPrice = $aTmpStats[$i]['price'];
			$iCleared = $aTmpStats[$i]['cleared'];
		}
		$aStats[] = array(
			'month' => $oLocale->getValue('B', $i),
			'price' => $iPrice,
			'cleared' => $iCleared,
			'productgroups' => $aTmpStats[$i]['productgroups'],
			'diff' => $iPrice - $iCleared
		);
		$iTotal += $iPrice;
		$iTotalCleared += $iCleared;
	}

	/* ================================================== */

	// HTML-Code bauen
	$aArticleGroups = $objOfficeDao->getArticleGroups();
	
	$sTableCode = '';
	$sTableCode .= '<table id="tableContractsStats" cellpadding="0" cellspacing="0" border="0" class="table" style="width: 100%; margin: 10px 0;">';
		$sTableCode .= '<thead>';
			$sTableCode .= '<tr>';
				$sTableCode .= '<th style="width: auto;">Monat</th>';
				$sTableCode .= '<th style="width: 120px;">Insgesamt</th>';
				$sTableCode .= '<th style="width: 120px;">Abgerechnet</th>';
				$sTableCode .= '<th style="width: 120px;">Differenz</th>';
				foreach($aArticleGroups as $aArticleGroup) {
					$sTableCode .= '<th style="width: 120px;">'.$aArticleGroup[1].'</th>';
				}
			$sTableCode .= '</tr>';
		$sTableCode .= '</thead>';
		$sTableCode .= '<tbody id="tbl_contract_stats">';

		$aSum = array();

		foreach($aStats as $aRow) {

			$aSum['price'] += $aRow['price'];
			$aSum['cleared'] += $aRow['cleared'];
			$aSum['diff'] += $aRow['diff'];

			$sTableCode .= '<tr>';
			$sTableCode .= '<td>'.$aRow['month'].'</td>';
			$sTableCode .= '<td style="text-align:right;">'.number_format($aRow['price'], 2, ',', '.').' €</td>';
			$sTableCode .= '<td style="text-align:right;">'.number_format($aRow['cleared'], 2, ',', '.').' €</td>';
			$sTableCode .= '<td style="text-align:right;">'.number_format($aRow['diff'], 2, ',', '.').' €</td>';
			
			foreach($aArticleGroups as $aArticleGroup) {
				$fArticleGroup = $aRow['productgroups'][$aArticleGroup[0]];
				$sTableCode .= '<td style="text-align:right;">'.number_format($fArticleGroup, 2, ',', '.').' €</td>';
				$aSum['articlegroup_'.$aArticleGroup[0]] += $fArticleGroup;
			}

			$sTableCode .= '</tr>';

		}
		
		$sTableCode .= '<tr>';
		$sTableCode .= '<th>Gesamt</th>';
		foreach($aSum as $fSum) {
			$sTableCode .= '<th style="text-align:right;">'.number_format($fSum, 2, ',', '.').' €</th>';
		}
		$sTableCode .= '</tr>';
		
		$sTableCode .= '</tbody>';
	$sTableCode .= '</table>';
 
	$arrTransfer['data'] = array(
		'aYears'		=> $aYears,
		'selectedYear'	=> $iSelectedYear,
		'table'		=> $sTableCode
	);

}
/* ==================================================================================================== */

if($strTask == 'get_contracts_stats_customer') {

	$oContract = new Ext_Office_Contract();

	if(!isset($_VARS['selected_year'])) {
		$iSelectedYear = date('Y');
	} else {
		$iSelectedYear = (int)$_VARS['selected_year'];
	}

	/* ================================================== */ // Prepare years

	$aTmpYears = Ext_Office_Contract::getAvailableYears();
	$aYears = array();
	foreach((array)$aTmpYears as $iValue) {
		$aYears[] = array($iValue, $iValue);
	}

	/* ================================================== */ // Prepare stats

	$oLocale = new WDLocale(System::d('systemlanguage'), 'date');

	$aTmpStats = $oContract->getContractsStatsCustomer($iSelectedYear);
	
	/* ================================================== */

	// HTML-Code bauen

	$sTableCode = '';
	$sTableCode .= '<table id="tableContractsStats" cellpadding="0" cellspacing="0" border="0" class="table" style="width: 100%; margin: 10px 0;">';
		$sTableCode .= '<thead>';
			$sTableCode .= '<tr>';
				$sTableCode .= '<th style="width: auto;">Kunde</th>';
				$sTableCode .= '<th style="width: 120px;">Summe</th>';
			$sTableCode .= '</tr>';
		$sTableCode .= '</thead>';
		$sTableCode .= '<tbody id="tbl_contract_stats">';

		$aSum = array();

		foreach($aTmpStats as $aRow) {

			$aSum['price'] += $aRow['price'];

			$sTableCode .= '<tr>';
			$sTableCode .= '<td>'.$aRow['name'].'</td>';
			$sTableCode .= '<td style="text-align:right;">'.number_format($aRow['price'], 2, ',', '.').' €</td>';

			$sTableCode .= '</tr>';

		}
		
		$sTableCode .= '<tr>';
		$sTableCode .= '<th>Gesamt</th>';
		$sTableCode .= '<th style="text-align:right;">'.number_format($aSum['price'], 2, ',', '.').' €</th>';
		$sTableCode .= '</tr>';

		$sTableCode .= '</tbody>';
	$sTableCode .= '</table>';
 
	$arrTransfer['data'] = array(
		'aYears'		=> $aYears,
		'selectedYear'	=> $iSelectedYear,
		'table'		=> $sTableCode
	);

}

/* ==================================================================================================== */

if($strTask == 'get_contract_data')
{
	$iContractID = (int)$_VARS['contract_id'];

	$oContract = new Ext_Office_Contract($iContractID);

	/* ================================================== */ // Set default values

	$aProducts			= array();
	$aCustomers			= array();
	$iSelectedProduct	= 0;
	$iSelectedCustomer	= 0;
	$iDiscount			= 0;
	$iPrice				= 0;
	$iInterval			= '';
	$iAmount			= 1;
	$sEnd				= '';
	$sText				= '';
	$sStart				= date('d.m.Y');

	/* ================================================== */ // Prepare products

	$sSQL = "SELECT * FROM `office_articles` WHERE `active` = 1 ORDER BY `product`";
	$aTmpProducts = DB::getQueryData($sSQL);

	foreach((array)$aTmpProducts as $iKey => $aValue)
	{
		$aProducts[] = array($aValue['id'], $aValue['product'], $aValue);
	}

	// Prepare editors
	$aTmpEditors		= $objOfficeDao->getEditors();
	$aEditors			= array();
	foreach((array)$aTmpEditors as $sKey => $sValue)
	{
		$aEditors[] = array($sKey, $sValue);
	}
	
	/* ================================================== */ // Prepare customers

	$aTmpCustomers = $objOfficeDao->getCustomers();
	foreach((array)$aTmpCustomers as $iKey => $aValue)
	{
		$aCustomers[] = array($iKey, $aValue['name']);
	}

	/* ================================================== */ // Prepare contact_persons

	$aContacts			= array(array('', '---'));
	$iSelectedContact	= 0;

	/* ================================================== */

	if($iContractID > 0)
	{
		// Get contract contact persons
		$aTmpContacts = $objOfficeDao->getContacts($oContract->customer_id);
		foreach((array)$aTmpContacts as $sKey => $aValue)
		{
			$aContacts[] = array($sKey, $aValue['firstname'].' '.$aValue['lastname']);
		}

		if($oContract->start > 0)
		{
			$sStart = date('d.m.Y', $oContract->start);
		}
		if($oContract->end > 0)
		{
			$sEnd = date('d.m.Y', $oContract->end);
		}
		$iSelectedProduct	= $oContract->product_id;
		$iSelectedCustomer	= $oContract->customer_id;
		$iSelectedContact	= $oContract->contact_id;
		$iSelectedEditor	= $oContract->editor_id;
		$iInterval			= $oContract->interval;
		$iAmount			= $oContract->amount;
		$iDiscount			= $oContract->discount;
		$iPrice				= $oContract->price;
		$sText				= $oContract->text;
	} else {
		$iSelectedEditor	= $user_data['id'];
	}

	/* ================================================== */

	$arrTransfer['data'] = array(
		'id'				=> $iContractID,

		'aProducts'			=> $aProducts,
		'aCustomers'		=> $aCustomers,
		'aContacts'			=> $aContacts,
		'aEditors'			=> $aEditors,

		'selectedProduct'	=> $iSelectedProduct,
		'selectedCustomer'	=> $iSelectedCustomer,
		'selectedContact'	=> $iSelectedContact,
		'selectedEditor'	=> $iSelectedEditor,
		'iInterval'			=> $iInterval,
		'iAmount'			=> $iAmount,
		'iDiscount'			=> $iDiscount,
		'iPrice'			=> $iPrice,

		'sStart'			=> $sStart,
		'sEnd'				=> $sEnd,
		'sText'				=> $sText
	);
}

/* ================================================================================================== */

if($strTask == 'save_contract') {

	$iContractID	= (int)$_VARS['contract_id'];
	$oContract		= new Ext_Office_Contract($iContractID);
	$sError			= '';
	$oDate			= new WDDate();

	// Check the discount
	if(
		!is_numeric($_VARS['discount']) || 
		(float)$_VARS['discount'] < 0 || 
		(float)$_VARS['discount'] > 100
	) {
		$sError = 'discount';
	}

	// Check the amount
	if(!is_numeric($_VARS['amount'])) {
		$sError = 'amount';
	}

	// Check the interval
	if(
		!is_numeric($_VARS['interval']) || 
		(int)$_VARS['interval'] <= 0
	) {
		$sError = 'interval';
	}

	// Check end date
	if(!empty($_VARS['end'])) {
		try {
			
			$dEnd = new DateTime($_VARS['end']);
			$oContract->end = $dEnd->getTimestamp();
			
		} catch(Exception $e) {
			$sError = 'end';
		}
	} else {
		$oContract->end = '00000000000000';
	}

	// Check start date
	try {
		$dStart = new DateTime($_VARS['start']);
		$oContract->start = $dStart->getTimestamp();
	} catch(Exception $e) {
		$sError = 'start';
	}

	if(empty($sError)) {
		$oContract->client_id	= (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');
		$oContract->editor_id	= (int)$_VARS['editor_id'];
		$oContract->customer_id	= (int)$_VARS['customer_id'];
		$oContract->contact_id	= (int)$_VARS['contact_id'];
		$oContract->product_id	= (int)$_VARS['product_id'];
		$oContract->interval	= (int)$_VARS['interval'];
		$oContract->amount		= (float)$_VARS['amount'];
		$oContract->discount	= (float)$_VARS['discount'];
		$oContract->price	= (float)$_VARS['price'];
		$oContract->text		= rawurldecode($objPurifier->purify($_VARS['text']));

		$oContract->save();

		$arrTransfer['data'] = array('id' => $oContract->id);
	} else {
		$arrTransfer['data'] = array('error' => $sError);
	}

}

/* ================================================================================================== */

if($strTask == 'check_contract_toolbar')
{
	$iContractID = (int)$_VARS['contract_id'];

	$oContract = new Ext_Office_Contract($iContractID);

	$arrIcons['toolbar_edit'] = 0;
	$arrIcons['toolbar_delete'] = 0;
	//$arrIcons['toolbar_stats'] = 1;

	if($oContract->id > 0)
	{
		$arrIcons['toolbar_edit'] = 1;
		$arrIcons['toolbar_delete'] = 1;
	}

	$arrTransfer['data'] = $arrIcons;
}

?>