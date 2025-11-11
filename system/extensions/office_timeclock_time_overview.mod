<?php

$sInterface = System::wd()->getInterface();

if(
	$user_data['id'] > 0 &&	
	$user_data['cms'] != 1
) {

	$oSession = \Core\Handler\SessionHandler::getInstance();
	
	$fTimeclockStart = microtime(true);

	$oTimeclock = new Ext_Office_Timeclock('office_timeclock');

	$iEmployeeID = $user_data['id'];

	/* ==================================================================================================== */

	if($_VARS['todo'] != 'request_new') {
		$oSession->set('o_tc_display_flag', $oSession->get('o_tc_display_flag')-1);
	}

	if(
		isset($_VARS['todo']) && 
		(
			$_VARS['todo'] == 'request_new' || 
			$_VARS['todo'] == 'request_change'
		)
	) {
		$oSession->set('o_tc_display_flag', 1);
	}

	/* ==================================================================================================== */

	if(isset($_VARS['save_request_new'])) {
		
		$oSession->set('o_tc_display_flag', 0);

		unset($_VARS['todo']);

		try {
			$oDate = new WDDate();
			$oDate->set($_VARS['o_tc_date_from'], WDDate::DATES);
			$oDate->set($_VARS['o_tc_time_from'].':00', WDDate::TIMES);
			$iStart = $oDate->get(WDDate::TIMESTAMP);
			$oDate->set($_VARS['o_tc_date_till'], WDDate::DATES);
			$oDate->set($_VARS['o_tc_time_till'].':00', WDDate::TIMES);
			$iEnd = $oDate->get(WDDate::TIMESTAMP);
		} catch(Exception $e) {
			$iDateError = 1;
		}

		if (
			!WDDate::isDate($_VARS['o_tc_date_from'], WDDate::DATES) ||
			!WDDate::isDate($_VARS['o_tc_time_from'].':00', WDDate::TIMES) ||
			!WDDate::isDate($_VARS['o_tc_date_till'], WDDate::DATES) ||
			!WDDate::isDate($_VARS['o_tc_time_till'].':00', WDDate::TIMES) ||
			date('Y', $iStart) != date('Y', $iEnd) ||
			$iStart > $iEnd
		) {
			$iDateError = 1;
		}

		$sSQL = "
			SELECT
				`id`
			FROM
				`office_project_employees`
			WHERE
				`project_id` = :iProjectID
					AND
				`employee_id` = :iEmployeeID
			LIMIT
				1
		";
		$aSQL = array(
			'iProjectID'	=> $_VARS['o_tc_projects'],
			'iEmployeeID'	=> $iEmployeeID
		);
		$i_P2E_ID = DB::getQueryOne($sSQL, $aSQL);

		$aInsert = array(
			'created'		=> date('YmdHis'),
			'action'		=> 'new',
			'p2p_id'		=> $_VARS['o_tc_activities'],
			'salary'		=> Ext_Office_Timeclock::getSalary($iEmployeeID, $iStart, $iEnd, $_VARS['o_tc_projects'], $_VARS['o_tc_activities']),
			'start'			=> date('YmdHis', $iStart),
			'end'			=> date('YmdHis', $iEnd),
			'p2e_id'		=> $i_P2E_ID,
			'comment'		=> (string)$_VARS['o_tc_comment'],
		);

		if($iDateError != 1)
		{
			DB::insertData('office_timeclock', $aInsert);

			\System::wd()->executeHook('send_timeclock_notification', $aInsert);

			header('Location: '.$oRequest->getPathInfo());
			exit();
		}
	}

	if(isset($_VARS['todo']) && ($_VARS['todo'] == 'request_new' || $_VARS['todo'] == 'request_change'))
	{
		$oDate = new WDDate();
		$oDate->sub(1, WDDate::MONTH);

		$sWhere = ' AND (UNIX_TIMESTAMP(`op`.`closed_date`) = 0 OR UNIX_TIMESTAMP(`op`.`closed_date`) >= ' . $oDate->get(WDDate::TIMESTAMP) . ')';
		$aNewEntryProjects = Ext_Office_Timeclock::getProjects($iEmployeeID, $sWhere);

		if(!isset($_VARS['o_tc_projects']))
		{
			if($_VARS['todo'] == 'request_new')
			{
				$aChangeData['project_id'] = $iSelNewPro = key($aNewEntryProjects);
			}
		}
		else
		{
			$aChangeData['project_id'] = $iSelNewPro = $_VARS['o_tc_projects'];

			if($_VARS['todo'] == 'request_change')
			{
				try
				{
					$oDate = new WDDate();
					$oDate->set($_VARS['o_tc_date_from'], WDDate::DATES);
					$oDate->set($_VARS['o_tc_time_from'].':00', WDDate::TIMES);
					$iStart = $oDate->get(WDDate::TIMESTAMP);
					$oDate->set($_VARS['o_tc_date_till'], WDDate::DATES);
					$oDate->set($_VARS['o_tc_time_till'].':00', WDDate::TIMES);
					$iEnd = $oDate->get(WDDate::TIMESTAMP);

					$aChangeData['start']	= $iStart;
					$aChangeData['end'] 	= $iEnd;
					$aChangeData['id']		= (int)$_VARS['o_tc_id'];
				}
				catch(Exception $e)
				{
					$iDateError = 1;
				}
			}
		}
	}

	/* ==================================================================================================== */

	if(isset($_VARS['reset_request_change']) || isset($_VARS['reset_request_new'])) {
		$oSession->set('o_tc_display_flag', 0);
	}

	if(isset($_VARS['save_request_change'])) {

		$oSession->set('o_tc_display_flag', 0);

		try
		{
			$oDate = new WDDate();
			$oDate->set($_VARS['o_tc_date_from'], WDDate::DATES);
			$oDate->set($_VARS['o_tc_time_from'].':00', WDDate::TIMES);
			$iStart = $oDate->get(WDDate::TIMESTAMP);
			$oDate->set($_VARS['o_tc_date_till'], WDDate::DATES);
			$oDate->set($_VARS['o_tc_time_till'].':00', WDDate::TIMES);
			$iEnd = $oDate->get(WDDate::TIMESTAMP);
		}
		catch(Exception $e)
		{
			$iDateError = 1;
		}

		if
		(
			!WDDate::isDate($_VARS['o_tc_date_from'], WDDate::DATES)	||
			!WDDate::isDate($_VARS['o_tc_time_from'].':00', WDDate::TIMES)			||
			!WDDate::isDate($_VARS['o_tc_date_till'], WDDate::DATES)	||
			!WDDate::isDate($_VARS['o_tc_time_till'].':00', WDDate::TIMES)			||
			date('Y', $iStart) != date('Y', $iEnd)						||
			$iStart > $iEnd
		)
		{
			$iDateError = 1;
		}

		$sSQL = "
			SELECT
				`id`
			FROM
				`office_project_employees`
			WHERE
				`project_id` = :iProjectID
					AND
				`employee_id` = :iEmployeeID
			LIMIT
				1
		";
		$aSQL = array(
			'iProjectID'	=> $_VARS['o_tc_projects'],
			'iEmployeeID'	=> $iEmployeeID
		);
		$i_P2E_ID = DB::getQueryOne($sSQL, $aSQL);

		$aChange = array(
			'p2p_id'	=> $_VARS['o_tc_activities'],
			'start'		=> $iStart,
			'end'		=> $iEnd,
			'comment'	=> (string)$_VARS['o_tc_comment']
		);

		if($i_P2E_ID > 0)
		{
			$aChange['p2e_id'] = $i_P2E_ID;
		}

		if($iDateError != 1)
		{
			DB::updateData('office_timeclock', array('change' => serialize($aChange), 'action' => 'change'), '`id` = '.(int)$_VARS['o_tc_id']);

			\System::wd()->executeHook('send_timeclock_notification', $aChange);

			header('Location: '.$oRequest->getPathInfo());
			exit();
		}
	}

	/* ==================================================================================================== */

	if(isset($_VARS['todo']) && $_VARS['todo'] == 'request_change')
	{
		$aTimes = Ext_Office_Timeclock::getTimes($iEmployeeID, $oSession->get('o_tc_from'), $oSession->get('o_tc_till'));

		foreach((array)$aTimes as $iKey => $aValue)
		{
			if($aValue['id'] == $_VARS['tc_id'])
			{
				$aChangeData = $aValue;
				break;
			}
		}
	}

	/* ==================================================================================================== */

	if(isset($_VARS['todo']) && $_VARS['todo'] == 'request_delete')
	{
		DB::updateData('office_timeclock', array('action' => 'delete'), '`id` = '.(int)$_VARS['tc_id']);

		unset($_VARS['todo']);

		\System::wd()->executeHook('send_timeclock_notification', $_VARS);

		header('Location: '.$oRequest->getPathInfo());
		exit();
	}

	/* ==================================================================================================== */

	$fTimeclockProjectsStart = microtime(true);

	$aTmpProjects = Ext_Office_Timeclock::getProjects($iEmployeeID);

	$aProjects = array('0' => 'alle');

	foreach((array)$aTmpProjects as $iKey => $sValue)
	{
		$aProjects[$iKey] = $sValue;
	}

	$fTimeclockProjectsEnd = microtime(true);

	// Prepare request
	$oDate		= new WDDate();
	$iFrom		= $oSession->get('o_tc_from');
	$iTill		= $oSession->get('o_tc_till');
	$iProjectID	= $oSession->get('o_tc_project');

	if(
		isset($_VARS['action']) && 
		$_VARS['action'] == 'show_timeclock_times'
	) {

		try {
			$oDate->set('00:00:00', WDDate::TIMES);
			$oDate->set($_VARS['o_tc_from'], WDDate::DATES);
			$iFrom = $oDate->get(WDDate::TIMESTAMP);
			$oSession->set('o_tc_from', $iFrom);
		} catch(Exception $e) {}
				
		try {
			$oDate->set('23:59:59', WDDate::TIMES);
			$oDate->set($_VARS['o_tc_till'], WDDate::DATES);
			$iTill = $oDate->get(WDDate::TIMESTAMP);
			$oSession->set('o_tc_till', $iTill);
		} catch(Exception $e) {}

		if($iFrom > $iTill) {
			$iTemp = $iFrom;
			$iFrom = $iTill;
			$iTill = $iTemp;
			$oSession->set('o_tc_from', $iFrom);
			$oSession->set('o_tc_till', $iTill);
		}

		$iProjectID = $_VARS['o_tc_projects'];
		$oSession->set('o_tc_project', $iProjectID);

	} else {

		if(!$oSession->has('o_tc_from')) {

			$oDate->set('00:00:00', WDDate::TIMES);
			$oDate->set(1, WDDate::DAY);
			$iFrom = $oDate->get(WDDate::TIMESTAMP);
			$oSession->set('o_tc_from', $iFrom);
		}
		
		if(!$oSession->has('o_tc_till')) {
			$oDate->set('23:59:59', WDDate::TIMES);
			$oDate->set($oDate->get(WDDate::MONTH_DAYS), WDDate::DAY);
			$iTill = $oDate->get(WDDate::TIMESTAMP);
			$oSession->set('o_tc_till', $iTill);
		}
		
		if(!$oSession->has('o_tc_project')) {
			$iProjectID = 0;
			$oSession->set('o_tc_project', $iProjectID);
		}
	}

	// Get times
	$fTimeclockTimesStart = microtime(true);
	$aTimes = Ext_Office_Timeclock::getTimes($iEmployeeID, $iFrom, $iTill, $iProjectID);
	$fTimeclockTimesEnd = microtime(true);

	/* ==================================================================================================== */

	// Get activities by project ID
	$fTimeclockActivitiesStart = microtime(true);
	$sSQL = "
		SELECT
			`opp`.`id`,
			`opc`.`title`,
			`opa`.`alias`
		FROM
			`office_project_positions`		AS `opp`
				LEFT OUTER JOIN
			`office_project_aliases`		AS `opa`
				ON
			`opp`.`alias_id` = `opa`.`id`
				LEFT OUTER JOIN
			`office_project_categories`		AS `opc`
				ON
			`opp`.`category_id` = `opc`.`id`
		WHERE
			`opp`.`project_id` = :iProjectID
	";
	$aActivities = DB::getPreparedQueryData($sSQL, array('iProjectID' => $aChangeData['project_id']));
	$fTimeclockActivitiesEnd = microtime(true);

	/* ==================================================================================================== */

	// Calculate total times
	$fTimeclockTotaltimesStart = microtime(true);
	$iTotal = 0;
	$aBreaks = array();
	$aDayTimes = array();
	foreach((array)$aTimes as $iKey => $aValue)
	{

		if($aValue['end'] == 0) {
			$aValue['end'] = time();
		}

		$iTotal += $aValue['end'] - $aValue['start'];

		$sDay = date('Y-m-d', $aValue['start']);

		if(!isset($aDayTimes[$sDay])) {
			$aDayTimes[$sDay] = array();
		}

		$aDayTimes[$sDay]['duration'] += ($aValue['end'] - $aValue['start']);

		if(!isset($aDayTimes[$sDay]['min'])) {
			$aDayTimes[$sDay]['min'] = $aValue['start'];
		} else {
			$aDayTimes[$sDay]['min'] = min($aDayTimes[$sDay]['min'], $aValue['start']);
		}

		if(!isset($aDayTimes[$sDay]['max'])) {
			$aDayTimes[$sDay]['max'] = $aValue['end'];
		} else {
			$aDayTimes[$sDay]['max'] = max($aDayTimes[$sDay]['max'], $aValue['end']);
		}
	}
	$fTimeclockTotaltimesEnd = microtime(true);

	$oEmployee = new Ext_Office_Employee($iEmployeeID);

	foreach($aDayTimes as &$aDayTime) {
		$aDayTime['gross'] = $oEmployee->getFormatedTimes($aDayTime['max'] - $aDayTime['min']);
		$aDayTime['break'] = $oEmployee->getFormatedTimes($aDayTime['max'] - $aDayTime['min'] - $aDayTime['duration']);
		$aDayTime['net'] = $oEmployee->getFormatedTimes($aDayTime['duration']);
	}

	if($_VARS['debug']) {
		__pout($aDayTimes);
	}

	// Get work times
	$fTimeclockWorktimesStart = microtime(true);
	$aWorkTimes['soll'] = $oEmployee->getSollTimes($oSession->get('o_tc_from'), $oSession->get('o_tc_till'));
	$aWorkTimes['between'] = $oEmployee->getFormatedTimes($iTotal);
	$fTimeclockWorktimesEnd = microtime(true);

	// Holiday info
	$fTimeclockHolidayStart = microtime(true);
	$aHolidayData = $oEmployee->refreshHolidaysData(date('Y'));
	$fTimeclockHolidayEnd = microtime(true);

	/* ==================================================================================================== */

	$oSmarty = new \Cms\Service\Smarty();
	$oSmarty->assign('aProjects', $aProjects);
	$oSmarty->assign('iProjectID', $iProjectID);
	$oSmarty->assign('aDayTimes', $aDayTimes);
	$oSmarty->assign('aTimes', $aTimes);
	$oSmarty->assign('aWorkTimes', $aWorkTimes);
	$oSmarty->assign('aChangeData', $aChangeData);
	$oSmarty->assign('aHolidayData', $aHolidayData);

	$fTimeclockHookStart = microtime(true);

	\System::wd()->executeHook('add_additional_timeclock_parameter', $oSmarty);

	$fTimeclockHookEnd = microtime(true);
	$fTimeclockEnd = microtime(true);
	$fTimeclockTotal = ($fTimeclockEnd - $fTimeclockStart);
	$fTimeclockProjectsTotal = ($fTimeclockProjectsEnd - $fTimeclockProjectsStart);
	$fTimeclockTimesTotal = ($fTimeclockTimesEnd - $fTimeclockTimesStart);
	$fTimeclockActivitiesTotal = ($fTimeclockActivitiesEnd - $fTimeclockActivitiesStart);
	$fTimeclockTotaltimesTotal = ($fTimeclockTotaltimesEnd - $fTimeclockTotaltimesStart);
	$fTimeclockWorktimesTotal = ($fTimeclockWorktimesEnd - $fTimeclockWorktimesStart);
	$fTimeclockHolidayTotal = ($fTimeclockHolidayEnd - $fTimeclockHolidayStart);
	$fTimeclockHookTotal = ($fTimeclockHookEnd - $fTimeclockHookStart);

	$oSmarty->assign('fTimeclockStart', $fTimeclockStart);
	$oSmarty->assign('fTimeclockEnd', $fTimeclockEnd);
	$oSmarty->assign('fTimeclockTotal', $fTimeclockTotal);
	$oSmarty->assign('fTimeclockProjectsStart', $fTimeclockProjectsStart);
	$oSmarty->assign('fTimeclockProjectsEnd', $fTimeclockProjectsEnd);
	$oSmarty->assign('fTimeclockProjectsTotal', $fTimeclockProjectsTotal);
	$oSmarty->assign('fTimeclockTimesStart', $fTimeclockTimesStart);
	$oSmarty->assign('fTimeclockTimesEnd', $fTimeclockTimesEnd);
	$oSmarty->assign('fTimeclockTimesTotal', $fTimeclockTimesTotal);
	$oSmarty->assign('fTimeclockActivitiesStart', $fTimeclockActivitiesStart);
	$oSmarty->assign('fTimeclockActivitiesEnd', $fTimeclockActivitiesEnd);
	$oSmarty->assign('fTimeclockActivitiesTotal', $fTimeclockActivitiesTotal);
	$oSmarty->assign('fTimeclockTotaltimesStart', $fTimeclockTotaltimesStart);
	$oSmarty->assign('fTimeclockTotaltimesEnd', $fTimeclockTotaltimesEnd);
	$oSmarty->assign('fTimeclockTotaltimesTotal', $fTimeclockTotaltimesTotal);
	$oSmarty->assign('fTimeclockWorktimesStart', $fTimeclockWorktimesStart);
	$oSmarty->assign('fTimeclockWorktimesEnd', $fTimeclockWorktimesEnd);
	$oSmarty->assign('fTimeclockWorktimeskTotal', $fTimeclockWorktimesTotal);
	$oSmarty->assign('fTimeclockHolidayStart', $fTimeclockHolidayStart);
	$oSmarty->assign('fTimeclockHolidayEnd', $fTimeclockHolidayEnd);
	$oSmarty->assign('fTimeclockHolidayTotal', $fTimeclockHolidayTotal);
	$oSmarty->assign('fTimeclockHookStart', $fTimeclockHookStart);
	$oSmarty->assign('fTimeclockHookEnd', $fTimeclockHookEnd);
	$oSmarty->assign('fTimeclockHookTotal', $fTimeclockHookTotal);

	$oSmarty->assign('aNewEntryProjects', $aNewEntryProjects);
	$oSmarty->assign('iSelNewPro', $iSelNewPro);
	$oSmarty->assign('aActivities', $aActivities);
	$oSmarty->assign('sFlag', $oSession->get('o_tc_display_flag'));
	$oSmarty->assign('sTodo', $_VARS['todo']);
	$oSmarty->assign('iDateError', $iDateError);
	$oSmarty->assign('sFrom', date('d.m.Y', $oSession->get('o_tc_from')));
	$oSmarty->assign('sTill', date('d.m.Y', $oSession->get('o_tc_till')));
	$oSmarty->displayExtension($element_data);

	echo '<!-- office_timeclock_time_overview :: $fTimeclockTotal = '.$fTimeclockTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockProjectsTotal = '.$fTimeclockProjectsTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockTimesTotal = '.$fTimeclockTimesTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockActivitiesTotal = '.$fTimeclockActivitiesTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockTotaltimesTotal = '.$fTimeclockTotaltimesTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockWorktimesTotal = '.$fTimeclockWorktimesTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockHolidayTotal = '.$fTimeclockHolidayTotal.' -->'.PHP_EOL;
	echo '<!-- office_timeclock_time_overview :: $fTimeclockHookTotal = '.$fTimeclockHookTotal.' -->'.PHP_EOL;

	/* ==================================================================================================== */

}
