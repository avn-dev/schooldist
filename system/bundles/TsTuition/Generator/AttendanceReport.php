<?php

namespace TsTuition\Generator;

use Core\Helper\DateTime;
use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table;
use TcStatistic\Model\Statistic\Column;
use TsStatistic\Generator\Statistic\AbstractGenerator;

class AttendanceReport extends AbstractGenerator {

	/**
	 * @var \Ext_TS_Inquiry[]
	 */
	private $inquiries;

	/**
	 * @var \Closure
	 */
	private $cTranslate;

	private $reverseWeekSorting = false;
	
	public function getTitle() {
		$title = 'Attendance Report ';
		$inquiryCount = 0;
		foreach ($this->inquiries as $inquiry) {
			if ($inquiryCount > 4) {
				// Titel sonst zu lange?
				$customerNumbers[] = '...';
				break;
			}
			$customerNumbers[] = $inquiry->getCustomer()->getCustomerNumber();
			$inquiryCount++;
		}

		$title .= implode(', ', $customerNumbers);

		return $title;
	}

	/**
	 * @param \Ext_TS_Inquiry[] $inquiries
	 * @param \Closure $cTranslate
	 */
	public function __construct(array $inquiries, \Closure $cTranslate) {
		$this->inquiries = $inquiries;
		$this->cTranslate = $cTranslate;
	}

	public function reverseWeekSorting($reverseWeekSorting) {
		$this->reverseWeekSorting = $reverseWeekSorting;
	}
	
	public function generateData()
	{

		$oAllocationService = new \Ext_Thebing_School_Tuition_Allocation_Result();
		$oAllocationService->setBlockWeekSortDesc($this->reverseWeekSorting);

		foreach ($this->inquiries as $inquiry) {
			$oAllocationService->setInquiry($inquiry);
			$inquiriesAllocationData[$inquiry->id] = $oAllocationService->fetch();
			$inquiriesAttendances[$inquiry->id] = \Ext_Thebing_Tuition_Attendance::getRepository()->findBy(['inquiry_id' => $inquiry->id]);

			$aSums[$inquiry->id] = [
				'lessons_allocated' => 0,
				'lessons_allocated_attendance' => 0,
				'lessons_attended' => 0,
				'lessons_percent' => 0,
				'lessons_duration' => 0,
				'lessons_duration_absent' => 0,
				'lessons_duration_percent' => 0
			];

			// Nicht anzeigen, wenn es keine Anwesenheit gibt
			if (empty($inquiriesAttendances[$inquiry->id])) {
				$aSums[$inquiry->id]['lessons_attended'] = null;
				$aSums[$inquiry->id]['lessons_percent'] = null;
				$aSums[$inquiry->id]['lessons_duration_percent'] = null;
			}

			foreach ($inquiriesAllocationData[$inquiry->id] as &$axData) {
				$this->prepareRow($axData, $aSums[$inquiry->id], $inquiriesAttendances[$inquiry->id]);
			}
		}

		return [$inquiriesAllocationData, $aSums];
	}
	
	public function generateDataTable() {

		list($inquiriesAllocationData, $aSums) = $this->generateData();

		$inquiriesGroupedData = [];
		foreach ($inquiriesAllocationData as $inquiryId => $inquiryAllocationData) {
			foreach($inquiryAllocationData as $aData) {
				$inquiriesGroupedData[$inquiryId][$aData['class_id']][] = $aData;
			}
		}

		$aColumns = $this->getColumns();

		$oTable = new Table\Table();
		$oTable->setCaption(($this->cTranslate)('Anwesenheitsbericht'));

		$this->addPrintDateTableInfo($oTable);

		foreach ($inquiriesGroupedData as $inquiryId => $inquiryGroupedData) {

			$this->addTableInfoRows($oTable, $inquiryId);

			$oTable[] = $this->generateHeaderRow();

			foreach ($inquiryGroupedData as $aClassData) {
				$bFirst = true;

				foreach ($aClassData as $aData) {

					$oRow = new Table\Row();
					foreach ($aColumns as $oColumn) {

						$oCell = $oColumn->createCell();

						if (!empty($oColumn->aAdditional['skip_on_first'])) {
							if ($bFirst) {
								$oCell->setValue($aData[$oColumn->getKey()]);
							}
						} else {
							$oCell->setValue($aData[$oColumn->getKey()]);
						}

						if ($oColumn->getKey() == 'lesson_cancelled') {
							$oCell->setAlignment('center');
						}

						$oRow[] = $oCell;

					}

					$bFirst = false;

					$oTable[] = $oRow;

				}
			}
			$this->addTableSumRow($oTable, $aSums[$inquiryId], $inquiryId);
		}

		return $oTable;
	}

	/**
	 * @param array $aData
	 * @param array $aSums
	 * @param array $aAttendances
	 */
	private function prepareRow(array &$aData, array &$aSums, array $aAttendances) {

		$aAttendances = array_filter($aAttendances, function(\Ext_Thebing_Tuition_Attendance $oAttendance) use ($aData) {
			return $oAttendance->allocation_id == $aData['block_allocation_id'];

		});
		$unit = \TsTuition\Entity\Block\Unit::query()
			->where('block_id', $aData['block_id'])
			->where('day', $aData['day'])
			->first();

		$comment = $unit?->comment ?? '';

		$aData['lesson_cancelled'] = $unit?->isCancelled() ? 'x' : '';

		// Zeilenumbrüche umwandeln
		$comment = preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, (string)$comment);

		$dayDateFrom = new \DateTime($aData['block_day_date']);
		$dayDateUntil = new \DateTime($aData['block_day_date']);
		
		$aBlockTimeFrom = explode(':', $aData['block_from']);
		$aData['block_day_date'] = $dayDateFrom->setTime($aBlockTimeFrom[0], $aBlockTimeFrom[1]);
		$aData['block_from'] = $aData['block_day_date'];
		
		$aBlockTimeUntil = explode(':', $aData['block_until']);
		$aData['block_until'] = $dayDateUntil->setTime($aBlockTimeUntil[0], $aBlockTimeUntil[1]);
		
		if(!empty($comment)) {
			$htmlHelper = new \PhpOffice\PhpSpreadsheet\Helper\Html();
			$richText = $htmlHelper->toRichTextObject($comment);
			$aData['daily_comment'] = $richText;
		}

		$aData['lessons_attended'] = null;
		$aData['lessons_percent'] = null;
		$aData['lessons_duration_percent'] = null;
		$aData['lessons_allocated'] = $aData['allocated_lessons'];

		$aSums['lessons_allocated'] += $aData['lessons_allocated'];

		if(!empty($aAttendances)) {

			if(count($aAttendances) > 1) {
				throw new \RuntimeException('More than one attendance for block allocation!');
			}

			$oAttendance = reset($aAttendances); /** @var \Ext_Thebing_Tuition_Attendance $oAttendance */

			if($oAttendance->week !== $aData['block_week']) {
				throw new \RuntimeException('Mismatching week for attendance and block! '.$oAttendance->id);
			}

			$sTwoLetterDay = \Ext_TC_Util::convertWeekdayToString($aData['day']);
			$fAbsenceMinutes = $oAttendance->$sTwoLetterDay;

			$bExcused = ($oAttendance->excused >> $aData['day']-1) & 1;
			
			if($fAbsenceMinutes !== null) {

				$absenceString = '';

				$lessonDuration = $aData['allocated_lessons'] * $aData['lesson_duration'];

				$fullyAbsentAndExclude = false;
				if (
					$aData['tuition_excused_absence_calculation'] === 'exclude' &&
					$bExcused &&
					(float)$fAbsenceMinutes == (float)$lessonDuration
				) {
					$fullyAbsentAndExclude = true;
				}

				$partiallyAbsent = false;
				if (
					(float)$fAbsenceMinutes < (float)$lessonDuration &&
					(float)$fAbsenceMinutes != 0 &&
					$aData['tuition_excused_absence_calculation'] === 'exclude' && $bExcused
				) {
					// Wenn teilweise anwesend, dann nicht ganz ignorieren, sondern nur den abwesend- und entschuldigten
					// Teil ignorieren (s.u.)
					$partiallyAbsent = true;
					// "Zwischenspeichern", weil gleich der Wert auf 0 gesetzt wird.
					$partiallyAbsentMinutes = $fAbsenceMinutes;
				}

				// @todo Besser wäre es wohl, das zusätzlich noch zu markieren
				// Nachtrag: Wenn damit eine "Abwesenheits-Spalte" gemeint war, ist das ja jetzt erledigt.
				if($bExcused) {
					$fAbsenceMinutes = 0;
					$absenceString = ($this->cTranslate)('Entsch.');
				}

				$absenceReasonId = $oAttendance->absence_reasons[$aData['day']];

				if(!empty($absenceReasonId)) {

					$absenceReasonKey = \TsTuition\Entity\AbsenceReason::query()
						->where('id', $absenceReasonId)
						->pluck('key')
						->first();

					if($bExcused) {
						$absenceString .= ' (' . $absenceReasonKey . ')';
					} else {
						$absenceString = $absenceReasonKey;
					}
				}

				$aData['absence'] = $absenceString;
				
				/*
				 * @todo Abfrage eigentlich überflüssig. Wert hier darf NIE 0 sein. Muss sichergestellt werden beim Speichern einer Zuweisung!
				 */
				if($aData['lesson_duration'] > 0) {
					$aData['lessons_duration_absent'] = $fAbsenceMinutes;
					if(
						!$fullyAbsentAndExclude ||
						$aData['tuition_excused_absence_calculation'] === 'include' ||
						($aData['tuition_excused_absence_calculation'] === 'exclude' && !$bExcused)
					) {

						if ($partiallyAbsent) {
							$fAbsenceMinutes = $partiallyAbsentMinutes;
						}

						$aData['lessons_attended'] = round($aData['allocated_lessons'] - ((float)$fAbsenceMinutes / $aData['lesson_duration']), 2);
						$aData['lessons_percent'] = $aData['lessons_attended'] / $aData['allocated_lessons'] * 100;
						$aData['lessons_duration'] = $lessonDuration - $partiallyAbsentMinutes;
						$aData['lessons_duration_percent'] = 100 - $aData['lessons_duration_absent'] / $aData['lessons_duration'] * 100;
					}

					$aSums['lessons_attended'] += $aData['lessons_attended'];
					$aSums['lessons_allocated_attendance'] += $aData['lessons_allocated'];

					$aSums['lessons_duration'] += $aData['lessons_duration'];
					$aSums['lessons_duration_absent'] += $aData['lessons_duration_absent'];
				}
				
			}

		}

	}

	private function addPrintDateTableInfo(Table\Table $oTable) {

		$aColumns = $this->getColumns();

		$iTitleColspan = 2;
		$iValueColspan = count($aColumns) - $iTitleColspan;

		// Leerzeile
		$oRow = new Table\Row();
		$oCell = new Table\Cell('');
		$oCell->setColspan(count($aColumns));
		$oRow[] = $oCell;
		$oTable[] = $oRow;

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(($this->cTranslate)('Druckdatum'), true);
		$oCell->setColspan($iTitleColspan);
		$oRow[] = $oCell;

		$oCell = new Table\Cell(new DateTime(), false, 'date');
		$oCell->setColspan($iValueColspan);
		$oCell->setAlignment('right');
		$oRow[] = $oCell;
	}

	/**
	 * @param Table\Table $oTable
	 */
	private function addTableInfoRows(Table\Table $oTable, $inquiryId) {

		$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);

		$aColumns = $this->getColumns();

		$iTitleColspan = 2;
		$iValueColspan = count($aColumns) - $iTitleColspan;

		// Leerzeile
		$oRow = new Table\Row();
		$oCell = new Table\Cell('');
		$oCell->setColspan(count($aColumns));
		$oRow[] = $oCell;
		$oTable[] = $oRow;

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(($this->cTranslate)('Kundennummer'), true);
		$oCell->setColspan($iTitleColspan);
		$oRow[] = $oCell;

		$oCell = new Table\Cell($inquiry->getCustomer()->getCustomerNumber(), false);
		$oCell->setColspan($iValueColspan);
		$oCell->setAlignment('right');
		$oRow[] = $oCell;

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(($this->cTranslate)('Name'), true);
		$oCell->setColspan($iTitleColspan);
		$oRow[] = $oCell;

		$oCell = new Table\Cell($inquiry->getCustomer()->getName(), false);
		$oCell->setColspan($iValueColspan);
		$oCell->setAlignment('right');
		$oRow[] = $oCell;

		// Leerzeile
		$oRow = new Table\Row();
		$oCell = new Table\Cell('');
		$oCell->setColspan(count($aColumns));
		$oRow[] = $oCell;
		$oTable[] = $oRow;

	}

	/**
	 * @param Table\Table $oTable
	 * @param array $aSums
	 */
	private function addTableSumRow(Table\Table $oTable, array $aSums, $inquiryId) {

		$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);

		$aColumns = $this->getColumns();

		// Nur zugewiesene Lektionen, wo bereits Anwesesenheit eingetragen wurde
		if ($aSums['lessons_allocated_attendance'] > 0) {
			$aSums['lessons_percent'] = $aSums['lessons_attended'] / $aSums['lessons_allocated_attendance'] * 100;
			$aSums['lessons_duration_percent'] = round(($aSums['lessons_duration'] - $aSums['lessons_duration_absent']) / $aSums['lessons_duration'] * 100, 4);
		}

		// Assertion – hier MUSS derselbe Wert rauskommen
		$fAttendanceInquiry = \Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiry($inquiry);

		if (bccomp($aSums['lessons_duration_percent'], $fAttendanceInquiry, 2) !== 0) {
			throw new \LogicException(sprintf('Total attendance percent mismatch: %.2F %% vs %.2F %% (Inquiry %d)', $aSums['lessons_duration_percent'], $fAttendanceInquiry, $inquiryId));
		}

		$oRow = new Table\Row();
		$oRow->setRowSet('foot');

		$oCell = new Table\Cell(($this->cTranslate)('Summe'), true);

		$sumCellColSpan = 0;
		foreach ($aColumns as $column) {
			if (!$column->bSummable) {
				$sumCellColSpan++;
			}
		}

		$oCell->setColspan($sumCellColSpan);
		$oRow[] = $oCell;

		foreach($aColumns as $oColumn) {

			if(
				$oColumn->bSummable
			) {
				$oCell = new Table\Cell(null, true);
				$oRow[] = $oCell;

				if($aSums[$oColumn->getKey()] !== null) {

					$oCell->setFormat($oColumn->getFormat());
					$oCell->setValue($aSums[$oColumn->getKey()]);

					// Kritische Anwesenheit
					if(
						$oColumn->getKey() === 'lessons_duration_percent' &&
						$aSums['lessons_duration_percent'] < $inquiry->getSchool()->critical_attendance
					) {
						// TODO Hier muss eine Lösung gefunden werden für HTML und Excel, da Excel keine # will
						$oCell->setFontStyle('color', ['rgb' => 'FF0000']);
					}
				}

			}

		}

		$oTable[] = $oRow;

	}

	public function render() {

		$oExcel = new Excel($this->generateDataTable());
		$oExcel->setFileName($this->getTitle());
		$oExcel->setTitle($this->getTitle());

		$oExcel->generate();
		$oExcel->render();

	}

	protected function getColumns() {

		$oColumn = new Column('course_short', ($this->cTranslate)('Kurs'));
		$oColumn->setBackground('service');
		$oColumn->aAdditional['skip_on_first'] = true;
		$aColumns[] = $oColumn;

		$oColumn = new Column('course_name', ($this->cTranslate)('Kursname'));
		$oColumn->setBackground('service');
		$oColumn->aAdditional['skip_on_first'] = true;
		$aColumns[] = $oColumn;

		$oColumn = new Column('class_name', ($this->cTranslate)('Klasse'));
		$oColumn->setBackground('service');
		$oColumn->aAdditional['skip_on_first'] = true;
		$aColumns[] = $oColumn;

		$oColumn = new Column('teacher_name', ($this->cTranslate)('Lehrer'));
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('lesson_cancelled', ($this->cTranslate)('Nicht stattgefunden'));
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('daily_comment', ($this->cTranslate)('Täglicher Kommentar'));
		$oColumn->setBackground('service');
		$oColumn->setKeepLineBreaks(true);
		$aColumns[] = $oColumn;

		$oColumn = new Column('block_day_date', ($this->cTranslate)('Datum'), 'date');
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('block_from', ($this->cTranslate)('Uhrzeit'), 'time');
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('absence', ($this->cTranslate)('Abw.'));
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('lessons_allocated', ($this->cTranslate)('ZL'), 'number_float');
		$oColumn->setBackground('service');
		$oColumn->bSummable = true;
		$aColumns[] = $oColumn;

		$oColumn = new Column('lessons_attended', ($this->cTranslate)('TL'), 'number_float');
		$oColumn->setBackground('service');
		$oColumn->bFormatNullValue = false;
		$oColumn->bSummable = true;
		$aColumns[] = $oColumn;

		$oColumn = new Column('lessons_duration_percent', ($this->cTranslate)('Anw. %'), 'number_percent');
		$oColumn->setBackground('service');
		$oColumn->bFormatNullValue = false;
		$oColumn->bSummable = true;
		$aColumns[] = $oColumn;

		return $aColumns;

	}

}
