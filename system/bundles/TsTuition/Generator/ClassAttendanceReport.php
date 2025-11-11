<?php

namespace TsTuition\Generator;

use Closure;
use Core\Helper\DateTime;
use Exception;
use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table;
use TcStatistic\Model\Statistic\Column;
use Spatie\Period\Period;
use TsStatistic\Generator\Statistic\AbstractGenerator;

class ClassAttendanceReport extends AbstractGenerator
{

	/**
	 * @var Closure
	 */
	protected Closure $translate;

	/**
	 * @var \Ext_Thebing_Tuition_Class[]
	 */
	private array $classes = [];

	/**
	 * @var ?Column[]
	 */
	private ?array $columns = null;

	/**
	 * @param array $classIds
	 * @param Closure $translate
	 * @throws Exception
	 */
	public function __construct(array $classIds, Closure $translate)
	{
		$this->translate = $translate;
		foreach ($classIds as $classId) {
			$this->classes[] = \Ext_Thebing_Tuition_Class::getInstance($classId);
		}
	}

	/**
	 * Erstellt den Bericht
	 * @return void
	 * @throws Exception
	 */
	public function render(): void
	{
		$oExcel = new Excel($this->generateDataTable());
		$oExcel->setFileName($this->getTitle());
		$oExcel->setTitle($this->getTitle());

		$oExcel->generate();
		$oExcel->render();
	}

	/**
	 * Text für Titel und Dateinamen
	 * @return string
	 */
	public function getTitle(): string
	{
		$title = 'Attendance Report ';
		$classNames = [];
		foreach ($this->classes as $class) {
			if (count($classNames) > 4) {
				// Titel sonst zu lange?
				$classNames[] = '...';
				break;
			}
			$classNames[] = $class->getName();
		}
		$title .= implode(', ', $classNames);
		return $title;
	}

	/**
	 * Stellt die Daten zusammen.
	 * @return array
	 * @throws Exception
	 */
	protected function generateData(): array
	{
		$classesData = [];
		$customers = [];
		$htmlHelper = new \PhpOffice\PhpSpreadsheet\Helper\Html();
		$school = \Ext_Thebing_School::getInstance($this->classes[0]->school_id);
		$absenceCalculation = $school->tuition_excused_absence_calculation;

		// Loop durch Klassen
		foreach ($this->classes as $class) {
			$blocks = \Ext_Thebing_School_Tuition_Block::query()
				->where(['class_id' => $class->id])
				->orderBy('week')
				->get();
			// Loop durch Blöcke
			/** @var \Ext_Thebing_School_Tuition_Block $block */
			foreach ($blocks as $block) {
				// vielleicht nicht notwendig, aber sichergehen
				sort($block->days);
				// Daten zur Zeile zusammenstellen
				$lessons = $block->getTemplate()->lessons;
				foreach ($block->days as $day) {
					$dailyUnit = $block->getUnit($day);
					$date = $dailyUnit->getStartDate();
					$classesData[$class->id][$block->id][$dailyUnit->day] = [
						'date' => new \DateTime($date->format('Y-m-d H:i:s')),
						'time' => new \DateTime($date->format('Y-m-d H:i:s')),
						'comment' => $dailyUnit->comment ? $htmlHelper->toRichTextObject(
							preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, (string)$dailyUnit->comment)
						) : '',
						'teacher_name' => $block->getTeacher()?->getName(),
						'lesson_cancelled' => $dailyUnit->isCancelled() ? 'x' : '',
						'lessons_allocated' => $lessons,
					];
				}
				// Loop durch die Zuweisungen
				/** @var \Ext_Thebing_School_Tuition_Allocation $allocation */
				foreach ($block->getAllocations() as $allocation) {
					$attendances = \Ext_Thebing_Tuition_Attendance::getRepository()
						->findBy(['allocation_id' => $allocation->id]);
					if (empty($attendances)) {
						// Keine Anwesenheit eingetragen, kann ignoriert werden,
						// Nicht eingetragene Anwesenheiten sollen nicht für % zählen.
						continue;
					}
					// Highländer
					$attendance = reset($attendances);
					// Daten zum Customer ergänzen
					$customer = $allocation->getJourneyCourse()->getInquiry()->getFirstTraveller();
					if (!isset($customers[$customer->id])) {
						$customers[$customer->id] = [
							'name' => $customer->getName(),
							'id' => $customer->id,
							'number' => $customer->getCustomerNumber(),
							'lessons_duration_percent_sum' => 0,
							'lessons_attended_count' => 0,
						];
					}
					// Daten zur Anwesenheit ergänzen
					if (!empty($classesData[$class->id][$block->id])) {
						foreach (array_keys($classesData[$class->id][$block->id]) as $day) {
							$dayString = \Ext_TC_Util::convertWeekdayToString($day);
							if (
								!is_null($attendance->$dayString) &&
								$class->lesson_duration > 0
							) {
								$excused = ($attendance->excused >> $day-1) & 1;

								$lessonDuration = $class->lesson_duration * $lessons;

								$absenceMinutes = $attendance->$dayString;

								$fullyAbsentAndExclude = false;
								if (
									$absenceCalculation === 'exclude' &&
									$excused &&
									(float)$absenceMinutes == (float)$lessonDuration
								) {
									$fullyAbsentAndExclude = true;
								}

								// Teilweise abwesend, aber enschuldigt und es soll exkludiert werden
								if (
									(float)$absenceMinutes < (float)$lessonDuration &&
									(float)$absenceMinutes != 0 &&
									$absenceCalculation === 'exclude' && $excused
								) {
									$lessonDuration = $lessonDuration - $absenceMinutes;
								}

								if ($excused) {
									$absenceMinutes = 0;
								}

								if (
									!$fullyAbsentAndExclude ||
									$absenceCalculation === 'include' ||
									$absenceCalculation === 'exclude' && !$excused
								) {
									$classesData[$class->id][$block->id][$day]['lessons_duration_percent_' . $customer->id] =
										round(
											100 - ($absenceMinutes / ($lessonDuration) * 100),
											2
										);

									$customers[$customer->id]['lessons_duration'] += $lessonDuration;
									$customers[$customer->id]['lessons_duration_absence'] += $absenceMinutes;

									$customers[$customer->id]['lessons_attended_count'] += 1;
								}
							}
						}
					}
				}
			}
		}
		return [$classesData, $customers];
	}

	/**
	 * Generiert die Tabelle für den Excel export
	 * @return Table\Table
	 * @throws Exception
	 */
	protected function generateDataTable(): Table\Table
	{
		[$classesData, $customers] = $this->generateData();

		$columns = $this->getColumns($customers);

		$table = new Table\Table();
		$table->setCaption(($this->translate)('Anwesenheitsbericht'));

		$this->addPrintDateTableInfo($table);
		$sums = [
			'lessons_allocated' => 0,
		];
		foreach ($classesData as $classId => $classData) {
			$class = \Ext_Thebing_Tuition_Class::getInstance($classId);
			// Informationen zur Klasse.
			$this->addTableInfoRows($table, $class);
			// Tabellenheader.
			$table[] = $this->generateHeaderRow();
			// Tabellenzeilen.
			foreach ($classData as $blockId => $blockData) {
				foreach ($blockData as $day => $dayData) {
					$row = new Table\Row();
					foreach ($columns as $column) {
						$cell = $column->createCell();
						$row[] = $cell->setValue($dayData[$column->getKey()]);
						if ($column->getKey() == 'lesson_cancelled') {
							$cell->setAlignment('center');
						}
					}
					$table[] = $row;
					$sums['lessons_allocated'] += $dayData['lessons_allocated'];
				}
			}
			// Summenzeilen.
			$row = new Table\Row();
			$row->setRowSet('foot');
			$cell = new Table\Cell(($this->translate)('Summe'), true);
			$sumCellColSpan = array_sum(
				array_map(fn ($column) => !$column->bSummable ? 1 : 0, $columns)
			);
			$cell->setColspan($sumCellColSpan);
			$row[] = $cell;
			foreach ($columns as $column) {
				if ($column->bSummable) {
					$cell = new Table\Cell(null, true);
					$row[] = $cell;
					// Prüfen ob es sich um eine Customer Spalte handelt
					if (str_contains($column->getKey(), 'lessons_duration_percent_')) {
						$customerId = str_replace('lessons_duration_percent_', '', $column->getKey());
						$cell->setFormat($column->getFormat());
						// Prozente für Summenzeile berechnen
						// Nicht die Prozentzahl der Tage zusammenrechnen, weil bei dem Setting tuition_excused_absence_calculation = "exclude"
						// ein Tag zwar 100% anwesend war, aber es nicht wirklich der volle Tag war und insgesamt nicht 100% sind..
						$lessons_attended_percent = $customers[$customerId]['lessons_attended_count'] > 0 ?
							round(
								($customers[$customerId]['lessons_duration'] - $customers[$customerId]['lessons_duration_absence']) /
								 $customers[$customerId]['lessons_duration'] * 100, 4
							) : 0;
						$cell->setValue($lessons_attended_percent);
					} else if ($sums[$column->getKey()] !== null) {
						$cell->setFormat($column->getFormat());
						$cell->setValue($sums[$column->getKey()]);
					}
				}
			}
			$table[] = $row;
		}
		return $table;
	}

	/**
	 * Informationen zur Klasse
	 * @param Table\Table $table
	 * @param $class
	 */
	private function addTableInfoRows(Table\Table $table, \Ext_Thebing_Tuition_Class $class): void
	{
		$columns = $this->getColumns();

		$titleColspan = 2;
		$valueColspan = 4;

		// Leerzeile
		$row = new Table\Row();
		$cell = new Table\Cell('');
		$cell->setColspan(count($columns));
		$row[] = $cell;
		$table[] = $row;

		$row = new Table\Row();
		$table[] = $row;

		$cell = new Table\Cell(($this->translate)('Klasse'), true);
		$cell->setColspan($titleColspan);
		$row[] = $cell;

		$cell = new Table\Cell($class->getName(), false);
		$cell->setColspan($valueColspan);
		$cell->setAlignment('right');
		$row[] = $cell;

		// Leerzeile
		$row = new Table\Row();
		$cell = new Table\Cell('');
		$cell->setColspan(count($columns));
		$row[] = $cell;
		$table[] = $row;
	}

	/**
	 * Spalten für die Tabelle des Berichts
	 * @return Column[]
	 */
	protected function getColumns(array $customers = []): array
	{
		if (!is_null($this->columns)) {
			return $this->columns;
		}
		$column = new Column('comment', ($this->translate)('Täglicher Kommentar'));
		$column->setBackground('service');
		$column->setKeepLineBreaks(true);
		$columns[] = $column;

		$column = new Column('teacher_name', ($this->translate)('Lehrer'));
		$column->setBackground('service');
		$columns[] = $column;

		$column = new Column('date', ($this->translate)('Datum'), 'date');
		$column->setBackground('service');
		$columns[] = $column;

		$column = new Column('time', ($this->translate)('Uhrzeit'), 'time');
		$column->setBackground('service');
		$columns[] = $column;

		$column = new Column('lesson_cancelled', ($this->translate)('Ausgefallen'));
		$column->setBackground('service');
		$columns[] = $column;

		$column = new Column('lessons_allocated', ($this->translate)('ZL'), 'number_float');
		$column->setBackground('service');
		$column->bSummable = true;
		$columns[] = $column;

		foreach ($customers as $customer) {
			$column = new Column('lessons_duration_percent_'.$customer['id'],
				($this->translate)('Anw. %').
				"\n".$customer['name']."\n(".$customer['number'].')', 'number_percent'
			);
			$column->setBackground('service');
			$column->setKeepLineBreaks(true);
			$column->bFormatNullValue = false;
			$column->bSummable = true;
			$columns[] = $column;
		}
		$this->columns = $columns;
		return $this->columns;
	}

	/**
	 * Informationen zum Bericht.
	 * Einmal pro Bericht angehängt.
	 * @param Table\Table $table
	 * @return void
	 */
	protected function addPrintDateTableInfo(Table\Table $table)
	{
		$columns = $this->getColumns();

		$titleColspan = 2;
		$valueColspan = 4;

		// Leerzeile
		$row = new Table\Row();
		$cell = new Table\Cell('');
		$cell->setColspan(count($columns));
		$row[] = $cell;
		$table[] = $row;

		$row = new Table\Row();
		$table[] = $row;

		$cell = new Table\Cell(($this->translate)('Druckdatum'), true);
		$cell->setColspan($titleColspan);
		$row[] = $cell;

		$cell = new Table\Cell(new DateTime(), false, 'date');
		$cell->setColspan($valueColspan);
		$cell->setAlignment('right');
		$row[] = $cell;
	}
}