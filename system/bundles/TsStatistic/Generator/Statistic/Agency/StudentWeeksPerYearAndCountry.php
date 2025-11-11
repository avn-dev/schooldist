<?php

namespace TsStatistic\Generator\Statistic\Agency;

use TcStatistic\Exception;
use TcStatistic\Generator\Table\AbstractTable;
use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Statistic\Column;
use TcStatistic\Model\Table;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TsStatistic\Model\Filter;

/**
 * Schülerwochen pro Agentur und Land + Auflistung Agenturgruppen
 *
 * https://redmine.thebing.com/redmine/issues/7406
 */
class StudentWeeksPerYearAndCountry extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Courses::class
	];

	/** @var Column[] */
	protected $aColumns = [];

	/** @var Column[] */
	protected $aSchoolColumns = [];

	/** @var int[]|null */
	protected $aChangeColumnYears;

	/** @var int[] */
	protected $aYears = [];

	/** @var string[] */
	protected $aCountryNames = [];

	/** @var string[] */
	protected $aAgencyNames = [];

	/** @var string[] */
	protected $aAgencyGroupNames = [];

	/**
	 * @inheritdoc
	 */
	public function __construct() {
		//$this->aCountryNames[0] = \Ext_TC_L10N::getEmptySelectLabel('country');
		$this->aCountryNames[0] = self::t('Kein Land');
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Schülerwochen pro Agentur / Land');
	}

	/**
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @return array
	 */
	protected function getQueryData(\DateTime $dFrom, \DateTime $dUntil) {

		$sLanguage = \Ext_TC_System::getInterfaceLanguage();

		// Daten für Direktbuchungen
		$oSchool = \Ext_Thebing_School::getFirstSchool();
		$sAgency = mb_strtoupper(self::t('Direktbuchungen'));
		$sAgencyGroup = mb_strtoupper(self::t('Direktbuchungen'));

		$sSelect = " SUM(`ts_ijc`.`weeks`) `weeks` ";
		$sWhere = " AND `ts_i`.`created` BETWEEN :from AND :until ";
		if($this->aFilters['based_on'] === 'service_period') {
			$sSelect = " SUM(calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`)) `weeks` ";
			$sWhere = " AND `ts_i`.`service_from` <= :until AND `ts_i`.`service_until` >= :from ";
		}

		$sSql = "
			SELECT
				`ka`.`id` `agency_id`,
				IFNULL(`ka`.`ext_1`, '{$sAgency}') `agency_name`,
				IFNULL(`ka`.`ext_6`, '{$oSchool->country_id}') `country_iso`,
				`dc`.`cn_short_{$sLanguage}` `country_name`,
				`ts_ij`.`school_id`,
				IF(
					`ka`.`id` IS NULL,
					 '0,{$sAgencyGroup}',
					 (
					 	SELECT
					 		CONCAT(`kag`.`id`, ',', `kag`.`name`)
						FROM
							`kolumbus_agency_groups` `kag` INNER JOIN
							`kolumbus_agency_groups_assignments` `kaga` ON
								`kaga`.`group_id` = `kag`.`id`
						WHERE
							`kag`.`active` = 1 AND
							`kaga`.`agency_id` = `ka`.`id`
						ORDER BY
							`kag`.`id`
						LIMIT
							1
					 )
				) `agency_group`,
				{$sSelect}
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`ts_journeys_travellers_detail` `ts_jtd` ON
					`ts_jtd`.`journey_id` = `ts_ij`.`id` AND
					`ts_jtd`.`traveller_id` = `tc_c`.`id` AND
					`ts_jtd`.`type` = 'guide' LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`data_countries` AS `dc` ON
					`dc`.`cn_iso_2` = IFNULL(`ka`.`ext_6`, '{$oSchool->country_id}')
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`canceled` = 0 AND
				`ts_ij`.`school_id` IN (:schools) AND
				`ts_ijc`.`course_id` IN (:courses) AND
				IFNULL(`ts_jtd`.`value`, 0) = 0
				{$sWhere}
			GROUP BY
				`ka`.`id`,
				`ts_ij`.`school_id`
				
		";

		$aResult = (array)\DB::getQueryRows($sSql, [
			'from' => $dFrom->format('Y-m-d'),
			'until' => $dUntil->format('Y-m-d'),
			'schools' => $this->aFilters['schools'],
			'courses' => $this->aFilters['courses']
		]);

		return $aResult;

	}

	/**
	 * @return \Core\DTO\DateRange[]
	 */
	protected function getYearsForQuery() {

		// Filter darf nicht länger als ein Jahr sein
		$dFilterFrom1Yr = clone $this->aFilters['from'];
		$dFilterFrom1Yr->add(new \DateInterval('P1Y'));
		if($this->aFilters['until'] >= $dFilterFrom1Yr) {
			throw new Exception\InvalidDateException();
		}

		$aYears = [];
		foreach([2, 1, 0] as $iInterval) {
			$oInterval = new \DateInterval('P'.$iInterval.'Y');
			$dFrom = clone $this->aFilters['from'];
			$dFrom->sub($oInterval);
			$dUntil = clone $this->aFilters['until'];
			$dUntil->sub($oInterval);
			$aYears[] = new \Core\DTO\DateRange($dFrom, $dUntil);
		}

		return $aYears;

	}

	/**
	 * @return array
	 */
	protected function prepareData() {

		$bHasData = false;
		$aYearData = [];
		$aYears = $this->getYearsForQuery();

		foreach($aYears as $oDateRange) {
			$aData = $this->getQueryData($oDateRange->from, $oDateRange->until);
			$aYearData[$oDateRange->until->format('Y')] = $aData;

			$sLabel = $oDateRange->until->format('Y');
			if($this->aFilters['from']->format('Y') != $this->aFilters['until']->format('Y')) {
				$sLabel = $oDateRange->from->format('Y').'/'.$sLabel;
			}
			$this->aYears[$oDateRange->until->format('Y')] = $sLabel;

			if(!empty($aData)) {
				$bHasData = true;
			}
		}

		if(!$bHasData) {
			throw new Exception\NoResultsException();
		}

		$aGroupedData = [];
		foreach($aYearData as $iYear => $aYearData2) {
			foreach($aYearData2 as $aRowData) {

				$sCountryIso = $aRowData['country_iso'];
				if($aRowData['country_name'] === null) {
					// In alten Datenbanken oder bei Imports scheint hier auch Müll drin zu stehen
					$sCountryIso = 0;
				} else {
					$this->aCountryNames[$sCountryIso] = $aRowData['country_name'];
				}

				$this->aAgencyNames[$aRowData['agency_id']] = $aRowData['agency_name'];

				if(isset($aGroupedData[$sCountryIso][$aRowData['agency_id']][$aRowData['school_id']][$iYear])) {
					// Sollte nicht auftreten (country_iso gehört zu Agency und ansonsten wird nach ka.id, school_id und $iYear gruppiert)
					throw new \LogicException('Data set does already exist in data array?');
				}

				if(!empty($aRowData['agency_group'])) {
					list($iAgencyGroupId, $sAgencyGroupName) = explode(',', $aRowData['agency_group'], 2);
					$aRowData['agency_group_id'] = (int)$iAgencyGroupId;
					$this->aAgencyGroupNames[$iAgencyGroupId] = $sAgencyGroupName;
				}

				$aGroupedData[$sCountryIso][$aRowData['agency_id']][$aRowData['school_id']][$iYear] = $aRowData;
			}
		}

		// Nach Ländernamen sortieren (MySQL funktioniert hiert nicht, da mehrere Querys)
		$aSortNull = $aSortCountry = [];
		foreach(array_keys($aGroupedData) as $sCountryIso) {
			$aSortNull[] = empty($sCountryIso);
			$aSortCountry[] = $sCountryIso;
 		}
		array_multisort($aSortNull, SORT_ASC, $aSortCountry, SORT_ASC, $aGroupedData);

		// Nach Agenturnamen sortieren
		foreach($aGroupedData as &$axCountryData) {
			uksort($axCountryData, function($iAgencyId1, $iAgencyId2) {
				return strnatcasecmp($this->aAgencyNames[$iAgencyId1], $this->aAgencyNames[$iAgencyId2]);
			});
		}

		return $aGroupedData;

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aData = $this->prepareData();

		$this->getColumns();

		$aRows = $this->generateHeaderRow();
		$oTable = new Table\Table();
		$oTable[] = $aRows[0];
		$oTable[] = $aRows[1];

		//$aColumns = $this->getColumns();

		$aGrandTotal = [];
		$aAgencyGroupTotal = [];
		foreach($aData as $sCountryIso => $aCountryData) {

			// Agenturzeile
			$aCountrySum = [];
			foreach($aCountryData as $iAgencyId => $aAgencyData) {

				$oRow = new Table\Row();
				$oTable[] = $oRow;

				$sAgencyName = $this->aAgencyNames[$iAgencyId];
				if(\System::d('debugmode') == 2) {
					$sAgencyName .= ' ('.$iAgencyId.')';
				}
				$oCell = $this->aColumns['agency_name']->createCell();
				$oCell->setValue($sAgencyName);
				$oRow[] = $oCell;

				if($iAgencyId == 0) {
					$oCell->setFontStyle('bold');
				}

				$oCell = $this->aColumns['country_name']->createCell();
				$oCell->setValue(mb_strtoupper($this->aCountryNames[$sCountryIso]));
				$oRow[] = $oCell;

				$fAgencySum = 0;
				foreach($this->aSchoolColumns as $oColumn) {
					$oCell = $oColumn->createCell();
					$oRow[] = $oCell;

					$iSchoolId = $oColumn->aAdditional['school_id'];
					$iYear = $oColumn->aAdditional['year'];

					if(isset($aAgencyData[$iSchoolId][$iYear])) {
						$fWeeks = (float)$aAgencyData[$iSchoolId][$iYear]['weeks'];
						$oCell->setValue($fWeeks);

						// Summe für Agentur (vertikal)
						$fAgencySum += $fWeeks;

						// Summe für Länderzeile (horizontal)
						if(!isset($aCountrySum[$iSchoolId][$iYear])) {
							$aCountrySum[$iSchoolId][$iYear] = 0;
						}
						$aCountrySum[$iSchoolId][$iYear] += $fWeeks;

						// Summe für Agenturgruppen (horizontal, untere Tabelle)
						if($aAgencyData[$iSchoolId][$iYear]['agency_group_id'] !== null) {
							$iAgencyGroupId = $aAgencyData[$iSchoolId][$iYear]['agency_group_id'];

							if(!isset($aAgencyGroupTotal[$iAgencyGroupId][$iSchoolId][$iYear])) {
								$aAgencyGroupTotal[$iAgencyGroupId][$iSchoolId][$iYear] = 0;
							}
							$aAgencyGroupTotal[$iAgencyGroupId][$iSchoolId][$iYear] += $fWeeks;
						}
					}
				}

				$oCell->setBorder(Table\Cell::BORDER_RIGHT);

				$this->setTotalCellsToRow($oRow, $aAgencyData);

			}

			// Summenzeile pro Land
			$oTable[] = $this->generateCountrySumRow($sCountryIso, $aCountrySum, $aGrandTotal);

		}

		$oTable[] = $this->generateSumRow($aGrandTotal);

		$this->setAgencyGroupTableRows($aAgencyGroupTotal, $oTable);

		return $oTable;

	}

	/**
	 * Summenzeile pro Land generieren
	 *
	 * @param string $sCountryIso
	 * @param array $aCountrySum
	 * @param array $aGrandTotal
	 * @return Table\Row
	 */
	protected function generateCountrySumRow($sCountryIso, array $aCountrySum, array &$aGrandTotal) {

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell();
		$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
		$oRow[] = $oCell;

		$oCell = $this->aColumns['country_name']->createCell();
		$oCell->setValue(mb_strtoupper($this->aCountryNames[$sCountryIso].' '.self::t('Total')));
		$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
		$oCell->setFontStyle('bold');
		$oRow[] = $oCell;

		// Summenzeile pro Land
		foreach($this->aSchoolColumns as $oColumn) {
			$oCell = $oColumn->createCell();
			$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
			$oCell->setFontStyle('bold');
			$oRow[] = $oCell;

			$iSchoolId = $oColumn->aAdditional['school_id'];
			$iYear = $oColumn->aAdditional['year'];

			$oCell->setValue(0);
			if(isset($aCountrySum[$iSchoolId][$iYear])) {
				$oCell->setValue($aCountrySum[$iSchoolId][$iYear]);

				if(!isset($aGrandTotal[$iSchoolId])) {
					$aGrandTotal[$iSchoolId][$iYear] = 0;
				}

				$aGrandTotal[$iSchoolId][$iYear] += $aCountrySum[$iSchoolId][$iYear];
			}
		}

		$oCell->setBorder(Table\Cell::BORDER_RIGHT | Table\Cell::BORDER_BOTTOM);

		$this->setTotalCellsToRow($oRow, $aCountrySum, true);

		return $oRow;

	}

	/**
	 * Summen-Zellen rechts (pro Zeile) setzen
	 *
	 * @param Table\Row $oRow
	 * @param array $aAmount
	 * @param bool $bBoldColumn
	 * @param bool $bGrayBackground
	 */
	protected function setTotalCellsToRow(Table\Row $oRow, array $aAmount, $bBoldColumn = false, $bGrayBackground = false) {

		// Wochenzahl aus Datensammlung rausfischen
		$fTotal = 0;
		$aYearTotal = [];
		foreach($aAmount as $iSchoolId => $aYearData) {
			foreach($aYearData as $iYear => $mData) {
				if(is_numeric($mData)) {
					$fValue = $mData;
				} elseif(
					is_array($mData) &&
					isset($mData['weeks'])
				) {
					$fValue = (float)$mData['weeks'];
				} else {
					throw new \InvalidArgumentException('Unknown array structure');
				}

				if(!isset($aYearTotal[$iYear])) {
					$aYearTotal[$iYear] = 0;
				}

				$fTotal += $fValue;
				$aYearTotal[$iYear] += $fValue;
			}
		}

		$oSetCellSettings = function(Table\Cell $oCell) use($bBoldColumn, $bGrayBackground) {
			if($bBoldColumn) {
				$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
				$oCell->setFontStyle('bold');
			}
			if($bGrayBackground) {
				$oCell->setBackground(self::getColumnColor('general'));
			}
		};

		foreach(array_keys($this->aYears) as $iYear) {
			$oCell = $this->aColumns['agency_sum_'.$iYear]->createCell();
			$oCell->setValue($aYearTotal[$iYear]);
			$oSetCellSettings($oCell);
			$oRow[] = $oCell;
		}

//		$oCell = $this->aColumns['agency_sum_total']->createCell();
//		$oCell->setValue($fTotal);
//		$oSetCellSettings($oCell);
//		$oRow[] = $oCell;

		// Spalten für absolute/relative Änderung
		if(
			$this->aChangeColumnYears !== null &&
			$this->aChangeColumnYears[1] > 0
		) {
			$fOldValue = (float)$aYearTotal[$this->aChangeColumnYears[0]];
			$fNewValue = (float)$aYearTotal[$this->aChangeColumnYears[1]];

			$fChangeAbsolute = $fNewValue - $fOldValue;

			$fChangeRelativePercent = null;
			if($fOldValue != 0) {
				$fChangeRelativePercent = round($fChangeAbsolute / $fOldValue * 100, 2);
			}

			$oCell = $this->aColumns['agency_sum_change_absolute']->createCell();
			$oCell->setValue($aYearTotal[$this->aChangeColumnYears[1]] - $aYearTotal[$this->aChangeColumnYears[0]]);
			$oSetCellSettings($oCell);
			$oRow[] = $oCell;

			$oCell = $this->aColumns['agency_sum_change_relative']->createCell();
			$oCell->setValue($fChangeRelativePercent);
			$oSetCellSettings($oCell);
			$oRow[] = $oCell;
		}

	}

	/**
	 * Summenzeile aller Summen
	 *
	 * @param array $aGrandTotal
	 * @return array|Table\Row
	 */
	protected function generateSumRow(array $aGrandTotal) {

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(self::t('Gesamtsumme'), true);
		$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
		$oCell->setColspan(2);
		$oRow[] = $oCell;

		foreach($this->aSchoolColumns as $oColumn) {
			$iSchoolId = $oColumn->aAdditional['school_id'];
			$iYear = $oColumn->aAdditional['year'];
			$fValue = 0;

			if(isset($aGrandTotal[$iSchoolId][$iYear])) {
				$fValue = $aGrandTotal[$iSchoolId][$iYear];
			}

			$oCell = new Table\Cell($fValue, true, 'number_float');
			$oCell->setBorder(Table\Cell::BORDER_BOTTOM);
			$oCell->setBackground(self::getColumnColor('general'));
			$oRow[] = $oCell;
		}

		$oCell->setBorder(Table\Cell::BORDER_RIGHT | Table\Cell::BORDER_BOTTOM);

		$this->setTotalCellsToRow($oRow, $aGrandTotal, true, true);

		return $oRow;

	}

	/**
	 * Agenturgruppen (untere Tabelle) generieren
	 *
	 * @param array $aAgencyGroupTotal
	 * @param Table\Table $oTable
	 */
	protected function setAgencyGroupTableRows(array $aAgencyGroupTotal, Table\Table $oTable) {

		$oRow = new Table\Row();
		$oCell = new Table\Cell();
		$oCell->setColspan(count($this->aColumns));
		$oRow[] = $oCell;
		$oTable[] = $oRow;

		$aRows = $this->generateHeaderRow();
		$aRows[0]->setRowSet('body');
		$aRows[1]->setRowSet('body');
		$aRows[1]->offsetUnset(0);
		$aRows[1]->offsetUnset(1);
		$oTable[] = $aRows[0];
		$oTable[] = $aRows[1];

		$oAgencyGroupColumn = new Column('agency_group', self::t('Agenturgruppe'));
		$oAgencyGroupColumn->setBackground('agency');

		$oCell = $oAgencyGroupColumn->createCell(true);
		$oCell->setColspan(2);
		$aRow = $aRows[1]->getArrayCopy();
		array_unshift($aRow, $oCell);
		$aRows[1]->exchangeArray($aRow);

		uasort($this->aAgencyGroupNames, function($sAgencyGroup1, $sAgencyGroup2) {
			return strnatcasecmp($sAgencyGroup1, $sAgencyGroup2);
		});

		// Agenturgruppen (Zeilen) durchlaufen
		foreach($this->aAgencyGroupNames as $iAgencyGroupId => $sAgencyGroupName) {
			$oRow = new Table\Row();
			$oTable[] = $oRow;

			$oCell = $oAgencyGroupColumn->createCell();
			$oCell->setValue($sAgencyGroupName);
			$oCell->setColspan(2);
			$oRow[] = $oCell;

			if($iAgencyGroupId == 0) {
				$oCell->setFontStyle('bold');
			}

			foreach($this->aSchoolColumns as $oColumn) {
				$iSchoolId = $oColumn->aAdditional['school_id'];
				$iYear = $oColumn->aAdditional['year'];

				$oCell = $oColumn->createCell();
				$oCell->setValue(0);
				$oRow[] = $oCell;

				if(isset($aAgencyGroupTotal[$iAgencyGroupId][$iSchoolId][$iYear])) {
					$oCell->setValue($aAgencyGroupTotal[$iAgencyGroupId][$iSchoolId][$iYear]);
				}
			}

			$oCell->setBorder(Table\Cell::BORDER_RIGHT);

			$this->setTotalCellsToRow($oRow, $aAgencyGroupTotal[$iAgencyGroupId]);
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function getColumns() {

		$oColumn = new Column('agency_name', self::t('Agentur'));
		$oColumn->setBackground('agency');
		$this->aColumns[$oColumn->getKey()] = $oColumn;

		$oColumn = new Column('country_name', self::t('Land'));
		$oColumn->setBackground('agency');
		$this->aColumns[$oColumn->getKey()] = $oColumn;

		$this->setSchoolColumns();

		foreach($this->aYears as $iYear => $sYear) {
			$oColumn = new Column('agency_sum_'.$iYear, self::t('Summe').' '.$sYear, 'number_int');
			$oColumn->setBackground('general');
			$this->aColumns[$oColumn->getKey()] = $oColumn;
		}

//		$oColumn = new Column('agency_sum_total', self::t('Summe'), 'number_float');
//		$oColumn->setBackground('general');
//		$this->aColumns[$oColumn->getKey()] = $oColumn;

		if(count($this->aYears) >= 2) {
			$this->aChangeColumnYears = array_slice(array_keys($this->aYears), -2, 2);
			$sYears = ' '.$this->aChangeColumnYears[0].' - '.$this->aChangeColumnYears[1];

			$oColumn = new Column('agency_sum_change_absolute', '# '.self::t('Änderung').$sYears, 'number_int');
			$oColumn->setBackground('general');
			$this->aColumns[$oColumn->getKey()] = $oColumn;

			$oColumn = new Column('agency_sum_change_relative', '% '.self::t('Änderung').$sYears, 'number_percent_color');
			$oColumn->setBackground('general');
			$oColumn->bFormatNullValue = false;
			$oColumn->mNullValueReplace = '–';
			$this->aColumns[$oColumn->getKey()] = $oColumn;
		}

		return $this->aColumns;

	}

	/**
	 * Spalten für die Schulen setzen
	 */
	protected function setSchoolColumns() {

		foreach($this->aFilters['schools'] as $iSchoolId) {
			$oSchool = \Ext_Thebing_School::getInstance($iSchoolId);
			foreach($this->aYears as $iYear => $sYear) {
				$oColumn = new Column('school_'.$oSchool->id.'_'.$iYear, $oSchool->getName().' '.$sYear, 'number_float');
				$oColumn->setBackground('booking');
				$oColumn->bFormatNullValue = false;
				$oColumn->aAdditional = ['school_id' => $oSchool->id, 'year' => $iYear];
				$this->aColumns[$oColumn->getKey()] = $oColumn;
				$this->aSchoolColumns[$oColumn->getKey()] = $oColumn;
			}
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function generateHeaderRow() {

		$oRow1 = new Table\Row();
		$oRow1->setRowSet('head');

		$iAgencyColumns = 2;
		$iSchoolColumns = count($this->aSchoolColumns);
		$iSumColumns = count($this->aYears) + 1;
		if(!empty($this->aChangeColumnYears)) {
			$iSumColumns += count($this->aChangeColumnYears);
		}

		$oCell = new Table\Cell(self::t('Agenturen'), true);
		$oCell->setBackground(self::getColumnColor('agency', 'dark'));
		$oCell->setColspan($iAgencyColumns);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell(self::t('Schülerwochen pro Schule'), true);
		$oCell->setBackground(self::getColumnColor('booking', 'dark'));
		$oCell->setBorder(Table\Cell::BORDER_RIGHT);
		$oCell->setColspan($iSchoolColumns);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell(self::t('Summen'), true);
		$oCell->setBackground(self::getColumnColor('general', 'dark'));
		$oCell->setColspan($iSumColumns);
		$oRow1[] = $oCell;

		$oRow2 = new Table\Row();
		$oRow2->setRowSet('head');
		foreach($this->aColumns as $oColumn) {
			$oCell = $oColumn->createCell(true);
			$oRow2[] = $oCell;
			if($oColumn === end($this->aSchoolColumns)) {
				$oCell->setBorder(Table\Cell::BORDER_RIGHT);
			}
		}

		return [$oRow1, $oRow2];

	}

	/**
	 * @inheritdoc
	 */
	public function generateViewGenerator(AbstractTable $oGenerator) {

		$mMixed = parent::generateViewGenerator($oGenerator);

		if($oGenerator instanceof Excel) {
			
			/** @var \TcStatistic\Generator\Table\Excel $oGenerator */
			$oSpreadsheet = $oGenerator->getSpreadsheetObject();
			$oSpreadsheet->getActiveSheet()->freezePane('C3');

			$oSpreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
			$oSpreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(40);
			$oSpreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
			$oSpreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			
		}

		return $mMixed;
	}

	/**
	 * @inheritdoc
	 */
	public function getFilters() {
		$aFilters = parent::getFilters();
//		$aFilters['schools']->setAllDefaultValues();
		return $aFilters;
	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'registration_date' => self::t('Buchungsdatum'),
			'service_period' => self::t('Leistungszeitraum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			//self::t('Diese Statistik berücksichtig jeweils den Zeitraum eines kompletten Jahres, nicht nur den des Filters.'),
			self::t('Diese Statistik berücksichtigt neben dem Zeitraum auch den Zeitraum der letzten beiden Jahre. Es kann nur ein Zeitraum von einem Jahr ausgewählt werden.'),
			self::t('Die Länder basieren auf dem Land der Agentur, nicht dem Land des Schülers.'),
			self::t('Agenturen mit mehr als einer Agenturgruppe werden der ersten Agenturgruppe (Erstellungsdatum) zugeordnet.')
		];
	}

}
