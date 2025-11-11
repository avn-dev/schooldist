<?php

class Ext_Thebing_Accounting_Gui2_Agency_Format_CreditnoteCourseDates extends Ext_TC_Gui2_Format {

	/** @var string */
	private $sColumn;

	/**
	 * @param string $sColumn
	 */
	public function __construct($sColumn) {
		$this->sColumn = $sColumn;
	}

	/**
     * @inheritdoc
     */
    public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if(empty($aResultData['cn_course_data'])) {
			return '';
		}

		$aCourses = [];
		$aCourseData = explode(';', $aResultData['cn_course_data']);
		$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $aResultData['school_id']);

		foreach($aCourseData as $sCourse) {
			list($iCourseId, $sFrom, $sUntil) = explode(',', $sCourse);

			$sValue = $sFrom;
			if($this->sColumn === 'until') {
				$sValue = $sUntil;
			}

			$aCourses[$iCourseId] = $oFormat->format($sValue);
		}

		ksort($aCourses);

		return implode('<br>', $aCourses);

	}

}