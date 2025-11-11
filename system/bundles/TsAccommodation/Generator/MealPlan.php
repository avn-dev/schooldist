<?php

namespace TsAccommodation\Generator;

use Core\Helper\DateTime;
use TcStatistic\Model\Table\Table;
use TcStatistic\Model\Table\Row;
use TcStatistic\Model\Table\Cell;

/**
 * Class MealPlan
 * @package TsAccommodation\Generator
 */
class MealPlan {

	const TRANSLATION_PATH = 'Thebing » Accommodation » Mealplan';

	protected $sWeek;
	protected $sCategory;
	protected $status;

	/**
	 * MealPlan constructor.
	 * @param string $sWeek
	 * @param string $iCategory
	 */
	public function __construct($sWeek, $iCategory, $status = 'not_matched') {
		$this->sWeek = $sWeek;
		$this->iCategory = $iCategory;
		$this->status = $status;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getMealInfo() {
		
		$where = '';
		
		switch($this->status) {
			case 'not_matched':
				$where .= " AND `kaa`.`id` IS NULL";
				break;
			case 'matched':
				$where .= " AND `kaa`.`id` IS NOT NULL";
				break;
			default:
				break;
		}
		
		//Alle Schüler gewähltem Zeitraum suchen inkl. Matching_Details
		$sSql = "
			SELECT
				`ts_ija`.`id`,
				`ts_ija`.`from`,
				`ts_ija`.`until`,
				`ts_ija`.`active`,
				`ts_imd`.`acc_allergies`,
				`ts_imd`.`acc_muslim_diat`,
				`ts_imd`.`acc_vegetarian`,
				`tc_c`.`firstname`,
				`tc_c`.`lastname`,
			    `k_am`.`meal_plan`
			FROM
				`ts_inquiries_journeys_accommodations` `ts_ija` INNER JOIN 
				`ts_inquiries_journeys` `ts_ij` ON 
					`ts_ij`.`id` = `ts_ija`.`journey_id` AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				    `ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_ic` ON 
				    `ts_ij`.`inquiry_id` = `ts_ic`.`inquiry_id` AND 
				    `ts_ic`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
				    `tc_c`.`id` = `ts_ic`.`contact_id` LEFT JOIN
				`ts_inquiries_matching_data` `ts_imd` ON
					`ts_ij`.`inquiry_id` = `ts_imd`.`inquiry_id` INNER JOIN
				`kolumbus_accommodations_meals` `k_am` ON
					`k_am`.`id` = `ts_ija`.`meal_id` LEFT JOIN
				`kolumbus_accommodations_allocations` AS `kaa` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 AND
					`kaa`.`room_id` > 0
			WHERE
				".($this->iCategory != 0 ? "`ts_ija`.`accommodation_id` = :sCategory AND": "")."
				`ts_ija`.`from` <= :sEndDate  AND
				`ts_ija`.`until` >= :sStartDate AND
				`ts_ija`.`active` = 1
				".$where."			
			;
		";

		$dStartDate = new \DateTime($this->sWeek);
		$dEndDate = new \DateTime($this->sWeek);
		$dEndDate->modify('+6 days');
		$aSql = [
			'sStartDate' => $this->sWeek,
			'sCategory' => $this->iCategory,
			'sEndDate' => $dEndDate->format('Y-m-d')
		];
		$aMealInfo = (array)\DB::getQueryRows($sSql, $aSql);
		$aCountPerDay = $this->prepareMealArray($aMealInfo, $dStartDate);

		return $aCountPerDay;
	}

	/**
	 * @param array $aMealInfo
	 * @param DateTime $dStartDate
	 * @return array
	 */
	public function prepareMealArray(array $aMealInfo, \DateTime $dStartDate) {
		//MealInfo enthält immer 7 Einträge / Für jeden Tag ausgeführt

		for ($i=0; $i<7; $i++) {
			$aMealcount = [
				'breakfast' => 0,
				'lunch' => 0,
				'dinner' => 0
			];

			//Meal enthält die Anzahl der Essen für jede Tageszeit sowie Zusätzliche Informationen (Allergien, etc)
			foreach ($aMealInfo as $aMeal) {

				$aAdditionalStringArray = [];

				if(!empty($aMeal['acc_allergies'])) {
					$aAdditionalStringArray['allergies'] = $aMeal['acc_allergies'];
				};
				if($aMeal['acc_muslim_diat'] == 2) {
					$aAdditionalStringArray['muslim_diet'] = \L10N::t('Halal',self::TRANSLATION_PATH);
				}
				if($aMeal['acc_vegetarian'] == 2) {
					$aAdditionalStringArray['vegetarian'] = \L10N::t('Vegetarier',self::TRANSLATION_PATH);
				}

				$sAdditionalString = '';
				if(!empty($aAdditionalStringArray)) {
					$sAdditionalString = $aMeal['lastname'].", ".$aMeal['firstname'].": ".implode(', ', $aAdditionalStringArray);
				}

				if(
					$aMeal['from'] === $dStartDate->format('Y-m-d') &&
					\Ext_Thebing_Accommodation_Meal::MEAL_PLAN_DINNER & $aMeal['meal_plan']
				) {
					$aMealcount['dinner']++;
					if($sAdditionalString != "") {
						$aMealcount['additional'][] = $sAdditionalString;
					}
				} elseif (
					$aMeal['until'] === $dStartDate->format('Y-m-d') &&
					\Ext_Thebing_Accommodation_Meal::MEAL_PLAN_BREAKFAST & $aMeal['meal_plan']
				) {
					$aMealcount['breakfast']++;
					if($sAdditionalString != "") {
						$aMealcount['additional'][] = $sAdditionalString;
					}
				} elseif (
					$aMeal['from'] < $dStartDate->format('Y-m-d') &&
					$aMeal['until'] > $dStartDate->format('Y-m-d')
				) {
					if(\Ext_Thebing_Accommodation_Meal::MEAL_PLAN_BREAKFAST & $aMeal['meal_plan']) {
						$aMealcount['breakfast']++;
					}
					if (\Ext_Thebing_Accommodation_Meal::MEAL_PLAN_LUNCH & $aMeal['meal_plan']) {
						$aMealcount['lunch']++;
					}
					if (\Ext_Thebing_Accommodation_Meal::MEAL_PLAN_DINNER & $aMeal['meal_plan']) {
						$aMealcount['dinner']++;
					}
					if($sAdditionalString != "") {
						if(
							$aMealcount['breakfast'] == 0 &&
							$aMealcount['lunch'] == 0 &&
							$aMealcount['dinner'] == 0
						) {
							unset($aMealcount['additional']);
						} else {
							$aMealcount['additional'][] = $sAdditionalString;
						}
					}
				}
			}

			$aCountPerDay[] = $aMealcount;

			$dStartDate->modify('+1 days');
		}

		return $aCountPerDay;
	}

	/**
	 * @return array|Table
	 */
	public function getTableData(): Table {
		$oTable = new Table();
		$aMealData = $this->getMealInfo();

		$oRow = $this->getWeekDayColumns();
		$oTable[] = $oRow;
		$oRowBreak = new Row();
		$oRowBreak[] = new Cell(\L10N::t('Frühstück'),self::TRANSLATION_PATH);
		$oRowLunch = new Row();
		$oRowLunch[] = new Cell(\L10N::t('Mittagessen'),self::TRANSLATION_PATH);
		$oRowDinner = new Row();
		$oRowDinner[] = new Cell(\L10N::t('Abendessen'),self::TRANSLATION_PATH);
		$oRowAdditional = new Row();
		$oRowAdditional[] = new Cell(\L10N::t('Zusätzlich'),self::TRANSLATION_PATH);

		foreach ($aMealData as $aMealDay) {
			//Anzahl Früshtück pro Tag
			$oCellBreak = new Cell($aMealDay['breakfast']);
			$oRowBreak[] = $oCellBreak;

			//Anzahl Mittagessen pro Tag
			$oCellLunch = new Cell($aMealDay['lunch']);
			$oRowLunch[] = $oCellLunch;

			//Anzahl Abendessen pro Tag
			$oCellDinner = new Cell($aMealDay['dinner']);
			$oRowDinner[] = $oCellDinner;

			//Zusätzliche Informationen
			if(!empty($aMealDay['additional'])) {
				$oCellAdditional = new Cell(implode("\n", $aMealDay['additional']));
				$oRowAdditional[] = $oCellAdditional;
			}

		}

		$oTable[] = $oRowBreak;
		$oTable[] = $oRowLunch;
		$oTable[] = $oRowDinner;
		$oTable[] = $oRowAdditional;

		return $oTable;
	}

	/**
	 * @return array|Row
	 */
	public function getWeekDayColumns() {

		$oRow = new Row();
		$oTable[] = $oRow;

		$oCell = new Cell(\L10N::t('Wochentag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Montag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Dienstag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Mittwoch',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Donnerstag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Freitag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Samstag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		$oCell = new Cell(\L10N::t('Sonntag',self::TRANSLATION_PATH), true);
		$oCell->setColspan(1);
		$oRow[] = $oCell;

		return $oRow;

	}



}