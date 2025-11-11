<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_Data extends Ext_TC_Marketing_Feedback_Questionary_Process_Gui2_Data {

	/**
	 * Gibt die zugehörigen WDBasic-Klassen zurück, mit Hilfe der DependencyId
	 *
	 * @param Ext_TC_Marketing_Feedback_Question $oQuestion
	 * @param int $iDependencyId
	 * @return Ext_Thebing_Accommodation|Ext_Thebing_Accommodation_Room|Ext_Thebing_Teacher|Ext_Thebing_Tuition_Course_Category|static
	 * @throws InvalidArgumentException
	 */
	public function getWDBasic(Ext_TC_Marketing_Feedback_Question $oQuestion, $iDependencyId) {

		switch($oQuestion->dependency_on) {
			case 'course':
				$oObject = Ext_Thebing_Tuition_Course::getInstance($iDependencyId);
				break;
			case 'accommodation_category':
				$oObject = Ext_Thebing_Accommodation_Category::getInstance($iDependencyId);
				break;
			case 'teacher';
				$oObject = Ext_Thebing_Teacher::getInstance($iDependencyId);
				break;
			case 'course_category':
				$oObject = Ext_Thebing_Tuition_Course_Category::getInstance($iDependencyId);
				break;
			case 'meal':
				$oObject = Ext_Thebing_Accommodation_Meal::getInstance($iDependencyId);
				break;
			case 'transfer':
				$oObject = new stdClass();
				$oObject->iTypeId = $iDependencyId;
				break;
			case 'rooms':
				$oObject = Ext_Thebing_Accommodation_Room::getInstance($iDependencyId);
				break;
			case 'accommodation_provider':
				$oObject = Ext_Thebing_Accommodation::getInstance($iDependencyId);
				break;
			default:
				throw new InvalidArgumentException('Invalid WDBasic Type');
		}

		return $oObject;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getUserSelectOptions($bForFilter = true) {

		$oUser = Ext_Thebing_Client::getInstance();
		$aUsers = $oUser->getUsers(true, false);

		if(!$bForFilter) {
			$aUsers = Ext_TC_Util::addEmptyItem($aUsers, '', '0');
		}

		return $aUsers;

	}

	/**
	 * @return array
	 */
	public static function getCourseList() {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if((int)$oSchool->id > 0) {
			$aCourses = $oSchool->getCourseList(true);
		} else {
			/** @var Ext_Thebing_Tuition_CourseRepository $oCourseRepository */
			$oCourseRepository = Ext_Thebing_Tuition_Course::getRepository();
			$aCourses = $oCourseRepository->findAllCategoriesForSelect();
		}

		return $aCourses;
	}

	/**
	 * @inheritdoc
	 */
	public function switchAjaxRequest($_VARS) {
		if($_VARS['action'] == 'extended_export') {
			$this->createExtendedExport();
			die();
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds=array(), $sAdditional=false) {

		if($sIconAction === 'communication') {
			$oData = $this->createOtherGuiData('Ext_Thebing_Gui2_Data');
			return $oData->getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		} else {
			return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {

		if($sAction === 'communication') {
			$oData = $this->createOtherGuiData('Ext_Thebing_Gui2_Data');
			return $oData->saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		} else {
			return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

	}

	/**
	 * Erweiterter Export: Fragen gruppiert und Schüler-Antworten als Details
	 */
	private function createExtendedExport() {

		$aTableData = $this->getTableQueryData([], [], [], true);

		$oGenerator = new Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_ExtendedExport();
		$oGenerator->generate($aTableData['data']);
		$oGenerator->send();

	}

}