<?php 

/*
* @property int $id 
* @property string $changed 	
* @property string $created 	
* @property int $active 	
* @property int $creator_id 	
* @property int $user_id 	
* @property int $teacher_id 	
* @property int $costcategory_id 	
* @property string $valid_from 	
* @property string $valid_until 	
* @property string $comment 	
* @property int $lessons 	
* @property dtring $lessons_period 	
* @property int $salary 	
* @property string $salary_period
*/
class Ext_Thebing_Teacher_Salary extends Ext_Thebing_Basic {
    
	protected $_sTable = 'kolumbus_teacher_salary';
	protected $_sTableAlias = 'kts';

	protected $_aFormat = array(
		'valid_from' => array(
			'validate' => 'DATE'
			),
		'lessons' => array(
			'validate' => 'FLOAT_NOTNEGATIVE'
			),
		'salary' => array(
			'validate' => 'FLOAT_NOTNEGATIVE'
			),
		'costcategory_id' => array(
			'validate' => 'REGEX',
			'validate_value' => '\-?[0-9]+'
			)
	);

	protected $_aJoinedObjects = [
		'teacher' => [
			'class' => 'Ext_Thebing_Teacher',
			'key' => 'teacher_id',
			'type'=> 'parent'
		]
	];
	
	public function  __get($sName) {

		if($sName == 'teacher_name') {
			if($this->_aData['teacher_id'] > 0) {
				$oTeacher = Ext_Thebing_Teacher::getInstance($this->_aData['teacher_id']);
				$sValue = $oTeacher->name;
			} else {
				$sValue = '';
			}
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	public static function getPeriods($sPeriod=false) {

		$aPeriods = array();
		$aPeriods['week'] = L10N::t('Woche', 'Thebing » Tuition » Teachers');
		$aPeriods['month'] = L10N::t('Monat', 'Thebing » Tuition » Teachers');

		if($sPeriod) {
			return $aPeriods[$sPeriod];
		} else {
			return $aPeriods;
		}

	}

	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if($aErrors === true) {
			$aErrors = array();
		}

		// Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt
		$iLatestEntry = $this->getLatestEntry();

		if($iLatestEntry) {
			$oLatestEntry = self::getInstance($iLatestEntry);

			$oDate = new WDDate($oLatestEntry->valid_from, WDDate::DB_DATE);
			$iCompare = $oDate->compare($this->valid_from, WDDate::DB_DATE);

			if($iCompare >= 0) {
				//$aErrors['valid_from'][] = array('input'=>array('dbcolumn'=>'valid_from', 'dbalias'=>'kts'), 'message'=>'Der Wert in Feld "Gültig ab" ist zu klein!');
				$aErrors['kts.valid_from'][] = 'Der Wert in Feld "Gültig ab" ist zu klein!';
			}
		}

		$aIntersectionData	= (array)$this->getIntersectionData();
		$aPayments			= $this->getPayments();

		if(!empty($aPayments)){

			if($this->active == 0){
				$aErrors['lessons_period'][] = 'PAYMENTS_EXISTS';
			}
			elseif(
				array_key_exists('costcategory_id', $aIntersectionData)
			){
				$aErrors['costcategory_id'][] = 'COSTCATEGORY_NOT_CHANGABLE';
			}
			elseif(
				array_key_exists('salary_period', $aIntersectionData)
			){
				$aErrors['lessons_period'][] = 'SALARY_PERIOD_NOT_CHANGABLE';
			}

		}

		if(empty($aErrors)) {
			return true;
		}

		return $aErrors;

	}

	public function save($bSetLastEntry=true) {
		global $user_data;

		// Alter oder neuer Eintrag
		if($this->_aData['id'] > 0) {
			$bInsert = false;
		} else {
			$bInsert = true;
		}

		// Eintrag soll gelöscht werden
		// Gültig bis Datum bei dem letzten Eintrag anpassen
		if($this->active == 0) {

			$sSql = "
					SELECT
						id
					FROM
						kolumbus_teacher_salary
					WHERE
						`active` = 1 AND
						`teacher_id` = :teacher_id AND
						`valid_until` != '0000-00-00'
					ORDER BY
						`valid_until` DESC
					LIMIT 1
						";
			$aSql = array('teacher_id'=>$this->teacher_id);
			$iLastEntry = DB::getQueryOne($sSql, $aSql);

			if($iLastEntry > 0) {
				$oLastEntry = self::getInstance($iLastEntry);
				$oLastEntry->valid_until = '0000-00-00';
				$oLastEntry->save(false);
			}

			$bSetLastEntry = false;

		}

		parent::save();

		if(
			$bSetLastEntry &&
			$this->id > 0
		) {
			// Gültig bis Datum bei dem letzten Eintrag anpassen
			$iLastEntry = $this->getLatestEntry();

			if($iLastEntry > 0) {
				$oDate = new WDDate($this->valid_from, WDDate::DB_DATE);
				$oDate->sub(1, WDDate::DAY);

				$oLastEntry = self::getInstance($iLastEntry);
				$oLastEntry->valid_until = $oDate->get(WDDate::DB_DATE);
				$oLastEntry->save(false);
			}

		}

		return $this;

	}

	public function getLatestEntry() {

		$sSql = "
				SELECT
					id
				FROM
					kolumbus_teacher_salary
				WHERE
					`active` = 1 AND
					`teacher_id` = :teacher_id AND
					`school_id` = :school_id AND
					`valid_until` = '0000-00-00' AND
					`id` != :id
				ORDER BY
					`valid_from` DESC
				LIMIT 1
					";
		$aSql = array(
			'teacher_id'=>$this->teacher_id, 
			'school_id'=>$this->school_id, 
			'id'=>$this->id
		);
		
		$iLastEntry = DB::getQueryOne($sSql, $aSql);

		return $iLastEntry;
	}

	public function getPayments(){

		$dUntil = $this->valid_until;

		if(
			$dUntil == '0000-00-00' || 
			empty($dUntil)
		){
			$oDate = new WDDate();
			$oDate->add(10, WDDate::YEAR);
			$dUntil = $oDate->get(WDDate::DB_DATE);
		}

		$sSql = "
			SELECT
				*
			FROM
				`ts_teachers_payments` `ktep` INNER JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktep`.`teacher_id` AND
					`kt`.`active` = 1
			WHERE
				`ktep`.`teacher_id` = :teacher_id AND
				`ktep`.`timepoint` BETWEEN :valid_from AND :valid_until AND
				`ktep`.`active` = 1
		";

		$aSql = array(
			'teacher_id'				=> (int)$this->teacher_id,
			'valid_from'				=> $this->valid_from,
			'valid_until'				=> $dUntil,
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}

}