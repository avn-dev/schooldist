<?php

use \TcStatistic\Model\Table;
use \TsStatistic\Generator\Statistic\AbstractGenerator;

/**
 * Statische Statistik – Muttersprachen in % je Inbox je Schule
 *
 * https://redmine.thebing.com/redmine/issues/6063
 */
class Ext_Thebing_Management_Statistic_Static_MotherTonguePerInbox extends Ext_Thebing_Management_Statistic_Static_Abstract {

	private $aWeekData = array();

	/** @var Table\Table */
	private $oTable;
	private $aSchoolCols = array();
	private $aInboxCols = array();
	private $aMothertongueCols = array();
	private $aTotalStudentWeekSums = array();

	public function __construct(DateTime $dFrom, DateTime $dUntil) {

		parent::__construct($dFrom, $dUntil);

		// Immer alle Schulen
		$this->_aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);

		$this->oTable = new Table\Table();

	}

	public static function getTitle() {
		return self::t('Muttersprache je Inbox');
	}

	public static function isExportable() {
		return true;
	}

	public function getFakeStatisticObject() {
		$oStatistic = parent::getFakeStatisticObject();
		$oStatistic->customer_invoice_filter = 'invoice';
		return $oStatistic;
	}

	private function getQueryData(DateTime $dFrom, DateTime $dUntil) {

		$sSql = "
			SELECT
				`sub`.*,
				SUM(`sub`.`weeks`) `weeks_total`
			FROM (
				SELECT
					/*`ts_i`.`id` `inquiry_id`,*/
					`cdb2`.`id` `school_id`,
					`kil`.`id` `inbox_id`,
					`tc_c`.`language` `mothertongue_iso`,
					calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`) `weeks`
				FROM
					`ts_inquiries` AS `ts_i` INNER JOIN
					`ts_inquiries_journeys` AS `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_ij`.`active` = 1 INNER JOIN
					`kolumbus_inboxlist` `kil` ON
						`kil`.`short` = `ts_i`.`inbox` INNER JOIN
					`customer_db_2` AS `cdb2` ON
						`ts_ij`.`school_id` = `cdb2`.`id` INNER JOIN
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
					-- Joins für Filter
					`ts_companies` `ka` ON
						`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
					(
						`tc_contacts_to_addresses` AS `tc_cta` INNER JOIN
						`tc_addresses` AS `tc_a` INNER JOIN
						`tc_addresslabels` AS `tc_al`
					) ON
						`tc_cta`.`contact_id` = `tc_c`.`id` AND
						`tc_cta`.`address_id` = `tc_a`.`id` AND
						`tc_a`.`active` = 1 AND
						`tc_a`.`label_id` = `tc_al`.`id` AND
						`tc_al`.`active` = 1 AND
						`tc_al`.`type` = 'contact_address' LEFT JOIN
					(
						`kolumbus_agency_groups_assignments` AS `kaga` INNER JOIN
						`kolumbus_agency_groups` `kag`
					) ON
						`kaga`.`agency_id` = `ka`.`id` AND
						`kag`.`id` = `kaga`.`group_id`
				WHERE
					`ts_i`.`active` = 1 AND
					`ts_i`.`canceled` = 0 AND
					`cdb2`.`active`= 1 AND (
						`ts_i`.`service_from` <= :until AND
						`ts_i`.`service_until` >= :from
					)
					{WHERE}
				GROUP BY
					`ts_ijc`.`id`
			) `sub`
			GROUP BY
				`sub`.`school_id`,
				`sub`.`inbox_id`,
				`sub`.`mothertongue_iso`
		";

		$aSql = array(
			'from' => $dFrom->format('Y-m-d'),
			'until' => $dUntil->format('Y-m-d')
		);

		// Filter-WHERE-Teile hinzufügen
		$this->_addWherePart($sSql, $aSql);

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		return $aResult;

	}

	/**
	 * Rohdaten aus dem Query verarbeiten
	 */
	private function setWeekData() {

		$aWeeks = \Core\Helper\DateTime::getWeekPeriods($this->dFrom, $this->dUntil, false);

		foreach($aWeeks as $oWeek) {
			$aResult = $this->getQueryData($oWeek->from, $oWeek->until);
			$sWeekKey = $oWeek->from->format('Y-W');

			foreach($aResult as $aMothertongueRow) {
				// Alle vorhandenen Spalten sammeln (Value wird später mit richtigem Label überschrieben)
				$this->aSchoolCols[$aMothertongueRow['school_id']] = $aMothertongueRow['school_id'];
				$this->aInboxCols[$aMothertongueRow['inbox_id']] = $aMothertongueRow['inbox_id'];
				$this->aMothertongueCols[$aMothertongueRow['mothertongue_iso']] = $aMothertongueRow['mothertongue_iso'];

				// Summen ermitteln (gruppiert nach Schule/Inbox): Für Totale pro Woche (x), Totale pro Muttersprache (y), Totale (xy)
				$this->aTotalStudentWeekSums[$aMothertongueRow['school_id']][$aMothertongueRow['inbox_id']]['total_week'][$sWeekKey] += $aMothertongueRow['weeks_total'];
				$this->aTotalStudentWeekSums[$aMothertongueRow['school_id']][$aMothertongueRow['inbox_id']]['total_mothertongue'][$aMothertongueRow['mothertongue_iso']] += $aMothertongueRow['weeks_total'];
				$this->aTotalStudentWeekSums[$aMothertongueRow['school_id']][$aMothertongueRow['inbox_id']]['total'] += $aMothertongueRow['weeks_total'];
			}

			foreach($aResult as $aMothertongueRow) {
				// Anteil der Muttersprache (Prozent) ausrechnen
				$fTotalStudentWeeks = $this->aTotalStudentWeekSums[$aMothertongueRow['school_id']][$aMothertongueRow['inbox_id']]['total_week'][$sWeekKey];
				if($fTotalStudentWeeks != 0) {
					$aMothertongueRow['mothertongue_rate'] = $aMothertongueRow['weeks_total'] / $fTotalStudentWeeks * 100;
				} else {
					/*
					 * Division durch 0 verhindern:
					 * Wenn ausgewähltes Datum Samstag oder Sonntag startet, ist der Wert 0,
					 * da reinfallende Kursbuchungen im Wochenzeitraum immer 0 Wochen haben
					 * (wenn sie normal von Montag bis Freitag gehen)
					 */
					$aMothertongueRow['mothertongue_rate'] = 0;
				}

				// Daten gruppieren nach Woche, Schule, Inbox, Muttersprache
				$this->aWeekData[$sWeekKey]['week'] = $oWeek;
				$this->aWeekData[$sWeekKey]['values'][$aMothertongueRow['school_id']][$aMothertongueRow['inbox_id']][$aMothertongueRow['mothertongue_iso']] = $aMothertongueRow;
			}
		}

	}

	/**
	 * Labels für Header-Spalten setzen
	 */
	private function setLabelData() {

		foreach($this->aSchoolCols as $iSchoolId => &$mSchoolData) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
			$mSchoolData = $oSchool->getName();
		}

		foreach($this->aInboxCols as $iInboxId => &$mInboxData) {
			$oInbox = Ext_Thebing_Client_Inbox::getInstance($iInboxId);
			$mInboxData = $oInbox->getName();
		}

		$aLanguages = Ext_TC_Language::getSelectOptions();
		foreach($this->aMothertongueCols as $sMothertongueIso => &$mMothertongueData) {
			$mMothertongueData = $aLanguages[$sMothertongueIso];
		}

		asort($this->aMothertongueCols);

	}

	/**
	 * Tabelle generieren, die mit dem Statistik-Renderer kompatibel ist
	 *
	 * @return array
	 */
	private function generateTable() {

		$this->setWeekData();
		$this->setLabelData();

		$this->setTableHeader();
		$this->setTableData();
		$this->setTableSumRow();

		return $this->oTable;

	}

	/**
	 * Kopfzeilen der Tabelle (Labels) setzen
	 */
	private function setTableHeader() {

		$iInboxTotalCols = 1;
		$iInboxColspan = count($this->aMothertongueCols) * 2;
		$iSchoolColspan = ($iInboxColspan * count($this->aInboxCols)) + (count($this->aInboxCols) * $iInboxTotalCols);

		$this->oTable[0] = new Table\Row();
		$this->oTable[1] = new Table\Row();
		$this->oTable[2] = new Table\Row();
		$this->oTable[3] = new Table\Row();
		$this->oTable[4] = new Table\Row();

		foreach($this->oTable as $oRow) {
			$oRow->setRowSet('head');
		}

		$oCell = new Table\Cell('', true);
		$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
		$oCell->setRowspan(5);
		$this->oTable[0][] = $oCell;

		$oCell = new Table\Cell($this->t('Schule / Inbox / Muttersprache'), true);
		$oCell->setBackground(AbstractGenerator::getColumnColor('general', 'dark'));
		$oCell->setColspan($iSchoolColspan * count($this->aSchoolCols));
		$this->oTable[0][] = $oCell;

		foreach($this->aSchoolCols as $iSchoolId => $sSchoolName) {

			$oCell = new Table\Cell($sSchoolName, true);
			$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
			$oCell->setColspan($iSchoolColspan);
			$this->oTable[1][] = $oCell;

			foreach($this->aInboxCols as $iInboxId => $sInboxName) {

				$oCell = new Table\Cell($sInboxName, true);
				$oCell->setBackground(AbstractGenerator::getColumnColor('booking', 'dark'));
				$oCell->setColspan($iInboxColspan);
				$this->oTable[2][] = $oCell;

				$oCell = new Table\Cell($this->t('Total'), true);
				$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
				$oCell->setRowspan(2);
				$this->oTable[2][] = $oCell;

				foreach($this->aMothertongueCols as $sMothertongueIso => $sMothertongueName) {

					$oCell = new Table\Cell($sMothertongueName, true);
					$oCell->setBackground(AbstractGenerator::getColumnColor('agency'));
					$oCell->setColspan(2);
					$this->oTable[3][] = $oCell;

					$oCell = new Table\Cell($this->t('Totale Schülerwochen (kursbezogen, mit Rechnung)'), true);
					$oCell->setBackground(AbstractGenerator::getColumnColor('booking'));
					$this->oTable[4][] = $oCell;

					$oCell = new Table\Cell($this->t('Anteil Muttersprache in %'), true);
					$oCell->setBackground(AbstractGenerator::getColumnColor('booking'));
					$this->oTable[4][] = $oCell;

				}

				$oCell = new Table\Cell($this->t('Totale Schülerwochen (kursbezogen, mit Rechnung)'), true);
				$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
				$this->oTable[4][] = $oCell;

			}
		}

	}

	/**
	 * Daten der Tabelle (je Woche bzw. Query eine Zeile) setzen
	 */
	private function setTableData() {

		foreach($this->aWeekData as $aWeekData) {
			$sWeekKey = $aWeekData['week']->from->format('Y-W');
			$sWeekFormatted = Ext_Thebing_Format::LocalDate($aWeekData['week']->from->getTimestamp()).' - ';
			$sWeekFormatted .= Ext_Thebing_Format::LocalDate($aWeekData['week']->from->getTimestamp());

			$oRow = new Table\Row();

			$oCell = new Table\Cell($sWeekFormatted, true);
			$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
			$oCell->setNoWrap(true);
			$oRow[] = $oCell;

			// Über Keys durchlaufen, damit Tabelle festes Layout hat (und ansonsten leere Zellen)
			foreach(array_keys($this->aSchoolCols) as $iSchoolId) {
				foreach(array_keys($this->aInboxCols) as $iInboxId) {
					foreach(array_keys($this->aMothertongueCols) as $sMothertongueIso) {

						$iWeeksTotal = 0;
						$iMothertongueRate = 0;

						// Da die Spalten durch die ermittelten Labels vorgegeben sind, muss der Wert nicht vorhanden sein
						if(!empty($aWeekData['values'][$iSchoolId][$iInboxId][$sMothertongueIso])) {
							$aCellData = $aWeekData['values'][$iSchoolId][$iInboxId][$sMothertongueIso];
							$iWeeksTotal = $aCellData['weeks_total'];
							$iMothertongueRate = $aCellData['mothertongue_rate'];
						}

						$oRow[] = new Table\Cell(round($iWeeksTotal, 2), false, 'number_float');
						$oRow[] = new Table\Cell(round($iMothertongueRate, 2), false, 'number_float');
					}

					// Summenzelle: Summe pro Woche (X-Achse)
					if(!empty($this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total_week'][$sWeekKey])) {
						$oRow[] = new Table\Cell(round($this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total_week'][$sWeekKey], 2), false, 'number_float');
					} else {
						$oRow[] = new Table\Cell(0, false, 'number_int');
					}
				}
			}

			$this->oTable[] = $oRow;
		}

	}

	/**
	 * Summenzeile ausrechnen
	 */
	private function setTableSumRow() {

		$oRow = new Table\Row();
		$oRow->setRowSet('foot');

		$oCell = new Table\Cell($this->t('Total'), true);
		$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
		$oRow[] = $oCell;

		foreach(array_keys($this->aSchoolCols) as $iSchoolId) {
			foreach(array_keys($this->aInboxCols) as $iInboxId) {

				// Summe total (gruppiert nach Schule/Inbox; X-Achse + Y-Achse)
				$fSumTotal = 0;
				if(!empty($this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total'])) {
					$fSumTotal = $this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total'];
				}

				foreach(array_keys($this->aMothertongueCols) as $sMothertongueIso) {

					// Summe pro Muttersprache (Y-Achse)
					$fSumPerMothertongue = 0;
					if(!empty($this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total_mothertongue'][$sMothertongueIso])) {
						$fSumPerMothertongue = $this->aTotalStudentWeekSums[$iSchoolId][$iInboxId]['total_mothertongue'][$sMothertongueIso];
					}

					// Anteil der Muttersprache (Prozent) für Y-Achse ausrechnen
					$fSumPerMothertonguePercent = 0;
					if(
						Ext_TC_Util::compareFloat($fSumTotal, 0) != 0 &&
						Ext_TC_Util::compareFloat($fSumPerMothertongue, 0) != 0
					) {
						$fSumPerMothertonguePercent = $fSumPerMothertongue / $fSumTotal * 100;
					}

					$oCell = new Table\Cell(round($fSumPerMothertongue, 2), true, 'number_float');
					$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
					$oRow[] = $oCell;

					$oCell = new Table\Cell(round($fSumPerMothertonguePercent, 2), true, 'number_float');
					$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
					$oRow[] = $oCell;

				}

				// Summe total (gruppiert nach Schule/Inbox; X-Achse + Y-Achse)
				$oCell = new Table\Cell(round($fSumTotal, 2), true, 'number_float');
				$oCell->setBackground(AbstractGenerator::getColumnColor('general'));
				$oRow[] = $oCell;

			}
		}

		$this->oTable[] = $oRow;

	}

	/**
	 * HTML-Ausgabe
	 *
	 * @return string
	 */
	public function render() {

		$oTable = $this->generateTable();

		$oGenerator = new \TsStatistic\Generator\Table\Html($oTable);
		$oGenerator->sTableCSSClass = 'stat_result';
		return $oGenerator->generate();

	}

	/**
	 * Excel-Ausgabe (Download)
	 */
	public function getExport() {

		$oTable = $this->generateTable();

		$oGenerator = new \TcStatistic\Generator\Table\Excel($oTable);
		$oGenerator->setFileName(self::getTitle());
		$oGenerator->generate();
		$oGenerator->render();

	}
}
