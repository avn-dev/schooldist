<?php

if($strTask == 'get_project_contact_persons')
{
	// Get the project id
	$iProjectID = (int)$objPurifier->purify($_VARS['project_id']);

	$aContacts = array();
	if((int)$_VARS['customer_id'] > 0)
	{
		$oCustomer = new Ext_Office_Customer('DEFAULT', (int)$_VARS['customer_id']);
		$aContacts = $oCustomer->getContactsList();
	}

	unset($_SESSION['project_'.$iProjectID]['contacts']);

	$aContacts = __prepareContactPersons($aContacts);

	$arrTransfer['data'] = array(
		'aContacts' => $aContacts
	);
}

/* ==================================================================================================== */

if($strTask == 'save_project_conclusion') {

	$iProjectID = (int)$_VARS['project_id'];

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);
	$oProject->conclusion = urldecode($_VARS['conclusion']);

	\System::wd()->executeHook('office_close_project', $_VARS);

	$oProject->save();
}

/* ==================================================================================================== */

if($strTask == 'close_project')
{
	$iProjectID = (int)$_VARS['project_id'];
	$oProject = new Ext_Office_Project('office_projects', $iProjectID);
	$oProject->conclusion = urldecode($_VARS['conclusion']);

	\System::wd()->executeHook('office_close_project', $_VARS);

	$oProject->close();

	$arrTransfer['data'] = array();
}

/* ==================================================================================================== */

if($strTask == 'copy_project')
{
	$iProjectID = (int)$_VARS['project_id'];
	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	$aNewProject = $oProject->cloneProject();

	$aIDs = array(
		'old_id'	=> $iProjectID,
		'new_id'	=> $aNewProject['id']
	);

	\System::wd()->executeHook('copy_project_additional_fields', $aIDs);

	$arrTransfer['data']['sNewTitle'] = $aNewProject['title'];
}

/* ==================================================================================================== */

if($strTask == 'check_project_toolbar')
{
	$iProjectID = (int)$_VARS['project_id'];

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	if((int)$oProject->closed_date > 0)
	{
		$arrIcons['toolbar_finish'] = 0;
		$arrIcons['toolbar_delete'] = 1;
	}
	else
	{
		$arrIcons['toolbar_finish'] = 1;
		$arrIcons['toolbar_delete'] = 0;
	}

	$arrIcons['toolbar_edit'] = 1;
	$arrIcons['toolbar_analysis'] = 1;
	$arrIcons['toolbar_copy'] = 1;

	$arrTransfer['data'] = $arrIcons;
}

/* ==================================================================================================== */

if($strTask == 'get_project_analysis')
{
	$iProjectID = (int)$_VARS['project_id'];

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	$aAnalysis = $oProject->getAnalysis();

	$aEmployees	= $oProject->getProjectEmployeesList();
	$aEmployeeSelect = array();
	foreach($aEmployees as $aEmployeeGroup) {
		foreach($aEmployeeGroup['employees'] as $aEmployee) {
			$aEmployeeSelect[$aEmployee['employee_id']] = $aEmployee['name'];
		}
	}
	asort($aEmployeeSelect);
	$arrTransfer['employees'] = array();
	foreach($aEmployeeSelect as $iId=>$sName) {
		$arrTransfer['employees'][] = array(
			'id'=>$iId,
			'name'=>$sName
		);
	}

	$arrTransfer['data'] = $aAnalysis;
}

/* ==================================================================================================== */

if($strTask == 'add_project_cc' || $strTask == 'remove_project_cc')
{
	// Add contact person
	if($strTask == 'add_project_cc')
	{
		$_SESSION['project_'.(int)$_VARS['project_id']]['contacts'][(int)$_VARS['cc_id']] = 1;
	}
	// Remove contact person
	else
	{
		unset($_SESSION['project_'.(int)$_VARS['project_id']]['contacts'][(int)$_VARS['cc_id']]);
	}
}

/* ==================================================================================================== */

if($strTask == 'get_projects')
{
	// Get search attributes
	$_SESSION['project_state']  = $sState       = $objPurifier->purify($_VARS['state']);
	$_SESSION['project_search'] = $sSearch      = $objPurifier->purify($_VARS['search']);
	$_SESSION['project_from']   = $sFrom        = $objPurifier->purify($_VARS['from']);
	$_SESSION['project_to']     = $sTo          = $objPurifier->purify($_VARS['to']);
    $_SESSION['product_area']   = $sProductArea = $objPurifier->purify($_VARS['product_area']);

	// Get the list of projects
	$oProject = new Ext_Office_Project('office_projects');
	$aProjects = $oProject->getProjectsList($sState, $sFrom, $sTo, $sSearch, $sProductArea);

    // convert null values to empty string to provent javascript to display "null" in cells
    array_walk(
        $aProjects,
        function(&$aRow) {
            foreach ($aRow as $k => $v) {
                if ($v === null) {
                    $aRow[$k] = '';
                }
            }
        }
    );

	$arrTransfer['data'] = $aProjects;

}

/* ==================================================================================================== */

if($strTask == 'remove_project_employee')
{
	$iLinkID = (int)$_VARS['employee_link_id'];

	$sSQL = "UPDATE `office_project_employees` SET `active` = 0 WHERE `id` = " . (int)$iLinkID;
	DB::executeQuery($sSQL);

	$strTask = 'add_project_employees';
}

if($strTask == 'add_project_employees') {

	$iProjectID = (int)$_VARS['project_id'];
	$aEmployees = explode('|', $_VARS['employees']);

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	$aCurrentEmployees = (array)$oProject->getProjectEmployees(true);

	foreach((array)$aEmployees as $iKey => $iValue) {

		if(is_numeric($iValue)) {

			$aInsert = array(
				'created'		=> date('YmdHis'),
				'active'		=> 1,
				'project_id'	=> $iProjectID,
				'employee_id'	=> $iValue,
				'group'			=> (int)$_VARS['group_id']
			);
			
			// Wenn der Empfänger schon zugewiesen ist (eventuell inaktiv), dann nur aktualisieren
			if(array_key_exists($iValue, $aCurrentEmployees)) {
				$aWhere = array(
					'project_id'	=> $iProjectID,
					'employee_id'	=> $iValue
				);
				DB::updateData('office_project_employees', $aInsert, $aWhere);
			} else {
				DB::insertData('office_project_employees', $aInsert);
			}

		}
	}

	$aList = $oProject->getProjectEmployeesList();

	$arrTransfer['data'] = $aList;

}

/* ==================================================================================================== */

if($strTask == 'get_project_employees_list')
{
	$iProjectID = (int)$_VARS['project_id'];
	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	if(isset($_VARS['search']))
	{
		$_SESSION['add_emp_to_pro'] = $_VARS['search'];
	}

	$aList = $oProject->getEmployeesListByGroup((int)$_VARS['group_id'], $_SESSION['add_emp_to_pro']);

	$arrTransfer['data'] = $aList;
}

/* ==================================================================================================== */

if($strTask == 'get_project_data')
{
	// Get the project id
	$iProjectID = (int)$_VARS['project_id'];

	$oProject = new Ext_Office_Project('office_projects');

	// Set temporary id for new projects
	if(!isset($_SESSION['project_counter']) && $iProjectID == 0)
	{
		$_SESSION['project_counter'] = -1;
		$iProjectID = $_SESSION['project_counter'];
	}
	else if($iProjectID == 0)
	{
		$iProjectID = --$_SESSION['project_counter'];
	}
	unset($_SESSION['project_'.$iProjectID]['positions']);

	/* ================================================== */ // Prepare editors

	$aTmpEditors		= $oProject->getEditors();
	$aEditors			= array();
	$iSelectedEditor	= 0;
	foreach((array)$aTmpEditors as $iKey => $aValue)
	{
		$aEditors[] = array(
			$aValue['id'],
			$aValue[$oProject->config['pro_field_firstname']] . ' ' . $aValue[$oProject->config['pro_field_lastname']]
		);
	}

	/* ================================================== */ // Prepare customers

	$aTmpCustomers		= $objOfficeDao->getCustomers();
	$aCustomers			= array(array('', '---'));
	$iSelectedCustomer	= 0;
	foreach((array)$aTmpCustomers as $iKey => $aValue)
	{
		$aCustomers[] = array($iKey, $aValue['name']);
	}

	$aPositions = $aEmployees = array();
	$iDocumentID = 0;
	$iBudget = 0;
	$sTitle = '';
    $iSelectedProductArea = 0;

	if(isset($_VARS['document_id']))
	{
		$iDocumentID = (int)$_VARS['document_id'];

		// Get budget, customer ID, title
		$sSQL = "SELECT * FROM `office_documents` WHERE `id` = :iDocumentID LIMIT 1";
		$aDocument = DB::getQueryRow($sSQL, array('iDocumentID' => $iDocumentID));

		$iBudget = $aDocument['price_net'];
		$iSelectedCustomer = $aDocument['customer_id'];
		$sTitle = $aDocument['subject'];
        $iSelectedProductArea = $aDocument['product_area_id'];

		// Get offer positions
		$oProject->getOfferPositions($iDocumentID);
		$aPositions = $oProject->aPositions;

		foreach((array)$aPositions as $iKey => $aValue)
		{
			if(!isset($_SESSION['project_'.$iProjectID]['positions_counter']))
			{
				$_SESSION['project_'.$iProjectID]['positions_counter'] = -1;
				$iPositionID = $_SESSION['project_'.$iProjectID]['positions_counter'];
			}
			else
			{
				$iPositionID = --$_SESSION['project_'.$iProjectID]['positions_counter'];
			}

			$aPositions[$iKey]['id'] = $iPositionID;
		}

		$_SESSION['project_'.$iProjectID]['positions'] = $aPositions;
	}

 	/* ================================================== */ // Get categories

	$aTmpCategories = $oProject->getCategories();
	$aCategories = array();
	$iSelectedCategory = 0;
	foreach((array)$aTmpCategories as $iKey => $aValue)
	{
		$aCategories[] = array($aValue['id'], $aValue['title']);
	}

	$aTmpActivities = $oProject->getCategories('activity');
	$aActivities = array(array(0, 'bitte wählen'));
	foreach((array)$aTmpActivities as $iKey => $aValue)
	{
		$aActivities[] = array($aValue['id'], $aValue['title']);
	}

	/* ================================================== */ // Get employee groups

	$aEmployeeGroups = array();
	$sSQL = "SELECT `id`, `name` FROM `customer_groups` WHERE `db_nr` = ".$oProject->config['pro_database']." ORDER BY `name`";
	$aTmpGroups = DB::getQueryPairs($sSQL);
	foreach((array)$aTmpGroups as $iKey => $sValue)
	{
		$aEmployeeGroups[] = array($iKey, $sValue);
	}

	/* ================================================== */ // Get product areas

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

	/* ================================================== */ // Default values

	$sDescription = $sFrom = $sTill = $sConclusion = '';
	$bProjectClosed = false;

	/* ================================================== */

	// Project already exists
	if($iProjectID > 0)
	{
		$oProject = new Ext_Office_Project('office_projects', $iProjectID);

		if($oProject->start_date > 0)
		{
			$sFrom = date('d.m.Y', $oProject->start_date);
		}
		if($oProject->end_date > 0)
		{
			$sTill = date('d.m.Y', $oProject->end_date);
		}
		$sTitle				  = $oProject->title;
		$iSelectedCategory	  = $oProject->category_id;
		$iSelectedCustomer	  = $oProject->customer_id;
		$iSelectedEditor	  = $oProject->editor_id;
        $iSelectedProductArea = $oProject->product_area_id;
		$sDescription		  = $oProject->description;
		$iBudget			  = $oProject->iBudget;
		$aPositions			  = $oProject->aPositions;
		$iDocumentID		  = $oProject->offer_id;
		$sConclusion		  = $oProject->conclusion;
		$aEmployees			  = $oProject->getProjectEmployeesList();

		if($oProject->closed_date > 0)
		{
			$bProjectClosed = true;
		}

		$_SESSION['project_'.$iProjectID]['positions'] = $aPositions;

	}

	/* ================================================== */ // Get contact persons

	$aTmpContacts = array();
	if($iSelectedCustomer > 0)
	{
		$oCustomer = new Ext_Office_Customer('DEFAULT', $iSelectedCustomer);
		$aTmpContacts = $oCustomer->getContactsList();
	}

	/* ================================================== */ // Prepare contact persons

	$aContacts = __prepareContactPersons($aTmpContacts);

	/* ================================================== */

	$arrTransfer['data'] = array(
		'id'					=> $iProjectID,

		'aEditors'				=> $aEditors,
		'aCustomers'			=> $aCustomers,
		'aContacts'				=> $aContacts,
		'aCategories'			=> $aCategories,
		'aPositions'			=> $aPositions,
		'aUnits'				=> $aArticleUnits,
		'aActivities'			=> $aActivities,
		'aEmployees'			=> $aEmployees,
		'aEmployeeGroups'		=> $aEmployeeGroups,
        'aProductAreas'         => $aProductAreas,

		'selectedEditor'		=> $iSelectedEditor,
		'selectedCustomer'		=> $iSelectedCustomer,
		'selectedCategory'		=> $iSelectedCategory,
        'selectedProductArea'   => $iSelectedProductArea,
		'nameSearch'			=> '',

		'sDescription'			=> $sDescription,
		'sConclusion'			=> $sConclusion,
		'sTitle'				=> $sTitle,
		'iBudget'				=> $iBudget,
		'sFrom'					=> $sFrom,
		'sTill'					=> $sTill,
		'iOfferID'				=> $iDocumentID,
		'bProjectClosed'		=> $bProjectClosed
	);

	\System::wd()->executeHook('get_project_rate', $arrTransfer['data']);
	\System::wd()->executeHook('get_project_additional_fields', $arrTransfer['data']);
}

/* ==================================================================================================== */

if
(
	$strTask == 'save_position_alias'
		||
	$strTask == 'save_position_amount'
		||
	$strTask == 'save_position_activity'
)
{
	$iPositionID	= (int)$_VARS['position_id'];
	$iProjectID		= (int)$_VARS['project_id'];

	if($strTask == 'save_position_alias')
	{
		$sValue = rawurldecode($objPurifier->purify($_VARS['alias']));
		$sKey = 'alias';
	}
	if($strTask == 'save_position_amount')
	{
		$sValue = rawurldecode($objPurifier->purify($_VARS['amount']));
		$sKey = 'planed_amount';
	}
	if($strTask == 'save_position_activity')
	{
		$sValue = (int)$_VARS['activity'];
		$sKey = 'category_id';
	}

	foreach((array)$_SESSION['project_'.$iProjectID]['positions'] as $iKey => $aValue)
	{
		if($aValue['id'] == $iPositionID)
		{
			$_SESSION['project_'.$iProjectID]['positions'][$iKey][$sKey] = $sValue;
			if($strTask == 'save_position_alias')
			{
				$_SESSION['project_'.$iProjectID]['positions'][$iKey]['flag'] = 'set_alias';
			}
			break;
		}
	}
}

/* ==================================================================================================== */

if($strTask == 'copy_project_position')
{
	$iProjectPositionID = (int)$_VARS['position_id'];
	$iProjectID = (int)$_VARS['project_id'];

	if(!isset($_SESSION['project_'.$iProjectID]['positions_counter']))
	{
		$_SESSION['project_'.$iProjectID]['positions_counter'] = -1;
		$iPositionID = $_SESSION['project_'.$iProjectID]['positions_counter'];
	}
	else
	{
		$iPositionID = --$_SESSION['project_'.$iProjectID]['positions_counter'];
	}

	$aPositions = array();
	$bFlag = false;
	foreach((array)$_SESSION['project_'.$iProjectID]['positions'] as $iKey => $aValue)
	{
		if($aValue['id'] != $iProjectPositionID && !$bFlag)
		{
			$aPositions[] = $aValue;
		}
		else if($aValue['id'] == $iProjectPositionID && !$bFlag)
		{
			// Prepare the copy
			$aCopy = $aValue;
			$aCopy['id'] = $iPositionID;
			$aCopy['title'] = $aCopy['alias'] = '';
			unset($aCopy['created'], $aCopy['changed']);

			$aPositions[] = $aValue;
			$aPositions[] = $aCopy;
			$bFlag = true;
		}
		else if($aValue['id'] != $iProjectPositionID && $bFlag)
		{
			$aPositions[] = $aValue;
		}
	}

	$_SESSION['project_'.$iProjectID]['positions'] = $aPositions;

	$arrTransfer['data'] = array('aPositions' => $_SESSION['project_'.$iProjectID]['positions']);
}

/* ==================================================================================================== */

if($strTask == 'add_project_position')
{
	//$iPositionID = (int)$_VARS['position_id'];
	$iProjectID = (int)$_VARS['project_id'];

	if(!isset($_SESSION['project_'.$iProjectID]['positions_counter']))
	{
		$iPositionID = $_SESSION['project_'.$iProjectID]['positions_counter'] = -1;
	}
	else
	{
		$iPositionID = --$_SESSION['project_'.$iProjectID]['positions_counter'];
	}

	$_SESSION['project_'.$iProjectID]['positions'][] = array(
		'id'				=> $iPositionID,
		'planed_amount'		=> 1,
		'amount'			=> 0,
		'unit'				=> 'Std.',
		'title'				=> '',
		'category_id'		=> 0,
		'doc_position_id'	=> 0,
		'project_id'		=> $iProjectID,
		'alias_id'			=> 0,
		'price'				=> 0,
		'alias'				=> ''
	);

	$arrTransfer['data'] = array('aPositions' => $_SESSION['project_'.$iProjectID]['positions']);
}

/* ==================================================================================================== */

if($strTask == 'remove_project_position')
{
	$iPositionID = (int)$_VARS['position_id'];
	$iProjectID = (int)$_VARS['project_id'];

	$aPositions = array();
	$bFlag = false;
	foreach((array)$_SESSION['project_'.$iProjectID]['positions'] as $iKey => $aValue)
	{
		if($aValue['id'] != $iPositionID)
		{
			$aPositions[] = $aValue;
		}
		else if ($iPositionID > 0)
		{
			$aValue['task'] = 'delete';
			$aPositions[] = $aValue;

			if($aValue['doc_position_id'] > 0 && trim($aValue['alias']) != '')
			{
				$bFlag = true;
				$iDocID = $aValue['doc_position_id'];
				$sAlias = $aValue['alias'];
			}
		}
		else
		{
			if($aValue['doc_position_id'] > 0 && trim($aValue['alias']) != '')
			{
				$bFlag = true;
				$iDocID = $aValue['doc_position_id'];
				$sAlias = $aValue['alias'];
			}
		}
	}
	if($bFlag)
	{
		foreach((array)$aPositions as $iKey => $aValue)
		{
			if($iDocID == $aValue['doc_position_id'] && !isset($aValue['task']))
			{
				$aPositions[$iKey]['alias'] = $sAlias;
				break;
			}
		}
	}

	$_SESSION['project_'.$iProjectID]['positions'] = $aPositions;

	$arrTransfer['data'] = array('aPositions' => $_SESSION['project_'.$iProjectID]['positions']);
}

/* ==================================================================================================== */

if($strTask == 'delete_project')
{
	$iProjectID = (int)$_VARS['project_id'];

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);

	$oProject->active = 0;

	$oProject->save();
}

if($strTask == 'save_project')
{
	$iProjectID = (int)$_VARS['project_id'];
	$iEditorId = (int)$_VARS['editor_id'];

	/* ================================================== */ // Set the times

	$oDate = new WDDate();

	try
	{
		$oDate->set(rawurldecode($objPurifier->purify($_VARS['start_date'])), WDDate::DATES);
		$iStartTimestamp = $oDate->get(WDDate::TIMESTAMP);
	}
	catch(Exception $e)
	{
		$iStartTimestamp = null;
	}
	try
	{
		$oDate->set(rawurldecode($objPurifier->purify($_VARS['end_date'])), WDDate::DATES);
		$iEndTimestamp = $oDate->get(WDDate::TIMESTAMP);
	}
	catch(Exception $e)
	{
		$iEndTimestamp = null;
	}

	/* ================================================== */ // Create and fill the object

	$iManagerID = null;

	// Create new project object
	if($_VARS['project_id'] <= 0) {
		$oProject = new Ext_Office_Project('office_projects');
	}
	// Get the project object by project ID
	else {
		$oProject = new Ext_Office_Project('office_projects', $_VARS['project_id']);

		// Get the manager
		$sSQL = "SELECT `editor_id` FROM `office_projects` WHERE `id` = ".(int)$_VARS['project_id'];
		$iManagerID = (int)DB::getQueryOne($sSQL);
	}

	$aNewManager = array(
		'created'		=> date('YmdHis'),
		'active'		=> 1,
		'employee_id'	=> (int)$iEditorId,
		'group'			=> (int)$oProject->config['pro_master_group'],
		'state'			=> 1
	);

	$oProject->start_date 		= $iStartTimestamp;
	$oProject->end_date 		= $iEndTimestamp;
	$oProject->editor_id		= $iEditorId != 0 ? $iEditorId : (int)$user_data['id'];
	$oProject->customer_id		= (int)$_VARS['customer_id'];
	$oProject->offer_id			= (int)$_VARS['offer_id'];
	$oProject->category_id		= (int)$_VARS['category_id'];
	$oProject->description		= rawurldecode($objPurifier->purify($_VARS['description']));
	$oProject->title			= rawurldecode($objPurifier->purify($_VARS['title']));
    $oProject->product_area_id  = (int)$_VARS['product_area_id'];
	$oProject->iBudget			= (float)rawurldecode($objPurifier->purify($_VARS['budget']));
	$oProject->aPositions		= $_SESSION['project_'.$iProjectID]['positions'];
	$oProject->aContacts		= $_SESSION['project_'.$iProjectID]['contacts'];
	$oProject->iTempProjectId	= $iProjectID;

	$oProject->save();

	$aNewManager['project_id'] = (int)$oProject->id;

	//$_VARS['project_id'] = (int)$oProject->id;
	\System::wd()->executeHook('save_project_additional_fields', $_VARS);

	// TODO : return new data to javascript
	$arrTransfer['data'] = array(
		'id'			=> $oProject->id,
		'aPositions'	=> $_SESSION['project_'.$oProject->id]['positions']
	);

	/**
	 * Wenn $iManagerID > 0, dann die Gruppe der Zuweisung ändern und prüfen, ob neuer Editor nicht schon zugewiesen ist
	 * Wenn $iManagerID == 0, dann neuen Eintrag machen.
	 * Nicht löschen!
	 */
	if(empty($iManagerID)) {

		DB::insertData('office_project_employees', $aNewManager);

	} else {

		$aEmployees = $oProject->getProjectEmployees(true);

		// Wenn der Editor nicht nicht zugewiesen ist
		if(!array_key_exists($iEditorId, $aEmployees)) {

			DB::insertData('office_project_employees', $aNewManager);

		} else {

			$aData = array(
				'group' => (int)$oProject->config['pro_master_group'],
				'active' => 1
			);

			$aWhere = array(
				'project_id' => (int)$oProject->id,
				'employee_id' => (int)$iManagerID
			);

			DB::updateData('office_project_employees', $aData, $aWhere);
			
		}

	}

}

/* ==================================================================================================== */

if($strTask == 'get_project_stats_list')
{
	$oProject = new Ext_Office_Project('office_projects');

	$aData = $oProject->getProjectHoursStatsList($_VARS['project_id'], $_VARS['from'], $_VARS['till']);

	$arrTransfer = $aData;
}

/* ==================================================================================================== */

function __prepareContactPersons($aTmpContacts)
{
	global $iProjectID;

	// Get available / selected contacts
	if($iProjectID > 0)
	{
		$sSQL = "SELECT `contact_id`, `id` FROM `office_project_contacts` WHERE `project_id` = :iProjectID";
		$aSelected = DB::getQueryPairs($sSQL, array('iProjectID' => $iProjectID));
	}

	$aContacts = array();
	foreach((array)$aTmpContacts as $iKey => $aValue)
	{
		$aContact = array($aValue['id'], $aValue['firstname'].' '.$aValue['lastname']);

		if($iProjectID > 0)
		{
			if(array_key_exists($aValue['id'], (array)$aSelected))
			{
				$aContact[] = 1;
				$_SESSION['project_'.$iProjectID]['contacts'][$aValue['id']] = 1;
			}
		}

		$aContacts[] = $aContact;
	}

	return $aContacts;
}
