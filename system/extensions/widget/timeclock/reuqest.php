<?

include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/autoload.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
$time_start = getmicrotime();
$session_data['public'] = 1;
include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
include(\Util::getDocumentRoot()."system/includes/access.inc.php");

try {

	require(\Util::getDocumentRoot()."system/extensions/office_timeclock.mod");

	$aTemp = array();
	$aTemp['login'] = 0;
	$aTemp['projects'] = array();
	$aTemp['activities'] = array();
	
	if($iProjectID <= 0){
		$iProjectID = 0;
	}
	
	if($iActivityID <= 0){
		$iActivityID = 0;
	}
	
	if($user_data['id'] > 0){
		$aTemp['login'] = 1;
	}	
		
	if(!empty($aProjects)){
		$aTemp['projects'] = $aProjects;
	}
	
	$aActivities_ = array();
	
	foreach($aActivities as $aTemp2){
		$sTemp = "";
		if($aTemp2['alias'] != ""){
			$sTemp = $aTemp2['alias'].' - ';
		}
		$sTemp .= $aTemp2['title'];
		$aActivities_[$aTemp2['id']] = $sTemp;
	}
	
	if(!empty($aActivities_)){
		$aTemp['activities'] = $aActivities_;
	}
	
	$aTemp['iProjectID'] = $iProjectID;
	$aTemp['iActivityID'] = $iActivityID;
	
	echo json_encode($aTemp);
	
} catch (Exception $e) {
	wdmail('cw@plan-i.de', 'widget debug', print_r($e->getMessage(),1));
}










die();

//$oTimeClock = new Ext_Office_Timeclock();

$aTemp = array();

//wdmail('cw@plan-i.de', 'widget debug', print_r($session_data,1));

if($user_data['id'] > 0){
	
	$aTemp['login'] = 1;
	
	$iEmployeeID = $user_data['id'];
	
	// Get projects by employee ID
	$sSQL = "
		SELECT
			`ope`.`project_id`,
			`op`.`title`
		FROM
			`office_project_employees`		AS `ope`
				INNER JOIN
			`office_projects`				AS `op`
				ON
			`ope`.`project_id` = `op`.`id`
		WHERE
			`ope`.`employee_id` = :iEmployeeID
				AND
			UNIX_TIMESTAMP(`op`.`closed_date`) = 0
		ORDER BY
			`op`.`title`
	";
	$aProjects = DB::getQueryPairs($sSQL, array('iEmployeeID' => $iEmployeeID));
	
	$aActivities = array();
	$iProjectID = $_VARS['iProjectId'];
	
	if($iProjectID > 0){
		
		// Get activities by project ID
		$sSQL = "
			SELECT
				`opp`.`id`,
				`opc`.`title`,
				`opc`.`time_flag`,
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
					AND
				`opp`.`active` = 1
		";
		$aActivities = DB::getQueryPairs($sSQL, array('iProjectID'	=> $iProjectID));
		
		foreach($aActivities as $iKey => $aValue)
		{
			if($aValue['time_flag'] == 0)
			{
				unset($aActivities[$iKey]);
			}
		}
		
	}
	
	
} else {
	$aTemp['login'] = 0;
}

if(!empty($aProjects)){
	$aTemp['projects'] = $aProjects;
}

if(!empty($aActivities)){
	$aTemp['activities'] = $aActivities;
}

echo json_encode($aTemp);

?>