<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace TsAccommodation\Controller;

use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table\Cell;
use TcStatistic\Model\Table\Row;
use TcStatistic\Model\Table\Table;
use TsAccommodation\Service\AvailabilityService;

class AvailabilityController extends \Illuminate\Routing\Controller
{
	public function overview()
	{
		$aWeeks			= AvailabilityService::getWeeks();
		$aCategories	= AvailabilityService::getCategories();

		$aSchools = \Ext_Thebing_System::getClient()->getSchoolListByAccess(true);
		$iDefaultSchool = 0;
		if (!\Ext_Thebing_System::isAllSchools()) {
			$iDefaultSchool = \Ext_Thebing_School::getSchoolFromSession()->id;
		}

		$aTranslations = array(
			'title'				=> \Ext_TS_System_Navigation::t(),
			'from'				=> \L10N::t('Von', AvailabilityService::$sDescription),
			'till'				=> \L10N::t('bis', AvailabilityService::$sDescription),
			'filter'			=> \L10N::t('Filter', AvailabilityService::$sDescription),
			'school'			=> \L10N::t('Schule', AvailabilityService::$sDescription),
			'category'			=> \L10N::t('Kategorie', AvailabilityService::$sDescription),
			'view'				=> \L10N::t('Darstellung', AvailabilityService::$sDescription),
			'hide_left_navi'	=> \L10N::t('Linkes Menü ausblenden', AvailabilityService::$sDescription),
			'show_left_navi'	=> \L10N::t('Linkes Menü einblenden', AvailabilityService::$sDescription),
			'all'				=> \L10N::t('Alle', AvailabilityService::$sDescription),
			'views'				=> array(
				'total'				=> \L10N::t('ganzer Zeitraum', AvailabilityService::$sDescription),
				'problems'			=> \L10N::t('nur Problemwochen', AvailabilityService::$sDescription),
				'all'				=> \L10N::t('alle Wochen', AvailabilityService::$sDescription),
				'days'				=> \L10N::t('pro Tag', AvailabilityService::$sDescription)
			)
		);

		return response()->view('availability/availability', [
			'aTranslations' => $aTranslations,
			'iCurrentWeek' => AvailabilityService::getWeeks(true),
			'aWeeks' => $aWeeks,
			'aCategories' => $aCategories,
			'aSchools' => $aSchools,
			'iDefaultSchool' => $iDefaultSchool,
			'l10n_path' => AvailabilityService::$sDescription
		]);
	}
	
	public function results(\MVC_Request $request)
	{
		$oSchool = null;
		if (($iSchoolId = (int)$request->get('school', 0)) > 0) {
			$oSchool = \Ext_Thebing_School::getInstance($iSchoolId);
		}

		$oAvailability = new \TsAccommodation\Service\AvailabilityService(
			$request->get('from'),
			$request->get('till'),
			$request->get('category'),
			$request->get('view'),
			$oSchool
		);

		$aData = $oAvailability->getTemplateData();
		if ($request->get('export')) {
			$this->export($oAvailability->createTableData($aData));
		}

		$aData['l10n_path'] = AvailabilityService::$sDescription;
		
		return response()->view('availability/results', $aData);
	}

	public function export(Table $data): void
	{
		$data->setCaption(\L10N::t('Verfügbarkeit', AvailabilityService::$sDescription));
		$excel = new Excel($data);
		$excel->setFileName('availability.xlsx');
		$excel->generate();
		$excel->render();
		die();
	}
	
}
