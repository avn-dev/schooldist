<?php

class Ext_TS_System_Checks_Tuition_Progress_CombinationCourse extends GlobalChecks {
	
	public function getTitle() {
		return 'Tuition progress';
	}
	
	public function getDescription() {
		return 'Prepares the database structure of tuition progresses';
	}
	
	public function executeCheck() {
			
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bSuccess = Util::backupTable('kolumbus_tuition_progress');
		
		if(!$bSuccess) {			
			__pout("Couldn't backup table!"); 
			return false;
		}
		
		DB::begin('Ext_TS_System_Checks_Tuition_Progress_CombinationCourse');
		
		try {
			
			$aProgresses = $this->_getTutitonProgresses();
			$aCourseLevelGroups = $this->_getCourseLevelGroupMapping();
			$aCombinedCourses = $this->_getCombinedCourses();

			$aCache = array();
			
			foreach($aProgresses as $iProcess => $aProgress) {
				
				if(isset($aCache[$iProcess])) {
					continue;
				}
				
				$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int) $aProgress['inquiry_course_id']);

				if(isset($aCombinedCourses[$oInquiryCourse->course_id])) {
					
					foreach($aCombinedCourses[$oInquiryCourse->course_id] as $iCombinedCourse) {
						
						$iLevelGroup = 0;
						if(isset($aCourseLevelGroups[$iCombinedCourse])) {
							$iLevelGroup = (int) $aCourseLevelGroups[$iCombinedCourse];
						}
						
						$iExistingProcess = $this->_getProgress($aProgress['inquiry_id'], $aProgress['inquiry_course_id'], $aProgress['week'], $iLevelGroup);
						
						if($iExistingProcess > 0) {
							DB::updateData('kolumbus_tuition_progress', array('course_id' => $iCombinedCourse), ' id = '.(int)$iExistingProcess);
							$aCache[$iExistingProcess] = true;
						} else {
							$aData = $aProgress;
							$aData['course_id'] = (int)$iCombinedCourse;
							$aData['levelgroup_id'] = (int)$iLevelGroup;
							$aData['id'] = null;
							$bInsert = DB::insertData('kolumbus_tuition_progress', $aData);
							if($bInsert === false) {
								throw new Exception('Unable to insert data!');
							}
						}	
								
					}

				} else {	
					DB::updateData('kolumbus_tuition_progress', array('course_id' => (int)$oInquiryCourse->course_id), ' id = '.(int)$aProgress['id']);
				}

				// Instanzen leeren, damit der Speicher nicht überläuft
				WDBasic::clearAllInstances();
				
			}

		} catch (Exception $e) {
			__pout($e);
			DB::rollback('Ext_TS_System_Checks_Tuition_Progress_CombinationCourse');
			return false;
		}
		
		DB::commit('Ext_TS_System_Checks_Tuition_Progress_CombinationCourse');
		
		return true;		
	}
	
	protected function _getTutitonProgresses() {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_progress`
			WHERE	
				`course_id` = 0 AND
				`active` = 1
		";
		
		$aData = (array) DB::getQueryData($sSql);
		
		$aReturn = array();
		foreach($aData as $aProgress) {
			$aReturn[$aProgress['id']] = $aProgress;
		}
		
		return $aReturn;
	}
	
	protected function _getCourseLevelGroupMapping() {
		
		$sSql = "
			SELECT 
				*
			FROM
				`kolumbus_tuition_levelgroups_courses`
		";
		
		$aData = (array)DB::getQueryData($sSql);
		
		$aReturn = array();
		foreach($aData as $aAllocation) {
			$aReturn[$aAllocation['course_id']] = $aAllocation['levelgroup_id'];
		}
		
		return $aReturn;
	}
	
	protected function _getCombinedCourses() {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_course_combination`
		";
		
		$aData = (array) DB::getQueryData($sSql);
		
		$aReturn = array();
		foreach($aData as $aEntry) {
			$aReturn[$aEntry['master_id']][] = $aEntry['course_id'];
		}
		
		return $aReturn;
	}

	protected function _getProgress($iInquiry, $iInquiryCourseId, $sWeek, $iLevelgroup) {
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`week` = :week AND
				`inquiry_course_id` = :inquiry_course_id AND
				`levelgroup_id` = :levelgroup_id
			LIMIT
				1
		";
		
		$aProgress = array(
			'inquiry_id' => $iInquiry,
			'inquiry_course_id' => $iInquiryCourseId,
			'week' => $sWeek,
			'levelgroup_id' => $iLevelgroup
		);
		
		$iId = (int) DB::getQueryOne($sSql, $aProgress);
		
		return $iId;
		
	}

}