<?php

/**
 * Die Klasse um Anwesenheitseinträge aus dem Index zu holen/berechnen
 * 
 * @author Mehmet Durmaz
 */
class Ext_Thebing_Tuition_Attendance_Index
{
//	public function __construct()
//	{
//		#$oSearch		= new Ext_WDSearch(self::getAttendanceIndexName());
//		
//		#$this->_oSearch = $oSearch;
//		#$this->_oSearch->setFields(array('*'));
//	}
	
//	/**
//	 * Anwesenheits Index-Name
//	 * 
//	 * @return string 
//	 */
//	public static function getAttendanceIndexName()
//	{
//		$sIndexName = Ext_Gui2_Index_Generator::createIndexName('ts_attendance');
//		
//		return $sIndexName;
//	}

	/**
	 * Anwesenheit aus dem Index bekommen pro gebuchter Kurs
	 *
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @param bool $bOriginalValue
	 * @param array $aFilter
	 * @return array|string
	 */
	public function getAttendanceForJourneyCourse(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, $bOriginalValue = true, array $aFilter = array()) {
		$aSql = array();
		
		$aFilter['journey_course_id'] = '`ktbic`.`inquiry_course_id`';
		
		if(isset($aFilter['week'])) {
			$aFilter['week_filter'] = array(
				'week' => ':week'
			);
			
			$aSql['week'] = $aFilter['week'];
		} elseif(isset($aFilter['week_from']) && isset($aFilter['week_until'])) {
			$aFilter['week_filter'] = array(
				'week_from' => ':week_from',
				'week_until' => ':week_until',
			);
			
			$aSql['week_from'] = $aFilter['week_from'];
			$aSql['week_until'] = $aFilter['week_until'];
		}

		// journey_course_only: Hier kommt der Kombinationskurs rein und dieser muss komplett betrachtet werden
		$sAttendanceSql = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course_only', $aFilter);
		
		$sSql = "
			SELECT
				(
					".$sAttendanceSql."
				)
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic`
			WHERE
				`ktbic`.`inquiry_course_id` = :journey_course_id AND
				`ktbic`.`active` = 1
		";
		
		$aSql['journey_course_id'] = $oJourneyCourse->id;

		$fAttendance = DB::getQueryOne($sSql, $aSql);

		if(!$bOriginalValue) {
			$oFormat = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent();
			$fAttendance = $oFormat->format($fAttendance);
		}

		return $fAttendance;
	}

	/**
	 * Anwesenheit aus dem Index bekommen pro Buchung
	 * 
	 * @todo Warum ist der Query so kompliziert mit Subquery? getAttendanceSql liefert doch schon den richtigen Wert!
	 * 
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param bool $bOriginalValue
	 * @param array $aFilter
	 * @return float|string
	 */
	public static function getAttendanceForInquiry(Ext_TS_Inquiry $oInquiry, $bOriginalValue = true, $aFilter = []) {

		$aFilter['inquiry_id'] = "`ts_i_j`.`inquiry_id`";

		$sAttendanceSql = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', $aFilter);
		
		$sSql = "
			SELECT
				(
					".$sAttendanceSql."
				)
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_i_j_c` ON
					`ts_i_j_c`.`id` = `ktbic`.`inquiry_course_id` AND
					`ts_i_j_c`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_i_j_c`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1
			WHERE
				`ts_i_j`.`inquiry_id` = :inquiry_id AND
				`ktbic`.`active` = 1
		";
		
		$aSql = array(
			'inquiry_id' => $oInquiry->id,
		);
		
		$fAttendance = (float)DB::getQueryOne($sSql, $aSql);

		if(!$bOriginalValue) {
			$oFormat = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent();
			$fAttendance = $oFormat->format($fAttendance);
		}

		return $fAttendance;
	}

	public function getAttendanceForJourneyCourseProgramService(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $bOriginalValue = true, array $aFilter = array()) {
		$aSql = [];

		if(isset($aFilter['week'])) {
			$aFilter['week_filter'] = ['week' => ':week'];
			$aSql['week'] = $aFilter['week'];
		} elseif(isset($aFilter['week_from']) && isset($aFilter['week_until'])) {
			$aFilter['week_filter'] = ['week_from' => ':week_from', 'week_until' => ':week_until'];
			$aSql['week_from'] = $aFilter['week_from'];
			$aSql['week_until'] = $aFilter['week_until'];
		}

		$aFilter['program_service_id'] = '`ktbic`.`program_service_id`';
		$aFilter['journey_course_id'] = '`ktbic`.`inquiry_course_id`';

		if (isset($aFilter['holiday_splittings']) && $aFilter['holiday_splittings'] === true) {
			$slittedJourneyCourseIds = array_map(fn($oJourneyCourse) => $oJourneyCourse->id, $oJourneyCourse->getRelatedServices());
			if (count($slittedJourneyCourseIds) > 1) {
				$aFilter['journey_course_id'] = 'RAW:IN (:journey_course_ids)';
				$aSql['journey_course_ids'] = $slittedJourneyCourseIds;
			}
		}

		$sAttendanceSql = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course_program_service', $aFilter);

		$sSql = "
			SELECT
				(
					".$sAttendanceSql."
				)
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic`
			WHERE
				`ktbic`.`inquiry_course_id` = :inquiry_course_id AND
				`ktbic`.`program_service_id` = :program_service_id AND
				`ktbic`.`active` = 1
		";

		$aSql['inquiry_course_id'] = $oJourneyCourse->getId();
		$aSql['program_service_id'] = $oProgramService->getId();

		$fAttendance = DB::getQueryOne($sSql, $aSql);

		if(!$bOriginalValue) {
			$oFormat = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent();
			$fAttendance = $oFormat->format($fAttendance);
		}

		return $fAttendance;
	}

	/**
	 * Anwesenheit beim Lehrer pro Buchung
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param Ext_Thebing_Teacher $oTeacher
	 * @param array $aFilter
	 * @return float|null
	 */
	public static function getAttendanceForInquiryAndTeacher(Ext_TS_Inquiry $oInquiry, Ext_Thebing_Teacher $oTeacher, array $aFilter=[]) {

		$aSql = [
			'inquiry_id' => $oInquiry->id,
			'teacher_id' => $oTeacher->id
		];

		// Bisher ist nur Woche eingebaut, ansonsten siehe getAttendanceForJourneyCourse()
		if(!empty($aFilter['week'])) {
			$aSql['week_filter'] = [
				'week' => ':week'
			];

			$aSql['week'] = $aFilter['week'];
		}

		$sAttendanceSql = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry_teacher', $aSql);
		$fAttendance = DB::getQueryOne($sAttendanceSql, $aSql);

		return $fAttendance;
	}

	/**
	 * Anwesenheit für Buchungskontakt: Anwesenheit über ALLE Buchungen des Kontakts
	 *
	 * @param Ext_TS_Inquiry_Contact_Abstract $oContact
	 * @return float|null
	 */
	public static function getAttendanceForInquiryContact(Ext_TS_Inquiry_Contact_Abstract $oContact) {

		$aInquiryIds = $oContact->getInquiries(false, false);

		$aFields = [
			'inquiry_id' => $aInquiryIds,
			'multiple' => true
		];

		$sSql = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', $aFields);

		$fAttendance = DB::getQueryOne($sSql);

		if($fAttendance !== null) {
			return (float)$fAttendance;
		}

		return null;

	}

	/**
	 * Funktion um im Index nach bestimmten Kriterien (nur direkte Suche) zu suchen
	 * 
	 * @param string $sField
	 * @param mixed $mFieldValue
	 * @return type 
	 */
	public function search(array $aSearch, $iLimit = false, $mSelect = '`kta`.`id`', array $aCheckDays = [])
	{
		$aSelect = (array)$mSelect;
		
		$sSelect = '';
		
		foreach($aSelect as $sColumn)
		{
			$sSelect .= $sColumn . ',';
		}
		
		$sSelect = substr($sSelect, 0, -1);
		
		$sSql = "
			SELECT
				".$sSelect."
			FROM
				`kolumbus_tuition_attendance` `kta` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`id` = `kta`.`allocation_id` AND
					`ktbic`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_i_j_c` ON
					`ts_i_j_c`.`id` = `ktbic`.`inquiry_course_id` AND
					`ts_i_j_c`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_i_j_c`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
					`ts_i`.`active` = 1
			WHERE
				`kta`.`active` = 1 AND
				`ts_i`.`canceled` <= 0
		";

		if (!empty($aCheckDays)) {
			// Nur bestimmte Tage prüfen
			$sSql .= " AND
				(
			";

			$sSql .= implode(" OR ", array_map(fn ($sDay) => "`kta`.`".$sDay."` IS NOT NULL", $aCheckDays));

			$sSql .= "
				)
			";
		}

		$iCounter = 1;
		
		$aSql = array();
		
		foreach($aSearch as $sField => $mFieldValue)
		{
			$sOperator = '=';
			
			if(is_array($mFieldValue))
			{
				if(isset($mFieldValue['operator']))
				{
					$sOperator = $mFieldValue['operator'];
				}
				
				if(isset($mFieldValue['value']))
				{
					$mFieldValue = $mFieldValue['value'];
				}
			}
			
			$sSql .= " AND
				".$sField." ".$sOperator." :field_value_".$iCounter."	
			";
			
			$aSql['field_value_' . $iCounter] = $mFieldValue;
			
			$iCounter++;
		}

		if($iLimit)
		{
			$sSql .= " LIMIT " . $iLimit;
			
			$aResult = DB::getQueryOne($sSql, $aSql);
		}
		else
		{
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
		}

		return $aResult;
	}

	/**
	 * Das Array umformatieren um direkt die Spaltenwerte zu bekommen
	 * 
	 * @param array $aIndexResult
	 * @return array 
	 */
	protected function _getFirstResult(array $aIndexResult)
	{
		if(
			is_array($aIndexResult) &&
			isset($aIndexResult['hits']) &&
			isset($aIndexResult['hits'][0]) &&
			isset($aIndexResult['hits'][0]['fields'])
		)
		{
			return $aIndexResult['hits'][0]['fields'];
		}
	}
	
	/**
	 * Im Index suchen ob irgendwelche Einträge mit einer bestimmten Klassenplanungs-Vorlage exitieren
	 * 
	 * @param Ext_Thebing_Tuition_Template $oTemplate
	 * @return bool 
	 */
	public function hasEntriesWithTemplate(Ext_Thebing_Tuition_Template $oTemplate)
	{
		if($oTemplate->id <= 0)
		{
			return false;
		}
		
		$aResult = $this->search(array(
			'`ktb`.`template_id`'		=> (int)$oTemplate->id,
		), 1);
		
		if(
			!empty($aResult)
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Im Index suchen ob irgendwelche Einträge mit einer bestimmten Klassenplanungs-Blöcken exitieren
	 * 
	 * @param Ext_Thebing_School_Tuition_Block $oTemplate
	 * @param array $aCheckDays
	 * @return bool
	 */
	public function hasEntriesWithBlock(Ext_Thebing_School_Tuition_Block $oBlock, array $aCheckDays = [])
	{
		if($oBlock->id <= 0) {
			return false;
		}
		
		$aResult = $this->search(
			aSearch: ['`ktbic`.`block_id`' => (int)$oBlock->id],
			iLimit: 1,
			aCheckDays: $aCheckDays
		);

		if(!empty($aResult)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param int $iAllocationId
	 * @return array 
	 */
	public function findById($iAllocationId)
	{
		$aIndexResult = $this->search(array(
			'id' => $iAllocationId,
		));
		
		$aFirstResult = $this->_getFirstResult($aIndexResult);
		
		return $aFirstResult;
	}
	
	/**
	 * Alle Zuweisungen in der Klassenplanung Finden pro Buchung
	 * 
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array 
	 */
	public function getAllocationIdsByInquiry(Ext_TS_Inquiry $oInquiry)
	{
		$aIds = $this->_getIdResult(array(
			'inquiry_id' => $oInquiry->id
		));

		return $aIds;
	}
	
	/**
	 * Alle Zuweisungen in der Klassenplanung Finden pro gebuchten Kurs
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return array 
	 */
	public function getAllocationIdsByJourneyCourse(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, $dWeek = false)
	{
		$aSearch = array(
			'journey_course_id' => $oJourneyCourse->id
		);
		
		if($dWeek)
		{
			$aSearch['block_week'] = $dWeek;
		}
		
		$aIds = $this->_getIdResult($aSearch);

		return $aIds;
	}
	
	/**
	 * Nur die Hauptids zurück liefern bei einem bestimmten Query
	 * 
	 * @param type $sField
	 * @param type $iId
	 * @return type 
	 */
	protected function _getIdResult($aSearch)
	{
		$this->_oSearch->setFields(array('id'));
		
		$aIndexResult	= (array)$this->search($aSearch);
		
		$aBack			= array();
		
		if(isset($aIndexResult['hits']))
		{
			$aHits = (array)$aIndexResult['hits'];
			
			foreach($aHits as $aIndexData)
			{
				$iId = (int)reset($aIndexData['fields']);
				
				$aBack[] = $iId;
			}
		}

		return $aBack;
	}
	
	/**
	 * Ersten Datensatz nach gebuchten Kurs laden
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return array 
	 */
	public function getFirstEntryByJourneyCourse(Ext_TS_Inquiry_Journey_Course $oJourneyCourse)
	{
		$aIndexResult	= $this->search(array(
			'journey_course_id' => (int)$oJourneyCourse->id
		), 1);
		
		$aFirst			= $this->_getFirstResult($aIndexResult);
		
		return $aFirst;
	}
	
	/**
	 * Ersten Datensatz nach Buchung laden
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return array 
	 */
	public function getFirstEntryByInquiry(Ext_TS_Inquiry $oInquiry)
	{
		$aIndexResult	= $this->search(array(
			'inquiry_id' => (int)$oInquiry->id
		), 1);
		
		$aFirst			= $this->_getFirstResult($aIndexResult);
		
		return $aFirst;
	}
	
	/**
	 * Von mehrfach dimensionalem Ergebnis array einen einfach dimensionalen array machen, felder wie hits etc interessieren uns nicht
	 */
	public function calculateAverageScore(array $aSearch)
	{
		if(isset($aSearch['journey_course_id']))
		{
			$aSearch['`ts_i_j_c`.`id`'] = $aSearch['journey_course_id'];
			
			unset($aSearch['journey_course_id']);
		}
		
		if(isset($aSearch['inquiry_id']))
		{
			$aSearch['`ts_i`.`id`'] = $aSearch['inquiry_id'];
			
			unset($aSearch['inquiry_id']);
		}
		
		$aResult = $this->search($aSearch, false, '`kta`.`score`');
		
		foreach($aResult as $aRowData)
		{
			$mScore		= $aRowData['score'];
			
			$aScores[]	= $mScore;
		}
		
		$sScore = Ext_Thebing_Util::getAverageFromFormattedValue($aScores);
		
		return $sScore;
	}
}
