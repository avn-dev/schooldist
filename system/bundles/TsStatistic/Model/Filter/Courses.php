<?php

namespace TsStatistic\Model\Filter;

use TcStatistic\Model\Filter\AbstractFilter;
use TsStatistic\Model\Filter\Tool\FilterInterface;

class Courses extends AbstractFilter implements FilterInterface {

	public function getKey() {
		return 'courses';
	}

	public function getTitle() {
		return self::t('Kurse');
	}

	public function getInputType() {
		return 'multiselect';
	}

	public function getSelectOptions() {

		$aCourses = [];
		$aSchools = \Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(false, true);

		foreach($aSchools as $oSchool) {
			$aSchoolCourses = $oSchool->getCourseList();
			foreach($aSchoolCourses as $iCourseId => $sName) {
				$aCourses[$iCourseId] = $oSchool->short.' - '.$sName;
			}
		}
		return $aCourses;

	}

	public function getDefaultValue() {

		$aValues = array_keys($this->getSelectOptions());

		if (!\Ext_Thebing_System::isAllSchools()) {
			$aValues = array_filter($aValues, function ($id) {
				$oCourse = \Ext_Thebing_Tuition_Course::getInstance($id);
				return $oCourse->school_id == \Ext_Thebing_School::getSchoolFromSession()->id;
			});
		}

		// TODO Eventuell nach createSmartyObject() auslagern, wenn öfter benötigt
		\System::wd()->executeHook('ts_statistic_filter_course', $aValues);

		return $aValues;

	}

	public function getJoinParts(): array {
		return ['course'];
	}

	public function getJoinPartsAdditions(): array {
		return ['JOIN_JOURNEY_COURSES' => " AND `ts_ijc`.`course_id` IN (:courses) "];
	}

	public function getSqlWherePart(): string {
		return "";
	}
}
