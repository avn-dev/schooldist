<?php

namespace TsStatistic\Generator\Tool\Columns\Accommodation;

use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\StudentCount;
use TsStatistic\Generator\Tool\Groupings;

class StudentCountArrived extends StudentCount {

	public function getTitle() {
		return self::t('Anzahl der Schüler (erste Unterkunft)');
	}

	public function getAvailableBases() {
		return [
			BookingServicePeriod::class
		];
	}

	public function getAvailableGroupings() {
		return [
			Groupings\Nationality::class
		];
	}

	public function getSelect() {

		$select = " IF(`ts_ija`.`from` BETWEEN :from AND :until, COUNT(DISTINCT `tc_c`.`id`), 0) `result` ";

		$select .= " , IF(`ts_ija`.`from` BETWEEN :from AND :until, GROUP_CONCAT(DISTINCT `tc_cn`.`number`), '') `label` ";

		return $select;

	}

	public function getJoinParts() {

		$parts = parent::getJoinParts();
		$parts[] = 'accommodation';

		return $parts;

	}

	public function getJoinPartsAdditions() {

		$additions = parent::getJoinPartsAdditions();

		$additions['JOIN_JOURNEY_ACCOMMODATIONS'] = " AND
			`ts_ija`.`from` <= :until AND
			`ts_ija`.`until` >= :from AND
			`ts_ija`.`id` = (
				SELECT
					`id`
				FROM
					`ts_inquiries_journeys_accommodations`
				WHERE
					`journey_id` = `ts_ij`.`id` AND
					`active` = 1 AND
					`visible` = 1
				ORDER BY
					`from` ASC,
					`id` ASC
				LIMIT
					1
			)
		";

		return $additions;

	}

	public function isSummable() {
		return true;
	}

	public function getColumnColor() {
		return 'general';
	}

	public function getConfigurationOptions() {
		return [];
	}

}