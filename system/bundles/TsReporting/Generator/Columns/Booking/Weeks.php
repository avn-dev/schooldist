<?php

namespace TsReporting\Generator\Columns\Booking;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Columns\AbstractColumn;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\ColumnReduceTrait;

class Weeks extends AbstractColumn
{
	use ColumnReduceTrait;

	protected array $availableGroupings = [
		\TsReporting\Generator\Groupings\Aggregated::class,
		\TsReporting\Generator\Groupings\Booking\Accommodation::class,
		\TsReporting\Generator\Groupings\Booking\AgeGroup::class,
		\TsReporting\Generator\Groupings\Booking\Agency::class,
		\TsReporting\Generator\Groupings\Booking\Booking::class,
		\TsReporting\Generator\Groupings\Booking\Course::class,
		\TsReporting\Generator\Groupings\Booking\Gender::class,
		\TsReporting\Generator\Groupings\Booking\Group::class,
		\TsReporting\Generator\Groupings\Booking\Inbox::class,
		\TsReporting\Generator\Groupings\Booking\Nationality::class,
		\TsReporting\Generator\Groupings\Booking\SalesPerson::class,
		\TsReporting\Generator\Groupings\Booking\StudentStatus::class,
		// TODO #18748 Erst möglich ab MariaDB 10.2.3 wg. JSON-Funktionen
		// TODO Außerdem muss NEGATE_FACTOR neu implementiert werden
		//\TsReporting\Generator\Groupings\Document\Course::class,
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class
	];

	protected string $type;

	public function getTitle(?array $varying = null): string
	{
		return $this->t('Wochen');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->requireScope(CourseScope::class);
		$builder->selectRaw("GROUP_CONCAT(DISTINCT tc_cn.number SEPARATOR ', ') label");
		$builder->addSelect('cdb2.course_startday');
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		$period = Period::make($values->getPeriod()->getStartDate(), $values->getPeriod()->getEndDate());

		$result->transform(function (array $row) use ($period, $values) {
			if (!$this->base instanceof BookingServicePeriod) {
				$row['result'] = $row['course_weeks'];
				return $row;
			}

			$from = new Carbon($row['course_from']);
			$until = new Carbon($row['course_until']);

			// Samstag oder Sonntag: Tage abziehen, um auf 5 Tage zu kommen (analog zu calcWeeksPart())
			// Fixe Woche mit 5 Tagen!
			$blockDays = \Ext_Thebing_Util::getBlockWeekDays($row['course_startday']);
			if (
				($subTwo = $until->dayOfWeekIso === $blockDays[6]) ||
				$until->dayOfWeekIso === $blockDays[5]
			) {
				$until->subDays($subTwo ? 2 : 1);
			}

			if ($until < $from) {
				$until = $from;
			}

			$overlap = $period->overlap(Period::make($from, $until));
			if ($overlap) {
				// Entsprechenden der fixen Woche mit 5 Tagen die Wochenenden abziehen
				$weekendDays = floor($overlap->length() / 7) * 2;
				$row['result'] = ($overlap->length() - $weekendDays) / 5;
			}

			return $row;
		});

		return $result;
	}

	public function getFormat(ValueHandler $values): array
	{
		return [
			'type' => 'number',
			'locale' => $values->getLocale(),
			'summable' => true,
		];
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'type',
				'label' => $this->t('Typ'),
				'type' => 'select',
				'options' => [
					'course' => $this->t('Kurswochen')
				]
			]
		];
	}
}