<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

class AccommodationFees extends GeneralFees {

	public function getTitle() {
		return self::t('Zusätzliche Unterkunftsgebühren');
	}

	public function getJoinParts() {
		return ['accommodation'];
	}

	protected function getFeeType() {
		return 'additional_accommodation';
	}

}
