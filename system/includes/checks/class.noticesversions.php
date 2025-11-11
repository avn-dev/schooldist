<?php

class Checks_NoticesVersions extends GlobalChecks {

	public function executeCheck() {

		$tables = \DB::listTables();
		
		// Check ist schon durchgelaufen, wenn Tabelle existiert.
		if(in_array('notices_versions', $tables)) {
			return true;
		}
		
		$bBackup = Util::backupTable('notices');

		if(!$bBackup) {
			__pout('Backup failed!');
			return false;
		}

		$statements = [];

		$statements[] = "CREATE TABLE `notices_versions` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `creator_id` INT(11) NOT NULL DEFAULT '0' , `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `notice` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL , `notice_id` INT(11) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
		$statements[] = "ALTER TABLE `notices_versions` ADD INDEX `notice_id` (`notice_id`)";
		$statements[] = "ALTER TABLE `notices` CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
		$statements[] = "ALTER TABLE `notices` ADD `latest_version_id` INT(11) NOT NULL AFTER `notice`";
		$statements[] = "ALTER TABLE `notices` ADD INDEX `latest_version_id` (`latest_version_id`)";
		$statements[] = "INSERT INTO `notices_versions`(`created`, `creator_id`, `notice`, `notice_id`) SELECT `created`, `creator_id`, `notice`, `id`  FROM `notices`";
		$statements[] = "UPDATE `notices` INNER JOIN `notices_versions` ON `notices_versions`.`notice_id` = `notices`.`id` SET `latest_version_id` = `notices_versions`.`id`";
		$statements[] = "ALTER TABLE `notices` DROP `notice`";

		foreach($statements as $statement) {
			DB::executeQuery($statement);
		}

		return true;
	}

	public function getTitle() {
		return 'Update and change tables for notices';
	}

	public function getDescription() {
		return 'Changes for new features.';
	}

}
