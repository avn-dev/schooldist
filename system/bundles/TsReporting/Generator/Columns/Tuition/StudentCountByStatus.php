<?php

namespace TsReporting\Generator\Columns\Tuition;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Columns\Booking\StudentCount;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class StudentCountByStatus extends StudentCount
{
	protected string $type;

	protected int $status;

	protected array $availableGroupings = [
		\TsReporting\Generator\Groupings\Aggregated::class,
		\TsReporting\Generator\Groupings\Booking\Agency::class,
		\TsReporting\Generator\Groupings\Booking\Booking::class,
		\TsReporting\Generator\Groupings\Booking\Course::class,
		\TsReporting\Generator\Groupings\Booking\Gender::class,
		\TsReporting\Generator\Groupings\Booking\Inbox::class,
		\TsReporting\Generator\Groupings\Booking\Nationality::class,
		\TsReporting\Generator\Groupings\Booking\SalesPerson::class,
		\TsReporting\Generator\Groupings\Booking\StudentStatus::class,
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class,
	];

	public function getTitle(?array $varying = null): string
	{
		$title = $this->t('Anzahl der Schüler');
		$options = collect($this->getConfigOptions());

		$additional = [];
		if ($varying === null) {
			$additional[] = $this->t('Status nach Klassenplanung');
		} else {
			$additional[] = data_get($options->firstWhere('key', 'type'), 'options.'.$this->type);
		}

		if (in_array('status', (array)$varying)) {
			$additional[] = lcfirst(data_get($options->firstWhere('key', 'status'), 'options.'.$this->status));
		}

		if (!empty($additional)) {
			$title .= ' ('.join(', ', $additional).')';
		}

		return $title;
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		parent::build($builder, $values);

		if ($this->type === 'course') {
			$builder->requireScope(CourseScope::class);
		}

		$builder->requireScope(function (QueryBuilder $builder, ValueHandler $values) {
			$table = $this->type === 'booking' ? 'ts_inquiries_tuition_index' : 'ts_inquiries_journeys_courses_tuition_index';
			$alias = $this->type === 'booking' ? 'ts_iti' : 'ts_ijcti';

			$builder->join($table.' as '.$alias, function (JoinClause $join) use ($alias, $values) {
				match ($this->type) {
					'booking' => $join->on($alias.'.inquiry_id', 'ts_i.id'),
					'course' => $join->on($alias.'.journey_course_id', 'ts_ijc.id')
				};
				$join->where($alias.'.state', '&', $this->status);
				$join->whereRaw('getCorrectCourseStartDay('.$alias.'.week, cdb2.course_startday) <= ?', [$values->getPeriod()->getEndDate()]);
				$join->whereRaw('getCorrectCourseStartDay('.$alias.'.week, cdb2.course_startday) + INTERVAL 6 DAY >= ?', [$values->getPeriod()->getStartDate()]);
			});
		});
	}

	public function getConfigOptions(): array
	{
		$language = (new \Tc\Service\Language\Backend(\Ext_TC_System::getInterfaceLanguage()))
			->setContext(\TsReporting\Entity\Report::TRANSLATION_PATH);

		$states = (new \TsTuition\Helper\State(\TsTuition\Helper\State::KEY_BINARY, $language))
			->getOptions();

		return [
			[
				'key' => 'type',
				'label' => $this->t('Typ'),
				'type' => 'select',
				'options' => [
					'booking' => $this->t('Status der Buchung'),
					'course' => $this->t('Status des Kurses')
				]
			],
			[
				'key' => 'status',
				'label' => $this->t('Status'),
				'type' => 'select',
				'options' => $states
			]
		];
	}
}