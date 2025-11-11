<?php

function __calculateAgeByBirthdate($sBirthdate)
{
	list($iDay_Now, $iMonth_Now, $iYear_Now) = explode('.', date('d.m.Y'));
	list($iDay_BDate, $iMonth_BDate, $iYear_BDate) = explode('.', $sBirthdate);

	if(($iMonth_Now < $iMonth_BDate) || (($iMonth_Now == $iMonth_BDate) && ($iDay_Now < $iDay_BDate)))
	{
		$iAge = $iYear_Now - $iYear_BDate - 1;
	}
	else
	{
		$iAge = $iYear_Now - $iYear_BDate;
	}

	return $iAge;
}

/* ==================================================================================================== */

if($strTask == 'get_employees')
{
	$oEmployee = new Ext_Office_Employee();

	$_SESSION['employee_sign']		= $sSign	= $objPurifier->purify($_VARS['sign']);
	$_SESSION['employee_search']	= $sSearch	= $objPurifier->purify($_VARS['search']);
	$_SESSION['employee_limit']		= $iLimit	= $objPurifier->purify($_VARS['limit']);
	$_SESSION['employee_group']		= $iGroup	= $objPurifier->purify($_VARS['group']);

	$aEmployees = $oEmployee->getEmployeesList($sSign, $sSearch, $iLimit, $iGroup);

	// Calculate the age of employee
	foreach((array)$aEmployees as $iKey => $aValue)
	{
		if($aValue['date_o_b'] != 0)
		{
			$aEmployees[$iKey]['age'] = '('.__calculateAgeByBirthdate(date('d.m.Y', $aValue['date_o_b'])).')';
			$aEmployees[$iKey]['date_o_b'] = date('d.m.Y', $aValue['date_o_b']);
		}
		else
		{
			$aEmployees[$iKey]['age'] = $aEmployees[$iKey]['date_o_b'] = '';
		}
	}

	$arrTransfer['data'] = $aEmployees;
}


if($strTask == 'delete_employee') {

	$oEmployee = new Ext_Office_Employee((int)$_VARS['employee_id']);
	$oEmployee->remove();

}

/* ==================================================================================================== */

else if($strTask == 'get_employee_timeclock')
{
	$iEmployeeID = (int)$_VARS['employee_id'];

	$oEmployee = new Ext_Office_Employee($iEmployeeID);

	/* ================================================== */ // Prepare dates

	$aMonths = $aYears = array();
	$oDate = new WDDate();

	// Months
	for($i = 1; $i <= 12; $i++)
	{
		$oDate->set($i, WDDate::MONTH);
		$aMonths[] = array($i, date('F', $oDate->get(WDDate::TIMESTAMP)));
	}

	// Get earlest year from timeclock
	$sSQL = "
		SELECT
			MIN(YEAR(`ot`.`start`))
		FROM
			`office_timeclock`			AS `ot`
				INNER JOIN
			`office_project_employees`	AS `ope`
				ON
			`ot`.`p2e_id` = `ope`.`id`
		WHERE
			`ope`.`employee_id` = :iEmployeeID
	";
	$iMinYear = DB::getQueryOne($sSQL, array('iEmployeeID' => $iEmployeeID));

	// Years
	if(is_numeric($iMinYear))
	{
		for($i = date('Y'); $i >= $iMinYear; $i--)
		{
			$aYears[] = array($i, $i);
		}
	}
	else
	{
		$aYears[] = array(date('Y'), date('Y'));
	}

	$iSelMonth	= date('m');
	$iSelYear	= date('Y');
	$bOpen = true;
	if(isset($_VARS['month']) && is_numeric($_VARS['month']))
	{
		$iSelMonth	= $_VARS['month'];
		$bOpen = false;
	}
	if(isset($_VARS['year']) && is_numeric($_VARS['year']))
	{
		$iSelYear	= $_VARS['year'];
		$bOpen = false;
	}

	/* ================================================== */ // Get the data

	$aTimes		= $oEmployee->getWorkTimes($iSelMonth, $iSelYear);
	$aWorks		= $oEmployee->getWorks($iSelMonth, $iSelYear);
	$aDetails	= $oEmployee->getWorksDetails($iSelMonth, $iSelYear);

	/* ================================================== */

	$arrTransfer['data'] = array(
		'aMonths'		=> $aMonths,
		'aYears'		=> $aYears,
		'aTimes'		=> $aTimes,
		'aWorks'		=> $aWorks,
		'aDetails'		=> $aDetails,

		'iSelMonth'		=> $iSelMonth,
		'iSelYear'		=> $iSelYear,
		'bOpen'			=> $bOpen
	);
}

/* ==================================================================================================== */

else if($strTask == 'check_employee_toolbar')
{
	$iEmployeeID = (int)$_VARS['employee_id'];

	$arrIcons['toolbar_edit']		= 0;
	$arrIcons['toolbar_delete']		= 0;
	$arrIcons['toolbar_holiday']	= 0;
	$arrIcons['toolbar_timeclock']	= 0;
	$arrIcons['toolbar_factors']	= 0;
	$arrIcons['toolbar_rating']		= 0;

	if($iEmployeeID > 0)
	{
		$arrIcons['toolbar_edit']		= 1;
		$arrIcons['toolbar_delete']		= 1;
		$arrIcons['toolbar_holiday']	= 1;
		$arrIcons['toolbar_timeclock']	= 1;
		$arrIcons['toolbar_factors']	= 1;
		$arrIcons['toolbar_rating']		= 1;
	}

	$arrTransfer['data'] = $arrIcons;
}

/* ==================================================================================================== */

else if($strTask == 'send_access_data')
{
	$oEmployee = new Ext_Office_Employee((int)$_VARS['employee_id']);

	$oEmployee->sendAccessData();

	$arrTransfer['data'] = array('email' => $oEmployee->email);
}

/* ==================================================================================================== */

else if($strTask == 'get_employee_data') {

	$oEmployee = new Ext_Office_Employee();

	$iEmployeeID = (int)$_VARS['employee_id'];

	// Set temporary ID for new employees
	if(!isset($_SESSION['employee_counter']) && $iEmployeeID == 0)
	{
		$_SESSION['employee_counter'] = -1;
		$iEmployeeID = $_SESSION['employee_counter'];
	}
	else if($iEmployeeID == 0)
	{
		$iEmployeeID = --$_SESSION['employee_counter'];
	}
	unset($_SESSION['employee_'.$iEmployeeID]['files']);

	/* ================================================== */ // DEFAULTS

	$aData = array(
		'id'				=> $iEmployeeID,
		'email'				=> '',
		'sex'				=> 0,
		'firstname'			=> '',
		'lastname'			=> '',
		'date_o_b'			=> '',
		'nationality'		=> '',
		'web'				=> '',
		'phone'				=> '',
		'fax'				=> '',
		'mobile'			=> '',
		'company'			=> '',
		'sektion'			=> '',
		'position'			=> '',
		'street'			=> '',
		'zip'				=> '',
		'city'				=> '',
		'country'			=> '',
		'notice'			=> '',
		'reporting_group'	=> ''
	);

	/* ================================================== */

	if($iEmployeeID > 0) {

		$oEmployee = new Ext_Office_Employee($iEmployeeID);
		
		/* ================== */
		
		// get Contracts Datas
		$arrTransfer['contracts'] = $oEmployee->getContractListData();

		// get Groups
		$arrTransfer['selectedGroups'] = $oEmployee->handleEmployeeGroups($iEmployeeID);

		// get contract Data and set Contract Data into arrTransfer
//		$arrTransfer['contract'] = $oEmployee->getContractData();
		
		/* ================== */
		
		$aConfig = $oEmployee->config;

		/* ================== */
		
		$aData = array();
		foreach((array)$aConfig as $sKey => $mValue) {

			if(substr($sKey, 0, 10) == 'pro_field_') {
				$sTmpKey = str_replace('pro_field_', '', $sKey);
				try {
					$aData[$sTmpKey] = $oEmployee->$mValue;
				} catch(Exception $e) {
					$aData[$sTmpKey] = '';
				}
			}

		}

		$aData['id'] = $iEmployeeID;
		$aData['email'] = $oEmployee->email;
		$aData['nickname'] = $oEmployee->nickname;

		$aData['date_o_b'] = strftime('%x',$aData['date_o_b']);

		$aData['reporting_group'] = $oEmployee->reporting_group;

		/* ================== */

		$arrTransfer['files'] = $oEmployee->getFileList();
		
	} else {
		$arrTransfer['files'] = array('0' => '');
	}
	
	/* ================== */
	// get aviable Groups
	$aGroups = $oEmployee->getGroups();
	$arrGroups = array();

	foreach((array)$aGroups as $sKey => $mValue) {
		$arrGroups[] = array($mValue['id'], $mValue['name']);
	}
	
	$arrTransfer['groups'] = $arrGroups;

	// put cookie information into arrTransfer['cookie'] to get positive accesscheck after flash-upload
	$arrTransfer['cookie']['user'] = $_COOKIE['usercookie'];
	$arrTransfer['cookie']['pass'] = $_COOKIE['passcookie'];

	$arrTransfer['data'] = $aData;

}

/* ==================================================================================================== */


else if ($strTask == 'delete_employee_file')
{
	$iEmployeeID 	= (int)$_VARS['employee_id'];
	$sFile 			= $_VARS['filename'];
	
	if(!empty($sFile))
	{
		unlink(\Util::getDocumentRoot()."storage/office/employees/".$iEmployeeID."/".$sFile);
	}
	
	$oEmployee = new Ext_Office_Employee($iEmployeeID);
	
	$arrTransfer['files'] = $oEmployee->getFileList();

}

/* ==================================================================================================== */

// Refresh File List
else if ($strTask == 'refresh_file_list')
{
	
	$iEmployeeID 	= (int)$_VARS['employee_id'];
	
	$oEmployee = new Ext_Office_Employee($iEmployeeID);
	
	$arrTransfer['files'] = $oEmployee->getFileList();
}

/* ==================================================================================================== */
else if($strTask == 'save_employee')
{
	$iEmployeeID = (int)$_VARS['employee_id'];
	
	/* ================================================== */ // Create and fill the object

	// Create new employee object
	if($_VARS['employee_id'] <= 0)
	{
		$oEmployee = new Ext_Office_Employee($iEmployeeID);
	}
	
	// Get the employee object by employee ID
	else
	{
		$oEmployee = new Ext_Office_Employee($iEmployeeID);
	}

	$sDate = strtotimestamp($_VARS['date_o_b']);

	$oEmployee->changed	= date('YmdHis');
	$oEmployee->email 			= rawurldecode($_VARS['email']);
	$oEmployee->nickname 		= rawurldecode($_VARS['nickname']);
	$oEmployee->sex				= (int)$_VARS['sex'];;
	$oEmployee->firstname		= rawurldecode($_VARS['firstname']);
	$oEmployee->lastname		= rawurldecode($_VARS['lastname']);
	$oEmployee->date_o_b		= $sDate;
	$oEmployee->nationality		= rawurldecode($_VARS['nationality']);
/*
	$oEmployee->bank_name		= rawurldecode($_VARS['bank_name']);
	$oEmployee->bank_holder		= rawurldecode($_VARS['bank_holder']);
	$oEmployee->bank_number		= rawurldecode($_VARS['bank_number']);
	$oEmployee->bank_code		= rawurldecode($_VARS['bank_code']);
*/
	$oEmployee->web				= rawurldecode($_VARS['web']);
	$oEmployee->phone			= rawurldecode($_VARS['phone']);
	$oEmployee->fax				= rawurldecode($_VARS['fax']);
	$oEmployee->mobile			= rawurldecode($_VARS['mobile']);
	$oEmployee->company			= rawurldecode($_VARS['company']);
	$oEmployee->sektion			= rawurldecode($_VARS['sektion']);
	$oEmployee->position		= rawurldecode($_VARS['position']);
	$oEmployee->street			= rawurldecode($_VARS['street']);
	$oEmployee->zip				= rawurldecode($_VARS['zip']);
	$oEmployee->city			= rawurldecode($_VARS['city']);
	$oEmployee->country			= rawurldecode($_VARS['country']);
	$oEmployee->notice			= rawurldecode($_VARS['notice']);
	$oEmployee->reporting_group	= (int)$_VARS['reporting_group'];

	// save employee data
	$aErrors = $oEmployee->save();

	// give id back
	$arrTransfer['data'] = array(
		'id'		=> $oEmployee->id
	);
	if(isset($aErrors['email']))
	{
		$arrTransfer['data']['email'] = 'DOUBLE';
	}
	else if(!checkEmailMx(rawurldecode($_VARS['email']), 'EmailAddress'))
	{
		$arrTransfer['data']['email'] = 'NOT_VALID';
	}
	if(isset($aErrors['nickname']))
	{
		$arrTransfer['data']['nickname'] = 'DOUBLE';
	}
	else if(empty($_VARS['nickname']))
	{
		$arrTransfer['data']['nickname'] = 'EMPTY';
	}

	// save Groups of employee
	$oEmployee->handleEmployeeGroups($oEmployee->id, $_VARS['groups']);

}

/* ==================================================================================================== */

else if($strTask == 'add_contract' || $strTask == 'edit_contract')
{
	
	$aContractData = array(
		'employee_id'				=>	rawurldecode($_VARS['employee_id']),
		'from'						=>	rawurldecode($_VARS['from']),
		'until'						=>	rawurldecode($_VARS['until']),
		'social_security_number'	=>	rawurldecode($_VARS['social_security_number']),
		'religion'					=>	rawurldecode($_VARS['religion']),
		'tax_class'					=>	rawurldecode($_VARS['tax_class']),
		'tax_number'				=>	rawurldecode($_VARS['tax_number']),
		'factor'					=>	rawurldecode($_VARS['factor']),
		'health_insurance'			=>	rawurldecode($_VARS['health_insurance']),
		'gross_salary'				=>	rawurldecode($_VARS['gross_salary']),
		'salary'					=>	rawurldecode($_VARS['salary']),
		'hours_type'				=>	rawurldecode($_VARS['hours_type']),
		'hours_value'				=>	rawurldecode($_VARS['hours_value']),
		'days_per_week'				=>	rawurldecode($_VARS['days_per_week']),
		'holiday'					=>	rawurldecode($_VARS['holiday'])
	);

	// make object employee
	$oEmployee = new Ext_Office_Employee($_VARS['employee_id']);

	// save contract data
	if($_VARS['id'])
	{
		// edit contract
		$mCheck = $oEmployee->saveEditContractData($aContractData, (int)$_VARS['id']);

		$sLastId = (int)$_VARS['id'];
	}
	else
	{
		// new contract
		$mCheck = $sLastId = $oEmployee->saveEditContractData($aContractData);
	}

	$arrTransfer['contracts'] = $oEmployee->getContractListData();

	if($mCheck['ERROR'])
	{
		$arrTransfer['ERROR'] = $mCheck['ERROR'];
	}
}

/* ==================================================================================================== */

else if($strTask == 'add_holiday' || $strTask == 'edit_holiday')
{
	$aHolidayData = array(
		'employee_id'				=>	rawurldecode($_VARS['employee_id']),
		'from'						=>	rawurldecode($_VARS['from']),
		'till_hours'				=>	rawurldecode($_VARS['till_hours']),
		'till_days'					=>	rawurldecode($_VARS['till_days']),
		'type'						=>	rawurldecode($_VARS['type']),
		'notice'					=>	rawurldecode($_VARS['notice'])
	);

	// make object employee
	$oEmployee = new Ext_Office_Employee($_VARS['employee_id']);

	// save contract data
	if($_VARS['id'])
	{
		// edit contract
		$oEmployee->saveEditHolidayData($aHolidayData, (int)$_VARS['id']);
		$sLastId = (int)$_VARS['id'];
	} else {
		// new contract
		$sLastId = $oEmployee->saveEditHolidayData($aHolidayData);
	}

	$aDate = explode('-', $_VARS['from']);
	$iYear = $aDate[0];

	// new list of active contracts
	$arrTransfer['holiday'] = $oEmployee->refreshHolidaysData($iYear);
}

/* ==================================================================================================== */

else if($strTask == 'delete_holiday')
{
	
	// make object employee
	$oEmployee = new Ext_Office_Employee((int)$_VARS['employee_id']);
	
	// delete contract via id
	$oEmployee->deleteHoliday((int)$_VARS['id']);

	$aDate = explode('-', $_VARS['from']);
	$iYear = $aDate[0];
	
	// new list of active contracts
	$arrTransfer['holiday'] = $oEmployee->refreshHolidaysData($iYear);
//	$arrTransfer['holiday'] = $oEmployee->getHolidaysData();
	
}

/* ==================================================================================================== */

else if($strTask == 'delete_contract')
{
	
	// make object employee
	$oEmployee = new Ext_Office_Employee((int)$_VARS['employee_id']);
	
	// delete contract via id
	$oEmployee->deleteContract((int)$_VARS['id']);
	
	// new list of active contracts
	$arrTransfer['contracts'] = $oEmployee->getContractListData();
	
}

/* ==================================================================================================== */

else if($strTask == 'get_holiday_data')
{
	$iEmployeeID = (int)$_VARS['employee_id'];

	// make object employee
	$oEmployee = new Ext_Office_Employee($iEmployeeID);

	// get holiday/sickday data
	$aHolidaysData = $oEmployee->getHolidaysData();

	// set data into transferarray for ajax
	$arrTransfer['holiday'] = $aHolidaysData;
}

/* ==================================================================================================== */

else if($strTask == 'refresh_holiday_data')
{
	$iEmployeeID = (int)$_VARS['employee_id'];
	$iYear = (int)$_VARS['year'];
	// make object employee
	$oEmployee = new Ext_Office_Employee($iEmployeeID);
	
	// get actualy (year) holiday/sickday data
	$aHolidaysData = $oEmployee->refreshHolidaysData($iYear);
	
	// set data into transferarray for ajax
	$arrTransfer['holiday'] = $aHolidaysData;
}

/* ==================================================================================================== */

else if($strTask == 'get_factors')
{
	$iEmployeeID = (int)$_VARS['employee_id'];

	$oEmployee = new Ext_Office_Employee($iEmployeeID);

	$arrTransfer = $oEmployee->getFactors();
}

/* ==================================================================================================== */

else if($strTask == 'save_factors')
{
	$iEmployeeID = (int)$_VARS['employee_id'];

	$oEmployee = new Ext_Office_Employee($iEmployeeID);

	$arrTransfer = $oEmployee->saveFactors($_VARS['factors']);
}

?>