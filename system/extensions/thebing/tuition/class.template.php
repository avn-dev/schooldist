<?php

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $creator_id
 * @property string $school_id
 * @property string $name
 * @property string $from
 * @property string $until
 * @property string $lessons
 * @property string $user_id
 * @property string $custom
 * @property string $position
 * @property string $valid_until
 */
class Ext_Thebing_Tuition_Template extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_tuition_templates';

	// Tabellenalias
	protected $_sTableAlias = 'ktt';

	protected $_aFormat = array(
		'lessons'=>array(
			'required'=>true,
			'validate'=>'FLOAT_POSITIVE'
		)
	);

	protected $_aJoinTables = array(
		'rooms'=>array(
			'table'=>'kolumbus_tuition_blocks',
			'class'=>'Ext_Thebing_School_Tuition_Block',
			'primary_key_field'=>'template_id',
			'autoload'=>false,
			'check_active'=>true,
			'delete_check'=>true
		)
	);

    public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);
		
		if($sField == 'ext_7'
		) {
			$mValue = json_decode($this->_aData[$sField]);
            $mValue = (array)$mValue;
		} else {
			$mValue = parent::__get($sField);
		}

		return $mValue;

	}

	public function __set($sField, $mValue) {

		if(
			$sField == 'ext_7'
		) {
			$this->_aData[$sField] = (string)json_encode($mValue);
		} else {
			parent::__set($sField, $mValue);
		}

	}

	public function getSchool() {
    	return Ext_Thebing_School::getInstance($this->school_id);
	}

	/**
	 * @param bool $aCompareFields
	 * @return bool
	 */
	public function checkIfChanged($aCompareFields=false) {

		$oFormatTime = new Ext_Thebing_Gui2_Format_Time();

		$sFrom = $oFormatTime->convert($this->from);
		$sFromOld = $this->_aOriginalData['from'];
		$Until = $oFormatTime->convert($this->until);
		$UntilOld = $this->_aOriginalData['until'];
		$iLessons = (float)$this->_aData['lessons'];
		$iLessonsOld = (float)$this->_aOriginalData['lessons'];

		if(
			$sFrom != $sFromOld ||
			$Until != $UntilOld ||
			bccomp($iLessons, $iLessonsOld, 4)
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions=false) {
		$mReturn = parent::validate($bThrowExceptions);
		
		if($mReturn === true)  {
			$mReturn = array();
		}
		
		$bChanged = $this->checkIfChanged();
		
		if($bChanged) {
			$aTeacherPayments = Ext_Thebing_Teacher_Payment::searchByBlockTemplate($this->id);

			if(!empty($aTeacherPayments)) {
				$mReturn[] = 'TEACHER_PAYMENTS_EXISTS';
			}
		}

		$aFrom = explode(':',$this->from);
		$iFrom = $aFrom[0]*60 + $aFrom[1];
		$aUntil = explode(':',$this->until);
		$iUntil = $aUntil[0]*60 + $aUntil[1];
		
		if($iFrom >= $iUntil) {
			$mReturn['ktt.until'][] = 'INVALID_UNTIL';
		}

		if(!empty($this->valid_until)) {

			$oTemplates = Ext_Thebing_School_Tuition_Block::getBlocksByTemplate($this);
			
			$classIds = [];
			foreach($oTemplates as $aTemplate) {
				$classIds[$aTemplate['class_id']] = $aTemplate['class_id'];
			}
			
			$oValidUntil = new \DateTime($this->valid_until);

			foreach($classIds as $classId) {
				
				$oTuitionClass = Ext_Thebing_Tuition_Class::getInstance($classId);
				$oTuitionClassLastDate = $oTuitionClass->getLastDate();

				if($oTuitionClassLastDate >= $oValidUntil) {
					$mReturn[] = 'ALLOCATION_FOUND';
				}
			}
		}

		if(
			$this->getId() > 0 && 
			$bChanged
		) {
			if($this->custom == 0) {
				// Wenn keine Individuellen Vorlage, dürfen die Uhrzeiten & Lektionen nicht verändert werden,
				// sobald eine Zuweisung in der Klassenplanung existiert

				$oTemplates = Ext_Thebing_School_Tuition_Block::getBlocksByTemplate($this);

				$iCount = count($oTemplates);

				if($iCount > 0) {
					$mReturn[] = 'ALLOCATION_FOUND';
				}	
			}

			// Wenn Anwesenheit eingetragen, dann darf die Vorlage auch nicht verändert werden!
			$bHasAttendanceEntries = $this->hasAttendanceEntries();
			
			if($bHasAttendanceEntries) {
				$mReturn[] = 'ATTENDANCE_FOUND';
			}
		}
		
		if(empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;
	}

	/**
	 * @return bool
	 */
	public function hasAttendanceEntries() {

		$oAttendanceIndex = new Ext_Thebing_Tuition_Attendance_Index();
		$bHasAttendanceEntries = $oAttendanceIndex->hasEntriesWithTemplate($this);
		
		return $bHasAttendanceEntries;
	}

	public function getNameAndTime() {

		$sInfo = $this->name;
		
		if(
			WDDate::isDate($this->from, WDDate::TIMES) &&
			WDDate::isDate($this->until, WDDate::TIMES)	&&
			$this->custom == 0
		) {
			$sInfo .= ' ';

			$sInfo .= $this->getShortTime('from');

			$sInfo .= ' - ';

			$sInfo .= $this->getShortTime('until');	
		}
		
		return $sInfo;
	}
	
	/**
	 * Kurzform der Uhrzeit
	 * 
	 * @param string $sColumn
	 * @return string
	 */
	public function getShortTime($sColumn) {
		
		$oDate = new WDDate();
		$oDate->set($this->$sColumn, WDDate::TIMES);
		$sTime = $oDate->get('H:I');
		
		return $sTime;
	}

}
