<?php 

class Ext_Thebing_System_Checks_AdditionalCosts extends GlobalChecks {
	
	public function executeCheck(){
		global $user_data;
		
		try{
			Ext_Thebing_Util::backupTable('kolumbus_costs_courses');
			Ext_Thebing_Util::backupTable('kolumbus_costs_accommodations');
		}catch(Exception $e){
			
		}

		// Alle Clients
		$sSql = "SELECT id FROM kolumbus_clients WHERE active = 1";
		$aClients = DB::getQueryData($sSql);
		
		foreach((array)$aClients as $aClient){
			// Alle Schulen für diesen Client
			$sSql = "SELECT id FROM customer_db_2 WHERE active = 1 AND idClient = " . (int)$aClient['id'];
			$aSchools = DB::getQueryData($sSql);
	
			foreach((array)$aSchools as $aSchool){
	
				$aCourses = $this->getCourseList($aClient['id'], $aSchool['id']);
				$aAccommodations = $this->getAccList(false, $aClient['id'], $aSchool['id']);
	
				// Weise jedem Kurs alle Kurszusatzkosten zu und jeder Unterkunft alle Unterkunftszusatzkosten
				$sSql = "
					SELECT 
						`id`, `type`
					FROM 
						`kolumbus_costs`
					WHERE 
						`type` IN (0,1) AND
						`active` = 1 AND
						`idSchool` = " . $aSchool['id'];
				$aResultCosts = DB::getQueryData($sSql);
	
				foreach((array)$aResultCosts as $aCost){
			
					switch($aCost['type']){
						case 0:
							$sSql = "SELECT * FROM `kolumbus_costs_courses` WHERE `kolumbus_costs_id` = :cost_id";
							$aSql['cost_id'] = (int)$aCost['id'];
							$aResultCourse = DB::getPreparedQueryData($sSql,$aSql);
							
							if(empty($aResultCourse)){
								// Alle Kurse durchlaufen und eintragen
								foreach((array)$aCourses as $id => $sCourse){
									$sSql = "INSERT INTO `kolumbus_costs_courses` SET `kolumbus_costs_id` = :cost_id, customer_db_3_id = :course_id";
									$aSql['cost_id'] = (int)$aCost['id'];
									$aSql['course_id'] = (int)$id;
									DB::executePreparedQuery($sSql,$aSql);
								}
	
							}
							break;
						case 1:
							$sSql = "SELECT * FROM `kolumbus_costs_accommodations` WHERE `kolumbus_costs_id` = :cost_id";
							$aSql['cost_id'] = (int)$aCost['id'];
							$aResultAccommodation = DB::getPreparedQueryData($sSql,$aSql);
							
							if(empty($aResultAccommodation)){
								// Alle Kurse durchlaufen und eintragen
								foreach((array)$aAccommodations as $id => $sAcc){
									$sSql = "INSERT INTO `kolumbus_costs_accommodations` SET `kolumbus_costs_id` = :cost_id, customer_db_8_id = :acc_id";
									$aSql['cost_id'] = (int)$aCost['id'];
									$aSql['acc_id'] = (int)$id;
									DB::executePreparedQuery($sSql,$aSql);
								}
	
							}
							break;
					}
				}	
			}
		}

		return true;
		
	}
	
	private function getAccList($iType=false, $iClientid = 0, $iSchoolId = 0) {
		
		$sWhere = "";
		$aSql = array();
		
		if($iType !== false) {
			$sWhere .= ' AND `ext_6` = :ext_6 ';
			$aSql['ext_6'] = (int)$iType;
		}
		
		$sSql = "SELECT 
							* 
						FROM 
							#table 
						WHERE
							`active` = 1 AND
							`ext_5` = :idSchool
							".$sWhere."
						ORDER BY 
							`position`
						";
		$aSql['table'] = 'customer_db_8';
		$aSql['idSchool'] = (int)$iSchoolId;
		$aResult = DB :: getPreparedQueryData($sSql, $aSql);


		foreach ($aResult as $aAccommodation){
			
			$aBack[$aAccommodation['id']] = $aAccommodation['ext_1'];

		}
		return $aBack;
	}


	private function getCourseList($iClientid = 0, $iSchoolId = 0){
		global $user_data;
		
		$sSql = "SELECT 
			`course`.`id` as `id`,
			`course`.`ext_33` as `name`,					
			CAST(`course`.`ext_2` AS SIGNED) as `lessions_week`,
			CAST(`course`.`ext_5` AS SIGNED) as `students`,
			`course`.`ext_3` as `ext_3`,
			`course`.`ext_34` as `ext_34`,
			`category`.ext_1 as `category`,
			`course`.*
		FROM	
			`customer_db_3` as `course`
		LEFT OUTER JOIN
			`customer_db_7` as 	`category`	
		ON
			(
			`category`.`id` = 	`course`.`ext_1` AND
			`category`.`idClient` = :client
			)
		WHERE 
			`course`.active 	=	1 
		AND 
			`course`.`ext_8` 	=	:idSchool 
		AND 
			`course`.`idClient` = :client
		ORDER BY
			`position`";

		$aSql = array();
		$aSql['client'] = (int)$iClientid;
		$aSql['idSchool'] = (int)$iSchoolId;
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach ((array)$aResult as $aCourse){

			$aBack[$aCourse['id']] = $aCourse['ext_33'];
			
		}
		return $aBack;
	}
	
	
}


?>