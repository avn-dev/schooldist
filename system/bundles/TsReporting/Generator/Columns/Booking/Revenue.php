<?php

namespace TsReporting\Generator\Columns\Booking;

use Core\DTO\DateRange;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Columns\AbstractColumn;
use TsReporting\Generator\Scopes\Booking\ItemScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\ColumnReduceTrait;
use TsStatistic\Service\DocumentItemAmount;

class Revenue extends AbstractColumn
{
	use ColumnReduceTrait;

	protected array $availableGroupings = [
		\TsReporting\Generator\Groupings\Aggregated::class,
		\TsReporting\Generator\Groupings\Booking\Agency::class,
		\TsReporting\Generator\Groupings\Booking\Booking::class,
		\TsReporting\Generator\Groupings\Booking\Gender::class,
		\TsReporting\Generator\Groupings\Booking\Group::class,
		\TsReporting\Generator\Groupings\Booking\Inbox::class,
		\TsReporting\Generator\Groupings\Booking\Nationality::class,
		\TsReporting\Generator\Groupings\Booking\SalesPerson::class,
		\TsReporting\Generator\Groupings\Booking\StudentStatus::class,
		\TsReporting\Generator\Groupings\Document\Accommodation::class,
		\TsReporting\Generator\Groupings\Document\Course::class,
		\TsReporting\Generator\Groupings\Document\Fees::class,
		\TsReporting\Generator\Groupings\Document\ItemType::class,
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class
	];

	protected string $amountType;

	protected string $serviceType;

	protected string $taxType;

	protected string $currency;

	public function getTitle(?array $varying = null): string
	{
		$title = $this->getRevenueTitle();
		$options = collect($this->getConfigOptions());

		$additional = [];
		foreach ((array)$varying as $key) {
			$additional[] = data_get($options->firstWhere('key', $key), 'options.'.$this->{Str::camel($key)});
		}

		if (!empty($additional)) {
			$title .= ' ('.join(', ', $additional).')';
		}

		return $title;
	}

	protected function getRevenueTitle(): string
	{
		return $this->t('Umsatz');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->requireScope(ItemScope::class)
			->addJoinAddition(function (JoinClause $join) use ($values) {
//				if ($this->base instanceof BookingServicePeriod) {
//					$join->where('kidvi.index_from', '<=', $values->getPeriod()->getEndDate());
//					$join->where('kidvi.index_until', '>=', $values->getPeriod()->getStartDate());
//
////					$join->whereRaw("kidvi.index_from <= IF(kidvi.type = 'course', ? - INTERVAL 1 WEEK, ?)", [$values->getPeriod()->getEndDate(), $values->getPeriod()->getEndDate()]);
////					$join->whereRaw("kidvi.index_until >= IF(kidvi.type = 'course', ? + INTERVAL 1 WEEK, ?)", [$values->getPeriod()->getStartDate(), $values->getPeriod()->getStartDate()]);
//
////					// Da der Zeitraum bei Kursen durch DocumentItemAmount korrigiert wird, pauschal jeweils eine Woche ergänzen, damit Zeitraum immer voll enthalten ist
////					$fromAddition = $this->serviceType === 'course' ? '- INTERVAL 1 WEEK' : '';
////					$untilAddition = $this->serviceType === 'course' ? '+ INTERVAL 1 WEEK' : '';
////					$join->whereRaw('kidvi.index_from <= ? '.$fromAddition, [$values->until]);
////					$join->whereRaw('kidvi.index_until >= ? '.$untilAddition, [$values->from]);
//
//					// Setzen für BookingServicePeriod, damit where nicht zwei mal angewendet wird und somit die Logik für Kurse nicht funktionieren kann
//					$join->macro(BookingServicePeriod::ITEM_PERIOD_FILTERED, fn() => true);
//				}

				if (!empty($types = $this->matchItemType($this->serviceType))) {
					$join->whereIn('kidvi.type', $types);
				}
			});

		$amountHelper = $this->createAmountHelper($values);

		$builder->addSelect('kid.document_number as label');
		$builder->addSelect('ts_i.currency_id as currency');
		$builder->addSelect(...$amountHelper->getSelectForBuilder());
		$builder->where('ts_i.currency_id', $this->currency);
		// Entwürfe sollen nicht angezeigt werden.
		$builder->where('kid.draft', 0);
	}

	private function createAmountHelper(ValueHandler $values)
	{
		$amountHelper = new DocumentItemAmount();
		$amountHelper->sAmountType = $this->amountType;
		$amountHelper->iTaxMode = $this->taxType === 'yes' ? 1 : 0;

		if ($this->base instanceof BookingServicePeriod) {
			$amountHelper->bSplitByServicePeriod = true;
			$amountHelper->oServicePeriodSplitDateRange = new DateRange($values->getPeriod()->getStartDate()->toDate(), $values->getPeriod()->getEndDate()->toDate());
		}

		return $amountHelper;
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		$currency = null;
		$amountHelper = $this->createAmountHelper($values);

		$result->transform(function (array $item) use (&$currency, $amountHelper) {
			if ($currency === null) {
				$currency = $item['currency'];
			} elseif ($currency !== $item['currency']) {
				throw new \RuntimeException('Can not mix different currencies');
			}
			$item['result'] = $amountHelper->calculate($item);
			return $item;
		});

		return parent::prepare($result, $values);
	}

	public function getFormat(ValueHandler $values): array
	{
		return [
			'type' => 'number',
			'style' => 'currency',
			'currency' => \Ext_Thebing_Currency::getInstance($this->currency)->getIso(),
			'locale' => $values->getLocale(),
			'summable' => true,
		];
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'service_type',
				'label' => $this->t('Leistungstyp'),
				'type' => 'select',
				'options' => [
					'all' => $this->t('Totale'),
					'course' => $this->t('Kurs'),
					'accommodation' => $this->t('Unterkunft'),
					'insurance' => $this->t('Versicherung'),
					'transfer' => $this->t('Transfer'),
					'activity' => $this->t('Aktivität'),
					// 'additional' => $this->t('Zusatzgebühren'),
					'additional_general' => $this->t('Generelle Zusatzgebühr'),
					'additional_course' => $this->t('Kursgebühr'),
					'additional_accommodation' => $this->t('Unterkunftsgebühr'),
					'extraPosition' => $this->t('Manuelle Position')
				]
			],
			[
				'key' => 'amount_type',
				'label' => $this->t('Betragstyp'),
				'type' => 'select',
				'options' => [
					'net' => $this->t('netto'),
					'gross' => $this->t('brutto')
				]
			],
			[
				'key' => 'currency',
				'label' => $this->t('Währung'),
				'type' => 'select',
				'options' => \Ext_Thebing_Client::getFirstClient()->getSchoolsCurrencies(true)
			],
			[
				'key' => 'tax_type',
				'label' => $this->t('Steuer'),
				'type' => 'select',
				'options' => [
					'no' => $this->t('Steuern abziehen'),
					'yes' => $this->t('Steuern hinzufügen')
				]
			],
		];
	}

	public function matchItemType(string $type): array
	{
		return match ($type) {
			'all' => [],
			'course' => ['course'],
			'accommodation' => ['accommodation', 'extra_nights', 'extra_weeks'],
			'transfer' => ['transfer'],
			'insurance' => ['insurance'],
			'activity' => ['activity'],
			// 'additional' => ['additional_general', 'additional_course', 'additional_accommodation'],
			'additional_general' => ['additional_general'],
			'additional_course' => ['additional_course'],
			'additional_accommodation' => ['additional_accommodation'],
			'extraPosition' => ['extraPosition']
		};
	}
}