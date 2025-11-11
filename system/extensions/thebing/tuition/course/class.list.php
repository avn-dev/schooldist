<?php

/**
 * @deprecated
 */
class Ext_Thebing_Tuition_Course_List {

	public $iUnitCourses = null;
	public $iCombinedCourses = null;
	public $iSchoolId = null;
	public $bForSelect = false;
	public $sLanguage = null;
	public $bShort = false;
	public $bOnlyForCombinationCourses = false;
	public $bForScheduling = true;

	/**
	 * @return array
	 * @deprecated
	 */
	public function getList() {
		
		$aFilteredList = array();
		
		if($this->iSchoolId !== null) {
			$oTuitionCourse = Ext_Thebing_Tuition_Course::getInstance();
			$oTuitionCourse->setSchoolId($this->iSchoolId);
			$aArrayList = $oTuitionCourse->getArrayListSchool();
		} else {
			$oTuitionCourse = Ext_Thebing_Tuition_Course::getInstance();
			$aArrayList = $oTuitionCourse->getArrayList();
		}
		
		if($this->bShort) {
			$sNameField = 'name_short';
		} else {
			
			if(empty($this->sLanguage)) {
				$this->sLanguage = Ext_TC_System::getInterfaceLanguage();
			}
			$sNameField = 'name_' . $this->sLanguage;

		}

		foreach($aArrayList as $aRowData) {

			if(
				$this->iUnitCourses !== null &&
				$aRowData['per_unit'] != $this->iUnitCourses
			) {
				continue;
			}

			if($this->iCombinedCourses !== null) {
				if(
					(int)$this->iCombinedCourses === 0 &&
					in_array((int)$aRowData['per_unit'], [Ext_Thebing_Tuition_Course::TYPE_COMBINATION, Ext_Thebing_Tuition_Course::TYPE_PROGRAM])
				) {
					continue;
				} else if(
					(int)$this->iCombinedCourses === 1 &&
					!in_array((int)$aRowData['per_unit'], [Ext_Thebing_Tuition_Course::TYPE_COMBINATION, Ext_Thebing_Tuition_Course::TYPE_PROGRAM])
				) {
					continue;
				}
			}
			
			if(
				$this->bOnlyForCombinationCourses !== null &&
				(bool)$aRowData['only_for_combination_courses'] != $this->bOnlyForCombinationCourses
			) {
				continue;
			}

			if(
				$this->bForScheduling &&
				in_array((int)$aRowData['per_unit'], [Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT])
			) {
				continue;
			}

			if($this->bForSelect) {
				$aInsert = $aRowData[$sNameField];
			} else {
				$aInsert = $aRowData;
			}
			
			$aFilteredList[$aRowData['id']] = $aInsert;
			
		}

		return $aFilteredList;
	}

	/**
	 * @return Ext_Thebing_Tuition_Course[]
	 * @deprecated
	 */
	public function getObjectList() {

		$aObjectList = array();
		$aArrayList = $this->getList();

		foreach($aArrayList as $iRowId => $aRowData) {
			$oObject = Ext_Thebing_Tuition_Course::getObjectFromArray($aRowData);
			$aObjectList[$iRowId] = $oObject;
		}
		
		return $aObjectList;
	}

}
