<?php

class Ext_Thebing_Gui2_Format_School_Tuition_BookedCourses extends Ext_Gui2_View_Format_Abstract{

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return mixed
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		return parent::format($this->getCourses($aResultData, true), $oColumn, $aResultData);
	}


	/**
	 * @param null $oColumn
	 * @param array $aResultData
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		// Zeigt die Kurskurzform nochmals an falls diese zu lange ist für das Feld
		$aReturn = array();
		$aReturn['content'] = $this->getCourses($aResultData, false);
		$aReturn['tooltip'] = true;

		return $aReturn;
	}

	/**
	 * @param array $aResultData
	 * @param bool $bShort
	 * @return string
	 */
	private function getCourses($aResultData, $bShort) {

		$oInquiry = Ext_TS_Inquiry::getInstance($aResultData['id']);

		$aCourseNames = array();
		$aCourses = $oInquiry->getCourses();
		foreach($aCourses as $oCourse) {
			$aCourseNames[] = $oCourse->getCourseName($bShort);
		}

		return join('<br />', $aCourseNames);
	}

}
