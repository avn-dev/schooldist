<?php

class Ext_Thebing_System_Checks_Inquiry_InsuranceWeeks extends GlobalChecks {

	public function getTitle() {
		return 'Maintenance for insurances of bookings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$sSql = "
			SELECT
				`ts_iji`.`id`,
				`ts_iji`.`from`,
				`ts_iji`.`until`
			FROM
				`ts_inquiries_journeys_insurances` `ts_iji` INNER JOIN
				`kolumbus_insurances` `kins` ON
					`kins`.`id` = `ts_iji`.`insurance_id`
			WHERE
				`kins`.`payment` = 3 AND (
					`ts_iji`.`weeks` IS NULL OR
					`ts_iji`.`weeks` = 0
				)
		";

		$aInsurances = (array)DB::getQueryRows($sSql);

		if(empty($aInsurances)) {
			return true;
		}

		Util::backupTable('ts_inquiries_journeys_insurances');

		foreach($aInsurances as $aInsurance) {

			// Aus \Ext_Thebing_Inquiry_Gui2_Html::getInsuranceBodyHtml() kopiert
			$oDateFrom = new WDDate($aInsurance['from'].' 00:00:00', WDDate::DB_TIMESTAMP);
			$oDateTill = new WDDate($aInsurance['until'].' 00:00:00', WDDate::DB_TIMESTAMP);
			$iWeeks = 1;
			while(true) {
				$oDateFrom->add(1, WDDate::WEEK);
				if($oDateFrom->get(WDDate::TIMESTAMP) > $oDateTill->get(WDDate::TIMESTAMP) || $iWeeks > 1000) {
					break;
				}
				$iWeeks++;
			}

			$aInsurance['weeks'] = $iWeeks;

			$sSql = "
				UPDATE
					`ts_inquiries_journeys_insurances`
				SET
					`weeks` = :weeks,
					`changed` = `changed`
				WHERE
					`id` = :id
			";

			DB::executePreparedQuery($sSql, $aInsurance);

		}

		return true;

	}

}
