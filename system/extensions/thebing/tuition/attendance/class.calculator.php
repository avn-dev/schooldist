<?php

class Ext_Thebing_Tuition_Attendance_Calculator {

	/**
	 * @var string
	 */
	protected $_sFrom;

	/**
	 * @var string
	 */
	protected $_sUntil;

	/**
	 * Modus der Berechnung (journey_course, inquiry, journey_course_teacher)
	 * @var string
	 */
	protected $_sMode = 'journey_course';

	/**
	 * Verfügbare Modi
	 * @var type 
	 */
	protected $_aModes = array(
		'journey_course',		 
		'inquiry', 
		'journey_course_teacher'
	);
	
	/**
	 *
	 * Alle Klassenplanung Zuweisungen
	 * 
	 * @var array 
	 */
	protected $_aAllocations = array();

	/**
	 * Nach Zeitraum filtern?
	 * @var bool
	 */
	protected $_bFilterPeriod = false;
	
	/**
	 * Setzt den Zeitraum
	 * @param string $sFrom
	 * @param string $sUntil
	 * @throws Exception 
	 */
	public function setPeriod($sFrom, $sUntil) {

		$bCheckFrom = WDDate::isDate($sFrom, WDDate::DB_DATE);
		if(!$bCheckFrom) {
			throw new Exception('First argument is no valid date!');
		}
		
		$bCheckUntil = WDDate::isDate($sUntil, WDDate::DB_DATE);
		if(!$bCheckUntil) {
			throw new Exception('Second argument is no valid date!');
		}
		
		$this->_bFilterPeriod = true;
		
		$this->_sFrom = $sFrom;
		
		$this->_sUntil = $sUntil;
		
	}
	
	/**
	 * Setzt den Modus für die Berechnung
	 * 
	 * @param string $sMode
	 * @throws Exception 
	 */
	public function setMode($sMode) {
		
		if(!in_array($sMode, $this->_aModes)) {
			throw new Exception('Invalid mode!');
		}
		
		$this->_sMode = $sMode;
		
	}
	
	/**
	 *
	 * Zuweisung setzen
	 * 
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation 
	 */
	public function addAllocation(Ext_Thebing_School_Tuition_Allocation $oAllocation)
	{
		$this->_aAllocations[] = $oAllocation;
	}
	
	/**
	 * Führt die Berechnung der Anwesenheit durch
	 * @return float Anwesenheit in Prozent
	 */
	public function calculate() {
		
		$aAllocations		= $this->_aAllocations;
		$fSumAttendance		= 0;
		$fSumLessonDuration	= 0;

		foreach($aAllocations as $oAllocation)
		{
			$aAttendances = $oAllocation->getAttendances($this->_sFrom, $this->_sUntil);

			if(!empty($aAttendances))
			{
				// Falls Anwesenheit eingetragen wurde
				
				$fSumAttendance += array_sum($aAttendances);
				
				$aDurations = $oAllocation->getLessonDurations($this->_sFrom, $this->_sUntil);
				
				$fSumLessonDuration += array_sum($aDurations);
			}
		}

		$fAttendance = -1;

		if($fSumLessonDuration > 0)
		{
			$fPercent			= $fSumAttendance / $fSumLessonDuration;
			
			$fAttendance = 100 - ($fPercent * 100);
			$fAttendance = round($fAttendance, 2);
		}

		return $fAttendance;
	}

}