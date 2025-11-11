<?php

namespace Ts\Entity;

class DocumentRepository extends \WDBasic_Repository {

	/**
	 * Liefert den Status aller Dokumente
	 *
	 * @param array $aIds
	 * @return array
	 */
	public function getStatusByIds(array $aIds) {

		$sSql = "
			SELECT
				`kid`.`id`,
				`kid`.`status`,
				`kidv`.`path`
			FROM
				`kolumbus_inquiries_documents` `kid` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version`
			WHERE
				-- `kid`.`status` != 'pending' AND
				`kid`.`id` IN ( :ids )
		";

		$aResult = (array)\DB::getQueryRowsAssoc($sSql, array('ids' => $aIds));

		return $aResult;
	}

}