<?php

class Ext_TS_System_Checks_School_AppSettings extends GlobalChecks {

	public function getTitle() {
		return 'Migrate school app settings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$this->migratePageSettings();
		$this->migrateWelcomeTexts();

		return true;

	}

	/**
	 * »Verfügbare Seiten« migrieren (ts_schools_app_settings)
	 */
	private function migratePageSettings() {

		if(!self::checkAndBackupTable('ts_app_pages_enabled')) {
			return;
		}

		DB::begin(__CLASS__);

		$aPages = (array)DB::getQueryRows("
			SELECT
				*
			FROM
				`ts_app_pages_enabled`
		");

		foreach($aPages as $aPage) {

			DB::executePreparedQuery("
				REPLACE INTO
					`ts_schools_app_settings`
				SET
					`school_id` = :school_id,
					`key` = 'enabled_page',
					`additional` = :page,
					`value` = 1
			", [
				'school_id' => $aPage['school_id'],
				'page' => $aPage['page']
			]);

		}

		DB::commit(__CLASS__);

		DB::executeQuery(" DROP TABLE `ts_app_pages_enabled` ");

	}

	/**
	 * Willkommenstexte migrieren (ts_schools_i18n)
	 */
	private function migrateWelcomeTexts() {

		if(!self::checkAndBackupTable('ts_schools_i18n')) {
			return;
		}

		DB::begin(__CLASS__);

		$aTexts = (array)DB::getQueryRows("
			SELECT
				*
			FROM
				`ts_schools_i18n`
		");

		foreach($aTexts as $aText) {
			foreach(['student', 'teacher'] as $sType) {

				$sText = $aText['app_welcome_'.$sType];
				if(empty($sText)) {
					// Leere Texte überspringen
					continue;
				}

				DB::executePreparedQuery("
					REPLACE INTO
						`ts_schools_app_settings`
					SET
						`school_id` = :school_id,
						`key` = :key,
						`additional` = :additional,
						`value` = :text
				", [
					'school_id' => $aText['school_id'],
					'key' => 'welcome_text_'.$sType,
					'additional' => $aText['language_iso'],
					'text' => $sText
				]);

			}
		}

		DB::commit(__CLASS__);

		DB::executeQuery(" DROP TABLE `ts_schools_i18n` ");

	}

	private function checkAndBackupTable($sTable) {

		try {
			DB::describeTable($sTable, true);
			Util::backupTable($sTable);
			return true;
		} catch(DB_QueryFailedException $e) {
			// Tabellen existieren nicht mehr, adher
			return false;
		}

	}

}
