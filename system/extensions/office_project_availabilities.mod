<?php

$iEmployeeID = $user_data['id'];

$iAvaParentID = 0;

/* ==================================================================================================== */ // SAVE

if
(
	isset($_VARS['task'])
		&&
	$_VARS['task'] == 'save_availability_days'
		&&
	!isset($_VARS['add_ava_time'])
		&&
	!isset($_VARS['del_ava_time'])
)
{
	$sFlag = 'list';

	$aDates['from']	= $_VARS['from'];
	$aDates['till']	= $_VARS['till'];

	$bTimeError = false;
	foreach((array)$_SESSION['ava_times'] as $iKey => $aValue)
	{
		foreach((array)$aValue['times'] as $i_Key => $a_Value)
		{
			// ================================================== // Check the times

			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['id'] = $_VARS['day_id'][$iKey][$i_Key];
			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'] = $_VARS['time_from'][$iKey][$i_Key];
			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'] = $_VARS['time_till'][$iKey][$i_Key];

			if(!__checkTime($_VARS['time_from'][$iKey][$i_Key]))
			{
				$_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorF'] = $bTimeError = 1;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorF']);
				if(substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'], 0, 2) == '24')
				{
					$_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'] = '00'.substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'], 2);
				}
			}
			if(!__checkTime($_VARS['time_till'][$iKey][$i_Key]))
			{
				$_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorT'] = $bTimeError = 1;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorT']);
				if(substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'], 0, 2) == '24')
				{
					$_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'] = '23:59';
				}
			}
		}
	}

	// ================================================== // Check double entries

	if(!(bool)$bTimeError)
	{
		foreach((array)$_SESSION['ava_times'] as $iKey => $aValue)
		{
			if(!__checkDoubleDayTimes($_SESSION['ava_times'][$iKey]['times']))
			{
				$_SESSION['ava_times'][$iKey]['double'] = 1;
				$bTimeError = true;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['double']);
			}
		}
	}

	if((bool)$bTimeError)
	{
		$sFlag = 'parent_days';
	}
	else
	{
		$oDateF = new WDDate();
		$oDateT = new WDDate();
		$oDateF->set($_VARS['from'], WDDate::DATES);
		$oDateT->set($_VARS['till'], WDDate::DATES);
		$oDateF->set('00:00:00', WDDate::TIMES);
		$oDateT->set('23:59:59', WDDate::TIMES);

		$aSQL = array(
			'iEmployeeID'	=> $iEmployeeID,
			'iFrom1'		=> $oDateF->get(WDDate::TIMESTAMP),
			'iTill1'		=> $oDateT->get(WDDate::TIMESTAMP),
			'iFrom2'		=> $oDateF->get(WDDate::TIMESTAMP),
			'iTill2'		=> $oDateT->get(WDDate::TIMESTAMP),
			'iID'			=> $_VARS['ava_parent_id']
		);
		$iCount = __checkDoubleParents($aSQL);

		if(intval($iCount) > 0)
		{
			$aDates['flag']	= 'double';
			$sFlag			= 'parent';
		}
		else
		{
			$aParent = array(
				'id'			=> $_VARS['ava_parent_id'],
				'employee_id'	=> $iEmployeeID,
				'from'			=> date('YmdHis', $oDateF->get(WDDate::TIMESTAMP)),
				'till'			=> date('YmdHis', $oDateT->get(WDDate::TIMESTAMP))
			);

			if($_VARS['ava_parent_id'] <= 0)
			{
				unset($aParent['id']);
				$aParent['created'] = date('YmdHis');
				DB::insertData('office_project_availabilities', $aParent);
				$aParent['id'] = DB::fetchInsertID();
			}
			else
			{
				DB::updateData('office_project_availabilities', $aParent, '`id` = '.(int)$aParent['id']);
			}

			foreach((array)$_SESSION['ava_times'] as $iKey => $aValue)
			{
				foreach((array)$aValue['times'] as $i_Key => $a_Value)
				{
					$aChild = array(
						'id'			=> $a_Value['id'],
						'employee_id'	=> $iEmployeeID,
						'from'			=> '1970-01-01 '.$a_Value['from'].':00',
						'till'			=> '1970-01-01 '.$a_Value['till'].':00',
						'parent'		=> $aParent['id'],
						'day'			=> $iKey
					);

					if($aChild['id'] <= 0)
					{
						unset($aChild['id']);
						$aChild['created'] = date('YmdHis');
						DB::insertData('office_project_availabilities', $aChild);
					}
					else
					{
						DB::updateData('office_project_availabilities', $aChild, '`id` = '.(int)$aChild['id']);
					}
				}
			}
		}
	}

	unset($_SESSION['ava_times'], $_VARS['task']);
}

/* ==================================================================================================== */

if(isset($_VARS['change_ava_times']))
{
	$sFlag = 'parent';

	$aTmp = each($_VARS['change_ava_times']);
	$iAvaParentID = $aTmp['key'];

	$aDates['from']	= $_VARS['from'][$iAvaParentID];
	$aDates['till']	= $_VARS['till'][$iAvaParentID];

	$_VARS['task'] = 'XYZ';
}

/* ==================================================================================================== */

if(isset($_VARS['delete_ava_times']))
{
	$aTmp = each($_VARS['delete_ava_times']);
	$iAvaParentID = (int)$aTmp['key'];

	$sSQL = "DELETE FROM `office_project_availabilities` WHERE `id` = " . (int)$iAvaParentID . " OR `parent` = " . (int)$iAvaParentID;
	DB::executeQuery($sSQL);

	unset($_VARS['task']);
}

/* ==================================================================================================== */

if(isset($_VARS['task']) && $_VARS['task'] == 'create_neu_availability')
{
	$sFlag = 'parent';

	$iAvaParentID = $_VARS['ava_parent_id'];
}

/* ==================================================================================================== */

if(isset($_VARS['task']) && $_VARS['task'] == 'show_availability_days')
{
	$sFlag = 'parent_days';

	$iAvaParentID = $_VARS['ava_parent_id'];

	// ================================================== // Check the dates

	$oDateF = new WDDate();
	$oDateT = new WDDate();

	try
	{
		$oDateF->set($_VARS['from'], WDDate::DATES);
		$oDateT->set($_VARS['till'], WDDate::DATES);
		$oDateF->set('00:00:00', WDDate::TIMES);
		$oDateT->set('23:59:59', WDDate::TIMES);

		$aDates['from']	= $_VARS['from'];
		$aDates['till']	= $_VARS['till'];

		// Check double entries
		$aSQL = array(
			'iEmployeeID'	=> $iEmployeeID,
			'iFrom1'		=> $oDateF->get(WDDate::TIMESTAMP),
			'iTill1'		=> $oDateT->get(WDDate::TIMESTAMP),
			'iFrom2'		=> $oDateF->get(WDDate::TIMESTAMP),
			'iTill2'		=> $oDateT->get(WDDate::TIMESTAMP),
			'iID'			=> $iAvaParentID
		);
		$iCount = __checkDoubleParents($aSQL);

		if(intval($iCount) > 0)
		{
			$aDates['flag']	= 'double';
			$sFlag			= 'parent';
		}
	}
	catch(Exception $e)
	{
		$aDates['flag']	= 'error';
		$sFlag			= 'parent';
		$aDates['from']	= $_VARS['from'];
		$aDates['till']	= $_VARS['till'];
	}

	// ================================================== // Get available week days

	if(!isset($aDates['flag']))
	{
		$aDays = array();
		$oDateCopyF = new WDDate($oDateF);
		$oDateCopyT = new WDDate($oDateT);
		$oDateCopyT->add(1, WDDate::DAY);

		$oDate = new WDDate();
		$oDate->set('01.01.1970', WDDate::DATES);

		while($oDateCopyF->get(WDDate::DATES) != $oDateCopyT->get(WDDate::DATES))
		{
			$aDays[$oDateCopyF->get(WDDate::WEEKDAY)] = array(
				'day'		=> $oDateCopyF->get(WDDate::WEEKDAY),
				'times'		=> array()
			);

			if((int)$_VARS['ava_parent_id'] > 0)
			{
				$sSQL = "
					SELECT
						`id`,
						UNIX_TIMESTAMP(`from`) AS `from`,
						UNIX_TIMESTAMP(`till`) AS `till`,
						`day`
					FROM
						`office_project_availabilities`
					WHERE
						`parent` = :iParentID
							AND
						`day` = :iDay
				";
				$aTimes = DB::getPreparedQueryData(
					$sSQL,
					array('iParentID' => $_VARS['ava_parent_id'], 'iDay' => $oDateCopyF->get(WDDate::WEEKDAY))
				);
				foreach((array)$aTimes as $iKey => $aValue)
				{
					$oDate->set($aValue['from'], WDDate::TIMESTAMP);
					$aValue['from'] = $oDate->get(WDDate::HOUR) . ':' . $oDate->get(WDDate::MINUTE);
					$oDate->set($aValue['till'], WDDate::TIMESTAMP);
					$aValue['till'] = $oDate->get(WDDate::HOUR) . ':' . $oDate->get(WDDate::MINUTE);

					$aDays[$oDateCopyF->get(WDDate::WEEKDAY)]['times'][] = $aValue;
				}
			}

			if(count($aDays) >= 7)
			{
				unset($oDateCopyF, $oDateCopyT);
				ksort($aDays);
				break;
			}
			$oDateCopyF->add(1, WDDate::DAY);
		}

		$_SESSION['ava_times'] = $aDays;
	}
}

/* ==================================================================================================== */

if(isset($_VARS['add_ava_time']) || isset($_VARS['del_ava_time']))
{
	$sFlag = 'parent_days';

	$iAvaParentID = $_VARS['ava_parent_id'];

	$aDates['from']	= $_VARS['from'];
	$aDates['till']	= $_VARS['till'];

	if(isset($_VARS['del_ava_time']))
	{
		$aTmp1 = each($_VARS['del_ava_time']);
		$aTmp2 = each($_VARS['del_ava_time'][$aTmp1['key']]);
		unset($_SESSION['ava_times'][$aTmp1['key']]['times'][$aTmp2['key']]);

		$iTimeID = (int)$_VARS['day_id'][$aTmp1['key']][$aTmp2['key']];

		if($iTimeID > 0)
		{
			$sSQL = "DELETE FROM `office_project_availabilities` WHERE `id` = " . (int)$iTimeID;
			DB::executeQuery($sSQL);
		}
	}

	$bTimeError = false;
	foreach((array)$_SESSION['ava_times'] as $iKey => $aValue)
	{
		foreach((array)$aValue['times'] as $i_Key => $a_Value)
		{
			// ================================================== // Check the times

			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['id'] = $_VARS['day_id'][$iKey][$i_Key];
			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'] = $_VARS['time_from'][$iKey][$i_Key];
			$_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'] = $_VARS['time_till'][$iKey][$i_Key];

			if(!__checkTime($_VARS['time_from'][$iKey][$i_Key]))
			{
				$_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorF'] = $bTimeError = 1;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorF']);
				if(substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'], 0, 2) == '24')
				{
					$_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'] = '00'.substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['from'], 2);
				}
			}
			if(!__checkTime($_VARS['time_till'][$iKey][$i_Key]))
			{
				$_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorT'] = $bTimeError = 1;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['times'][$i_Key]['errorT']);
				if(substr($_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'], 0, 2) == '24')
				{
					$_SESSION['ava_times'][$iKey]['times'][$i_Key]['till'] = '23:59';
				}
			}
		}
	}

	// ================================================== // Check double entries

	if(!(bool)$bTimeError)
	{
		foreach((array)$_SESSION['ava_times'] as $iKey => $aValue)
		{
			if(!__checkDoubleDayTimes($_SESSION['ava_times'][$iKey]['times']))
			{
				$_SESSION['ava_times'][$iKey]['double'] = 1;
			}
			else
			{
				unset($_SESSION['ava_times'][$iKey]['double']);
			}
		}
	}

	if(isset($_VARS['add_ava_time']))
	{
		$aTmp = each($_VARS['add_ava_time']);
		$_SESSION['ava_times'][$aTmp['key']]['times'][] = array(
			'id'		=> 0,
			'from'		=> '',
			'till'		=> ''
		);
	}
}

/* ==================================================================================================== */

if(!isset($_VARS['task']))
{
	$sFlag = 'list';

	$sSQL = "
		SELECT
			`id`,
			UNIX_TIMESTAMP(`from`) AS `from`,
			UNIX_TIMESTAMP(`till`) AS `till`
		FROM
			`office_project_availabilities`
		WHERE
			`employee_id` = :iEmployeeID
				AND
			`parent` = 0
	";
	$aResult = DB::getPreparedQueryData($sSQL, array('iEmployeeID' => $iEmployeeID));

	$aTimesList = array();
	$oDate = new WDDate();
	$oDate->set('01.01.1970', WDDate::DATES);
	foreach((array)$aResult as $aValue)
	{
		$aTimesList[$aValue['id']]['from'] = date('d.m.Y', $aValue['from']);
		$aTimesList[$aValue['id']]['till'] = date('d.m.Y', $aValue['till']);

		$sSQL = "
			SELECT
				`id`,
				UNIX_TIMESTAMP(`from`) AS `from`,
				UNIX_TIMESTAMP(`till`) AS `till`,
				`day`
			FROM
				`office_project_availabilities`
			WHERE
				`parent` = :iParentID
			ORDER BY
				`day`
		";
		$aTmp = DB::getPreparedQueryData($sSQL, array('iParentID' => $aValue['id']));

		$aTimes = array();
		foreach((array)$aTmp as $a_Value)
		{
			$oDate->set($a_Value['from'], WDDate::TIMESTAMP);
			$a_Value['from'] = $oDate->get(WDDate::HOUR) . ':' . $oDate->get(WDDate::MINUTE);
			$oDate->set($a_Value['till'], WDDate::TIMESTAMP);
			$a_Value['till'] = $oDate->get(WDDate::HOUR) . ':' . $oDate->get(WDDate::MINUTE);
			$aTimes[$a_Value['day']][] = $a_Value;
		}

		$aTimesList[$aValue['id']]['times'] = $aTimes;
	}
}

/* ==================================================================================================== */

$oSmarty = new \Cms\Service\Smarty();
$oSmarty->assign('aTimesList', $aTimesList);
$oSmarty->assign('aDates', $aDates);
$oSmarty->assign('aDays', $_SESSION['ava_times']);
$oSmarty->assign('iAvaParentID', $iAvaParentID);
$oSmarty->assign('sFlag', $sFlag);
$oSmarty->displayExtension($element_data);

/* ==================================================================================================== */

function __checkDoubleParents($aSQL)
{
	$sSQL = "
		SELECT
			`id`
		FROM
			`office_project_availabilities`
		WHERE
			`employee_id` = :iEmployeeID
				AND
			(
				UNIX_TIMESTAMP(`from`) BETWEEN :iFrom1 AND :iTill1
					OR
				UNIX_TIMESTAMP(`till`) BETWEEN :iFrom2 AND :iTill2
			)
				AND
			`id` != :iID
		LIMIT
			1
	";
	$iCount = DB::getQueryOne($sSQL, $aSQL);

	return $iCount;
}

// ================================================== //

function __checkDoubleDayTimes($aTimes)
{
	$oDate = new WDDate();

	$aTSs = array();

	foreach((array)$aTimes as $aValue) {

		$oDate->set($aValue['from'].':00', WDDate::TIMES);
		$iTS_F = $oDate->get(WDDate::TIMESTAMP);
		$oDate->set($aValue['till'].':00', WDDate::TIMES);
		$iTS_T = $oDate->get(WDDate::TIMESTAMP);

		foreach((array)$aTSs as $i_Key => $a_Value) {

			if(
				(int)$a_Value['from'] <= (int)$iTS_F && 
				(int)$iTS_F <= (int)$a_Value['till']
			) {
				unset($aTSs);
				return false;
			}

		}

		$aTSs[] = array('from' => (int)$iTS_F, 'till' => (int)$iTS_T);
	}

	unset($aTSs);
	return true;
}

// ================================================== //

function __checkTime($sTime)
{
	preg_match("/^(\d{1,2}):(\d{2})$/", $sTime, $aMatches);

	if(empty($aMatches) || $aMatches[1] > 24 || $aMatches[1] < 0 || $aMatches[2] > 59 || $aMatches[2] < 0)
	{
		return false;
	}

	return true;
}

?>