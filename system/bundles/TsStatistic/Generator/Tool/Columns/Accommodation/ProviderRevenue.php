<?php

namespace TsStatistic\Generator\Tool\Columns\Accommodation;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Period\Period;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\Revenue;
use TsStatistic\Generator\Tool\Groupings;

class ProviderRevenue extends Revenue {

	public function __construct($grouping = null, $headGrouping = null, $configuration = null) {
		parent::__construct($grouping, $headGrouping, 'net_accommodation');
	}

	public function getTitle() {
		return self::t('Umsatz - Unterkunft (netto, exkl. Steuern, zugewiesene Nächte)');
	}

	public function getAvailableBases() {
		return [
			BookingServicePeriod::class
		];
	}

	public function getAvailableGroupings() {
		return [
			Groupings\Revenue\AccommodationProvider::class,
		];
	}

	public function getSelect() {

		$sSelect = parent::getSelect();

		$sSelect .= ",
			GROUP_CONCAT(CONCAT(`cdb4`.`id`, ',', `kaa`.`from`, ',', `kaa`.`until`) SEPARATOR ';') `allocation_periods`
		";

		return $sSelect;

	}

	public function prepareResult(array $result, FilterValues $values) {

		$filterPeriod = Period::make($values->from, $values->until);
		$amountHelper = $this->createDocumentItemAmountHelper($values);

		foreach ($result as &$item) {

			$itemFrom = Carbon::parse($item['item_from']);
			$itemUntil = Carbon::parse($item['item_until']);

			$amountHelper->setAccommodationNightServicePeriod($item, $itemFrom, $itemUntil);
			$item['item_from'] = $itemFrom->toDateString();

			$days = 0;
			$amount = $amountHelper->calculate($item);
//			$amountFormatted = \Ext_Thebing_Format::Number($amount);
			$itemPeriod = Period::make($itemFrom, $itemUntil);
			$itemOverlap = $filterPeriod->overlap($itemPeriod);
			if (!$itemOverlap) {
				continue;
			}

			$daysTotal = $itemOverlap->length();

			$allocations = Str::of($item['allocation_periods'])
				->explode(';')
				->map(fn(string $allocation) => Str::of($allocation)->explode(','));

			foreach ($allocations as $allocation) {
				[$providerId, $from, $until] = $allocation;

				if ($providerId != $item['grouping_id']) {
					// Durch GROUP BY grouping_id darf es hier nur den korrekten Anbieter geben
					throw new \LogicException('Invalid grouping id in result');
				}

				$from = Carbon::parse($from)->addDay();
				$until = Carbon::parse($until);

				$periodAllocation = Period::make($from, $until);

				$overlap = $itemOverlap->overlap($periodAllocation);
				if (!$overlap) {
					continue;
				}

				$days += $overlap->length();

			}

			$item['label'] .= sprintf(' (%s/%d*%d)', $amount, $daysTotal, $days);
			$item['result'] = $amount / $daysTotal * $days;

		}

		return $this->buildSum($result);

	}

	public function getConfigurationOptions() {
		return [];
	}

}
