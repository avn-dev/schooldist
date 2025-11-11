<?php

namespace TsSponsoring\Entity;

use Core\Helper\DateTime;

class SponsorRepository extends \WDBasic_Repository {

	public function getSponsorsForSelect() {

		$sSql = "
			SELECT
				`id`,
				`name`
			FROM
				`ts_sponsors`
			WHERE
				`active` = 1 AND (
				    `valid_until` = '0000-00-00' OR
				    `valid_until` >= :valid_until
				)
			ORDER BY 
			    `name`
		";

		$aSponsors = (array)\DB::getQueryPairs($sSql, [
			'valid_until' => (new DateTime())->format('Y-m-d')
		]);

		return $aSponsors;

	}

	/**
	 * @param int $iSponsorId
	 * @return \Ext_TS_Payment_Condition|null
	 */
	public function getValidPaymentCondition($iSponsorId) {

		$sSql = "
			SELECT
				`payment_condition_id`
			FROM
				`ts_sponsors_payment_conditions_validity`
			WHERE
				`active` = 1 AND
				`sponsor_id` = :sponsor_id AND (
					`valid_until` = '0000-00-00' OR
					:date BETWEEN `valid_from` AND `valid_until`
				)
		";

		$iPaymentConditionId = \DB::getQueryOne($sSql, [
			'sponsor_id' => $iSponsorId,
			'date' => (new \DateTime())->format('Y-m-d')
		]);

		if($iPaymentConditionId !== null) {
			return \Ext_TS_Payment_Condition::getInstance($iPaymentConditionId);
		}

		return null;

	}

}
