<?php

namespace TsStatistic\Generator\Statistic;

use \Core\Helper\DateTime;
use \TcStatistic\Exception\NoResultsException;
use \TcStatistic\Model\Statistic\Column;
use \TcStatistic\Model\Table;

class AttendanceScoreProgress extends AbstractGenerator {

	/**
	 * Muss wegen Hook public sein…
	 *
	 * @var \Ext_TC_Flexibility[]
	 */
	public $aFlexFields = [];
	public $aFilters = [];

	/**
	 * @inheritdoc
	 */
	public function __construct() {

		// Konfigurierte Flex-Felder auslesen
		$oConfig = \Ext_TS_Config::getInstance();
		$aFlexFields = unserialize($oConfig->getValue('ts_statistic_attendance_score_progress_columns'));
		if(!empty($aFlexFields)) {
			foreach($aFlexFields as $sFlexField) {
				$iFieldId = explode('_', $sFlexField)[1];
				$this->aFlexFields[$iFieldId] = \Ext_TC_Flexibility::getInstance($iFieldId);
			}
		}

		$aHook = ['action' => 'construct', 'class' => $this];
		\System::wd()->executeHook('tuition_attendance_score_progress_report', $aHook);

	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Fortschrittsbericht (Punkte)');
	}

	/**
	 * @return \TcStatistic\Model\Statistic\Column[][]
	 */
	protected function getColumns() {
		$aColumns = [];

		$oColumn = new Column('customer_number', self::t('Kundennummer'));
		$oColumn->setBackground('booking');
		$aColumns['week_independent'][] = $oColumn;

		$oColumn = new Column('customer_name', self::t('Name'));
		$oColumn->setBackground('booking');
		$aColumns['week_independent'][] = $oColumn;

		$aWeeks = DateTime::getWeekPeriods($this->aFilters['from'], $this->aFilters['until']);
		foreach($aWeeks as $oWeek) {
			$sWeek = $oWeek->from->format('Y-W');
			$oColumn = new Column('week_'.$sWeek, self::t('Woche').' '.$oWeek->from->format('W'));
			$oColumn->aAdditional['week'] = $oWeek;
			$oColumn->setBackground('service', 'dark');
			$aColumns['weeks'][$oColumn->getKey()] = $oColumn;
		}

		$oColumn = new Column('score', self::t('Punkte'));
		$oColumn->setBackground('service');
		$aColumns['week_dependent'][] = $oColumn;

		foreach($this->aFlexFields as $oFlexField) {
			$oColumn = new Column('flex_'.$oFlexField->id, $oFlexField->title);
			$oColumn->setBackground('service');
			$aColumns['week_dependent'][] = $oColumn;
		}

		$aHook = ['action' => 'columns', 'class' => $this, 'columns' => &$aColumns];
		\System::wd()->executeHook('tuition_attendance_score_progress_report', $aHook);

		return $aColumns;
	}

	/**
	 * @return array
	 */
	private function getQueryData() {

		$sSql = "
			SELECT
				`kta`.`id` `attendance_id`,
				`kta`.`score`,
				`ktb`.`id` `block_id`,
				`ktb`.`week`,
				`tc_c`.`id` `contact_id`,
				`tc_cn`.`number` `customer_number`,
				CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) `customer_name`,
				`cdb2`.`id` `school_id`,
				(
					SELECT
						GROUP_CONCAT(CONCAT(`tc_fsfv`.`field_id`, '_', `tc_fsfv`.`value`) SEPARATOR '{|}')
					FROM
						`tc_flex_sections_fields_values` `tc_fsfv`
					WHERE
						`tc_fsfv`.`field_id` IN (:flex_fields) AND
						`tc_fsfv`.`item_id` = `ktbic`.`id`
				) `flex_data`
			FROM
				`kolumbus_tuition_attendance` `kta` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`id` = `kta`.`allocation_id` AND
					`ktbic`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`id` = `ktbic`.`inquiry_course_id` AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1  AND
					`ts_i`.`canceled` = 0 INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id`  INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktb`.`school_id`
			WHERE
				`kta`.`active` = 1 AND
				`cdb2`.`id` IN (:schools) AND
				getCorrectCourseStartDay(`ktb`.`week`, `cdb2`.`course_startday`) <= :until AND
				getCorrectCourseStartDay(`ktb`.`week`, `cdb2`.`course_startday`) + INTERVAL 6 DAY >= :from
			GROUP BY
				`kta`.`id`
		";

		$aResult = (array)\DB::getQueryRows($sSql, [
			'from' => $this->aFilters['from']->format('Y-m-d'),
			'until' => $this->aFilters['until']->format('Y-m-d'),
			'schools' => $this->aFilters['schools'],
			'flex_fields' => array_map(function($oFlexField) {
				return $oFlexField->id;
			}, $this->aFlexFields)
		]);

		return $aResult;

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aResult = $this->getQueryData();

		if(empty($aResult)) {
			throw new NoResultsException();
		}

		$aBlockStructures = $this->getBlockStructures($aResult);
		$aBlockNames = [];

		$aTables = [];
		$aColumns = $this->getColumns();

		$aHook = ['action' => 'block_structure', 'statistic' => $this, 'block_structure' => &$aBlockStructures, 'block_names' => &$aBlockNames];
		\System::wd()->executeHook('tuition_attendance_score_progress_report', $aHook);

		foreach($aBlockStructures as $iMainBlockId => $aBlocks) {

			if(isset($aBlockNames[$iMainBlockId])) {
				// Hook
				$sCaption = $aBlockNames[$iMainBlockId];
			} else {
				$sCaption = $this->formatBlockName(\Ext_Thebing_School_Tuition_Block::getInstance($iMainBlockId));
			}

			$oTable = new Table\Table();
			$oTable->setCaption($sCaption);
			$oTable->exchangeArray($this->generateHeaderRow());
			$aTables[] = $oTable;

			// Die Wochen der Schüler müssen gruppiert werden (nach contact_id und week)
			$aStudents = [];
			foreach($aBlocks as $aBlockStudents) {
				foreach($aBlockStudents as $aBlockStudent) {

					$this->prepareFlexFields($aBlockStudent);

					// Generelle Daten
					if(!isset($aStudents[$aBlockStudent['contact_id']])) {
						$aStudents[$aBlockStudent['contact_id']] = $aBlockStudent;
					}

					// Selber Kontakt ist in selber Woche vom selben Block mehr als einmal zugewiesen
					// TODO Muss vielleicht auf inquiry_id umgestellt werden, sollte der Fall wirklich mal mit zwei Buchungen eintreten
					if(isset($aStudents[$aBlockStudent['contact_id']]['weeks'][$aBlockStudent['week']])) {
						throw new \RuntimeException('Customer block allocation week already exists?');
					}

					$aStudents[$aBlockStudent['contact_id']]['weeks'][$aBlockStudent['week']] = $aBlockStudent;

				}
			}

			$aHook = ['action' => 'students', 'statistic' => $this, 'students' => &$aStudents];
			\System::wd()->executeHook('tuition_attendance_score_progress_report', $aHook);

			// Finale Spalten bzw. Zellen bauen
			foreach($aStudents as $aStudent) {
				$oRow = new Table\Row();
				$oRow->setRowSet('body');

				foreach($aColumns['week_independent'] as $oColumn) {
					$oCell = $oColumn->createCell();
					$oCell->setValue($aStudent[$oColumn->getKey()]);
					$oRow[] = $oCell;
				}

				foreach($aColumns['weeks'] as $oColumn) {
					foreach($aColumns['week_dependent'] as $oColumn2) {
						$oCell = $oColumn2->createCell();
						$oRow[] = $oCell;

						$sWeek = $oColumn->aAdditional['week']->from->format('Y-m-d');
						if(isset($aStudent['weeks'][$sWeek])) {
							$oCell->setValue($aStudent['weeks'][$sWeek][$oColumn2->getKey()]);
						} else {
							$oCell->setBackground($this->getColumnColor('general'));
						}
					}
				}

				$oTable[] = $oRow;
			}

		}

		return $aTables;

	}

	/**
	 * @param array $aResult
	 * @return array
	 */
	private function getBlockStructures(array $aResult) {

		$aBlocks = [];
		foreach($aResult as $aRow) {
			$aBlocks[$aRow['block_id']][] = $aRow;
		}

		$aBlockStructures = [];
		$aProcessedBlocks = [];
		foreach(array_keys($aBlocks) as $iBlockId) {
			if(in_array($iBlockId, $aProcessedBlocks)) {
				continue;
			}

			$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
			$aBlockStructures[$iBlockId] = [$iBlockId => $aBlocks[$iBlockId]];
			$aProcessedBlocks[] = $iBlockId;

			// Zeitraum übergeben, sonst braucht die Statistik bei langen Klassen hundert Jahre
			$aRelevantBlocks = $oBlock->getRelevantBlocks($this->aFilters['from'], $this->aFilters['until']);

			foreach(array_keys($aBlocks) as $iBlockId2) {
				if(in_array($iBlockId2, $aProcessedBlocks)) {
					continue;
				}

				foreach($aRelevantBlocks as $oRelevantBlock) {
					if($oRelevantBlock->id == $iBlockId) {
						continue;
					}

					if($oRelevantBlock->id == $iBlockId2) {
						$aBlockStructures[$iBlockId][$iBlockId2] = $aBlocks[$iBlockId2];
						$aProcessedBlocks[] = $iBlockId2;
					}
				}
			}
		}

		return $aBlockStructures;

	}

	/**
	 * @inheritdoc
	 */
	protected function generateHeaderRow() {

		$aColumns = $this->getColumns();
		$oRow1 = new Table\Row();
		$oRow1->setRowSet('head');
		$oRow2 = new Table\Row();
		$oRow2->setRowSet('head');

		foreach($aColumns['week_independent'] as $oColumn) {
			$oCell = $oColumn->createCell(true);
			$oCell->setRowspan(2);
			$oRow1[] = $oCell;
		}

		foreach($aColumns['weeks'] as $oColumn) {
			$oCell = $oColumn->createCell(true);
			$oCell->setColspan(count($aColumns['week_dependent']));
			$oRow1[] = $oCell;

			foreach($aColumns['week_dependent'] as $oColumn2) {
				$oCell = $oColumn2->createCell(true);
				$oRow2[] = $oCell;
			}
		}

		return [$oRow1, $oRow2];

	}

	public function getBasedOnOptionsForDateFilter() {
		return ['block_structure' => self::t('Blockstruktur')];
	}

	public function getInfoTextListItems() {
		return [
			self::t('Graue Zellen unter den Wochen bedeuten, dass für diese Woche bei dem Schüler keine Anwesenheit eingetragen wurde.')
		];
	}

	/**
	 * @param array $aStudentData
	 */
	private function prepareFlexFields(array &$aStudentData) {

		if(empty($aStudentData['flex_data'])) {
			return;
		}

		$aValues = explode('{|}', $aStudentData['flex_data']);
		foreach($aValues as $sValue) {
			list($iFieldId, $mValue) = explode('_', $sValue, 2);
			$aStudentData['flex_'.$iFieldId] = $this->aFlexFields[$iFieldId]->formatValue($mValue, \Ext_TC_System::getInterfaceLanguage());
		}

	}

	/**
	 * public für Hook!
	 *
	 * @param \Ext_Thebing_School_Tuition_Block $oBlock
	 * @return mixed
	 */
	public function formatBlockName(\Ext_Thebing_School_Tuition_Block $oBlock) {
		$sName = $oBlock->getClass()->getName().', ';
		$sName .= $oBlock->getTemplate()->getNameAndTime();
		return $sName;

	}

}
