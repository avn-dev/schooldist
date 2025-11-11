<?php

class Ext_TS_System_Checks_Document_EntityColumn extends GlobalChecks {

	private $backup = false;

	public function getTitle() {
		return "Migrate document's database table for enquiry tool migration";
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$fields = DB::describeTable('kolumbus_inquiries_documents', true);

		// Columns neu sortieren
		if (isset($fields['changed_by_user_id'])) {
			$this->makeBackup();
			DB::executeQuery("
				ALTER TABLE `kolumbus_inquiries_documents`
					CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
					CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `id`,
					CHANGE `changed` `changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created`,
					CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `changed`,
					CHANGE `status` `status` ENUM('ready','pending','fail') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ready' AFTER `active`,
					CHANGE `creator_id` `creator_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `status`,
					CHANGE `changed_by_user_id` `editor_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `creator_id`,
					CHANGE `type` `type` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `editor_id`,
				    CHANGE `latest_version` `latest_version` INT NOT NULL COMMENT 'version_id' AFTER `type`,
					CHANGE `numberrange_id` `numberrange_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `latest_version`,
					CHANGE `document_number` `document_number` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `numberrange_id`,
					CHANGE `partial_invoice` `partial_invoice` TINYINT(1) NOT NULL DEFAULT '0' AFTER `document_number`,
					CHANGE `is_credit` `is_credit` TINYINT(1) NOT NULL DEFAULT '0' AFTER `partial_invoice`,
					CHANGE `released` `released` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `is_credit`,
					CHANGE `released_student_login` `released_student_login` TINYINT(1) NOT NULL DEFAULT '0' AFTER `released`
			");
		}

		// Neue Spalten für type/type_id anlegen
		if (!isset($fields['entity'])) {
			$this->makeBackup();
			DB::executeQuery("
				ALTER TABLE `kolumbus_inquiries_documents`
					ADD `entity` ENUM('Ext_TS_Inquiry','Ext_TS_Inquiry_Journey') NULL DEFAULT NULL AFTER `editor_id`,
					ADD `entity_id` INT UNSIGNED NULL DEFAULT NULL AFTER `entity`
			");
			DB::executeQuery("ALTER TABLE `kolumbus_inquiries_documents` ADD INDEX(`entity`, `entity_id`)");
		}

		// Inquiry-ID umschreiben auf neues Feld
		if (isset($fields['inquiry_id'])) {
			$this->makeBackup();
			DB::executeQuery("UPDATE `kolumbus_inquiries_documents` SET `entity` = 'Ext_TS_Inquiry', `entity_id` = `inquiry_id`, `changed` = `changed` WHERE `inquiry_id` > 0");
			// DB::executeQuery("ALTER TABLE `kolumbus_inquiries_documents` DROP `inquiry_id`");
			DB::executeQuery("ALTER TABLE `kolumbus_inquiries_documents` CHANGE `inquiry_id` `inquiry_id_` INT(11) NOT NULL DEFAULT '0'");
		}

		return true;

	}

	private function makeBackup() {
		if ($this->backup) {
			return;
		}
		Util::backupTable('kolumbus_inquiries_documents');
		$this->backup = true;
	}

}
