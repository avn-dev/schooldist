<?php

class Checks_LogStructure extends GlobalChecks {
	
	public function getTitle() {
		return 'Update log structure';
	}
	
	public function getDescription() {
		return '';
	}

	/**
	 * Die Queries sind hier, weil es sonst zu Fehlern beim Update kommt, da alter Code mit neuer Struktur zusammen kommt
	 * @return boolean
	 */
	public function executeCheck() {

        $columnsExists = \DB::getDefaultConnection()->checkField('system_logs', 'changed', true);
        if(!$columnsExists) {
            // Check wurde bereits ausgeführt
            return true;
        }

		Util::backupTable('system_logs');

		DB::executeQuery("ALTER TABLE `system_logs` DROP `changed`");
		DB::executeQuery("UPDATE `system_logs` SET element_id = page_id, elementname = '\\Cms\\Entity\\Page', page_id = 0 WHERE page_id > 0 AND element_id = 0");
		DB::executeQuery("ALTER TABLE `system_logs` DROP `page_id`");
		DB::executeQuery("ALTER TABLE `system_logs` CHANGE `elementname` `elementname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
		DB::executeQuery("ALTER TABLE `system_logs` CHANGE `element_id` `element_id` INT(11) NULL DEFAULT NULL");
		DB::executeQuery("ALTER TABLE `system_logs` DROP `tablename`, DROP `fieldname`, DROP `content`, DROP `active`");
		DB::executeQuery("ALTER TABLE `system_logs` ADD `additional` MEDIUMTEXT NULL DEFAULT NULL AFTER `elementname`");
		DB::executeQuery("ALTER TABLE `system_logs` ADD INDEX `element` ( `element_id`, `elementname`)");
		DB::executeQuery("ALTER TABLE `system_logs` ADD INDEX `code` (`code`)");
		DB::executeQuery("ALTER TABLE `system_logs` DROP `time`");
		DB::executeQuery("ALTER TABLE `system_logs` CHANGE `code` `code` VARCHAR(100) NULL DEFAULT NULL");
		DB::executeQuery("UPDATE `system_logs` SET `code` = NULL WHERE `code` = '0'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = `action` WHERE `code` IS NULL");
		DB::executeQuery("ALTER TABLE `system_logs` DROP `action`");
		DB::executeQuery("DROP TABLE language_messages");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'admin/login-failed' WHERE `code` = '40101'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'admin/login-successful' WHERE `code` = '20500'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/stats-updated' WHERE `code` = '20109'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/category-created' WHERE `code` = '20201'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/page-created' WHERE `code` = '20202'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/page-copy' WHERE `code` = '20203'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/template-created' WHERE `code` = '20204'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-languages-updated' WHERE `code` = '20301'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-language-deleted' WHERE `code` = '20302'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-language-position' WHERE `code` = '20303'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-init' WHERE `code` = '20304'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-updated' WHERE `code` = '20311'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-deleted' WHERE `code` = '20312'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/sites-position' WHERE `code` = '20313'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/page-published' WHERE `code` = '20205'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/block-updated' WHERE `code` = '20206'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/block-deleted' WHERE `code` = '20207'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/block-position' WHERE `code` = '20208'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/page-properties' WHERE `code` = '20209'");
		DB::executeQuery("UPDATE `system_logs` SET `code` = 'cms/category-properties' WHERE `code` = '20210'");
		
		return true;
	}
	
}
