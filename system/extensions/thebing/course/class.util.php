<?php

/**
 * @TODO Endgültig entfernen
 *
 * @deprecated
 */
class Ext_Thebing_Course_Util {

	/**
	 * @var Ext_Thebing_School
	 */
	public $oSchool;

	protected $aCourseList;
	protected $aCourse;
	protected $idCourse;
	protected $sDisplayLanguage;
    protected $aCombinedCourses = array();

	protected static $aCacheGetCourseList = array();

	protected static $_aCache = array();

	/**
	 * @param string|int|Ext_Thebing_School $oSchool
	 * @param string $sDisplayLanguage
	 * @throws \LogicException
	 * @deprecated
	 */
	public function __construct($oSchool = "noData", $sDisplayLanguage = '') {

		if(!is_object($oSchool)) {
			if((int)$oSchool > 0) {
				$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}
		}

		if(
			!($oSchool instanceof Ext_Thebing_School) ||
			$oSchool->id < 1
		) {
			$sMsg = 'No school available';
			throw new \LogicException($sMsg);
		}

		$this->oSchool = $oSchool;
		$this->sDisplayLanguage = (string)$sDisplayLanguage;

		if(empty($this->sDisplayLanguage)) {
			$this->sDisplayLanguage = $this->oSchool->getLanguage();
		}

		$this->_setCourseList();

	}

	public function setDisplayLanguage($sDisplayLanguage) {
		$this->sDisplayLanguage = $sDisplayLanguage;
	}

	public function getCourseId() {
		return $this->idCourse;
	}

	public function getName($sLang = '') {
		return $this->_getName();
	}

	public function getShortName() {
		$aCourse = $this->_getCourse();
		return $aCourse['name_short'];
	}

	public function getField($sField) {
		return $this->aCourse[$sField];
	}

	public function getFields() {
		return $this->aCourse;
	}

	public function getIntensity($bForSelect = false) {
		return '';
	}

	public function setCourse($idCourse) {
		$this->idCourse = (int)$idCourse;
		$this->_setCourse();
	}

	public function getCourseWeekList($bForSelects = false, $bWithExtraWeeks = true, $sSortColumn = 'position') {

		if(!isset(self::$_aCache['course_week_list'][$this->aCourse['id']][$bForSelects][$bWithExtraWeeks])) {

			$oCourse = $this->getCourseObject();
			$aWeekIds = $oCourse->weeks;

			if($bWithExtraWeeks === false){
				$sWhereAddon = " AND `kw`.`extra` = 0 ";
			} elseif($bWithExtraWeeks === 2) {
				$sWhereAddon = " AND `kw`.`extra` = 1 ";
			}

			$sSql = "
				SELECT
					`kw`.*
				FROM
					`kolumbus_weeks` `kw`
				INNER JOIN
					`ts_weeks_schools` `ts_ws`
				ON
					`ts_ws`.`week_id` = `kw`.`id` AND
					`ts_ws`.`school_id` = :idSchool
				WHERE
					`kw`.`active` = 1 AND
					`kw`.`id` IN(:week_ids)
					".$sWhereAddon."
				GROUP BY
					`kw`.`id`
				ORDER BY
					`kw`.`".$sSortColumn."` ASC
			";
			$aSql = [
				'idSchool' => (int)$this->oSchool->id,
				'week_ids' => $aWeekIds,
			];

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aBack = array();
			$p = 1;
			foreach((array)$aResult as $aWeek) {
				if($aWeek['position'] == 0) {
					$aWeek['position'] = $p;
					$p++;
				}
				if($bForSelects == false) {
					$aBack[$aWeek['start_week']] = $aWeek;
				} else {
					$aBack[$aWeek['id']] = $aWeek['title'];
				}
			}

			self::$_aCache['course_week_list'][$this->aCourse['id']][$bForSelects][$bWithExtraWeeks] = (array)$aBack;

		}

		return self::$_aCache['course_week_list'][$this->aCourse['id']][$bForSelects][$bWithExtraWeeks];

	}

	public function getCourseUnitList ($bForSelects = false, $bWithExtraUnits = true) {

		if ($this->getField('per_unit') == 2) {
			return \Ext_Thebing_Tuition_Course::getExamFakeUnitResource();
		}

		$oCourse = $this->getCourseObject();
		$aCourseUnitIds = $oCourse->units;

		if($bWithExtraUnits === false) {
			$sWhereAddon .= " AND `kcou`.`extra` = 0 ";
		} else if($bWithExtraUnits === 2) {
			$sWhereAddon .= " AND `kcou`.`extra` = 1 ";
		}

		$sSql = "
			SELECT 
				`kcou`.*
			FROM 
				`kolumbus_courseunits` `kcou`
			INNER JOIN
				`ts_courseunits_schools` `ts_cs`
			ON
				`ts_cs`.`courseunit_id` = `kcou`.`id` AND
				`ts_cs`.`school_id` = :idSchool
			WHERE
				`kcou`.`active` = 1 AND
				`kcou`.`id` IN(:unit_ids)
				".$sWhereAddon."
			GROUP BY
				`kcou`.`id`
			ORDER BY
				`kcou`.`position` ASC,
				`kcou`.`id` ASC
		";
		$aSql = [
			'idSchool' => (int)$this->oSchool->id,
			'unit_ids' => $aCourseUnitIds,
		];

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aBack = [];
		$p = 1;

		foreach($aResult as $aCourseUnit) {
			if($aCourseUnit['position'] == 0) {
				$aCourseUnit['position'] = $p;
				$p++;
			}
			if($bForSelects == false) {
				$aBack[$aCourseUnit['start_unit']] = $aCourseUnit;
			} else {
				$aBack[$aCourseUnit['id']] = $aCourseUnit['title'];
			}
		}

		return $aBack;

	}

	protected function _setCourseList() {
		$this->aCourseList = $this->_getCourseList();
	}

	protected function _setCourse() {
		$this->aCourse = $this->_getCourse();
	}

	/**
	 * @todo: Das gleiche gibts auch in der Ext_Thebing_School, diese Funktion in die School verschieben, Parameter
	 * und alle Stellen anpassen wo diese oder die Funktion aus der School benutzt wird
	 * get the all course Data Array
	 */
	protected function _getCourseList($bForSelect = false, $bCombinations = false, $bPerUnit=null)
	{

		$aCache = self::$aCacheGetCourseList;

		$iSchoolId = $this->oSchool->getId();

		if(!isset($aCache[$iSchoolId][$bCombinations])) {

			$sWhere = '';

			if($bCombinations !== false) {
				$sWhere = ' AND `per_unit` ';
				if($bCombinations == 2) {
					$sWhere .= ' != '.Ext_Thebing_Tuition_Course::TYPE_COMBINATION;
				} else {
					$sWhere .= ' = '.Ext_Thebing_Tuition_Course::TYPE_COMBINATION;
				}
			}

			if($bPerUnit===true){
				//Nur Lektionskurse
				$sWhere .= ' AND `per_unit` = 1';
			}elseif($bPerUnit===false){
				//alles außer Lektionskurse
				$sWhere .= ' AND `per_unit` = 0';
			}

			$sSql = "SELECT
								*
							FROM
								`kolumbus_tuition_courses`
							WHERE
								`active` = 1 AND
								`school_id` = :idSchool
								".$sWhere."
							ORDER BY position
							";
			$aSql = array (
				'idSchool' => (int)$iSchoolId,
			);
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			self::$aCacheGetCourseList[$iSchoolId][$bCombinations] = $aResult;
		}

		$aResult = self::$aCacheGetCourseList[$iSchoolId][$bCombinations];

		if($bForSelect == true){
			$aBack[0] = " --- ";
		}
		foreach ($aResult as $aCourse)
		{
			$sName = $aCourse['name_'.$this->sDisplayLanguage];

			if($sName == "") {
				$sName = $aCourse['ext_33'];
			}

			if($bForSelect == true){
				$aBack[$aCourse['id']] = $sName;
			} else {
				$aBack[$aCourse['id']] = $aCourse;
			}
			
		}
		return $aBack;
	}
	
	public static function getList($bForSelect = false)
	{

		$sSql = "SELECT 
							* 
						FROM 
							`kolumbus_tuition_courses`
						WHERE
							`active` = 1
						ORDER BY position
						";
		$aSql = array();
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($bForSelect == true){
			$aBack[0] = " --- ";
		}
		foreach ($aResult as $aCourse)
		{

			if($bForSelect == true){
				$aBack[$aCourse['id']] = $aCourse['ext_33'];
			} else {
				$aBack[$aCourse['id']] = $aCourse;
			}
			
		}
		return $aBack;
	}

	protected function _getCourse() {
		$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$this->idCourse);
		$aData = $oCourse->aData;
		$aData['weeks'] = $oCourse->weeks;
		$aData['units'] = $oCourse->units;
		return $aData;
	}

	public function getCourseObject() {
		return Ext_Thebing_Tuition_Course::getInstance((int)$this->idCourse);
	}

	protected function _getName() {
		$aCourse = $this->_getCourse();
		return $aCourse['name_'.$this->sDisplayLanguage];
	}

    public function isAvailableOnHolidays($iPublicOrSchoolHoliday = 0) {

        $aCourse = $this->_getCourse();
        if ($iPublicOrSchoolHoliday == 1) {
            return ($aCourse["schoolholiday"] == 1);
        }
        return ($aCourse["publicholiday"] == 1);

	}

	public function calculateByUnit(): bool {
		$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$this->idCourse);
		return $oCourse->calculateByUnit();		
	}

}
