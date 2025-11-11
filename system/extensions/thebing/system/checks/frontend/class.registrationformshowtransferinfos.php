<?php

/**
 * Checkbox »An- und Abreise-Informationen immer anzeigen« in den Transfer-Optionen (Dialog) setzen
 *
 * @link https://redmine.thebing.com/redmine/issues/6958
 */
class Ext_Thebing_System_Checks_Frontend_RegistrationFormShowTransferInfos extends GlobalChecks {
	
	public function getTitle() {
		return 'Update registration form settings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$sSql = "
			SELECT
				`kfpb`.`id`,
				GROUP_CONCAT(`kfs`.`school_id`) `school_ids`
			FROM
				`kolumbus_forms_pages_blocks` `kfpb` INNER JOIN
				`kolumbus_forms_pages` `kfp` ON
					`kfp`.`id` = `kfpb`.`page_id` INNER JOIN
				`kolumbus_forms` `kf` ON
					`kf`.`id` = `kfp`.`form_id` INNER JOIN
				`kolumbus_forms_schools` `kfs` ON
					`kfs`.`form_id` = `kf`.`id`
			WHERE
				`block_id` = 3 -- Transfers
			GROUP BY
				`kfpb`.`id`
		";

		$aTransferBlocks = (array)DB::getQueryPairs($sSql);

		foreach($aTransferBlocks as $iBlockId => $sSchoolIds) {
			$aSchoolIds = explode(',', $sSchoolIds);

			foreach($aSchoolIds as $iSchoolId) {

				$sSql = "
					REPLACE INTO
						`kolumbus_forms_pages_blocks_settings`
					SET
						`block_id` = :block_id,
						`setting` = :setting,
						`value` = 1
				";

				DB::executePreparedQuery($sSql, array(
					'block_id' => $iBlockId,
					'setting' => 'always_show_inputs_'.$iSchoolId
				));
			}
		}

		return true;
	}
}
