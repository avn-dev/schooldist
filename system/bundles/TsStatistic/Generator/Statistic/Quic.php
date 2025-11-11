<?php

namespace TsStatistic\Generator\Statistic;

use \PhpOffice\PhpSpreadsheet\Writer\Xls;
use \TcStatistic\Exception\AlertException;
use \TcStatistic\Exception\NoResultsException;
use \TcStatistic\Generator\Table\AbstractTable;
use \TcStatistic\Generator\Table\Excel;
use \TcStatistic\Model\Statistic\Column;
use \TcStatistic\Model\Table;
use TsStatistic\Handler\ExternalApp;
use \TsStatistic\Helper\QuicExcel;

/**
 * Ticket #12458 – QUIC Report
 */
class Quic extends AbstractGenerator {

	/**
	 * @var QuicExcel
	 */
	private $oQuicExcelHelper;

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('QUIC Report');
	}

	/**
	 * @return array
	 */
	private function getQueryData() {

		$sSql = "
			SELECT
			  	`ts_i`.`id`,
				`ts_i`.`agency_id`,
				`ts_i`.`group_id`,
				`tc_c`.`nationality`,
				`ktc`.`uk_quarterly_course_type`,
				/*IF(getAge(`tc_c`.`birthday`) >= 18, 'adult', 'junior') `age_group`,*/
				IF(`ktc`.`uk_quarterly_junior_course` = 1, 'junior', 'adult') `age_group`,
				CEIL(calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`)) `weeks`,
				SUM(IF(
					/* TODO: Lektionskurse mit Ferien können so nicht funktionieren */
					`ktc`.`per_unit` != ".\Ext_Thebing_Tuition_Course::TYPE_PER_UNIT." AND `ktc`.`per_unit` != ".\Ext_Thebing_Tuition_Course::TYPE_EXAMINATION.",
					`ts_ijclc`.`lessons`,
					IF(`ts_ijclc`.`weeks` > 0, `ts_ijclc`.`lessons` / `ts_ijclc`.`weeks`, 0)
				)) `lessons`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN 
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN 
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND 
					`ts_ijc`.`active`      = 1 AND
					`ts_ijc`.`for_tuition` = 1 INNER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
					`ts_tcps`.`active` = 1 AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' INNER JOIN
				`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
					`ts_ijclc`.`journey_course_id` = `ts_ijc`.`id` AND
					`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`active` = 1  AND
					`ktc`.`per_unit` != ".\Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." AND
					`ktc`.`id` = `ts_tcps`.`type_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itoc` ON
					`ts_i`.`id` = `ts_itoc`.`inquiry_id` AND
					`ts_itoc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.id = `ts_itoc`.`contact_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`has_invoice` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`canceled` = 0 AND
				`ts_ijc`.`from` <= :until AND
				`ts_ijc`.`until` >= :from AND
				`tc_c`.`nationality` != '' AND
				`tc_c`.`nationality` != '0' AND
				`ts_ij`.`school_id` IN (:schools) AND
				`ktc`.`uk_quarterly_course_type` != ''
			GROUP BY
				`ts_ijc`.`id`
			HAVING
				`lessons` >= 10
		";

		return (array)\DB::getQueryRows($sSql, [
			'from' => $this->aFilters['from']->format('Y-m-d'),
			'until' => $this->aFilters['until']->format('Y-m-d'),
			'schools' => $this->aFilters['schools']
		]);

	}

	/**
	 * @return array
	 */
	protected function prepareData() {

		$aData = [];
		$aQueryData = $this->getQueryData();
		$aQuicCourseTypes = $this->getQuicCourseTypes();

		if(empty($aQueryData)) {
			throw new NoResultsException();
		}

		foreach($aQueryData as $aRow) {

			$sNationality = $this->oQuicExcelHelper->getNationalityByKey($aRow['nationality']);
			if(empty($sNationality)) {
				throw new AlertException('Nationality "'.$aRow['nationality'].'" is not mapped');
			}

			if(!isset($aQuicCourseTypes[$aRow['uk_quarterly_course_type']])) {
				throw new \RuntimeException('Unknown UK course type "'.$aRow['uk_quarterly_course_type'].'"');
			}

			if(!isset($aData[$sNationality])) {
				$aData[$sNationality] = [
					'source_weeks_agency' => 0,
					'source_weeks_direct' => 0,
					'age_weeks_adult' => 0,
					'age_weeks_junior' => 0,
					'type_weeks_group' => 0,
					'type_weeks_individual' => 0
				];

				foreach($aQuicCourseTypes as $sQuicCourseType => $sAgeGroup) {
					if($sAgeGroup === 'adult' || $sAgeGroup === 'both') {
						$aData[$sNationality]['adult_weeks_'.$sQuicCourseType] = 0;
					}
					if($sAgeGroup === 'junior' || $sAgeGroup === 'both') {
						$aData[$sNationality]['junior_weeks_'.$sQuicCourseType] = 0;
					}
				}
			}

			$fWeeks = round((float)$aRow['weeks']);

			if($aRow['agency_id'] > 0) {
				$aData[$sNationality]['source_weeks_agency'] += $fWeeks;
			} else {
				$aData[$sNationality]['source_weeks_direct'] += $fWeeks;
			}

			if($aRow['group_id'] > 0) {
				$aData[$sNationality]['type_weeks_group'] += $fWeeks;
			} else {
				$aData[$sNationality]['type_weeks_individual'] += $fWeeks;
			}

			$sAgeGroup = $aQuicCourseTypes[$aRow['uk_quarterly_course_type']];
			if($sAgeGroup === 'both') {
				$sAgeGroup = $aRow['age_group'];
			}

			$aData[$sNationality]['age_weeks_'.$sAgeGroup] += $fWeeks;

			$aData[$sNationality][$sAgeGroup.'_weeks_'.$aRow['uk_quarterly_course_type']] += $fWeeks;

		}

		return $aData;

	}

	/**
	 * Achtung: Wird nur für HTML-Ansicht verwendet, da beim Excel direkt das Excel von Quic verwendet wird
	 *
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$this->oQuicExcelHelper = new QuicExcel();
		$aData = $this->prepareData();

		$aColumns = $this->getColumns();

		$aRows = $this->generateHeaderRow();
		$oTable = new Table\Table();
		$oTable[] = $aRows[0];
		$oTable[] = $aRows[1];

		$aNationalities = $this->oQuicExcelHelper->getExcelNationalities();

		foreach($aNationalities as $sNationality) {
			$oRow = new Table\Row();

			foreach($aColumns as $oColumn) {

				if($oColumn->getKey() === 'nationality') {
					$oCell = $oColumn->createCell(true);
					$oCell->setValue($sNationality);
				} else {
					$oCell = $oColumn->createCell();

					if(!empty($aData[$sNationality][$oColumn->getKey()])) {
						$oCell->setValue($aData[$sNationality][$oColumn->getKey()]);
					}
				}

				$oRow[] = $oCell;
			}

			$oTable[] = $oRow;
		}

		return $oTable;

	}

	/**
	 * Columns stimmen mit denen des Quic Excels überein, wichtig für die korrekten Spalten!
	 *
	 * @inheritdoc
	 */
	protected function getColumns() {

		$aColumns = [];

		$oColumn = new Column('nationality', 'Nationality of student');
		$oColumn->setBackground('#fdef99');
		$aColumns[] = $oColumn;

		$oColumn = new Column('source_weeks_agency', 'Commissionable (via agent)');
		$oColumn->setBackground('#eec44c');
		$aColumns[] = $oColumn;

		$oColumn = new Column('source_weeks_direct', 'Non-commissionable (direct)');
		$oColumn->setBackground('#eec44c');
		$aColumns[] = $oColumn;

		$oColumn = new Column('age_weeks_adult', 'Adult');
		$oColumn->setBackground('#fdef99');
		$aColumns[] = $oColumn;

		$oColumn = new Column('age_weeks_junior', 'Junior');
		$oColumn->setBackground('#fdef99');
		$aColumns[] = $oColumn;

		$oColumn = new Column('type_weeks_group', 'Group');
		$oColumn->setBackground('#eec44c');
		$aColumns[] = $oColumn;

		$oColumn = new Column('type_weeks_individual', 'Individual');
		$oColumn->setBackground('#eec44c');
		$aColumns[] = $oColumn;

		$aCourseTypeLabels = \Ext_Thebing_Tuition_Course_Gui2::getUkQuarterlyReportCourseTypes();

		foreach(array_keys($this->getQuicCourseTypes('adult')) as $sCourseType) {
			$oColumn = new Column('adult_weeks_'.$sCourseType, $aCourseTypeLabels[$sCourseType]);
			$oColumn->setBackground('#fdef99');
			$aColumns[] = $oColumn;
		}

		foreach(array_keys($this->getQuicCourseTypes('junior')) as $sCourseType) {
			$oColumn = new Column('junior_weeks_'.$sCourseType, $aCourseTypeLabels[$sCourseType]);
			$oColumn->setBackground('#eec44c');
			$aColumns[] = $oColumn;
		}

		return $aColumns;

	}

	/**
	 * Nur relevant für HTML
	 *
	 * @inheritdoc
	 */
	protected function generateHeaderRow() {

		$aColumns = $this->getColumns();

		$oRow1 = new Table\Row();
		$oRow1->setRowSet('head');

		$oCell = reset($aColumns)->createCell(true);
		$oCell->setRowspan(2);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell('Student weeks by source', true);
		$oCell->setBackground('#eec44c');
		$oCell->setColspan(2);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell('Student weeks by age', true);
		$oCell->setBackground('#fdef99');
		$oCell->setColspan(2);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell('Student weeks by booking type', true);
		$oCell->setBackground('#eec44c');
		$oCell->setColspan(2);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell('Student weeks by course type - adult', true);
		$oCell->setBackground('#fdef99');
		$oCell->setColspan(7);
		$oRow1[] = $oCell;

		$oCell = new Table\Cell('Student weeks by course type - juniors', true);
		$oCell->setBackground('#eec44c');
		$oCell->setColspan(3);
		$oRow1[] = $oCell;

		$oRow2 = new Table\Row();
		$oRow2->setRowSet('head');

		foreach($aColumns as $oColumn) {
			if($oColumn->getKey() === 'nationality') {
				continue;
			}

			$oCell = $oColumn->createCell(true);
			$oRow2[] = $oCell;
		}

		return [$oRow1, $oRow2];

	}

	/**
	 * @inheritdoc
	 */
	public function generateViewGenerator(AbstractTable $oGenerator) {

		// Excel wird in das Excel von Quic generiert, kein eigenes Excel
		if($oGenerator instanceof Excel) {

			$aData = $this->prepareData();
			$aColumns = $this->getColumns();

			$oExcel = $this->oQuicExcelHelper->getQuicExcel();
			$aQuicExcelNationalities = $this->oQuicExcelHelper->getExcelNationalities();

			foreach($aQuicExcelNationalities as $iRow => $sNationality) {

				$iCol = 1;
				foreach($aColumns as $oColumn) {

					if($oColumn->getKey() === 'nationality') {
						continue;
					}

					if(!empty($aData[$sNationality][$oColumn->getKey()])) {
						$sCol = \Util::getColumnCodeForExcel($iCol);
						$oExcel->getActiveSheet()->setCellValue($sCol.$iRow, $aData[$sNationality][$oColumn->getKey()]);
					}

					$iCol++;

				}

			}

			$schoolIdsByFilter = $this->aFilters['schools'];

			// Wenn nur eine Schule im Filter ist dann diese Schule natürlich, sonst die erstbeste einfach.
			// -> Wenn es andere Daten wären, würde es keinen Sinn ergeben, nach mehreren Schulen Filtern zu wollen.
			$schoolIdByFilter = reset($schoolIdsByFilter);
			$school = \Ext_Thebing_School::getInstance($schoolIdByFilter);

			$email = $school->getMeta(ExternalApp::KEY_EMAIL) ?? $school->email;
			$zip = $school->getMeta(ExternalApp::KEY_POSTCODE) ?? $school->zip;
			$schoolName = $school->getMeta(ExternalApp::KEY_CENTRE_NAME) ?? $school->ext_1;

			$oExcel->getActiveSheet()->setCellValue(
				'B7',
				$school->getMeta(ExternalApp::KEY_CONTACT_NAME)
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B8',
				$schoolName
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B9',
				$school->getMeta(ExternalApp::KEY_MEMBER_NO, '')
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B10',
				\Ext_Thebing_Format::LocalDate(new \DateTime('now'), $school)
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B11',
				$email
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B12',
				\Ext_Thebing_Format::LocalDate($this->aFilters['from'], $school)
			);

			$oExcel->getActiveSheet()->setCellValue(
				'D12',
				\Ext_Thebing_Format::LocalDate($this->aFilters['until'], $school)
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B14',
				$zip
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B15',
				$school->getMeta(ExternalApp::KEY_CONTACT_TELEPHONE_NUMBER, '')
			);

			$oExcel->getActiveSheet()->setCellValue(
				'B16',
				$school->getMeta(ExternalApp::KEY_POSITION_OF_MAIN_CONTACT, '')
			);

			$oExcel->getProperties()->setTitle($this->getTitle());
			$oExcel->getProperties()->setCreator('English UK'); // Die scheinen sich nicht so dafür zu interessieren
			$oExcel->getProperties()->setLastModifiedBy('Fidelo School '.\System::d('version'));
			$oExcel->getProperties()->setModified(time());

			$oGenerator->setSpreadsheetWriter(Xls::class);
			$aPathInfo = pathinfo($this->oQuicExcelHelper->getQuicExcelFile());
			$oGenerator->setFileName($aPathInfo['filename']);

			// Excel-Objekt vom Renderer komplett überschreiben
			$oGenerator->setSpreadsheetObject($oExcel);

			$mMixed = $oExcel; // Wird bei Excel eigentlich nicht verwendet

		} else {
			$mMixed = parent::generateViewGenerator($oGenerator);
		}

		return $mMixed;

	}

	/**
	 * Mapping, welche UK Quarterly Course Types wo verfügbar sind (Adults, Juniors)
	 *
	 * @param null $sFilter
	 * @return array
	 */
	public static function getQuicCourseTypes($sFilter = null) {

		$aTypes = [
			'general_english' => 'both',
			'business_professional' => 'adult',
			'english_plus' => 'adult',
			'summer_winter_camps' => 'junior',
			'eap' => 'both',
			'esp' => 'adult',
			'1to1' => 'adult',
			'teacher_development' => 'adult'
		];

		if($sFilter !== null) {
			$aTypes = array_filter($aTypes, function($sType) use($sFilter) {
				return $sType === $sFilter || $sType === 'both';
			});
		}

		return $aTypes;

	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'service_period' => self::t('Leistungszeitraum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			self::t('Gebuchte Kurse werden erst ab 10 Lektionen pro Woche berücksichtigt. Die Schülerwochen werden gerundet.'),
		];
	}

}

