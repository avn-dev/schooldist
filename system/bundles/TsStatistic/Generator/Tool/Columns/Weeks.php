<?php

namespace TsStatistic\Generator\Tool\Columns;

use Core\Helper\DateTime;
use TsStatistic\Generator\Tool\Bases;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Service\DocumentItemAmount;

class Weeks extends AbstractColumn {

	protected $bOverwriteGroupingColumn = true;

	public function getTitle() {
		return self::t('Kurswochen');
	}

	public function getAvailableBases() {
		return [
			Bases\Booking::class,
			Bases\BookingServicePeriod::class
		];
	}

	public function getAvailableGroupings() {
		return [
			Groupings\InquiryChannel::class
		];
	}

	public function getSelect() {

		$sSelect = "
			`tc_cn`.`number` `label`,
			`ts_ijc`.`from`,
			`ts_ijc`.`until`,
			`ts_ijc`.`weeks`,
			`cdb2`.`course_startday`
		";

		return $sSelect;

	}

	public function getColumnColor() {
		return 'service';
	}

	public function getJoinParts() {
		return ['course', 'contact_number'];
	}

	public function getGroupBy() {
		return ['`ts_ijc`.`id`'];
	}

	public function getResult($sql, $values) {

		$aResult = parent::getResult($sql, $values);

		$oItemAmountService = new DocumentItemAmount();

		foreach($aResult as &$axItem) {

			if(!$this->base instanceof Bases\BookingServicePeriod) {

				$axItem['result'] = $axItem['weeks'];

			} else {

				$dFrom = new DateTime($axItem['from']);
				$dUntil = new DateTime($axItem['until']);

				// Kurse auf volle Wochen erweitern
				$oItemAmountService->setCourseServicePeriod($axItem, $dFrom, $dUntil);

				$axItem['result'] = DateTime::getDaysInPeriodIntersection($values['from'], $values['until'], $dFrom, $dUntil);

				// DateTime::getDaysInPeriodIntersection() zählt nur Nächte, daher am Ende den letzten Tag hinzufügen
				if(
					$axItem['result'] > 0 &&
					$values['until_datetime'] > $dUntil
				) {
					$axItem['result']++;
				}

				$axItem['result'] /= 7;

			}

		}

		// Da die Items nicht (mehr) im Query summiert werden, muss das manuell passieren
		$aResult = $this->buildSum($aResult);

		return $aResult;

	}

	public function isSummable() {
		return true;
	}

	public function getFormat() {
		return 'number_float';
	}

	public function getConfigurationOptions() {
		return [
			'course' => self::t('Kurswochen')
		];
	}

}
