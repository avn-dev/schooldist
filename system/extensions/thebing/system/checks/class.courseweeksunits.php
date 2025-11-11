<?php


class Ext_Thebing_System_Checks_CourseWeeksUnits extends GlobalChecks {

	public function getTitle() 
	{
		$sTitle = 'Course Weeks Units Update';
		return $sTitle;
	}

	public function getDescription() 
	{
		$sDescription = 'Update course weeks and course units allocations.';
		return $sDescription;
	}

	public function executeCheck()
	{
		
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		try
		{
			$aErrors		= array();

			$aColumns		= DB::describeTable('kolumbus_tuition_courses', true);

			if(
				isset($aColumns['ext_35'])
			)
			{
				$sBackup		= Ext_Thebing_Util::backupTable('kolumbus_tuition_courses', true, '__kolumbus_tuition_courses_weeks_units');
				$sTable			= 'kolumbus_tuition_courses';
			}
			else
			{
				if(
					!Ext_Thebing_Util::checkTableExists('__kolumbus_tuition_courses_weeks_units')
				)
				{
					$sBackup	= false;
				}
				else
				{
					$sBackup	= true;
					$sTable		= '__kolumbus_tuition_courses_weeks_units';
				}
			}

			if(
				!$sBackup
			)
			{
				$this->_reportError('backup_failed');
				return true;
			}

			$bSuccessWeekTable = $this->_createTable('week');

			if(
				$bSuccessWeekTable !== true
			)
			{
				$aErrors['week']['table'] = $bSuccessWeekTable;
			}

			$bSuccessUnitTable = $this->_createTable('unit');

			if(
				$bSuccessUnitTable !== true
			)
			{
				$aErrors['unit']['table'] = $bSuccessUnitTable;
			}

			if(
				empty($aErrors)
			)
			{
				$sSql = "
					SELECT
						*
					FROM
						#table
					WHERE
						`active` = 1 AND
						`ext_35` != ''
				";

				$aSql = array(
					'table' => $sTable,
				);

				$aResult	= DB::getPreparedQueryData($sSql, $aSql);

				foreach($aResult as $aRowData)
				{
					$iCourseId			= $aRowData['id'];
					$sCourseUnitField	= $aRowData['ext_35'];

					if( !empty($sCourseUnitField) )
					{
						$aCourseUnitField = json_decode($sCourseUnitField);

						if( is_array($aCourseUnitField) )
						{	
							
							if( $aRowData['per_unit'] == 1 )
							{
								$sTable		= 'kolumbus_tuition_courses_to_units';
								$sColumn	= 'unit_id';
							}
							else
							{
								$sTable		= 'kolumbus_tuition_courses_to_weeks';
								$sColumn	= 'week_id';
							}
							
							foreach($aCourseUnitField as $iWeekUnitId)
							{
								$aInsert = array(
									'course_id'	=> $iCourseId,
									$sColumn	=> $iWeekUnitId,
								);

								$bSuccess = DB::insertData($sTable, $aInsert);

								if( $bSuccess === false )
								{
									$aErrors['convert'][$iCourseId][] = $iWeekUnitId;
								}
							}
						}
						else
						{
							$aErrors['convert'][$iCourseId] = $sCourseUnitField;
						}
					}
				}

				if( !empty($aErrors) )
				{
					$this->_reportError($aErrors);
				}
				elseif( isset($aColumns['ext_35']) )
				{
					$sSql = "
						ALTER TABLE 
							`kolumbus_tuition_courses` 
						DROP 
							`ext_35`;
					";
					
					$rRes = DB::executeQuery($sSql);
					
					if(
						!$rRes
					)
					{
						$this->_reportError('column_drop_failed');
					}
				}
			}
			else
			{
				$this->_reportError($aErrors);
			}
		}
		catch(DB_QueryFailedException $e)
		{
			$this->_reportError($e);
		}
		catch(Exception $e)
		{
			$this->_reportError($e);
		}
		
		return true;
	}
	
	protected function _createTable($sType)
	{	
		if( $sType == 'week' )
		{
			$sTable		= 'kolumbus_tuition_courses_to_weeks';
			$sColumn	= 'week_id';
		}
		else
		{
			$sTable		= 'kolumbus_tuition_courses_to_units';
			$sColumn	= 'unit_id';
		}
		
		$aSql = array(
			'table'		=> $sTable,
			'column'	=> $sColumn,
		);
		
		if( !Ext_Thebing_Util::checkTableExists($sTable) )
		{
			$sSql = "
				CREATE TABLE #table (
					`course_id` INT NOT NULL ,
					#column INT NOT NULL 
				) ENGINE=INNODB;
			";

			$rRes = DB::executePreparedQuery($sSql, $aSql);

			if( !$rRes )
			{
				return 'create_table_failed';
			}

			$sSql = "
				ALTER TABLE 
					#table
				ADD PRIMARY KEY ( `course_id` , #column ) 
			";

			$rRes = DB::executePreparedQuery($sSql, $aSql);

			if( !$rRes )
			{
				return 'unique_failed';
			}

			$sSql = "ALTER TABLE #table ADD INDEX `course_id` ( `course_id` ) ";

			$rRes = DB::executePreparedQuery($sSql, $aSql);

			if( !$rRes )
			{
				return 'index1_failed';
			}

			$sSql = "ALTER TABLE #table ADD INDEX #column ( #column ) ";

			$rRes = DB::executePreparedQuery($sSql, $aSql);

			if( !$rRes )
			{
				return 'index2_failed';
			}
		}
		
		$sSql = "
			TRUNCATE #table
		";

		$rRes = DB::executePreparedQuery($sSql, $aSql);

		if( !$rRes )
		{
			return 'truncate_failed';
		}
		
		return true;
	}
	
	protected function _reportError($mError)
	{
		$oMail = new WDMail();
		$oMail->subject = get_class($this);
		
		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($mError, 1)."\n\n";
		
		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
	}
	
}