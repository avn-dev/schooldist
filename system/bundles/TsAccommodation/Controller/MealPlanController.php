<?php

namespace TsAccommodation\Controller;

use TcStatistic\Generator\Table\Excel;
use TsAccommodation\Generator\MealPlan;

class MealPlanController extends \Ext_Gui2_Page_Controller {

	/**
	 * @return mixed
	 */
	public function pageAction() {

		if($this->_oRequest->get('week') != null) {
			$sWeek = $this->_oRequest->get('week');
		} else {
			$sWeek = date('Y-m-d', strtotime('last monday', strtotime('tomorrow')));
		}

		$iCategory = (int) $this->_oRequest->get('category', 0);
		$selectedStatus = $this->_oRequest->get('status', 0);

		$oMealPlan = new MealPlan($sWeek, $iCategory, $selectedStatus);
		$aWeeks = (array)\Ext_Thebing_Util::getWeekOptions('date', 0);
		$iSelectedSchoolId = \Ext_Thebing_School::getSchoolFromSession()->getId();
		$aAccommodationCategories = \Ext_Thebing_Accommodation_Category::getListForSchools([$iSelectedSchoolId], true);
		$aAccommodationCategories = \Util::addEmptyItem($aAccommodationCategories, \L10N::t('Alle Kategorien',MealPlan::TRANSLATION_PATH), 0);

		$statusOptions = [
			'' => \L10N::t('Alle', MealPlan::TRANSLATION_PATH),
			'not_matched' => \L10N::t('Noch nicht zugewiesen', MealPlan::TRANSLATION_PATH),
			'matched' => \L10N::t('Zugewiesen', MealPlan::TRANSLATION_PATH)
		];
		
		$aViewData['aWeeks'] = $aWeeks;
		$aViewData['aAccommodationCategories'] = $aAccommodationCategories;
		$aViewData['sWeek'] = $sWeek;
		$aViewData['sCategory'] = $iCategory;
		$aViewData['statusOptions'] = $statusOptions;
		$aViewData['selectedStatus'] = $selectedStatus;
		$aViewData['aMeals'] = $oMealPlan->getMealInfo();

		return response()->view('mealplan/mealplan', $aViewData);
	}

	/**
	 *
	 */
	public function export() {

		if($this->_oRequest->get('week') != null) {
			$sWeek = $this->_oRequest->get('week');
		}

		if($this->_oRequest->get('category') !== 0) {
			$sCategory = $this->_oRequest->get('category');
		}

		$oMealPlan = new MealPlan($sWeek, $sCategory);

		$oTable = $oMealPlan->getTableData();
		$oTable->setCaption(\L10N::t('Essensplan', $oMealPlan::TRANSLATION_PATH));
		$oExcel = new Excel($oTable);
		$oExcel->setFileName('Meal Plan '.$sWeek.'/'.$sCategory);
		$oExcel->setTitle('Meal Plan '.$sWeek.'/'.$sCategory);
		$oExcel->generate();
		$oSheet = $oExcel->getSpreadsheetObject()->getActiveSheet();
		$oSheet->getStyle('A6')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		$oSheet->getStyle('B6:H6')->getAlignment()->setWrapText(true);
		for($iCol = 0; $iCol < 8; $iCol++) {
			$sCol = \Util::getColumnCodeForExcel($iCol);
			$oSheet->getColumnDimension($sCol)->setAutoSize(false);
			$oSheet->getColumnDimension($sCol)->setWidth(20);
		}

		$oExcel->render();
		die();

	}

}