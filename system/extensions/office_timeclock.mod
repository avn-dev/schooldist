<?php

if(
	$user_data['id'] > 0 &&	
	$user_data['cms'] != 1
) {

	$fTimeclockStart = microtime(true);

	$oTimeclock = new Ext_Office_Timeclock('office_timeclock');

	$iEmployeeID = $user_data['id'];

	$sLongLoginDuration = 'NO';

	/* ==================================================================================================== */

	if(isset($_VARS['action']) && $_VARS['action'] == 'manage_timeclock')
	{
		$iProjectID		= (int)$_VARS['projects'];
		$iPositionID = (int)$_VARS['activities'];

		$iLastTimeclockID = Ext_Office_Timeclock::getLastTimeclockEntry($iEmployeeID);

		// Check the duration of last login and print warning
		if(isset($_VARS['login']) || isset($_VARS['logout']))
		{
			if(is_numeric($iLastTimeclockID) && $iLastTimeclockID > 0 && $iPositionID > 0)
			{
				$sSQL = "
					SELECT
						(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`ot`.`start`)) AS `seconds`
					FROM
						`office_timeclock`			AS `ot`
							INNER JOIN
						`office_project_employees`	AS `ope`
							ON
						`ope`.`id` = `ot`.`p2e_id`
					WHERE
						`ot`.`id` = :iTimeID
				";
				$iSeconds = DB::getQueryOne($sSQL, array('iTimeID' => $iLastTimeclockID));

				if($iSeconds >= (8 * 3600)) // 8 hours
				{
					$sLongLoginDuration = 'YES';

					$aData = array(
						'project_id'	=> (int)$iProjectID,
						'position_id'	=> (int)$iPositionID,
						'employee_id'	=> (int)$iEmployeeID,
						'timeclock_id'	=> (int)$iLastTimeclockID,
						'login_time'	=> $iSeconds,
					);
					\System::wd()->executeHook('send_information_email', $aData);
				}
			}
		}

		if(isset($_VARS['login']) && $iPositionID > 0)
		{
			if(is_numeric($iLastTimeclockID) && $iLastTimeclockID > 0)
			{
				// Close last entry
				$oTimeclock = new Ext_Office_Timeclock('office_timeclock', $iLastTimeclockID);
				$oTimeclock->end = time();

				if(isset($_VARS['comment']))
				{
					$oTimeclock->comment = (string)$_VARS['comment'];
				}

				$oTimeclock->save();
			}

			// Reset / set the object
			$oTimeclock = new Ext_Office_Timeclock('office_timeclock');

			// ================================================== // Prepare new entry >>> START

			$sSQL = "
				SELECT
					`salary`
				FROM
					`office_employee_contract_data`
				WHERE
					`employee_id` = :iEmployeeID AND
					`active` = 1 AND
					(
						`until` = 0 OR
						(
							UNIX_TIMESTAMP(`until`) > " . time() . " AND
							UNIX_TIMESTAMP(`from`) < " . time() . "
						)
					)
				LIMIT
					1
			";
			$iSalary = DB::getQueryOne($sSQL, array('iEmployeeID' => (int)$iEmployeeID));

			if(!is_numeric($iSalary))
			{
				$sSQL = "
					SELECT
						`opc`.`price`
					FROM
						`office_project_categories`		AS `opc`
							INNER JOIN
						`office_project_positions`		AS `opp`
							ON
						`opc`.`id` = `opp`.`category_id`
					WHERE
						`opp`.`id` = :iPositionID
							 AND
						`opp`.`project_id` = :iProjectID
					LIMIT
						1
				";
				$aSQL = array(
					'iProjectID'	=> (int)$iProjectID,
					'iPositionID'	=> (int)$iPositionID
				);
				$iSalary = DB::getQueryOne($sSQL, $aSQL);
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
				'iProjectID'	=> (int)$iProjectID,
				'iEmployeeID'	=> (int)$iEmployeeID
			);
			$iP2E_ID = DB::getQueryOne($sSQL, $aSQL);

			// ================================================== // Prepare new entry <<< END

			// Set new prpoperties
			$oTimeclock->start	= time();
			$oTimeclock->salary	= $iSalary;
			$oTimeclock->p2p_id	= $iPositionID;
			$oTimeclock->p2e_id	= $iP2E_ID;

			$oTimeclock->save();

		} else if(isset($_VARS['logout'])) {

			if(
				is_numeric($iLastTimeclockID) && 
				$iLastTimeclockID > 0
			) {
				// Close opened entry
				$oTimeclock = new Ext_Office_Timeclock('office_timeclock', $iLastTimeclockID);
				$oTimeclock->end = time();

				if(isset($_VARS['comment'])) {
					$oTimeclock->comment = (string)$_VARS['comment'];
				}

				$oTimeclock->save();
			}

		}

	}

	/* ==================================================================================================== */

	// Get projects by employee ID
	$sSQL = "
		SELECT
			`ope`.`project_id`,
			`op`.`title`
		FROM
			`office_project_employees`		AS `ope` INNER JOIN
			`office_projects`				AS `op` ON
				`ope`.`project_id` = `op`.`id`			
		WHERE
			`ope`.`employee_id` = :iEmployeeID AND
			`ope`.`active` = 1 AND
			UNIX_TIMESTAMP(`op`.`closed_date`) = 0
		ORDER BY
			`op`.`title`
	";
	$aProjects = (array)DB::getQueryPairs($sSQL, array('iEmployeeID' => $iEmployeeID));
	/* ==================================================================================================== */

	$iLastTimeclockID = Ext_Office_Timeclock::getLastTimeclockEntry($iEmployeeID);

	if(isset($_VARS['projects']) && isset($_VARS['action']) && $_VARS['action'] == '')
	{
		$iProjectID = (int)$_VARS['projects'];
	}
	else if(
		is_numeric($iLastTimeclockID) && 
		$iLastTimeclockID > 0
	) {

		$sSQL = "
			SELECT
				`opp`.`id`,
				`opp`.`project_id`,
				`op`.`title`,
				UNIX_TIMESTAMP(`ot`.`start`) AS `start`
			FROM
				`office_project_positions`	AS `opp` INNER JOIN
				`office_timeclock`			AS `ot` ON
					`opp`.`id` = `ot`.`p2p_id` INNER JOIN
				`office_projects`			AS `op` ON
					`opp`.`project_id` = `op`.`id`
			WHERE
				`ot`.`id` = :iTimeclockID
		";
		$aTmpInfo = DB::getQueryRow($sSQL, array('iTimeclockID'	=> $iLastTimeclockID));

		$iProjectID = $aTmpInfo['project_id'];
		$iActivityID = $aTmpInfo['id'];
	}
	else
	{

		// Set the first project as default
		reset($aProjects);
		$iProjectID = key($aProjects);
	}

	/* ==================================================================================================== */

	// Get activities by project ID
	$sSQL = "
		SELECT
			`opp`.`id`,
			`opc`.`title`,
			`opc`.`time_flag`,
			`opa`.`alias`
		FROM
			`office_project_positions`		AS `opp` LEFT OUTER JOIN
			`office_project_aliases`		AS `opa` ON
				`opp`.`alias_id` = `opa`.`id` LEFT OUTER JOIN
			`office_project_categories`		AS `opc` ON
				`opp`.`category_id` = `opc`.`id`
		WHERE
			`opp`.`project_id` = :iProjectID AND
			`opp`.`active` = 1
	";
	$aActivities = DB::getPreparedQueryData($sSQL, array('iProjectID'	=> $iProjectID));

	/* ==================================================================================================== */

	// Get information
	$sSQL = "
		SELECT
			CONCAT(`".$oTimeclock->config['pro_field_lastname']."`, ' ', `".$oTimeclock->config['pro_field_firstname']."`) AS `name`
		FROM
			`customer_db_".$oTimeclock->config['pro_database']."`
		WHERE
			`id` = :iEmployeeID
		LIMIT
			1
	";
	$aInfo['user'] = DB::getQueryOne($sSQL, array('iEmployeeID' => $iEmployeeID));
	$aInfo['project'] = $aTmpInfo['title'];
	$aInfo['start'] = $aTmpInfo['start'];

	foreach((array)$aActivities as $iKey => $aValue)
	{
		if($aValue['id'] == $iActivityID)
		{
			$aInfo['activity'] = $aValue['alias'] . ' - ' . $aValue['title'];
			break;
		}
		if($aValue['time_flag'] == 0)
		{
			unset($aActivities[$iKey]);
		}
	}

	/* ==================================================================================================== */

	$oSmarty = new \Cms\Service\Smarty();
	if(isset($_VARS['login']) && $iPositionID == 0)
	{
		$oSmarty->assign('sError', 'NO_POSITION_SELECTED');
	}

	if((isset($_VARS['login']) || isset($_VARS['logout'])) && $iPositionID > 0)
	{
		header('Location: '.$oRequest->getPathInfo());
		exit();
	}

	$fTimeclockEnd = microtime(true);
	$fTimeclockTotal = ($fTimeclockEnd - $fTimeclockStart);

	$oSmarty->assign('fTimeclockStart', $fTimeclockStart);
	$oSmarty->assign('fTimeclockEnd', $fTimeclockEnd);
	$oSmarty->assign('fTimeclockTotal', $fTimeclockTotal);

	$oSmarty->assign('aProjects', $aProjects);
	$oSmarty->assign('iProjectID', $iProjectID);
	$oSmarty->assign('aActivities', $aActivities);
	$oSmarty->assign('iActivityID', $iActivityID);
	$oSmarty->assign('sLongLoginDuration', $sLongLoginDuration);
	$oSmarty->assign('aInfo', $aInfo);
	
	echo $oSmarty->displayExtension($element_data, false);

	echo '<!-- office_timeclock :: $fTimeclockTotal = '.$fTimeclockTotal.' -->'.PHP_EOL;

}
