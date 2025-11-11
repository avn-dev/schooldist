<?php
// v2
require_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
require_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

Access_Backend::checkAccess("office");

DB::setResultType(MYSQL_ASSOC);

$arrConfigData = $objOffice->getConfigData();

$objPurifier = new Core\Service\HtmlPurifier();

$strTask = $objPurifier->purify($_VARS['task']);
  
// Initialize logging array
$aLog = array();

if($strTask == 'getTemplateTexts') {

	$aTexts = $objOfficeDao->getTemplateTexts($objPurifier->purify($_VARS['template_id']));

	$arrTransfer['data']['tpl']['text'] = $aTexts['text'];
	$arrTransfer['data']['tpl']['field'] = $objPurifier->purify($_VARS['field']);

}  
elseif(
	$strTask == 'get_documents' ||
	$strTask == 'delete_document'
) {

	if($strTask == 'delete_document') {
		$iDocumentId = (int)$_VARS['delete_id'];
		$objOfficeDao->deleteDocument($iDocumentId);
	}

	$sType		= $objPurifier->purify($_VARS['type']);
	$sState		= $objPurifier->purify($_VARS['state']);
	$sSearch	= $objPurifier->purify($_VARS['search']);
	$iProductAreaId = (int)($_VARS['product_area_id'] ?? 0);
	if(isset($_VARS['from'])) {
		$sFrom	= $objPurifier->purify($_VARS['from']);
	}
	if(isset($_VARS['to'])) {
		$sTo	= $objPurifier->purify($_VARS['to']);
	}
	$sOrder = null;
	if(!empty($_VARS['order'])) {
		$sOrder = $objPurifier->purify($_VARS['order']);
	}

	$arrDocuments = $objOfficeDao->getDocuments((string)$sType, (string)$sState, (string)$sSearch, (string)$sFrom, (string)$sTo, $iProductAreaId, $sOrder);

	$arrTransfer['data'] = $arrDocuments;
	if(!empty($_VARS['id'])) {
		$arrTransfer['id'] = array_filter($_VARS['id'], function($iId) {  if(!is_numeric($iId)) { return false;} else { return true;} } );
	}
	$arrTransfer['order'] = (string)($_SESSION['office']['documents']['orderby'] ?? '');

} elseif($strTask == 'changeState') {

	$iDocumentID	= $objPurifier->purify($_VARS['document_id']);
	$sState			= $objPurifier->purify($_VARS['state']);

	$objDocument = new Ext_Office_Document($iDocumentID);

	if($sState == 'released') {
		$objDocument->release();
	} else {
		$objDocument->state = $sState;
		$objDocument->save();
	}

	// Generate PDF
	$objDocument = new Ext_Office_Document($iDocumentID);
	$objDocument->createFile();

} elseif($strTask == 'prepare_email') {
	
	$iDocumentID	= $objPurifier->purify($_VARS['document_id']);
	$iFormID		= $objPurifier->purify($_VARS['form_id']);

	$oDocument = new Ext_Office_Document($iDocumentID);
	
	$aDocument	= $objOfficeDao->getDocumentData($iDocumentID);
	$aContact	= $objOfficeDao->getContact($aDocument['contact_person_id']);
	$aCustomer	= $objOfficeDao->getCustomer($aDocument['customer_id']);
	$aCustomerAdd = $objOfficeDao->getCustomerAdditionals($aDocument['customer_id']);
	$aEditor	= $objOfficeDao->getEditor($aDocument['editor_id']);

	$oCustomer = new Ext_Office_Customer(null, $aDocument['customer_id']);

	$aEmails = [];
	
	if(empty($aContact['email'])) {
		$aEmails[] = $aCustomer['email'];
	} else {
		$aEmails[] = $aContact['email'];
	}

	$aContacts = $objOfficeDao->getContacts($aDocument['customer_id']);
	
	foreach($aContacts as $aContact) {
		if(
			$aContact['invoice_recipient'] == 1 &&
			!empty($aContact['email']) &&
			!in_array($aContact['email'], $aEmails)
		) {
			$aEmails[] = $aContact['email'];
		}
	}
	
	$arrTransfer['email'] = implode(',', $aEmails);
	
	if(empty($aContact['fax'])) {
		$arrTransfer['fax'] = $aCustomer['fax'];
	} else {
		$arrTransfer['fax'] = $aContact['fax'];
	}

	$arrTransfer['document_id']	= $iDocumentID;
	$arrTransfer['form_id'] 	= $iFormID;

	$arrTransfer['by_email'] 	= (int)$aCustomerAdd['by_email'];

	$aEmailVariables = array(
		$aDocument['number'],
		$aDocument['price'],
		strftime('%x', $aDocument['date']),
		$aEditor['firstname'].' '.$aEditor['lastname'],
		$aEditor['email'],
		$aEditor['phone'],
		$aContact['firstname'].' '.$aContact['lastname'],
		$aContact['email'],
		$aContact['phone'],
		$aCustomer['company'],
		$aCustomer['number'],
	);

	$aEmailTemplate = $objOffice->getEmailTemplate($aDocument['type'], $oCustomer->language);

	$arrTransfer['subject'] = $oDocument->replacePlaceholders($aEmailTemplate['subject']);
	$arrTransfer['body'] = $oDocument->replacePlaceholders($aEmailTemplate['body']);

} elseif($strTask == 'payments') {

	$iDocumentID = $objPurifier->purify($_VARS['document_id']);
	$floAmount = $objPurifier->purify($_VARS['amount']);
	$sComment = $objPurifier->purify($_VARS['comment']);
	$bSendMail = false;
	$bGrantCashDiscount = false;

	if($_VARS['action'] == 'sendMail') {
		$bSendMail = true;
	}

	if($_VARS['grant_cash_discount'] == '1') {
		$bGrantCashDiscount = true;
	}

	$objOfficeDao->savePayment($iDocumentID, $floAmount, $user_data['id'], $bSendMail, $sComment, $bGrantCashDiscount);

} elseif($strTask == 'check_customer_toolbar') {

	$intCustomerId = (int)$_VARS['customer_id'];

	$arrCustomer = $objOfficeDao->getCustomer($intCustomerId);

	$arrIcons['toolbar_edit'] = 1;
	$arrIcons['toolbar_delete'] = 1;
	$arrIcons['toolbar_billing'] = 1;
	$arrIcons['toolbar_new_protocol'] = 1;
	$arrIcons['toolbar_export'] = 1;

	$arrTransfer['data'] = $arrIcons;

} elseif($strTask == 'check_toolbar') {

	if(
		isset($_VARS['document_id']) &&
		!is_array($_VARS['document_id'])
	) {
		$_VARS['document_id'] = (array)$_VARS['document_id'];
	}

	$arrIcons['toolbar_pdf_export'] = 0;
	$arrIcons['toolbar_pdf_print'] = 0;
	$arrIcons['toolbar_copy'] = 0;
	$arrIcons['export_list_pdf'] = 0;
	$arrIcons['export_list_xlsx'] = 0;

	$arrIcons['toolbar_pdf_email'] = 0;
	$arrIcons['toolbar_payments'] = 0;
	$arrIcons['toolbar_ticket'] = 0;
	$arrIcons['toolbar_edit'] = 0;
	$arrIcons['toolbar_delete'] = 0;
	$arrIcons['toolbar_accept'] = 0;
	$arrIcons['toolbar_decline'] = 0;
	$arrIcons['toolbar_finish'] = 0;
	$arrIcons['toolbar_release'] = 0;
	$arrIcons['toolbar_checklist'] = 0;

	if(is_array($_VARS['document_id']) && count($_VARS['document_id']) == 1) {

		$arrIcons['toolbar_pdf_export'] = 1;
		$arrIcons['toolbar_pdf_print'] = 1;
		$arrIcons['toolbar_copy'] = 1;
		$arrIcons['export_list_pdf'] = 1;
		$arrIcons['export_list_xlsx'] = 1;

		$intDocumentId = reset($_VARS['document_id']);

		$arrDocument = $objOfficeDao->getDocumentData($intDocumentId);

		if(
			$arrDocument['type'] != 'offer' &&
			$arrDocument['type'] != 'confirmation' &&
			$arrDocument['type'] != 'account' &&
			$arrDocument['type'] != 'contract' &&
			$arrDocument['type'] != 'reminder' &&
			$arrDocument['type'] != 'letter' &&
			$arrDocument['type'] != 'fax'
		) {
			$arrIcons['toolbar_copy'] = 0;
		}

		if($intDocumentId > 0) {
			$arrIcons['toolbar_ticket'] = 1;
		}

		if(
			$arrDocument['state'] == 'accepted' && 
			$arrDocument['type'] == 'offer'
		) {
			$arrIcons['toolbar_finish'] = 1;
			$arrIcons['toolbar_checklist'] = 1;
		}

		if($arrDocument['state'] != 'draft') {
			$arrIcons['toolbar_pdf_email'] = 1;
		}

		if(
			$arrDocument['state'] != 'accepted' && 
			$arrDocument['state'] != 'paid' && 
			$arrDocument['state'] != 'finished' &&
			$arrDocument['released'] === '0'
		) {
			$arrIcons['toolbar_edit'] = 1;
		}

		if(
			$arrDocument['type'] == 'account' ||
			$arrDocument['type'] == 'credit' ||
			$arrDocument['type'] == 'cancellation_invoice'
		) {
			if($arrDocument['state'] != 'draft') {
				$arrIcons['toolbar_payments'] = 1;
			} else {
				$arrIcons['toolbar_delete'] = 1;
			}
		} else {
			if($arrDocument['state'] == 'draft') {
				$arrIcons['toolbar_delete'] = 1;
			}
		}

		if(
			$arrDocument['type'] == 'offer' &&
			$arrDocument['state'] == 'released'
		) {
			$arrIcons['toolbar_accept'] = 1;
			$arrIcons['toolbar_decline'] = 1;
		}

		if(
			$arrDocument['type'] == 'offer' &&
			(
				$arrDocument['state'] == 'released' ||
				$arrDocument['state'] == 'accepted'
			)
		) {
			$arrIcons['toolbar_project'] = 1;
		}

		if($arrDocument['state'] == 'draft') {
			$arrIcons['toolbar_release'] = 1;
		}

	} elseif(isset($_VARS['document_id']) && count($_VARS['document_id']) > 1) {

		$arrIcons['export_list_pdf'] = 1;
		$arrIcons['export_list_xlsx'] = 1;

	}

	$arrTransfer['data'] = $arrIcons;

} elseif($strTask == 'get_articles') {

	$aArticles = $objOfficeDao->getArticles();
	$arrTransfer['data']['aArticles'] = $aArticles;

} elseif($strTask == 'get_article_data') {

	if((int)$_VARS['article_id'] == 0)
	{
		$aArticle = array(
			'id'			=> 0,
			'number'		=> '',
			'product'		=> '',
			'unit'			=> '',
			'productgroup'	=> 0,
			'currency'		=> '',
			'price'			=> 0,
			'cost'			=> 0,
			'description'	=> ''
		);
	}
	else
	{
		$aArticle = $objOfficeDao->getArticle((int)$_VARS['article_id']);
	}
	
	// insert article data
	$arrTransfer['data']['aArticle'] = $aArticle;
	
	// get article units
	$aArticleUnits = array();
	foreach((array)$aUnits as $sKey => $sValue)
	{
		$aArticleUnits[] = array($sKey, $sValue);
	}
	$arrTransfer['data']['aArticle']['aUnits'] 		= $aArticleUnits;
	
	// get articlegroups
	$aProductgroups = $objOfficeDao->getArticleGroups();
	array_unshift($aProductgroups, array(0, 'Keine Auswahl'));
	$arrTransfer['data']['aArticle']['aProductgroups'] = $aProductgroups;

	// get curencys
	$aCurrencys = Data::getCurrencys();
	$aJSCurrencys = array();
	foreach((array)$aCurrencys as $iKey => $aCurrency)
	{
		if($iKey == 0)
		{
			$aJSCurrencys[] = array(
				0 => '',
				1 => 'keine Auswahl'
			);
		}
		$aJSCurrencys[] = array(
			0 => $aCurrency['iso4217'],
			1 => $aCurrency['sign'] . ' - ' . $aCurrency['name']
		);
	}
	
	$arrTransfer['data']['aArticle']['aCurrencys']	= $aJSCurrencys;

} elseif($strTask == 'save_article') {

	$iArticleID = (int)$_VARS['article_id'];

	$aArticle = array(
		'id'			=> (int)$_VARS['article_id'],
		'product'		=> urldecode($objPurifier->purify($_VARS['product'])),
		'number'		=> urldecode($objPurifier->purify($_VARS['number'])),
		'unit'			=> urldecode($objPurifier->purify($_VARS['unit'])),
		'productgroup'	=> urldecode($objPurifier->purify($_VARS['productgroup'])),
		'currency'		=> urldecode($objPurifier->purify($_VARS['currency'])),
		'price'			=> urldecode($objPurifier->purify($_VARS['price'])),
		'cost'			=> urldecode($objPurifier->purify($_VARS['cost'])),
		'vat'			=> urldecode($objPurifier->purify($_VARS['vat'])),
		'month'			=> urldecode($objPurifier->purify($_VARS['month'])),
		'description'	=> urldecode($objPurifier->purify($_VARS['description']))
	);

	$objOfficeDao->saveArticle($aArticle);

} elseif($strTask == 'delete_article') {

	$objOfficeDao->removeArticle((int)$_VARS['article_id']);

} elseif($strTask == 'fill_geo') {

	$sWhere	= "";
	$aSQL	= array();

	if($_VARS['by_field'] == 'zip') {
		$sWhere = "`gz`.`zip` LIKE CONCAT(:sZIP, '%')";
		$aSQL['sZIP'] = $_VARS['by_value'];
	}
	
	if($_VARS['by_field'] == 'city') {
		$sWhere = "`gc`.`city` LIKE CONCAT(:sCity, '%')";
		$aSQL['sCity'] = $_VARS['by_value'];
	}

	try {

		$sSQL = "
			SELECT
				`gc`.`city`,
				`gz`.`zip`
			FROM 
				`geo_city` AS `gc` INNER JOIN 
				`geo_zip2city` AS `z2c` ON 
					`gc`.`id` = `z2c`.`city_id` INNER JOIN 
				`geo_zip` AS `gz` ON 
					`gz`.`id` = `z2c`.`zip_id`
			WHERE 
				{WHERE}
			ORDER BY 
				`gz`.`zip`, `gc`.`city`
			LIMIT 
				30
		";
		$aList = DB::getPreparedQueryData(str_replace('{WHERE}', $sWhere, $sSQL), $aSQL);

	} catch (Exception $ex) {
		$aList = [];
	}
	
	$arrTransfer['data'] = array(
		'in_field'	=> $_VARS['in_field'],
		'by_field'	=> $_VARS['by_field'],
		'list'		=> $aList
	);

}

elseif(
	$strTask == 'get_customer'
		||
	$strTask == 'get_customers'
		||
	$strTask == 'get_customer_overview'
		||
	$strTask == 'save_customer_data'
		||
	$strTask == 'delete_customer'
		||
	$strTask == 'get_contact_persons'
		||
	$strTask == 'save_contact_person'
		||
	$strTask == 'delete_contact_person'
		||
	$strTask == 'get_protocols'
		||
	$strTask == 'add_protocol'
		||
	$strTask == 'get_protocols_data'
		||
	$strTask == 'get_billings'
		||
	$strTask == 'generate_billing_account'
		||
	$strTask === 'get_locations'
		||
	$strTask === 'get_location'
		||
	$strTask === 'save_location'
		||
	$strTask === 'delete_location'
		||
	$strTask === 'get_comments'
		||
	$strTask === 'get_comment'
		||
	$strTask === 'save_comment'
		||
	$strTask === 'delete_comment'
		||
	$strTask === 'export_customers'
)
{
	require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/office/customer.ajax.php");
}
elseif(
	$strTask == 'prepare_document'
		||
	$strTask == 'getCustomerData'
		||
	$strTask == 'getClientData'
		||
	$strTask == 'getPositionData'
		||
	$strTask == 'add_position'
		||
	$strTask == 'edit_position'
		||
	$strTask == 'delete_position'
		||
	$strTask == 'sort_positions'
		||
	$strTask == 'saveDocument'
		||
	$strTask == 'manageReminder'
		||
	$strTask == 'get_document_data'
		||
	$strTask == 'save_document_copy'
		||
	$strTask == 'prepare_ticket'
		||
	$strTask == 'save_ticket'
		||
	$strTask == 'get_block_text'
		||
	$strTask == 'load_checklist'
		||
	$strTask == 'save_checklist'
		||
	$strTask == 'remove_checklist'
		||
	$strTask == 'copy_document'
		||
	$strTask == 'check_copy_document'
)
{
	require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/office/document.ajax.php");
}
elseif(
	$strTask == 'get_project_data'
		||
	$strTask == 'get_project_contact_persons'
		||
	$strTask == 'add_project_position'
		||
	$strTask == 'remove_project_position'
		||
	$strTask == 'add_project_cc'
		||
	$strTask == 'remove_project_cc'
		||
	$strTask == 'get_projects'
		||
	$strTask == 'save_project'
		||
	$strTask == 'check_project_toolbar'
		||
	$strTask == 'save_position_alias'
		||
	$strTask == 'save_position_amount'
		||
	$strTask == 'save_position_activity'
		||
	$strTask == 'copy_project_position'
		||
	$strTask == 'get_project_employees_list'
		||
	$strTask == 'add_project_employees'
		||
	$strTask == 'remove_project_employee'
		||
	$strTask == 'get_project_analysis'
		||
	$strTask == 'close_project'
		||
	$strTask == 'save_project_conclusion'
		||
	$strTask == 'delete_project'
		||
	$strTask == 'copy_project'
		||
	$strTask == 'get_project_stats_list'
)
{
	require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/office/project.ajax.php");
}
elseif(
	$strTask == 'get_contracts'
		||
	$strTask == 'check_contract_toolbar'
		||
	$strTask == 'get_contract_data'
		||
	$strTask == 'save_contract'
		||
	$strTask == 'delete_contract'
		||
	$strTask == 'get_contracts_stats'
		||
	$strTask == 'get_contracts_stats_customer'
		||
	$strTask == 'get_actually_contracts'
		||
	$strTask == 'create_contract_invoices'
		||
	$strTask == 'load_contract_contacts'
)
{
	require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/office/contract.ajax.php");
}
elseif(
	$strTask == 'get_employees'
		||
	$strTask == 'get_employee_data'
		||
	$strTask == 'save_employee'
		||
	$strTask == 'check_employee_toolbar'
		||
	$strTask == 'delete_employee_file'
		||
	$strTask == 'refresh_file_list'
		||
	$strTask == 'add_contract'
		||
	$strTask == 'edit_contract'
		||
	$strTask == 'delete_contract'
		||
	$strTask == 'get_holiday_data'
		||
	$strTask == 'edit_holiday'
		||
	$strTask == 'add_holiday'
		||
	$strTask == 'delete_holiday'
		||
	$strTask == 'refresh_holiday_data'
		||
	$strTask == 'get_employee_timeclock'
		||
	$strTask == 'send_access_data'
		||
	$strTask == 'delete_employee'
		||
	$strTask == 'get_factors'
		||
	$strTask == 'save_factors'
)
{
	require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/office/employee.ajax.php");
}
elseif($strTask == 'get_employee_rates')
{
	$arrTransfer['data']['employee_id'] = (int)$_VARS['employee_id'];
	\System::wd()->executeHook('get_employee_rates', $arrTransfer['data']);
}
elseif($strTask == 'get_employee_rate_activities')
{
	$arrTransfer['data']['employee_id'] = (int)$_VARS['employee_id'];
	$arrTransfer['data']['project_id'] = (int)$_VARS['project_id'];
	\System::wd()->executeHook('get_employee_rate_activities', $arrTransfer['data']);
}
elseif($strTask == 'save_employee_rating')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('save_employee_rating', $arrTransfer['data']);
}
elseif($strTask == 'display_consulimus_search_mask')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('display_consulimus_search_mask', $arrTransfer['data']);
}
elseif($strTask == 'start_employees_searching')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('start_employees_searching', $arrTransfer['data']);
}
elseif($strTask == 'send_project_employees_newsletter')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('send_project_employees_newsletter', $arrTransfer['data']);
}
elseif($strTask == 'send_cc_access_data')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('send_cc_access_data', $arrTransfer['data']);
}
elseif($strTask == 'send_project_employees_accept_decline_mail')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('send_project_employees_accept_decline_mail', $arrTransfer['data']);
}
elseif($strTask == 'accept_checked_employees_now')
{
	$arrTransfer['data'] = $_VARS;
	\System::wd()->executeHook('accept_checked_employees_now', $arrTransfer['data']);

}

if(isset($_VARS['debug'])) {
	__pout(DB::getQueryHistory());
}

// Log the activity if required and get all activities by customer ID
$objOfficeDao->manageProtocols($aLog);

$strJson = json_encode($arrTransfer);

echo $strJson;
