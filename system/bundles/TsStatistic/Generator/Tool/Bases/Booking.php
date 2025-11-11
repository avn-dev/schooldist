<?php

namespace TsStatistic\Generator\Tool\Bases;

class Booking implements BaseInterface {

	public function getTitle(): string {
		return \TsStatistic\Generator\Tool\AbstractColumnOrGrouping::t('Buchung: Erstellungsdatum');
	}

	public function getQuery(string $select, string $joins, string $where, string $groupBy): string {

		$where = $this->getWherePart().$where;

		// TODO Currency sollte nicht generell drin sein, da das nur für Beträge relevant ist
		$sql = "
			SELECT
				{$select}
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id`= `ts_i`.`id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
				{$joins}
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`currency_id` = :currency AND
				`cdb2`.`id` IN (:schools)
				{$where}
		";

		if(!empty($groupBy)) {
			$sql .= " GROUP BY
				{$groupBy}
			";
		}

		return $sql;

	}

	protected function getWherePart() {

		return " AND `ts_i`.`created` BETWEEN :from AND :until ";

	}

}