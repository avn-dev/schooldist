<?php

class Ext_TS_System_Checks_Accommodation_Provider_SchoolSettings extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Accommodation provider - settings per school';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Price settings are restructured to vary per school.';
		return $sDescription;
	}

	public function executeCheck() {
		
		$aCategoryTableFields = DB::describeTable('kolumbus_accommodations_categories', true);
		
		// Wenn das Feld nicht mehr da ist, wurde der Check komplett ausgeführt.
		if(!isset($aCategoryTableFields['price_night'])) {
			return true;
		}
		
		$aBackupTables = array(
			'kolumbus_accommodations_categories',
			'ts_accommodation_categories_schools'
		);
		
		foreach($aBackupTables as $sBackupTable) {
			$bBackup = Util::backupTable($sBackupTable);
			if(!$bBackup) {
				return false;
			}
		}
	
		DB::begin(__METHOD__);
		
		DB::executeQuery("CREATE TABLE `ts_accommodation_categories_settings` (`id` int(11) NOT NULL,`category_id` int(11) NOT NULL,`weeks` text NOT NULL,`price_night` tinyint(4) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		DB::executeQuery("ALTER TABLE `ts_accommodation_categories_settings` ADD PRIMARY KEY (`id`), ADD KEY `category_id` (`category_id`)");
		DB::executeQuery("ALTER TABLE `ts_accommodation_categories_settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
		
		DB::executeQuery("RENAME TABLE `ts_accommodation_categories_schools` TO `ts_accommodation_categories_settings_schools`");
		DB::executeQuery("ALTER TABLE `ts_accommodation_categories_settings_schools` CHANGE `accommodation_category_id` `setting_id` INT(11) NOT NULL");

		$categories = DB::getDefaultConnection()->getCollection("SELECT * FROM kolumbus_accommodations_categories");
		
		foreach($categories as $category) {
			$setting = [
				'id' => $category['id'],
				'category_id' => $category['id'],
				'weeks' => $category['weeks'],
				'price_night' => $category['price_night']
			];
			DB::insertData('ts_accommodation_categories_settings', $setting);
		}
	
		// Nicht mehr verwendete Spalten löschen
		DB::executeQuery("ALTER TABLE `kolumbus_accommodations_categories` DROP `weeks`");
		DB::executeQuery("ALTER TABLE `kolumbus_accommodations_categories` DROP `price_night`");

		DB::commit(__METHOD__);
		
		return true;
	}

}
