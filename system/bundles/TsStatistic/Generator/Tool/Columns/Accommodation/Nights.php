<?php

namespace TsStatistic\Generator\Tool\Columns\Accommodation;

use Carbon\Carbon;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\AbstractColumn;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Service\NightCalculcator;

/**
 * TODO Beschreibung: Diese Spalte beachtet nur Unterkunftsbuchungen der Buchung und ignoriert Rechnungen. Tatsächliche Zuweisungen werden ignoriert.
 */
class Nights extends AbstractColumn {

	public function getTitle() {
		return self::t('Anzahl der Nächte (basierend auf Buchung)');
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

		return " 
			`tc_cn`.`number` `label`,
			`ts_ija`.`from`,
			`ts_ija`.`until`
		";

	}

	public function getJoinParts() {

		return [
			'contact_number',
			'accommodation'
		];

	}

	public function getJoinPartsAdditions() {

		$additions = [];

		$additions['JOIN_JOURNEY_ACCOMMODATIONS'] = " AND
			`ts_ija`.`from` <= :until AND
			`ts_ija`.`until` >= :from
		";

		return $additions;

	}

	public function getGroupBy() {
		return ['`ts_ija`.`id`'];
	}

	public function getResult(string $sql, FilterValues $values) {

		$result = parent::getResult($sql, $values);

		$nightCalculator = new NightCalculcator($values->from, $values->until);

		foreach ($result as &$row) {
			$row['result'] = $nightCalculator->calculate(Carbon::parse($row['from']), Carbon::parse($row['until']));
		}

		return $this->buildSum($result);

	}

	public function isSummable() {
		return true;
	}

	public function getFormat() {
		return 'number_int';
	}

}