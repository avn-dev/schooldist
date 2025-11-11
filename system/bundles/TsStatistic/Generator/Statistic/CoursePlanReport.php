<?php

namespace TsStatistic\Generator\Statistic;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table;
use TsStatistic\Model\Filter;

/**
 * Ticket #20911 – GA - Eigene Übersicht (Kursplan)
 * @link https://redmine.fidelo.com/issues/20911
 *
 * Vereinfachte Klassenübersicht für GoAcademy
 */
class CoursePlanReport extends AbstractGenerator
{
	const COLORS = [
		'new_agency' => '#ff99cc',
		'new_direct' => '#cc99ff',
		'last' => '#99cc00'
	];

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Courses::class,
		Filter\CourseCategory::class
	];

	public function getTitle()
	{
		return 'GoAcademy Kursplan';
	}

	protected function generateDataTable()
	{
		$from = Carbon::instance($this->aFilters['from']);
		$until = Carbon::instance($this->aFilters['until']);
		$periods = \Ext_TC_Util::generateDatePeriods($from, $until, 'week', true);

		$tables = [];
		foreach ($periods as $period) {
			$tables[] = $this->generateWeek($period->getStartDate());
		}

		return $tables;
	}

	private function getQueryData(CarbonInterface $week): Collection
	{
		$language = \System::getInterfaceLanguage();

		$sql = "
			SELECT
			    ktbic.id allocation_id,
			    ktcl.id class_id,
			    ktcl.name class_name,
			    ktcl.start_week class_from,
			    ktcl.weeks class_weeks,
			    ktcl.internal_comment,
			    CONCAT(tc_c.lastname, ', ', tc_c.firstname) student_name,
				ts_tl.name_$language level_name,
				IF(ktt.custom, 0, ktt.id) template_id,
				ktt.from template_from,
				ktt.until template_until,
				ts_iti.state,
				ts_i.agency_id,
				GROUP_CONCAT(DISTINCT CONCAT(ts_t.lastname, ', ', ts_t.firstname) SEPARATOR ';') teacher_names,
				GROUP_CONCAT(DISTINCT CONCAT(ts_t_substitute.lastname, ', ', ts_t_substitute.firstname) SEPARATOR ';') teacher_names_substitute,
				(
				    SELECT
				        CONCAT(MIN(`from`), ',', MAX(until))
				    FROM
				        ts_inquiries_journeys_courses
				    WHERE
				        active = 1 AND
				        journey_id = ts_ij.id AND
				        course_id = ts_ijc.course_id
				) course_period
			FROM
				kolumbus_tuition_blocks ktb INNER JOIN
				kolumbus_tuition_classes ktcl ON
					ktcl.id = ktb.class_id AND
					ktcl.active = 1 INNER JOIN
				kolumbus_tuition_templates ktt ON
					ktt.id = ktb.template_id INNER JOIN
				kolumbus_tuition_blocks_inquiries_courses ktbic ON
					ktbic.block_id = ktb.id AND
					ktbic.active = 1 INNER JOIN
				ts_inquiries_journeys_courses ts_ijc ON
				    ts_ijc.id = ktbic.inquiry_course_id AND
				    ts_ijc.active = 1 INNER JOIN
				kolumbus_tuition_courses ktc ON
				    ktc.id = ts_ijc.course_id AND
				    ktc.category_id IN (:course_categories) INNER JOIN
				ts_inquiries_journeys ts_ij ON
				    ts_ij.id = ts_ijc.journey_id AND
				    ts_ij.type & " . \Ext_TS_Inquiry_Journey::TYPE_BOOKING . " AND
				    ts_ij.active = 1 INNER JOIN
				ts_inquiries ts_i ON
				    ts_i.id = ts_ij.inquiry_id AND
				    ts_i.type & " . \Ext_TS_Inquiry::TYPE_BOOKING . " AND
				    ts_i.canceled = 0 AND
				    ts_i.active = 1 INNER JOIN
				ts_inquiries_to_contacts ts_itc ON
					ts_itc.inquiry_id = ts_ij.inquiry_id AND
					ts_itc.type = 'traveller' INNER JOIN
				tc_contacts tc_c ON
					tc_c.id = ts_itc.contact_id INNER JOIN
				ts_inquiries_tuition_index ts_iti ON
					ts_iti.inquiry_id = ts_i.id AND
					ts_iti.week = ktb.week INNER JOIN
				kolumbus_tuition_classes_courses ktclc ON
					ktclc.class_id = ktcl.id AND
					ktclc.course_id IN (:courses) LEFT JOIN
				ts_tuition_levels ts_tl ON
					ts_tl.id = ktb.level_id AND
					ts_tl.active = 1 LEFT JOIN
				ts_teachers ts_t ON
					ts_t.id = ktb.teacher_id AND
					ts_t.active = 1 LEFT JOIN
				kolumbus_tuition_blocks_substitute_teachers ktbst ON
					ktbst.block_id = ktb.id AND
					ktbst.active = 1 LEFT JOIN
				ts_teachers ts_t_substitute ON
					ts_t_substitute.id = ktbst.teacher_id AND
					ts_t_substitute.active = 1
			WHERE
			    ktb.active = 1 AND
			    ktb.week = :week AND
			    ktb.school_id IN (:schools)
			GROUP BY
				ktcl.id,
				ktbic.inquiry_course_id,
				ktbic.program_service_id
			ORDER BY
				-ts_tl.position DESC,
				ktcl.name
		";

		return collect(\DB::getQueryRows($sql, [
			'week' => $week->toDateString(),
			'schools' => $this->aFilters['schools'],
			'courses' => $this->aFilters['courses'],
			'course_categories' => $this->aFilters['course_category'],
		]));
	}

	private function generateWeek(CarbonInterface $week): Table\Table
	{
		$result = $this->getQueryData($week);

		$classes = $result->mapWithKeys(fn(array $r) => [$r['class_id'] => [
			'name' => $r['class_name'] . (!empty($r['level_name']) ? ' (' . $r['level_name'] . ')' : '') . (!empty($r['internal_comment']) ? "\n\"" . $r['internal_comment'] . '"' : ''),
//			'from' => new \DateTime($r['class_from']),
			'weeks' => $r['class_weeks']
		]]);

		$templates = $result->mapWithKeys(fn(array $r) => [$r['template_id'] => [
			'id' => $r['template_id'],
			'name' => $r['template_id'] ? sprintf('%.5s – %.5s', $r['template_from'], $r['template_until']) : $this->t('Individuell')
		]])->sortBy(fn(array $t) => $t['id'] ? $t['name'] : 2);

		$table = new Table\Table();
		$table->setCaption(\Ext_Thebing_Format::LocalDate($week));

		// Klassen
		$row = new Table\Row();
		$row->setRowSet('head');
		$table[] = $row;

		$cell = new Table\Cell(null, true);
		$row[] = $cell;

		foreach ($classes as $class) {
//			$until = \Ext_Thebing_Util::getCourseEndDate($class['from'], $class['weeks'], 1);
//			$label = sprintf("%s\n%s – %s", $class['name'], \Ext_Thebing_Format::LocalDate($class['from']), \Ext_Thebing_Format::LocalDate($until));
			$cell = new Table\Cell($class['name'], true);
			$cell->setKeepLineBreaks(true);
			$row[] = $cell;
		}

		// Tabelle pro Klassenzeit
		foreach ($templates as $template) {
			$this->setTuitionTemplateTable($table, $template, $classes, $result);
		}

		return $table;
	}

	private function setTuitionTemplateTable(Table\Table $table, array $template, Collection $classes, Collection $result)
	{
		$row = new Table\Row();
		$table[] = $row;

		$cell = new Table\Cell($template['name'], true);
		$cell->setColspan(count($classes) + 1);
		$row[] = $cell;

		$teachers = collect();
		$students = $classes->map(function (array $class, int $classId) use ($result, $template, $teachers) {
			return $result
				->filter(fn(array $r) => $r['template_id'] == $template['id'] && $r['class_id'] == $classId)
				->each(function (array $r) use ($classId, $teachers) {
					$t1 = !empty($r['teacher_names']) ? explode(';', $r['teacher_names']) : [];
					$t2 = !empty($r['teacher_names_substitute']) ? explode(';', $r['teacher_names_substitute']) : [];
					$teachers[$classId] = array_unique([...$teachers->get($classId, []), ...$t1, ...$t2]);
				})
				->sortByDesc('student_name', SORT_NATURAL)
				->map(fn(array $r) => Arr::only($r, ['student_name', 'state', 'agency_id', 'course_period']));
		});

		$rows = count($students->max());

		// Zeilen (Schüler)
		for ($i = 0; $i < $rows; $i++) {
			$row = new Table\Row();
			$table[] = $row;

			// Counter
			$cell = new Table\Cell($i + 1, true);
			$row[] = $cell;

			foreach ($classes->keys() as $classId) {
				$studentsClass = $students->get($classId); /** @var Collection $studentsClass */
				$student = $studentsClass->pop();

				$cell = new Table\Cell($student ? $student['student_name'] : null);
				$row[] = $cell;

				if (!$student) {
					continue;
				}

				if ($student['state'] & \Ext_TS_Inquiry_TuitionIndex::STATE_NEW) {
					$cell->setBackground($student['agency_id'] ? self::COLORS['new_agency'] : self::COLORS['new_direct']);
				} elseif ($student['state'] & \Ext_TS_Inquiry_TuitionIndex::STATE_LAST) {
					$cell->setBackground(self::COLORS['last']);
				}

				[$from, $until] = explode(',', $student['course_period']);
				$cell->setComment(sprintf('%s – %s', \Ext_Thebing_Format::LocalDate($from), \Ext_Thebing_Format::LocalDate($until)));
			}
		}

		// Lehrer
		$row = new Table\Row();
		$table[] = $row;

		$cell = new Table\Cell(self::t('Lehrer'), true);
		$row[] = $cell;

		foreach ($classes->keys() as $classId) {
			$cell = new Table\Cell(join("; ", $teachers->get($classId, [])), true);
			$row[] = $cell;
		}

		// Leerzeile
		$row = new Table\Row();
		$table[] = $row;

		$cell = new Table\Cell();
		$cell->setColspan(count($classes) + 1);
		$row[] = $cell;
	}

	public function createDateFilterPeriod(): ?CarbonPeriod
	{
		$from = Carbon::now()->startOfWeek();
		$until = $from->copy()->addWeeks(3)->endOfWeek();

		return CarbonPeriod::create($from, $until);
	}

	public function isShowingFiltersInitially() {
		return true;
	}

	public function generateViewGenerator(\TcStatistic\Generator\Table\AbstractTable $generator)
	{
		if ($generator instanceof Excel) {
			$generator->setTablePerWorkSheet();
		}

		return parent::generateViewGenerator($generator);
	}
}