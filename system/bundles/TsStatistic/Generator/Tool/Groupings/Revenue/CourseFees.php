<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

class CourseFees extends GeneralFees {

	public function getTitle() {
		return self::t('Zusätzliche Kursgebühren');
	}

	public function getJoinParts() {
		return ['course'];
	}

	protected function getFeeType() {
		return 'additional_course';
	}

}
