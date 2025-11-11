<?php

namespace TsPrivacy\Entity;

class DepurationRepository extends \WDBasic_Repository {

	/**
	 * @param string $sEntity
	 * @param int $iEntityId
	 * @param string $sDeletionDate
	 */
	public function insertEntry($sEntity, $iEntityId, $sDeletionDate) {

		\DB::insertData('ts_privacy_depuration', [
			'entity' => $sEntity,
			'entity_id' => $iEntityId,
			'deletion_date' => $sDeletionDate
		], true, true);

	}

	/**
	 * @param \DateTime $dDate
	 * @return array
	 */
	public function getEntries(\DateTime $dDate) {

		$sSql = "
			SELECT
				`id`,
				`entity`,
				`entity_id`
			FROM
				`ts_privacy_depuration`
			WHERE
				`deletion_date` <= :date
			ORDER BY
				`deletion_date`,
				`id`
			LIMIT
				1000
		";

		$aResult = (array)\DB::getQueryRows($sSql, [
			'date' => $dDate->format('Y-m-d')
		]);

		return $aResult;

	}

	/**
	 * @param int $iId
	 */
	public function deleteEntry($iId) {

		$sSql = "
			DELETE FROM
				`ts_privacy_depuration`
			WHERE
				`id` = :id
		";

		\DB::executePreparedQuery($sSql, [
			'id' => $iId
		]);

	}

}
