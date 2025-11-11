<?php

/**
 * @property integer $course_id
 * @property string $start_date "gültig von"-Datum im Format YYYY-MM-DD (0000-00-00 wenn nicht gesetzt)
 * @property string $end_date "gültig bis"-Datum im Format YYYY-MM-DD (0000-00-00 wenn nicht gesetzt)
 * @property string $last_start_date
 * @property integer $single_date
 * @property integer $minimum_duration
 * @property integer $maximum_duration
 * @property integer $fix_duration
 * @property integer $period Das Intervall in Wochen in denen der Kurs starten kann
 * @property int[] $levels
 * @property int[] $courselanguages
 */
class Ext_Thebing_Tuition_Course_Startdate extends Ext_Thebing_Basic {

	const TYPE_START_DATE = 'start_date';
	const TYPE_NOT_AVAILABLE = 'not_available';
	
	protected $_sTable = 'kolumbus_course_startdates';

	protected $_aJoinTables = [
		'levels' => [
			'table' => 'kolumbus_course_startdates_levels',
			'primary_key_field' => 'type_id',
			'foreign_key_field' => 'level_id',
			'autoload' => false,
			'on_delete' => 'delete'
		],
		'courselanguages' => [
			'table' => 'ts_tuition_courses_startdates_courselanguages',
			'primary_key_field' => 'type_id',
			'foreign_key_field' => 'courselanguage_id',
			'autoload' => false,
			'on_delete' => 'delete'
		]
	];

	protected $_aFormat = array(
		/*
		 * Erzeugt einen Fehler beim Import, da validate ja vor dem Speichern aufgerufen wird, aber die Kurs-ID durch 
		 * Joined-Objekt erst beim Speichern gesetzt wird
		 */
//		'course_id' => array(
//			'required' => true,
//			'validate' => 'INT_POSITIVE'
//		),
		'start_date' => array(
			'required' => true,
			'validate' => 'DATE'
		),
		'period' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
		'last_start_date' => array(
			'validate' => 'DATE'
		),
		'end_date' => array(
			'validate' => 'DATE'
		),
		'minimum_duration' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
		'maximum_duration' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
		'fix_duration' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
	);

	public function __set($sName, $mValue) {

		if('fix_duration' == $sName) {

			if(!empty($mValue)) {
				$this->_aData['fix_duration'] = $mValue;
				$this->_aData['minimum_duration'] = null;
				$this->_aData['maximum_duration'] = null;
			} else {
				$this->_aData['fix_duration'] = null;
			}

		} elseif('minimum_duration' == $sName) {

			if(is_numeric($mValue)) {
				$this->_aData['minimum_duration'] = $mValue;
				$this->_aData['fix_duration'] = null;
			} else {
				$this->_aData['minimum_duration'] = null;
			}

		} elseif('maximum_duration' == $sName) {

			if(!empty($mValue)) {
				$this->_aData['maximum_duration'] = $mValue;
				$this->_aData['fix_duration'] = null;
			} else {
				$this->_aData['maximum_duration'] = null;
			}

		} else {

			parent::__set($sName, $mValue);

		}

	}

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if ($mValidate === true) {
			if (
				$this->hasLastStartDate() &&
				new \DateTime($this->start_date) > new \DateTime($this->last_start_date)
			) {
				$mValidate = ['last_start_date' => 'INVALID_DATE_UNTIL_BEFORE_FROM'];
			}
			if($this->hasEndDate()) {
				if(
					$this->hasLastStartDate() &&
					new \DateTime($this->last_start_date) > new \DateTime($this->end_date)
				) {
					$mValidate = ['end_date' => 'INVALID_DATE_UNTIL_BEFORE_FROM'];
				} elseif(new \DateTime($this->start_date) > new \DateTime($this->end_date)) {
					$mValidate = ['end_date' => 'INVALID_DATE_UNTIL_BEFORE_FROM'];
				}
			}
			
		}

		return $mValidate;
	}


	public function save($bLog = true) {

		if ($this->single_date) {
			$this->last_start_date = '0000-00-00';
			$this->end_date = null;
		}

		if (!$this->period) {
			$this->period = 1;
		}

		return parent::save($bLog);

	}

	/**
	 * Gibt den Kurs zurück zu dem dieser feste Starttermin gehört.
	 *
	 * @return Ext_Thebing_Tuition_Course
	 */
	public function getCourse() {
		return Ext_Thebing_Tuition_Course::getInstance($this->course_id);
	}

	public function hasLastStartDate() {
		return \Core\Helper\DateTime::isDate($this->last_start_date, 'Y-m-d');
	}

	public function hasEndDate() {
		return \Core\Helper\DateTime::isDate($this->end_date, 'Y-m-d');
	}

	/**
	 * @param int $iDefaultMaximumDuration
	 * @return int[]
	 */
	public function calculateMinMaxDuration(DateTime $date, int $iDefaultMaximumDuration = 52): array {

		$iMinDuration = 1;
		$iMaxDuration = max([1, $iDefaultMaximumDuration]);

		if($this->minimum_duration > 0) {
			$iMinDuration = $this->minimum_duration;
		}

		if($this->maximum_duration > 0) {
			$iMaxDuration = max([$iMinDuration, $this->maximum_duration]);
		}

		if($this->fix_duration) {
			$iMinDuration = max([1, $this->fix_duration]);
			$iMaxDuration = max([1, $this->fix_duration]);
		}

		if($this->hasEndDate()) {

			$until = \Ext_Thebing_Util::getCourseEndDate($date, (int)$iMaxDuration, (int)$this->getCourse()->getSchool()->course_startday);
			$lastEnd = new \Carbon\Carbon($this->end_date);
			while($until > $lastEnd) {
				
				$iMaxDuration -= $this->period;
				
				if($iMaxDuration < 1) {
					$iMaxDuration = $iMinDuration;
					break;
				}
				
				$until = \Ext_Thebing_Util::getCourseEndDate($date, (int)$iMaxDuration, (int)$this->getCourse()->getSchool()->course_startday);

			}
			
		}
		
		return [$iMinDuration, $iMaxDuration];

	}

}
