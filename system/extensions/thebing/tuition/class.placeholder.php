<?php
			
class Ext_Thebing_Tuition_Placeholder extends Ext_Thebing_Placeholder {
	
	// The GUI description
	protected static $_sDescription	= 'Thebing » Tuition » Own overview';

	// The report object
	protected $_oReport;

	// The date object
	protected $_oDate;

	// The school object
	protected $_oSchool;

	// The placeholders
	protected $_aPlaceholders;

	protected $aValue = [];

	/* ==================================================================================================== */

	/**
	 * The constructor
	 *
	 * @param object $oReportID
	 * @param object $oDate
	 * @param array $aPlaceholders
	 */
	public function  __construct($oReport = null, $oDate = null, $aPlaceholders = null, $aValue=[]) {
		
		if(is_null($oReport)) {
			return;
		}

		$this->_oReport = $oReport;

		$this->_oDate = $oDate;

		$this->_aPlaceholders = $aPlaceholders;

		$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();

		$this->aValue = $aValue;
		
		parent::__construct();
		
	}

	/* ==================================================================================================== */

	/**
	 * Get the list of available placeholders
	 * 
	 * @return array
	 */
	public function getPlaceholders($sType = '') {
		
		$aList = self::getPlaceholderList();

		$aPlaceholders = array();

		foreach((array)$aList as $sKey => $aValue) {
			$aPlaceholders[$sKey] = $aValue['title'];
		}

		$aReturn = array(
			array(
				'section'		=> L10N::t('Klassenplanung', self::$_sDescription),
				'placeholders'	=> $aPlaceholders
			)
		);

		return $aReturn;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = '';

		switch($sPlaceholder) {
			case 'current_week':
				$aLimit  = $this->_oDate->getWeekLimits();
				$sValue  = L10N::t('Woche', self::$_sDescription) . ' ';
				$sValue .= $this->_oDate->get(WDDate::WEEK) . ', ';
				$sValue .= Ext_Thebing_Format::LocalDate($aLimit['start']) . ' - ';
				$sValue .= Ext_Thebing_Format::LocalDate($aLimit['end']);
				break;
			case 'school_name':
				$sValue = $this->_oSchool->ext_1;
				break;
			case 'weekdays_times':
				$sValue = implode('<br />', (array)$this->_aPlaceholders[$sPlaceholder][0]);
				break;
			case 'times':
				$sValue = implode('<br />', (array)$this->_aPlaceholders[$sPlaceholder][0]);
				break;
			case 'class_to':
				$sDate = reset($this->_aPlaceholders['class_from'][0]);
				$sValue = Ext_Thebing_Format::LocalDate($sDate);
				
				if($sPlaceholder === 'class_to') {
					$iWeeks = reset($this->_aPlaceholders['class_to'][0]);
					$sValue = Ext_Thebing_Util::getUntilDateOfCourse($sValue, $iWeeks, $this->_oSchool->id);
				}
				
				break;
			case 'total_number_of_students_per_class':
				$sValue = 0;
				foreach((array)$this->_aPlaceholders[$sPlaceholder] as $iKey => $aValue) {
					$sValue += array_sum((array)$aValue);
				}
				break;
			case 'last_course_end_date':
				$inquiry = \Ext_TS_Inquiry::getInstance($this->aValue['inquiry_id']);
				$lastCourseEnd = $inquiry->getLastCourseEnd();
				$sValue = Ext_Thebing_Format::LocalDate($lastCourseEnd);
				break;
			case 'class_name':
			case 'weekdays':
			case 'teacher_name':
			case 'teacher_firstname':
			case 'teacher_lastname':
			case 'student_name':
			case 'room':
			case 'building':
			case 'floor':
			case 'level':
			case 'courses':
			case 'class_content':
			default:
				if(is_array($this->_aPlaceholders[$sPlaceholder][0])){
					
					$aTemp = array_unique($this->_aPlaceholders[$sPlaceholder][0]);

					$sSeparator = ' / ';
					if($sPlaceholder === 'class_content') {
						$sSeparator = ', ';
					}

					$aTemp = array_filter($aTemp);
					
					$sValue = implode($sSeparator, $aTemp);
				} else{
					// parent methode gibt den string wieder normal zurück, genau den brauchen wir current_page, total_pages Platzhalter,
					// da diese von tcpdf heraus ersetzt werden, siehe Ext_Thebing_Tuition_Report_Result->getPDF()
					$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				}
				break;
		}

		return $sValue;
	}


	/**
	 * Get the list of placeholders
	 *
	 * @return string
	 */
	public static function getPlaceholderList() {

		$aPlaceholder = array(
			'current_week'		=> array('title' => L10N::t('Aktuelle Woche', self::$_sDescription)),
			'school_name'		=> array('title' => L10N::t('Schulname', self::$_sDescription)),
			'weekdays_times'	=> array('title' => L10N::t('Wochentage mit Zeiten', self::$_sDescription)),
			'class_name'		=> array('title' => L10N::t('Klasse', self::$_sDescription)),
			'class_from' => array('title' => L10N::t('Klasse - Startdatum', self::$_sDescription)),
			'class_to' => array('title' => L10N::t('Klasse - Enddatum', self::$_sDescription)),
			'weekdays'			=> array('title' => L10N::t('Wochentage', self::$_sDescription)),
			'times'				=> array('title' => L10N::t('Wochenzeiten', self::$_sDescription)),
			'teacher_name'		=> array('title' => L10N::t('Lehrer', self::$_sDescription)),
			'student_name'		=> array('title' => L10N::t('Schüler', self::$_sDescription)),
			'room'				=> array('title' => L10N::t('Klassenzimmer', self::$_sDescription)),
			'building'			=> array('title' => L10N::t('Gebäude', self::$_sDescription)),
			'floor'				=> array('title' => L10N::t('Etage', self::$_sDescription)),
			'level'				=> array('title' => L10N::t('Level', self::$_sDescription)),
			'courses'			=> array('title' => L10N::t('Kurse', self::$_sDescription)),
			'class_content'		=> array('title' => L10N::t('Inhalt (Block)', self::$_sDescription)),
			'course_language'	=> array('title' => L10N::t('Kurssprache', self::$_sDescription)),
		);

		$aPlaceholder['total_number_of_students_per_class'] = array(
			'title' => L10N::t('Anzahl der Schüler in dem entsprechenden Kurs', self::$_sDescription)
		);

		$aPlaceholder['teacher_firstname'] = array(
			'title' => L10N::t('Vorname des Lehrers', self::$_sDescription)
		);
		$aPlaceholder['teacher_lastname'] = array(
			'title' => L10N::t('Nachname des Lehrers', self::$_sDescription)
		);
		$aPlaceholder['last_course_end_date'] = array(
			'title' => L10N::t('Enddatum letzter Kurs', self::$_sDescription)
		);

		return $aPlaceholder;
	}

}
