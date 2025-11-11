<?php

/**
* @property $id 
* @property $active 	
* @property $creator_id 	
* @property $changed 	
* @property $user_id 	
* @property $created 	
* @property $position 	
* @property $type 	
* @property $automatic_assignment_from
* @property $automatic_assignment_until
* @property $name_short
* @property $name_de 	
* @property $name_en 	
* @property $name_es 	
* @property $name_fi 	
* @property $name_fr 	
* @property $name_ja 	
* @property $name_it 	
* @property $name_zh 	
* @property $name_pt
*/
class Ext_Thebing_Tuition_Level extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_tuition_levels';

	// Tabellenalias
	protected $_sTableAlias = 'ktul';
	
	public static $aCache = null;

	protected $_aFlexibleFieldsConfig = [
		'tuition_course_proficiency' => []
	];

	protected $_aJoinTables = array(
		'schools'=> [
			'table' => 'ts_tuition_levels_to_schools',
			'primary_key_field' => 'level_id',
			'foreign_key_field' => 'school_id',
		],
		'startdates'=> [
			'table' => 'kolumbus_course_startdates_levels',
			'primary_key_field' => 'level_id',
			'foreign_key_field' => 'type_id',
			'readonly' => true,
			'autoload' => false,
		]
	);
	
	public function getName($sIso = ''){
		$aData = $this->_aData;
		return $aData['name_'.$sIso];
	}

	public function getSchoolId(){
		return reset($this->schools);
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		if(
			(
				$this->valid_until != '0000-00-00' &&
				$this->valid_until != null &&
				$this->checkUse(true) &&
				$this->id != 0
			) ||
			(
				$this->active == 0 &&
				$this->checkUse(false) &&
				$this->id != 0
			)
		) {
			return ['tc_r.id' => ['LEVEL_IN_USE']];
		}

		$success = parent::validate($bThrowExceptions);

		// Automatische Zuweisung
		if (
			$success === true &&
			$this->type == 'internal' &&
			($this->automatic_assignment_from !== ''  && $this->automatic_assignment_from !== null ||
			$this->automatic_assignment_until !== '' && $this->automatic_assignment_until !== null )
		) {
			// Wenn die automatische Zuweisung auch überprüft werden muss

			$schools = $this->schools;

			$allLevels = $this::getAllValidLevels();

			foreach ($allLevels as $level) {

				// Wenn nur ein Wert der automatischen Zuweisung gesetzt ist
				if (
					($this->automatic_assignment_from !== ''&&
					$this->automatic_assignment_until === '') ||
					($this->automatic_assignment_from === '' &&
					$this->automatic_assignment_until !== '')
				) {
					return ['ktul.automatic_assignment_until' => ['LEVEL_BOTH_FIELDS']];
				}

				// Wenn der "Von"-Wert von der automatischen Zuweisung über dem "Bis"-Wert liegt
				if (
					$this->automatic_assignment_from >= $this->automatic_assignment_until
				) {
					return ['ktul.automatic_assignment_until' => ['LEVEL_FROM_LOWER']];
				}

				// Bei Überschneidungen von Prozentbereichen der automatischen Zuweisung
				if (
					$level->id != $this->id &&
					$level->automatic_assignment_from <= $this->automatic_assignment_until &&
					$level->automatic_assignment_until >= $this->automatic_assignment_from
				) {

					$schoolsOfLevelWithIntersection = $level->schools;

					// Wenn das Level, bei der die Überschneidung vorkommt, auch für die gleichen Schulen verfügbar ist
					if (!empty(array_intersect($schoolsOfLevelWithIntersection, $schools))) {
						return ['ktul.automatic_assignment_until' => ['LEVEL_ASSIGNMENT_IN_USE']];
					}
				}
			}
		}

		return $success;

	}

	/**
	 * @return bool
	 */
	private function checkUse($bDeactivate=false) {

		$checkDate = false;
		
		if($bDeactivate === false) {

			$aTables = [
				'kolumbus_tuition_blocks' => ['key_field' => 'level_id'],
				'kolumbus_tuition_progress' => ['key_field' => 'level'],
				'ts_inquiries_journeys_courses' => ['key_field' => 'level_id'],
				'ts_placementtests_results' => ['key_field' => 'level_id'],
			];
			
		} else {

			$aTables = [
				'kolumbus_tuition_blocks' => ['key_field' => 'level_id', 'date_field' => 'week'],
				'ts_inquiries_journeys_courses' => ['key_field' => 'level_id', 'date_field' => 'until']
			];

			$checkDate = true;
			
		}

		foreach($aTables as $sTable => $field) {
			$sSql = "
				SELECT
					COUNT(`id`)
				FROM
					`{$sTable}`
				WHERE
					`active` = 1 AND
					`{$field['key_field']}` = :id
			";

			if($checkDate === true) {
				$sSql .= " AND
					`{$field['date_field']}` > :valid_until";
			}
					
			$iCount = (int)DB::getQueryOne($sSql, $this->_aData);

			if($iCount > 0) {
				return true;
			}
		}

		// Weil die Tabelle keine active-Spalte und keine id-Spalte hat (Verbindungstabelle)
		if($bDeactivate === false && !empty($this->startdates)) {
			return true;
		}

		return false;
	}

	public function save($bLog = true) {

		if(
			$this->valid_until === '' || 
			$this->valid_until === '0000-00-00'
		) {
			$this->valid_until = null;
		}

		// Weil 0 ein Valider Wert ist und das Datenbankfeld ein Float-Feld ist, der Wert aber durch das Input
		// ein leerer String ist und dann als 0 in die Datenbank gespeichert werden würde, was falsch wäre
		if(
			empty($this->automatic_assignment_until) &&
			$this->automatic_assignment_until !== '0'
		) {
			$this->automatic_assignment_until = null;
		}

		// Weil 0 ein Valider Wert ist und das Datenbankfeld ein Float-Feld ist, der Wert aber durch das Input
		// ein leerer String ist und dann als 0 in die Datenbank gespeichert werden würde, was falsch wäre
		if(
			empty($this->automatic_assignment_from) &&
			$this->automatic_assignment_from !== '0'
		) {
			$this->automatic_assignment_from = null;
		}

	    if ($this->type === 'normal') {
			// Bei type == normal verschwinden die Auswahlmöglichkeiten für die Werte, sind aber trotzdem noch da und werden
			// gespeichert. Sollen sie aber nicht, also auf null setzen. Vor allem für die Validierung anderer Levels wichtig
			// -> LEVEL_ASSIGNMENT_IN_USE
			$this->automatic_assignment_from = null;
			$this->automatic_assignment_until = null;
	    }
		
		$mReturn = parent::save($bLog);
		
		self::$aCache = null;
		
		WDCache::delete('tuition_levels');

		return $mReturn;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {
		
		$aSqlParts['select'] .= ", GROUP_CONCAT(schools.school_id) `schools`";
		
	}
	
	
	static public function getList($sNiveau = 'internal', $sDisplayLanguage=null) {

		if(empty($sDisplayLanguage)) {

			$school = Ext_Thebing_Client::getFirstSchool();

			if($school) {
				$sDisplayLanguage = $school->getInterfaceLanguage();
			} else {
				$sDisplayLanguage = 'en';
			}

		}
		
		$sSql = "
			SELECT 
				* 
			FROM 
				`ts_tuition_levels` `ts_tl`
			WHERE
				`ts_tl`.`active` = 1 AND
				`ts_tl`.`type` = :type
			ORDER BY 
				`ts_tl`.`position` ASC,
				`ts_tl`.`id` ASC
			";

		$aSql = array (
			'type' => $sNiveau
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = [];

		foreach ($aResult as $aIntesy) {

			$sName = $aIntesy['name_'.$sDisplayLanguage];
//			if($bUseShortNames && !empty($aIntesy['name_short'])){
//				$sName = $aIntesy['name_short'];
//			}

			if(empty($sName)) {
				$sName = 'Level #'.$aIntesy['id'];
			}

			$aBack[$aIntesy['id']] = $sName;

		}

		return $aBack;
	}

	/**
	 * @return \Ext_Thebing_Tuition_Level[]
	 */
	public static function getLevelsBySchoolId($schoolId) {

		$allLevels = self::getAllValidLevels();

		$returnLevels = [];
		foreach ($allLevels as $level) {

			$schoolsOfLevel = $level->schools;

			// Wenn das Level zu der Schule vom Parameter gehört
			if (in_array($schoolId, $schoolsOfLevel)) {
				$returnLevels[$level->id] = $level;
			}
		}

		return $returnLevels;

	}

	public static function getAllValidLevels() {
		return self::query()
				->onlyValid()
				->get();
	}

}
