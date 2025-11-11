<?php 
/**
 * Der Scheck cached Level IDs damit bei der Klassenplanung die korrekten Levels angezeigt werden können
 */
class Ext_Thebing_System_Checks_CourseLevel extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update Course Levels';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Course Level Caching';
		return $sDescription;
	}

	public function executeCheck() {

		set_time_limit(3600 * 4);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_progress');
		
		try{
			
			$aTableData = DB::describeTable('kolumbus_tuition_progress', true);

			if(!isset($aTableData['inquiry_id']))
			{
				$sSql = "
					ALTER TABLE 
						`kolumbus_tuition_progress` 
					ADD 
						`inquiry_id` MEDIUMINT( 9 ) NOT NULL
				";
				
				DB::executeQuery($sSql);
			}
			
			if(!isset($aTableData['levelgroup_id']))
			{
				$sSql = "
					ALTER TABLE 
						`kolumbus_tuition_progress` 
					ADD 
						`levelgroup_id` MEDIUMINT( 9 ) NOT NULL
				";
				
				DB::executeQuery($sSql);
			}
			
			$this->dropIndex('inquiry_id');
			
			$this->dropIndex('levelgroup_id');
			
			$this->dropIndex('inquiry_course_id');
			
			$this->dropIndex('ktp_unique1');

			$this->addIndex('inquiry_id');
			
			$this->addIndex('levelgroup_id');

			$aSql = array();

			$sSql = "SELECT
							*
						FROM
							`kolumbus_tuition_progress`
					";

			$oDB = DB::getDefaultConnection();

			$aResult = $oDB->getCollection($sSql, $aSql);

			foreach($aResult as $aData){

				// Inquiry Id holen
				$sSql = "SELECT
								`ts_i_j`.`inquiry_id`,
								`k_t_l_c`.`levelgroup_id`
							FROM
								`ts_inquiries_journeys_courses` `ts_i_j_c` INNER JOIN
								`ts_inquiries_journeys` `ts_i_j` ON
									`ts_i_j`.`id` = `ts_i_j_c`.`journey_id`  LEFT JOIN
								`kolumbus_course_combination` `k_c_c` ON
									`k_c_c`.`master_id` = `ts_i_j_c`.`course_id` LEFT JOIN
								`kolumbus_tuition_levelgroups_courses` `k_t_l_c` ON
									`k_t_l_c`.`course_id` = IF(
																`k_c_c`.`course_id` IS NOT NULL,
																`k_c_c`.`course_id`,
																`ts_i_j_c`.`course_id`
															) 
							WHERE
								`ts_i_j_c`.`id` = :journey_course_id";

				$aSql = array();
				$aSql['journey_course_id'] = (int)$aData['inquiry_course_id'];

				$aMainData = DB::getQueryRow($sSql, $aSql);

				// Neue Spalten updaten
				$sSql = "UPDATE
								`kolumbus_tuition_progress`
							SET
								`inquiry_id` = :inquiry_id,
								`levelgroup_id` = :levelgroup_id
							WHERE
								`id` = :id
						";
				$aSql = array();
				$aSql['id']				= (int)$aData['id'];
				$aSql['inquiry_id']		= (int)$aMainData['inquiry_id'];
				$aSql['levelgroup_id']	= (int)$aMainData['levelgroup_id'];

				DB::executePreparedQuery($sSql, $aSql);


			}
			
			$this->clearUnique();

			$this->addIndex('ktp_unique1', array('inquiry_id', 'levelgroup_id', 'week'), true);
			
			// Bei manchen Mandanten, war dieses Feld noch auf timestamp...
			$sSql = "
				ALTER TABLE 
					`kolumbus_tuition_progress` 
				CHANGE 
					`week` `week` DATE NOT NULL DEFAULT '0000-00-00'
			";
			
			DB::executeQuery($sSql);
		
		}catch(Exception $e){
			
			__out($e->getMessage());
			
			return false;
		}

		return true;
		
	}	
	
	public function hasIndex($sIndexName)
	{
		$bExist = false;
		
		$sSql = "
			SHOW INDEX FROM
				`kolumbus_tuition_progress` 
		";

		$aIndexData = (array)DB::getQueryRows($sSql);
		
		foreach($aIndexData as $aData)
		{
			if($aData['Key_name'] == $sIndexName)
			{
				$bExist = true;
			}
		}
		
		return $bExist;
	}
	
	public function dropIndex($sIndexName)
	{
		if($this->hasIndex($sIndexName))
		{
			$sSql = "
				ALTER TABLE 
					`kolumbus_tuition_progress` 
				DROP INDEX
					#index_name
			";
			
			$aSql = array(
				'index_name' => $sIndexName,
			);

			DB::executePreparedQuery($sSql, $aSql);
		}
	}
	
	public function addIndex($sIndexName, $aFields = false, $bUnique = false)
	{
		if(!$aFields)
		{
			$aFields = array($sIndexName);
		}
		
		if($bUnique)
		{
			$sType = 'UNIQUE';
		}
		else
		{
			$sType = 'INDEX';
		}
		
		$sFields = '';
		
		foreach($aFields as $sField)
		{
			$sFields .= '`' . $sField . '`';
			$sFields .= ',';
		}
		
		$sFields = substr($sFields, 0, -1);
		
		$sSql = "
			ALTER TABLE 
				`kolumbus_tuition_progress` 
			ADD ".$sType."
				#index_name(".$sFields.")
		";

		$aSql = array(
			'index_name'	=> $sIndexName,
			'index_fields'	=> $sFields,
		);

		$rRes = DB::executePreparedQuery($sSql, $aSql);
		
		if(!$rRes && $bUnique)
		{
			throw new Exception('Query failed!');
		}
	}
	
	public function clearUnique()
	{
		$sSql = "
			DELETE FROM
				`kolumbus_tuition_progress`
			WHERE
				`week` = '0000-00-00' OR
				`week` = '0000-00-00 00:00:00'
		";
		
		DB::executeQuery($sSql);
		
		$aSql = array();

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_progress`
			GROUP BY
				`inquiry_id`,
				`levelgroup_id`,
				`week`
		";

		$oDB			= DB::getDefaultConnection();

		$oCollection	= $oDB->getCollection($sSql, $aSql);
		
		$aDeleted		= array();
		
		foreach($oCollection as $aRowData)
		{
			$sSql = "
				DELETE FROM
					`kolumbus_tuition_progress`
				WHERE
					`inquiry_id` = :inquiry_id AND
					`levelgroup_id` = :levelgroup_id AND
					DATE(`week`) = :week AND
					`id` != :self_id
			";
			
			$oDateTime	= new DateTime($aRowData['week']);
			
			$sDate		= $oDateTime->format('Y-m-d');
			
			$aSql = array(
				'inquiry_id'	=> $aRowData['inquiry_id'],
				'levelgroup_id' => $aRowData['levelgroup_id'],
				'week'			=> $sDate,
				'self_id'		=> $aRowData['id'],
			);
			
			DB::executePreparedQuery($sSql, $aSql);
		}
	}

}
