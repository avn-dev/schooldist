<?php

interface Ext_TS_Service_Interface_Course {

	public function getInfo($iSchoolId = false, $sDisplayLanguage = false);
	
	public function checkHoliday();
	
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iCourseCount, Tc\Service\LanguageAbstract $oLanguage);

	/**
	 * @return Ext_Thebing_Tuition_Course
	 */
	public function getCourse();
	
	public function getFrom();
	
	public function getUntil();

}