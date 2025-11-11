<?php

/**
 * Ticket #10596 – Einstellungen für Unterkunfts-Block funktionieren nicht mit globalen Unterkunftsressourcen
 *
 * https://redmine.thebing.com/redmine/issues/10596
 */
class Ext_Thebing_System_Checks_Frontend_Form_GlobalAccommodations extends GlobalChecks {

	public function getTitle() {
		return 'Update registration form settings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_forms_pages_blocks_settings');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kfpbs`.`setting`,
				`kfpbs`.`block_id`,
				`kfpbs`.`value`,
				`kf`.`id` `form_id`,
				GROUP_CONCAT(`kfs`.`school_id`) `school_ids`
			FROM
				`kolumbus_forms_pages_blocks_settings` `kfpbs` LEFT JOIN
				`kolumbus_forms_pages_blocks` `kfpb` ON
					`kfpb`.`id` = `kfpbs`.`block_id` LEFT JOIN
				`kolumbus_forms_pages` `kfp` ON
					`kfp`.`id` = `kfpb`.`page_id` LEFT JOIN
				`kolumbus_forms` `kf` ON
					`kf`.`id` = `kfp`.`form_id` LEFT JOIN
				`kolumbus_forms_schools` `kfs` ON
					`kfs`.`form_id` = `kf`.`id`
			WHERE
				`setting` REGEXP '^accommodation_[0-9]{1,}_[0-9]{1,}_[0-9]{1,}$'
			GROUP BY
				`kfpbs`.`block_id`,
				`kfpbs`.`setting`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			$sSql = "
				DELETE FROM
					`kolumbus_forms_pages_blocks_settings`
				WHERE
					`block_id` = :block_id AND
					`setting` = :setting
			";

			DB::executePreparedQuery($sSql, $aRow);

			if(empty($aRow['school_ids'])) {
				continue;
			}

			$aSchoolIds = explode(',', $aRow['school_ids']);

			foreach($aSchoolIds as $iSchoolId) {

				$sSql = "
					INSERT INTO
						`kolumbus_forms_pages_blocks_settings`
					SET
						`block_id` = :block_id,
						`setting` = :setting,
						`value` = :value
				";

				$aSql = $aRow;
				$aSql['setting'] = $aSql['setting'].'_'.$iSchoolId;

				DB::executePreparedQuery($sSql, $aSql);

			}

		}

		DB::executeQuery(" ALTER TABLE `kolumbus_forms_pages_blocks_settings` ORDER BY `block_id`, `setting` ");

		DB::commit(__CLASS__);

		return true;

	}

}