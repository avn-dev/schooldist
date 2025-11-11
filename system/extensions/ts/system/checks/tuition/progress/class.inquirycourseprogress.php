<?php


class Ext_TS_System_Checks_Tuition_Progress_InquiryCourseProgress extends GlobalChecks {
	
	protected $_aDebug = array();
	
	protected $_aCache = array();
	
	public function getTitle() {
		return 'Tuition Progresses';
	}
	
	public function getDescription() {
		return 'Set the missing entries for journey courses in the tuition progress table';
	}
	
	public function executeCheck() {
		
		$iStart = microtime(true);
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bSuccess = Util::backupTable('kolumbus_tuition_progress');
		
		if(!$bSuccess) {			
			__pout("Couldn't backup table!"); 
			return false;
		}
		
		try {
		
			$aProgresses = $this->_getTuitionProgresses();

			$this->_aDebug['progresses'] = count($aProgresses);

			foreach($aProgresses as $aProgress) {

				$iInquiry = (int) $aProgress['inquiry_id'];
				$iLevelGroupId = (int) $aProgress['levelgroup_id'];
				$iLevel = (int) $aProgress['level'];
				$sWeek = $aProgress['week'];

				// Inquiry muss für ein Datum nicht doppelt behandelt werden
				if(isset($this->_aCache['inquiry'][$iInquiry][$sWeek])) {
					$this->_aDebug['skipped'] = 'Inquiry "'.$iInquiry.'" was approached for week "'.$sWeek.'"!';
					continue;
				}
				
				// Alle journey courses der Inquiry holen
				$aJourneyCourses = $this->_getInquiryJourneyCourses($iInquiry);
				
				$this->_aCache['inquiry'][$iInquiry][$sWeek] = true;		
				
				foreach($aJourneyCourses as $oJourneyCourse) {

					// prüfen, ob ein neuer Progress-Eintrag angelegt werden muss
					if(!$this->_checkJourneyCourse($oJourneyCourse, $iLevelGroupId, $sWeek)) {
						continue;
					}

					$aInsert = array(
						'inquiry_course_id' => $oJourneyCourse->id,
						'week' => $sWeek,
						'level' => $iLevel,
						'levelgroup_id' => $iLevelGroupId,
						'inquiry_id' => $iInquiry
					);

					// Neuen Eintrag anlegen
					DB::insertData('kolumbus_tuition_progress', $aInsert);
					$this->_aDebug['insert']['data'][] = $aInsert;
				}

				// Instanzen leeren, damit der Speicher nicht überläuft
				WDBasic::clearAllInstances();

			}
		
			$this->_aDebug['insert']['count'] = count($this->_aDebug['insert']['data']);
			
		} catch (Exception $e) {
			__pout($e);
			return false;
		}
		
		$iTotal = microtime(true) - $iStart;
		
		$this->_aDebug['total'][] = $iTotal . 's';
		
		return true;
	}
	
	/**
	 * Funktion zum debuggen
	 * 
	 * @return array
	 */
	public function debug() {
		return $this->_aDebug;
	}
	
	/**
	 * Funktion liefert alle eingetragenen Progresses, für die ein Level eingetragen wurde und
	 * die auf active=1 stehen
	 * 
	 * @return type
	 */
	protected function _getTuitionProgresses() {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`active` = 1 AND 
				`level` > 0
		";
		
		$aData = (array) DB::getQueryData($sSql);
		
		return $aData;
	}
	
	/**
	 * Funktion liefert alle journey courses der Inquiry
	 * 
	 * @param integer $iInquiry
	 * @return array
	 */
	protected function _getInquiryJourneyCourses($iInquiry) {
		
		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiry);
		
		$aData = $oInquiry->getCourses();
		
		return $aData;		
	}
	
	protected function _checkJourneyCourse(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, $iLevelGroupId, $sDate) {
		
		$aProgresses = $this->_getInquiryJourneyCourseProgress($oJourneyCourse, $iLevelGroupId, $sDate);
		if(!empty($aProgresses)) {
			$this->_aDebug['check'][] = 'Progress for journey course "'.$oJourneyCourse->id.'" in week "'.$sDate.'" already exists!';
			return false;
		}
			
		$oDate = new WDDate($sDate, WDDate::DB_DATE);
		$oDate2 = new WDDate($sDate, WDDate::DB_DATE);
		$oDate2->add(6, WDDate::DAY);
		
		$oDateFrom	= new WDDate($oJourneyCourse->from, WDDate::DB_DATE);
		$oDateUntil = new WDDate($oJourneyCourse->until, WDDate::DB_DATE);
		
		$iCompare = WDDate::comparePeriod($oDate, $oDate2, $oDateFrom, $oDateUntil);
		
		if(
			$iCompare == WDDate::PERIOD_AFTER ||
			$iCompare == WDDate::PERIOD_BEFORE
		) {
			$this->_aDebug['check'][] = '"'.$sDate.'" does not match in journey course "'.$oJourneyCourse->id.'" period ("'.$oJourneyCourse->from.'" - "'.$oJourneyCourse->until.'")!';
			return false;
		}			
		
		$oCourse = $oJourneyCourse->getCourse();
		$oLevelGroup = $oCourse->getLevelgroup();
		if($oLevelGroup->id != $iLevelGroupId) {
			$this->_aDebug['check'][] = 'Journey course "'.$oJourneyCourse->id.'" has not the same levelgroup (has: '.$oLevelGroup->id.' - expect:'.$iLevelGroupId.')!';
			return false;
		}
		
		return true;
	}
	
	protected function _getInquiryJourneyCourseProgress(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, $iLevelGroupId, $sDate) {
		
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`active` = 1 AND 
				`inquiry_course_id` = :inquiry_course_id AND
				`week` = :week AND
				`levelgroup_id` = :levelgroup_id
		";
		
		$aSql = array(
			'inquiry_course_id' => $oJourneyCourse->id,
			'week' => $sDate,
			'levelgroup_id' => $iLevelGroupId
		);
		
		$aData = (array) DB::getQueryCol($sSql, $aSql);
		
		return $aData;
	}
	
}