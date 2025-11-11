<?php 

class Ext_Thebing_Teacher_Schedule extends Ext_Thebing_Basic {
    
	protected $_sTable = 'kolumbus_teacher_schedule';
	protected $_sTableAlias = 'kts';
	
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'idDay' => array(
			'validate' => 'REGEX',
			'validate_value'=>'[1-7]'
			),
		'timeFrom' => array(
			'validate' => 'TIME'
			),
		'timeTo' => array(
			'validate' => 'TIME'
			)
	);

	protected $_aFlexibleFieldsConfig = [
		'teachers_availability' => []
	];

	public function validate($throwExceptions = false) {
		$errors = parent::validate($throwExceptions);

		if ($this->valid_from > $this->valid_until) {
			if (!is_array($errors)) {
				$errors = [];
			}
			$errors[$this->_sTableAlias.'.valid_until'][] = 'INVALID_DATE_UNTIL_BEFORE_FROM';
		}

		return $errors;
	}
	
	public function  __get($sName) {

		if($sName == 'teacher_name') {
			if($this->_aData['idTeacher'] > 0) {
				$oTeacher = Ext_Thebing_Teacher::getInstance($this->_aData['idTeacher']);
				$sValue = $oTeacher->name;
			} else {
				$sValue = '';
			}
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {
		
		$aSqlParts['select'] .= ",
			`ts_t`.`firstname`,
			`ts_t`.`lastname`,
			TIMEDIFF(`kts`.`timeTo`, `kts`.`timeFrom`) `duration`
		";

		$aSqlParts['from'] .= " JOIN 
			`ts_teachers` `ts_t` ON
				`ts_t`.`id` = `kts`.`idTeacher` AND
				`ts_t`.`active` = 1
		";
		
	}
	
}