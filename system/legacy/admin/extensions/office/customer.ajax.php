<?php

// Get log entries
if(isset($_VARS['customer_id']))
{
	$aLog['customer_id'] = (int)$_VARS['customer_id'];
}

if($strTask == 'log_phone_click')
{
	__out($_VARS);
}

if($strTask === 'export_customers') {

	set_time_limit(600);
	ini_set("memory_limit", "512M");
	
	$aExport = array(
		array(
			'ID', 'Erstellungszeitpunkt', 'Geschlecht', 'Vorname', 'Nachname', 'E-Mail', 'Kundengruppen', 'Firma', 'Anschrift', 'Adresszusatz', 'Ort', 'PLZ', 'Land'
		)
	);

	$aSpecials = array();
		
	$aContacts = $objOfficeDao->getContacts();
	
	$aCustomers = $objOfficeDao->getCustomers();
	$aCountries = Data_Countries::getList('de');
	$aCustomerGroups = Ext_Office_Customer::getGroups();

	$iRow = 1;
	foreach($aContacts as $aContact) {
		
		$aCustomer = $aCustomers[$aContact['customer_id']];
		$aGroupIds = explode(',', $aCustomer['group_ids']);

		$sCustomerGroups = implode(', ', array_intersect_key($aCustomerGroups, array_flip($aGroupIds)));
		
		$dCreated = new DateTime($aContact['created']);
		
		$aExport[] = array(
			(int)$aContact['id'],
			$dCreated->format('d.m.Y H:i:s'),
			($aContact['sex']==1?'Herr':($aContact['sex']==2?'Frau':'')),
			$aContact['firstname'],
			$aContact['lastname'],
			$aContact['email'],
			$sCustomerGroups,
			$aCustomer['company'],
			$aCustomer['address'],
			$aCustomer['addition'],
			$aCustomer['city'],
			$aCustomer['zip'],
			$aCountries[$aCustomer['country']]
		);

		$aSpecials['cell_format'][$iRow][10]['format'] = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$aSpecials['cell_format'][$iRow][10]['style'] = '0000';

		$iRow++;
	}

	WDExport::exportXLSX('Kunden', $aExport, $aSpecials);
	
	die();
	
} elseif(
	$strTask == 'get_customer' || 
	$strTask == 'get_customer_overview'
) {

	DB::setResultType(MYSQL_ASSOC);

	// Get customer ID
	$iCustomerID = (int)$_VARS['customer_id'];

	$oCustomer = new Ext_Office_Customer(null, $iCustomerID);

	// Set temporary id for new documents
	if(!isset($_SESSION['customers_counter']) && $iCustomerID == 0)
	{
		$_SESSION['customers_counter'] = -1;
		$iCustomerID = $_SESSION['customers_counter'];
		unset($_SESSION['customer_'.$iCustomerID]['customers_counter']);
	}
	else if($iCustomerID == 0)
	{
		$iCustomerID = --$_SESSION['customers_counter'];
	}

	// Prepare customer data
	$arrCustomer = array(
		'id'		=> $iCustomerID,
		'number'	=> '',
		'matchcode'	=> '',
		'company'	=> '',
		'phone'		=> '',
		'fax'		=> '',
		'email'		=> '',
		'addition'	=> '',
		'address'	=> '',
		'zip'		=> '',
		'city'		=> '',
		'country'	=> ''
	);

	// Prepare customer payments
	$aTmpPayments	= $objOfficeDao->getCustomerPayments($iCustomerID);
	$aPayments		= array();
	foreach((array)$aTmpPayments['aPaymentTerms'] as $iKey => $aValue)
	{
		if($aValue['type_flag'] == 2)
		{
			$aPayments['invoices'][] = array($aValue['id'], $aValue['title']);
		}
		else if($aValue['type_flag'] == 0)
		{
			$aPayments['invoices'][] = array($aValue['id'], $aValue['title']);
			$aPayments['misc'][] = array($aValue['id'], $aValue['title']);
		}
		else
		{
			$aPayments['misc'][] = array($aValue['id'], $aValue['title']);
		}
	}
	$aPayments['selectedPaymentInvoice']	= $aTmpPayments['selectedPaymentInvoice'];
	$aPayments['selectedPaymentMisc']		= $aTmpPayments['selectedPaymentMisc'];

	// Prepare customer additional settings / data
	//$aAdditionals = $objOfficeDao->getCustomerAdditionals($iCustomerID);
	
	$aAdditionals = DB::getRowData('office_customers', $iCustomerID);

	$aKeys = array('customer_id'=>(int)$iCustomerID);
	$aCustomerGroups = DB::getJoinData('office_customers_groups_join', $aKeys, 'group_id');

	$aAdditionals = array(
		'cms_contact'	=> (int)$aAdditionals['cms_contact'],
		'by_email'		=> (int)$aAdditionals['by_email'],
		'debitor_nr'	=> (string)$aAdditionals['debitor_nr'],
		'creditor_nr'	=> (string)$aAdditionals['creditor_nr'],
		'vat_id_nr'		=> (string)$aAdditionals['vat_id_nr'],
		'vat_id_valid'	=> (string)$aAdditionals['vat_id_valid'],
		'client_id'		=> (int)$aAdditionals['client_id'],
		'language'		=> (string)$aAdditionals['language'],
		'group_id'		=> (array)$aCustomerGroups,
		'redmine_project_id' => (string)$aAdditionals['redmine_project_id'],
	);

	$aCMS_User = $objOfficeDao->getPreparedCMSUserList();

	// Customer already exists
	if($iCustomerID > 0)
	{
		$arrCustomer = $objOfficeDao->getCustomer($iCustomerID);
		$arrCustomer['phone_link'] = $objOffice->getPhoneLink($arrCustomer['phone']);
	}

	// Protect the customer number if the number is the ID
	if($arrConfigData['field_number'] == 'id') {
		$arrCustomer['bLockID'] = true;
	} else {
		$arrCustomer['bLockID'] = false;
	}

	$aItems = Ext_Office_Customer::getGroups(\Core\Handler\SessionHandler::getInstance()->get('office_client_id'));
	$aGroups = array();
	foreach((array)$aItems as $iKey=>$sValue) {
		$aGroups[] = array($iKey, $sValue);
	}

	DB::setResultType(MYSQL_NUM);

	$sSql = "SELECT `cn_iso_2`, `cn_short_de` FROM `data_countries` ORDER BY `cn_short_de`";
	$aCountries = DB::getQueryRows($sSql);
	$aCountries = array_merge(array(0=>array('','')), $aCountries);

	$aLanguages = System::getBackendLanguages();
	
	$aLanguages = array_merge(array(0=>array('','')), $aLanguages);
	
	DB::setResultType(MYSQL_ASSOC);

	if(strlen($arrCustomer['country']) != 2) {
		
		$sSql = "
			SELECT 
				`cn_iso_2`
			FROM 
				`data_countries` 
			WHERE
				`cn_short_de` LIKE :country OR
				`cn_short_en` LIKE :country
			ORDER BY 
				`cn_short_de`";
		$aSql = array(
			'country'=>$arrCustomer['country']
		);
		$arrCustomer['country'] = DB::getQueryOne($sSql, $aSql);

	}

	$arrTransfer['data'] = $arrCustomer;
	$arrTransfer['data']['aCustomerPayments']	= $aPayments;
	$arrTransfer['data']['aAdditionals']		= $aAdditionals;
	$arrTransfer['data']['aCMS_User']			= $aCMS_User;
	$arrTransfer['data']['languages']			= $aLanguages;
	$arrTransfer['data']['countries']			= $aCountries;
	$arrTransfer['data']['groups']				= (array)$aGroups;
	$arrTransfer['data']['full_address']		= nl2br($oCustomer->getAddress());

	$oLogoService = new Office\Service\LogoService();
	$arrTransfer['data']['logo']				= $oLogoService->getWebPath($oCustomer);

} elseif($strTask == 'get_customers') {

	$strSearch = $objPurifier->purify($_VARS['search']);
	$iClientID = (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');
	if($iClientID == 0)
	{
		$iClientID = 1;
	}

	$_SESSION['office']['group_id'] = $_VARS['group_id'];

	$arrTransfer = array();

	$aData = $objOfficeDao->getCustomers($strSearch, $iClientID, (int)$_VARS['group_id']);
	
	$aCountries = Data_Countries::getList(System::getInterfaceLanguage());

	$aCustomers = array();
	foreach((array)$aData as $intKey=>$aCustomer) {
		$aCustomer['phone_link']	= $objOffice->getPhoneLink($aCustomer['phone']);
		$aCustomer['contacts']		= $objOfficeDao->getContacts($aCustomer['id']);
		$aCustomer['country_name']	= (string)$aCountries[$aCustomer['country']];
		$aCustomer['vat_id_nr']		= (string)$aCustomer['vat_id_nr'];
		$aCustomers[] = $aCustomer;
	}

	$arrTransfer['data'] = $aCustomers;
}
elseif($strTask == 'save_customer_data') {

	// Get customer ID
	$iCustomerID = (int)$_VARS['customer_id'];

	// get client_id
	$iClientID = (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');

	// Create customer data array
	$aCustomerData = array(
		'id'		=> $iCustomerID,
		'email'		=> trim(rawurldecode($objPurifier->purify($_VARS['email']))),
		$arrConfigData['field_matchcode']		=> rawurldecode($_VARS['matchcode']),
		$arrConfigData['field_company']			=> rawurldecode($_VARS['company']),
		$arrConfigData['field_phone']			=> rawurldecode($objPurifier->purify($_VARS['phone'])),
		$arrConfigData['field_fax']				=> rawurldecode($objPurifier->purify($_VARS['fax'])),
		$arrConfigData['field_addition']		=> rawurldecode($objPurifier->purify($_VARS['addition'])),
		$arrConfigData['field_address']			=> rawurldecode($objPurifier->purify($_VARS['address'])),
		$arrConfigData['field_zip']				=> rawurldecode($objPurifier->purify($_VARS['zip'])),
		$arrConfigData['field_city']			=> rawurldecode($objPurifier->purify($_VARS['city'])),
		$arrConfigData['field_country']			=> rawurldecode($objPurifier->purify($_VARS['country'])),
		$arrConfigData['field_number']			=> rawurldecode($objPurifier->purify($_VARS['number'])),
		'payment_invoice'	=> (int)$_VARS['payment_invoice'],
		'payment_misc'		=> (int)$_VARS['payment_misc']
	);

	$aAdditionals = array(
		'cms_contact'	=> (int)$_VARS['cms_contact'],
		'by_email'		=> (int)$_VARS['by_email'],
		'debitor_nr'	=> rawurldecode($objPurifier->purify($_VARS['debitor_nr'])),
		'creditor_nr'	=> rawurldecode($objPurifier->purify($_VARS['creditor_nr'])),
		'vat_id_nr'		=> rawurldecode($objPurifier->purify($_VARS['vat_id_nr'])),
		'vat_id_valid'	=> 0,
		'language'		=> (string)$_VARS['language'],
		'group_id'		=> (array)$_VARS['group_id'],
		'client_id'		=> (int)$iClientID,
	);

	if(!empty($_VARS['redmine_project_id'])) {
		$aAdditionals['redmine_project_id'] = (string)$_VARS['redmine_project_id'];
	}
	
	// Wenn der Kunde ein neuer Kunde ist
	if($iCustomerID <= 0) {
		unset($aCustomerData['id']);
	}

	// Protect the customer number if the number is the ID
	if($arrConfigData['field_number'] == 'id') {
		unset($aCustomerData['number']);
	}

	if(!empty($aAdditionals['vat_id_nr'])) {

		$aAdditionals['vat_id_nr'] = str_replace(' ', '', $aAdditionals['vat_id_nr']);
		$sCountry = strtoupper(substr($aAdditionals['vat_id_nr'], 0, 2));
		$sVatNumber = substr($aAdditionals['vat_id_nr'], 2);

		$bCheck1 = preg_match("/^[A-Z]{2}$/", $sCountry);
		$bCheck2 = preg_match("/^[0-9A-Za-z\+\*\.]{2,12}$/", $sVatNumber);

		if($bCheck1 && $bCheck2) {

			$oVatHelper = new Office\Helper\VatHelper();

			try {			

				$mResponse = $oVatHelper->check(
					$sCountry, 
					$sVatNumber, 
					$aCustomerData[$arrConfigData['field_company']],
					$aCustomerData[$arrConfigData['field_city']],
					$aCustomerData[$arrConfigData['field_zip']],
					$aCustomerData[$arrConfigData['field_address']]
				);

				if($mResponse === true) {
					$arrTransfer['data']['check_vat'] = 1;
					$aAdditionals['vat_id_valid'] = 1;
				} else {
					$arrTransfer['data']['save_error'] = 'Bei der Prüfung der USt.-ID ist ein Fehler aufgetreten';
				}

				$arrTransfer['data']['vat_id_errors'] = $oVatHelper->getLastErrors();
				
				$aAdditionals['vat_id_check'] = json_encode($oVatHelper->getLastResponse());

			} catch(Exception $e) {
				
				if(System::d('debugmode')) {
					__out($e);
				}

				$arrTransfer['data']['save_error'] = 'VATID_CHECK_FAILED';
				$arrTransfer['data']['save_error_message'] = 'The VAT number check failed.';
				
				wdmail("office_vat_id@p32.de", "Office - VAT ID Check", print_r($e,1)."\n\n".print_r($_VARS,1)."\n\n".print_r($arrTransfer,1)."\n\n".print_r($oResponse,1)."\n\n".print_r($_SERVER,1));
			}

		} else {
			$arrTransfer['data']['save_error'] = 'VATID_WRONG_FORMAT';
		}

	}

	if(empty($arrTransfer['data']['save_error'])) {

		// Save the customer
		$iCustomerID = $objOfficeDao->saveCustomerData($aCustomerData, $aAdditionals);

		// Bild speichern
		if($iCustomerID){
			$oLogoService = new Office\Service\LogoService();

			$oOffice = new Ext_Office_Customer('office_customers', $iCustomerID);

			if($_VARS['delete_logo'] === "true"){
				$oLogoService->delete($oOffice);
			}

			$oLogoService->save($oOffice, $_FILES['logo']);
			$arrTransfer['data']['logo'] = $oLogoService->getWebPath($oOffice);
		}

		$arrTransfer['data']['customer_id'] = $iCustomerID;
	}

}
elseif($strTask == 'delete_customer')
{
	// Get customer ID
	$iCustomerId = (int)$_VARS['customer_id'];

	// Alle Standorte eines Kunden suchen
	$oLocationRepository = Office\Entity\Customer\Location::getRepository();
	$aCriteria = array('customer_id' => $iCustomerId);
	$aLocations = $oLocationRepository->findBy($aCriteria);

	// Standorte, die zu diesem Kunden gehören löschen (auf inaktiv setzen).
	foreach ($aLocations as $oLocation) {
		$oLocation->delete();
	}

	// TODO : DELETE ALL PROTOCOLS !!!

	// Delete customer
	$objOfficeDao->removeCustomer($iCustomerId);
}
elseif($strTask == 'get_contact_persons')
{
	// Get customer ID
	$iCustomerID = (int)$_VARS['customer_id'];

	// Get contact persons
	$aTmpContactPersons = $objOfficeDao->getContacts($iCustomerID);

	// Prepare contact persons (for javascript)
	$aContactPersons = array();
	foreach((array)$aTmpContactPersons as $iKey => $aValue)
	{
		$aValue['phone_link'] = $objOffice->getPhoneLink($aValue['phone']);
		$aValue['mobile_link'] = $objOffice->getPhoneLink($aValue['mobile']);
		$aContactPersons[] = $aValue;
	}

	$arrTransfer['data']['aContactPersons'] = $aContactPersons;
}
elseif($strTask == 'save_contact_person')
{
	$sSQL = "SELECT `id` FROM `office_contacts` WHERE `nickname` = :sNickname AND `nickname` != '' AND `id` != :iCC_ID LIMIT 1";
	$aSQL = array(
		'sNickname'	=> rawurldecode($objPurifier->purify($_VARS['nickname'])),
		'iCC_ID'	=> (int)$_VARS['contact_person_id']
	);
	$aCheckData = DB::getPreparedQueryData($sSQL, $aSQL);

	// Prepare the contact person array
	$aContactPerson = array(
		'customer_id'	=> (int)$_VARS['customer_id'],
		'id'			=> (int)$_VARS['contact_person_id'],
		'sex'			=> (int)$_VARS['sex'],
		'firstname'		=> rawurldecode($objPurifier->purify($_VARS['firstname'])),
		'lastname'		=> rawurldecode($objPurifier->purify($_VARS['lastname'])),
		'email'			=> trim(rawurldecode($objPurifier->purify($_VARS['email']))),
		'phone'			=> rawurldecode($objPurifier->purify($_VARS['phone'])),
		'mobile'		=> rawurldecode($objPurifier->purify($_VARS['mobile'])),
		'fax'			=> rawurldecode($objPurifier->purify($_VARS['fax'])),
		'description'	=> rawurldecode($objPurifier->purify($_VARS['description']))
	);

	$aContactPerson['invoice_contact'] = (isset($_VARS['invoice_contact']) && $_VARS['invoice_contact'] == 'on') ? 1 : 0;
	$aContactPerson['invoice_recipient'] = (isset($_VARS['invoice_recipient']) && $_VARS['invoice_recipient'] == 'on') ? 1 : 0;
	
	if
	(
		rawurldecode($objPurifier->purify($_VARS['password'])) != ''
			&&
		rawurldecode($objPurifier->purify($_VARS['password'])) == rawurldecode($objPurifier->purify($_VARS['password_c']))
	)
	{
		$aContactPerson['password'] = md5(rawurldecode($objPurifier->purify($_VARS['password'])));
	}

	if(!empty($aCheckData))
	{
		$arrTransfer['data']['save_error'] = 'NICKNAME_NOT_UNIQUE';
	}
	else
	{
		$aContactPerson['nickname'] = rawurldecode($objPurifier->purify($_VARS['nickname']));
	}

	// Save or update the contact person data
	$objOfficeDao->saveContactPerson($aContactPerson);

	$arrTransfer['data']['customer_id'] = (int)$_VARS['customer_id'];
}
elseif($strTask == 'delete_contact_person')
{
	$objOfficeDao->removeContactPerson((int)$_VARS['contact_person_id'], (int)$_VARS['customer_id']);

	$arrTransfer['data']['customer_id'] = (int)$_VARS['customer_id'];
}
elseif($strTask == 'get_protocols')
{
	$aLog['customer_id'] = (int)$_VARS['customer_id'];

	$objOfficeDao->manageProtocols($aLog);
	$arrTransfer['data']['aProtocols'] = $objOfficeDao->getProtocolsList((int)$_VARS['customer_id']);
	
} elseif($strTask == 'add_protocol') {
	
	$aLog = array(
		'id'			=> 0,
		'customer_id'	=> (int)$_VARS['customer_id'],
		'contact_id'	=> (int)$_VARS['contact_id'],
		'editor_id'		=> (int)$_VARS['editor_id'],
		'document_id'	=> 0,
		'topic'			=> rawurldecode($objPurifier->purify($_VARS['topic'])),
		'subject'		=> rawurldecode($objPurifier->purify($_VARS['subject'])),
		'state'			=> ''
	);

	$arrTransfer['data']['customer_id'] = (int)$_VARS['customer_id'];
	
} elseif($strTask == 'get_protocols_data') {
	
	// Prepare contacts
	$aTmpContacts		= $objOfficeDao->getContacts((int)$_VARS['customer_id']);
	$aContacts			= array(array('', '---', array()));
	$iSelectedContact	= '';
	foreach((array)$aTmpContacts as $sKey => $aValue) {
		$aContacts[] = array($sKey, $aValue['firstname'].' '.$aValue['lastname'], $aValue);
	}
	
	if(count($aContacts) == 2) {
		$iSelectedContact = $aContacts[1][0];
	}

	$arrTransfer['data']['aProContacts'] = $aContacts;
	$arrTransfer['data']['iProSelectedContacts'] = $iSelectedContact;

	// Prepare editors
	$aTmpEditors		= $objOfficeDao->getEditors();
	$aEditors			= array();
	$iSelectedEditor	= $user_data['id'];
	foreach((array)$aTmpEditors as $sKey => $sValue)
	{
		$aEditors[] = array($sKey, $sValue);
	}
	$arrTransfer['data']['aProEditors'] = $aEditors;
	$arrTransfer['data']['iProSelectedEditors'] = $iSelectedEditor;

	// Prepare activities
	$aNewActivities = array();
	foreach((array)$aActivities as $iKey => $aValue)
	{
		if(substr($iKey, 0, 4) == 'add_')
		{
			$aNewActivities[] = array($iKey, $aValue);
		}
	}
	$arrTransfer['data']['aActivities'] = $aNewActivities;

	$arrTransfer['data']['customer_id'] = (int)$_VARS['customer_id'];
}
elseif($strTask == 'get_billings')
{
	$oCustomer = new Ext_Office_Customer();

	$aBillings = $oCustomer->getBillings((int)$_VARS['customer_id']);

	$arrTransfer['data'] = $aBillings;
}
elseif($strTask == 'generate_billing_account')
{
	$oCustomer = new Ext_Office_Customer();

	$aResult = $oCustomer->generateBillingsAccount((int)$_VARS['customer_id'], $_VARS['save']);

	$arrTransfer['data'] = $aResult;
}
elseif ($strTask === 'get_locations') {
	// hole customer id
	$iCustomerId = (int) $_VARS['customer_id'];

	// Alle Standorte eines Kunden suchen
	$oLocationRepository = Office\Entity\Customer\Location::getRepository();
	$aCriteria = array('customer_id' => $iCustomerId);
	$aLocations = $oLocationRepository->findBy($aCriteria);

	$aLocationTransferData = array();
	foreach($aLocations as $oLocation){
		$aLocationData = $oLocation->getData();
		// Erstelle ein Kundengruppenobjekt, unm an den namen zu kommen
		$oCustomerGroup = Ext_Office_Customer_Group::getInstance($oLocation->customer_group_id);
		// Füge den Namen dem Array hinzu, damit der Name bei der tabelarischen
		// Ansicht aller Standorte angeziegt werden kann (statt nur der id/'customer_grou_id')
		$aLocationData['customer_group_name'] = $oCustomerGroup->name;
		$aLocationTransferData[] = $aLocationData;
	}

	$arrTransfer['data']['aLocations'] = $aLocationTransferData;
}
elseif ($strTask === 'get_location') {
	// hole alle Länder
	DB::setResultType(MYSQL_NUM);
	$sSql = "SELECT `cn_iso_2`, `cn_short_de` FROM `data_countries` ORDER BY `cn_short_de`";
	$aCountries = DB::getQueryRows($sSql);
	DB::setResultType(MYSQL_ASSOC);


	// Standort Id
	$iLocationId = (int) $_VARS['location_id'];
	// Hole den Standort aus der Datenbank
	$oLocation = Office\Entity\Customer\Location::getInstance($iLocationId);

	// Die Kundennummer des Standortes
	$iCustomerId = (int) $_VARS['customer_id'];
	// Alle Kundengruppen holen
	$aCustomerGroups = $oLocation->getCustomerGroups($iCustomerId);

	// Instanziiere ein neues Logoservice-Objekt
	$oLogoService = new Office\Service\LogoService;

	// Bildpfad holen
	$aLocationData = $oLocation->getData();
	$sLogoWebPath = $oLogoService->getWebPath($oLocation);

	$aLocationData['logo'] = $sLogoWebPath;
	$arrTransfer['data']['aLocation'] = $aLocationData;
	$arrTransfer['data']['aCountries'] = $aCountries;
	$arrTransfer['data']['aCustomerGroups'] = $aCustomerGroups;

}
elseif($strTask === 'save_location'){

	$iLocationId = (int) $_VARS['location_id'];
	if (!isset($iLocationId) || $iLocationId <= 0) {
		$oLocation = new \Office\Entity\Customer\Location();
	} else {
		$oLocation = \Office\Entity\Customer\Location::getInstance($iLocationId);
	}

	if ($_VARS['visible'] !== null) {
		$oLocation->visible = 1;
	} else {
		$oLocation->visible = 0;
	}

	if ($_VARS['anonymous'] !== null) {
		$oLocation->anonymous = 1;
	} else {
		$oLocation->anonymous = 0;
	}

	$oLocation->customer_id = $_VARS['customer_id'];
	$oLocation->name = $_VARS['name'];
	$oLocation->address = $_VARS['address'];
	$oLocation->addition = $_VARS['addition'];
	$oLocation->zip = $_VARS['zip'];
	$oLocation->city = $_VARS['city'];
	$oLocation->country = $_VARS['country'];
	$oLocation->customer_group_id = $_VARS['customer_group_id'];

	$mValide = $oLocation->validate();

	// Wenn das Formular nicht valide ist
	if($mValide !== true){

		$aErrors = array();
		foreach ($mValide as $sColumnName => $aColumnValidationErrors){
			$aColumnName = explode('.', $sColumnName);
			$sRawColumnName = array_pop($aColumnName);

			$aErrors[] = $sRawColumnName;
		}

		$arrTransfer['data']['save_error'] = $aErrors;
	} else { 
		// Wenn das Formular valide ist, dann speichern usw.

		$oLocation->save();

		// Wenn das Logo gelöscht werden soll
		if($_VARS['delete_logo'] === "true"){
			$oLogoService = new Office\Service\LogoService;
			$oLogoService->delete($oLocation);
		}

		$oLogoService = new Office\Service\LogoService;

		// Speichere das Logo
		$oLogoService->save($oLocation, $_FILES['logo']);
		$sLogoWebPath = $oLogoService->getWebPath($oLocation);

		$arrTransfer['data']['logo'] = $sLogoWebPath;
	}

	$arrTransfer['data']['location_id'] = $oLocation->id;
	$arrTransfer['data']['customer_id'] = $oLocation->customer_id;
}
elseif($strTask === 'delete_location'){
	$iLocationId = (int)$_VARS['location_id'];
	$oLocation = \Office\Entity\Customer\Location::getInstance($iLocationId);

	$arrTransfer['data']['customer_id'] = $oLocation->customer_id;

	$oLocation->delete();
}
elseif ($strTask === 'get_comments') {
	// hole customer id
	$iCustomerId = (int) $_VARS['customer_id'];

	// Alle Standorte eines Kunden suchen
	$oCommentRepository = Office\Entity\Customer\Comment::getRepository();
	$aComments = (array)$oCommentRepository->findByCustomerOrdered($iCustomerId, true);

	$aCommentTransferData = array();
	foreach($aComments as $oComment){
		$aCommentData = $oComment->getData();
		// Erstelle ein Kundengruppenobjekt, um an den namen zu kommen
		$oCustomerGroup = Ext_Office_Customer_Group::getInstance($oComment->customer_group_id);
		// Füge den Namen dem Array hinzu, damit der Name bei der tabelarischen
		// Ansicht aller Standorte angeziegt werden kann (statt nur der id/'customer_grou_id')
		$aCommentData['customer_group_name'] = $oCustomerGroup->name;
		$aCommentTransferData[] = $aCommentData;
	}

	$arrTransfer['data']['aComments'] = $aCommentTransferData;

}
elseif ($strTask === 'get_comment') {

	// Kommentar Id
	$iCommentId = (int) $_VARS['comment_id'];
	// Hole den Kommentar aus der Datenbank
	$oComment = Office\Entity\Customer\Comment::getInstance($iCommentId);

	// Die Kundennummer des Kommentars
	$iCustomerId = (int) $_VARS['customer_id'];
	// Alle Kundengruppen holen
	$aCustomerGroups = $oComment->getCustomerGroups($iCustomerId);

	$aCommentData = $oComment->getData();
	// Wenn neu, dann standardwerte setzen und position auf max
	if ($aCommentData['created'] === null) {
		$aCommentData = Array();
		$aCommentData['id'] = '';
		$aCommentData['visible'] = '';
		$aCommentData['box'] = '';
		$aCommentData['text'] = '';
		$aCommentData['position'] = Office\Entity\Customer\Comment::getRepository()->getMaxPosition() + 1;
		$aCommentData['customer_group_id'] = '';
	}

	$arrTransfer['data']['aComment'] = $aCommentData;
	$arrTransfer['data']['aCustomerGroups'] = $aCustomerGroups;

}
elseif($strTask === 'save_comment'){
	$iCommentId = (int) $_VARS['comment_id'];

	$bIsNew = !isset($iCommentId) || $iCommentId <= 0;
	
	if ($bIsNew) {
		$oComment = new \Office\Entity\Customer\Comment();
	} else {
		$oComment = \Office\Entity\Customer\Comment::getInstance($iCommentId);
	}

	if ($_VARS['visible'] !== null) {
		$oComment->visible = 1;
	} else {
		$oComment->visible = 0;
	}

	if ($_VARS['box'] !== null) {
		$oComment->box = 1;
	} else {
		$oComment->box = 0;
	}

	$oComment->customer_id = $_VARS['customer_id'];
	$oComment->text = $_VARS['text'];
	$sOldPosition = $oComment->position;
	$oComment->position = $_VARS['position'];
	$oComment->customer_group_id = $_VARS['customer_group_id'];

	$mValide = $oComment->validate();

	// Wenn das Formular nicht valide ist
	if($mValide !== true){

		$aErrors = array();
		foreach ($mValide as $sColumnName => $aColumnValidationErrors){
			$aColumnName = explode('.', $sColumnName);
			$sRawColumnName = array_pop($aColumnName);

			$aErrors[] = $sRawColumnName;
		}

		$arrTransfer['data']['save_error'] = $aErrors;
	} else {
		// Wenn das Formular valide ist, dann speichern
		
		$oCommentRepository = Office\Entity\Customer\Comment::getRepository();
		//prüfe ob position schon vorhanden, wenn ja, dann erhöhe alle anderen
		$oCommentWithSamePosition = $oCommentRepository->findOneBy(array("position" => $oComment->position));

		// Wenn Neuer Eintrag
		if($bIsNew){
			// Wenn Position schon belegt, dann erhöre die folgenden Positionen
			if(!empty($oCommentWithSamePosition)){
				$oCommentRepository->increasePositions($oComment->position);
			}
		} else {
			// Wenn Position schon belegt, dann erhöhe oder verkleinere die anderen
			if(!empty($oCommentWithSamePosition)){
				if($oCommentWithSamePosition->position > $sOldPosition){
					$oCommentRepository->decreasePositionsBetween($sOldPosition, $oComment->position);
				} else if($oCommentWithSamePosition->position < $sOldPosition){
					$oCommentRepository->increasePositionsBetween($oComment->position, $sOldPosition);
				}
			}
		}
		$oComment->save();
	}

	$arrTransfer['data']['comment_id'] = $oComment->id;
	$arrTransfer['data']['customer_id'] = $oComment->customer_id;
}
elseif($strTask === 'delete_comment'){
	$iCommentId = (int)$_VARS['comment_id'];
	$oComment = \Office\Entity\Customer\Comment::getInstance($iCommentId);

	$arrTransfer['data']['customer_id'] = $oComment->customer_id;

	// Vor dem Löschen die anderen Positionen um eins verrignern
	$oCommentRepository = Office\Entity\Customer\Comment::getRepository();
	$oCommentRepository->decreasePositions($oComment->position);

	$oComment->delete();
}

