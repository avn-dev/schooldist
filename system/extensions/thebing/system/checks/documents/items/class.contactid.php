<?php

class Ext_Thebing_System_Checks_Documents_Items_ContactId extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Check allocations of customers to invoice items.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		$this->migrateContactIdTable();
		$this->fixMissingContactIds();

		return true;

	}

	private function migrateContactIdTable() {

		if(!Util::checkTableExists('kolumbus_inquiries_documents_versions_items_to_contacts')) {
			return;
		}

		Util::backupTable('kolumbus_inquiries_documents_versions_items_to_contacts');

		DB::addField('kolumbus_inquiries_documents_versions_items', 'contact_id', "MEDIUMINT UNSIGNED NOT NULL DEFAULT '0'", 'type_parent_object_id', 'INDEX');

		DB::executeQuery("
			UPDATE
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions_items_to_contacts` `kidvitc` ON
					`kidvitc`.`item_id` = `kidvi`.`id` INNER JOIN
				/* Join auf Kontakte notwendig, damit keine Schrott-IDs übernommen werden */
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `kidvitc`.`contact_id`
			SET
				`kidvi`.`contact_id` = `tc_c`.`id`
		");

		DB::executeQuery(" DROP TABLE `kolumbus_inquiries_documents_versions_items_to_contacts` ");

	}

	private function fixMissingContactIds() {

		DB::begin(__METHOD__);

		$sSql = "
			SELECT
				`kidvi`.`id` `item_id`,
				`kidvi`.`contact_id` `item_contact_id`,
				`ts_i`.`id` `inquiry_id`,
				`ts_itc`.`contact_id` `inquiry_contact_id`,
				`ts_e`.`id` `enquiry_id`,
				`ts_etc`.`contact_id` `enquiry_contact_id`,
				`kidvi`.`contact_id` = 0 `contact_id_empty`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` LEFT JOIN
				(
					`ts_inquiries` `ts_i` INNER JOIN
					`ts_inquiries_to_contacts` `ts_itc`
				) ON
					`ts_i`.`id` = `kid`.`inquiry_id` AND
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' LEFT JOIN
				(
					`ts_enquiries_to_documents` `ts_etd` INNER JOIN
					`ts_enquiries` `ts_e` INNER JOIN
					`ts_enquiries_to_contacts` `ts_etc`
				) ON
					`ts_etd`.`document_id` = `kid`.`id` AND
					`ts_e`.`id` = `ts_etd`.`enquiry_id` AND
					`ts_etc`.`enquiry_id` = `ts_e`.`id` AND
					`ts_etc`.`type` = 'booker' LEFT JOIN
					`ts_enquiries_to_groups` `ts_etg` ON
						`ts_etg`.`enquiry_id` = `ts_e`.`id`
			WHERE
				(
					/* Irgendeine ID muss vorhanden sein, sonst fehlt der Eintrag in der Zwischentabelle (on_cascade hat Beziehung gelöscht?) */
					`ts_itc`.`contact_id` IS NOT NULL OR
					`ts_etc`.`contact_id` IS NOT NULL
				) AND (
					`kidvi`.`contact_id` = 0 OR (
						(
							`ts_i`.`id` IS NOT NULL AND
							`ts_itc`.`contact_id` != `kidvi`.`contact_id`
						) OR (
							`ts_e`.`id` IS NOT NULL AND
							/* Enquiry-Gruppen ausschließen, da dort die contact_id schon für die Gruppen benutzt wird */
							`ts_etg`.`group_id` IS NULL AND
							`ts_etc`.`contact_id` != `kidvi`.`contact_id`
						)
					)
				)
			GROUP BY
				`kidvi`.`id`
			ORDER BY
				`kidvi`.`id` DESC
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aItem) {
			if($aItem['contact_id_empty']) {

				if(!empty($aItem['inquiry_id'])) {
					$iContactId = $aItem['inquiry_contact_id'];
				} elseif(!empty($aItem['enquiry_id'])) {
					$iContactId = $aItem['enquiry_contact_id'];
				} else {
					throw new RuntimeException('All contact_id fields are empty!');
				}

				if(empty($iContactId)) {
					throw new RuntimeException('$iContactId is empty!');
				}

				$sSql = "
					UPDATE
						`kolumbus_inquiries_documents_versions_items`
					SET
						`contact_id` = {$iContactId},
						`changed` = `changed`
					WHERE
						`id` = {$aItem['item_id']}
				";

				DB::executeQuery($sSql);

				$this->logInfo('Item '.$aItem['item_id'].': Set empty contact_id to '.$iContactId, $aItem);

			} else {
				$this->logError('Item '.$aItem['item_id'].' contact_id does not belong to this inquiry/enquiry', $aItem);
			}
		}

		DB::commit(__METHOD__);

	}

}