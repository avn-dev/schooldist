<?php

/**
 * @property integer $course_id
 * @property integer $level_id
 * @property integer $units
 * @property ? $from
 * @property ? $until
 * @property integer $weeks
 * @property string $comment
 */
class Ext_TS_Enquiry_Combination_Course extends Ext_TS_Enquiry_Combination_Service implements Ext_TS_Service_Interface_Course {

	use Ts\Traits\Course\AdjustData;
	use Ts\Traits\LineItems\Course;
	
	protected $_sTable = 'ts_enquiries_combinations_courses';

	protected $_sTableAlias = 'ts_ecc';

	protected $sInfoTemplateType = 'course';
	
	protected $_aFormat = array(
		'course_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'level_id' => array(
			'validate' => 'INT_NOTNEGATIVE',
		),
		'units' => array(
			'validate' => 'FLOAT_NOTNEGATIVE',
		),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'from' => array(
			'validate' => 'DATE',
			'required' => true
		),
		'until' => array(
			'validate' => 'DATE',
			'required' => true
		)
	);

	/**
	 * @return Ext_Thebing_Tuition_Course 
	 */
	public function getCourse() {

		$oCourse = Ext_Thebing_Tuition_Course::getInstance($this->course_id);
		return $oCourse;

	}

	public function validate($bThrowExceptions = false) {

		if(
			$this->active &&
			$this->units > Ext_Thebing_Tuition_Course::getMaxUnits()
		) {
			return [$this->_sTableAlias.'.units' => ['TO_HIGH']];
		}

		return parent::validate($bThrowExceptions);

	}

	/**
	 * @return Ext_Thebing_Tuition_Level 
	 */
	public function getCourseLevel() {

		$oCourseLevel = Ext_Thebing_Tuition_Level::getInstance($this->level_id);
		return $oCourseLevel;

	}

	/**
	 * Ob Ferien überprüft werden soll beim generieren der Rechnungspositionen
	 *
	 * Bei Angeboten gibt es keine Ferien...
	 *
	 * @return boolean
	 */
	public function checkHoliday() {

		return false;

	}

	/**
	 * @param integer $iSchoolId
	 * @param string $sDisplayLanguage
	 * @return string
	 */
	public function getInfo($iSchoolId = false, $sDisplayLanguage = false) {
		return Ext_TS_Inquiry_Journey_Course::getOutputInfo($this, $iSchoolId, $sDisplayLanguage);
	}

	/**
	 * @param integer $iAdditionalCostId
	 * @param integer $iWeeks
	 * @param integer $iCourseCount
	 * @param Tc\Service\LanguageAbstract $oLanguage
	 * @return string 
	 */
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iCourseCount, Tc\Service\LanguageAbstract $oLanguage) {

		$oEnquiry = $this->getEnquiry();
		$oCourse = $this->getCourse();

		$oJourneyCourse = new Ext_TS_Inquiry_Journey_Course();
		$oJourneyCourse->setInquiry($oEnquiry);
		$oJourneyCourse->setCourse($oCourse);

		$sInfo = $oJourneyCourse->getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iCourseCount, $oLanguage);

		return $sInfo;

	}

	/**
	 * Erzeugt ein Journey Course Obj aus den Kurs Kombinationsdaten
	 *
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Course 
	 */
	public function getJourneyService(Ext_TS_Inquiry_Journey $oJourney = null) {

		if($oJourney instanceof Ext_TS_Inquiry_Journey) {
			$oCourse = $oJourney->getJoinedObjectChild('courses');
		} else {
			// Alten Müll am Funktionieren erhalten
			$oCourse = new Ext_TS_Inquiry_Journey_Course();
		}

		$oCourse = $this->_setServiceData($oCourse);

		return $oCourse;

	}

	/**
	 * Erzeugt ein Gruppen Course Obj aus den Kurs Kombinationsdaten
	 *
	 * @return Ext_Thebing_Inquiry_Group_Course 
	 */
	public function getGroupService(){

		$oCourse = new Ext_Thebing_Inquiry_Group_Course();
		$oCourse = $this->_setServiceData($oCourse);

		return $oCourse;

	}

	/**
	 * Setzt die Daten für ein Service Obj (Journey oder group)
	 *
	 * @param ? $oCourse
	 * @return ?
	 */
	protected function _setServiceData($oCourse) {

		$oCourse->course_id = $this->course_id;
		$oCourse->level_id = $this->level_id;
		$oCourse->weeks = $this->weeks;
		$oCourse->from = $this->from;
		$oCourse->until = $this->until;
		$oCourse->units = $this->units;
		$oCourse->comment = $this->comment;
		$oCourse->visible = 1;

		return $oCourse;

	}

	/**
	 * Gibt die Fehlermeldung für Kurse zurück, wenn kein Leistungszeitraum gebucht wurde
	 *
	 * @return string
	 */	
	protected function _getErrorMessage() {

		$oCourse = $this->getCourse();

		$sMessage = sprintf(
			Ext_Thebing_L10N::t('Sie haben für den Kurs "%s" keinen Leistungszeitraum angegeben'),
			$oCourse->getName()
		);

		return $sMessage;

	}

	/**
	 * Kursnamen der gewählten Kurskombination
	 *
	 * @TODO Die Signatur ist unterschiedlich zu der Methode aus dem Inquiry Course!
	 * @see \Ext_TS_Inquiry_Journey_Course::getCourseName()
	 * @see \Ext_Thebing_Tuition_Course::getName()
	 *
	 * @param string $sLang
	 * @return string 
	 */
	public function getCourseName($sLang='', $bShort = false) {

		$oCourse = $this->getCourse();

		if($bShort === false) {
			$sCourseName = $oCourse->getName($sLang);
		} else {
			$sCourseName = $oCourse->name_short;
		}

		return $sCourseName;

	}

	/**
	 * Kursstart
	 *
	 * @return string
	 */
	public function getFrom() {

		return $this->from;

	}

	/**
	 * Kursende
	 *
	 * @return string
	 */
	public function getUntil() {

		return $this->until;

	}

	/**
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts() {
		return $this->getJourneyService()->getAdditionalCosts();
	}

}
