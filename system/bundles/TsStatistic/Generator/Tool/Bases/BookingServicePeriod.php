<?php

namespace TsStatistic\Generator\Tool\Bases;

class BookingServicePeriod extends Booking {

	public function getTitle(): string {
		return \TsStatistic\Generator\Tool\AbstractColumnOrGrouping::t('Buchung: Leistungszeitraum');
	}

	protected function getWherePart() {

		return " AND
			`ts_i`.`service_from` <= :until AND
			`ts_i`.`service_until` >= :from
		";

	}

}