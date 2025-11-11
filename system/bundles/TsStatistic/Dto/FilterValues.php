<?php

namespace TsStatistic\Dto;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use TsReporting\Generator\Groupings\AbstractGrouping;

/**
 * @property-read Carbon $from
 * @property-read Carbon $until
 * @property-read int[] $schools
 * @property-read int[] $currency
 * @property-read string[] $document_types
 * @property-read ?AbstractGrouping $grouping
 */
class FilterValues extends Collection {

	public function __get($key) {

		if ($this->has($key)) {
			return $this->get($key);
		}

		return parent::__get($key);

	}

	public function toSqlData() {

		$self = $this->toArray();

		array_walk_recursive($self, function (&$item) {
			if ($item instanceof Carbon) {
				$item = $item->toDateTimeString();
			}
		});

		return $self;


	}

}