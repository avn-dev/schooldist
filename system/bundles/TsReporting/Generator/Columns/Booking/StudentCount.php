<?php

namespace TsReporting\Generator\Columns\Booking;

use Illuminate\Support\Collection;
use TsReporting\Generator\Columns\AbstractColumn;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\ColumnReduceTrait;

class StudentCount extends AbstractColumn
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
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class,
		\TsReporting\Generator\Groupings\Tuition\TuitionClass::class,
		\TsReporting\Generator\Groupings\Tuition\TuitionTime::class
	];

	public function getTitle(?array $varying = null): string
	{
		return $this->t('Anzahl der Schüler');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->selectRaw("GROUP_CONCAT(DISTINCT tc_c.id) id");
		$builder->selectRaw("GROUP_CONCAT(DISTINCT tc_cn.number SEPARATOR ', ') label");
		$builder->selectRaw("COUNT(DISTINCT tc_c.id) result");
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		$keys = [];

		// DISTINCT nachbilden für allerlei Gruppierungen, z.B. bei Kursbuchung (Gruppierung: Kurs)
		return $result->filter(function (array $row) use (&$keys) {
			$key = $this->buildGroupingRowKey($row, $row['id']);
			if (in_array($key, $keys)) {
				return false;
			}
			$keys[] = $key;
			return true;
		});
	}

	public function getFormat(ValueHandler $values): array
	{
		return [
			'type' => 'number',
			'summable' => true,
			'locale' => $values->getLocale()
		];
	}
}