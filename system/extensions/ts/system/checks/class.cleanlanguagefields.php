<?php

use Illuminate\Support\Str;

/**
 * Löscht die sprachabhängigen Spalten, die komplett leer sind und deren Sprachen nicht mehr als Frontend-Sprachen gesetzt sind
 *
 * https://redmine.fidelo.com/issues/15354
 */
class Ext_TS_System_Checks_CleanLanguageFields extends GlobalChecks {

	public function getTitle() {
		return 'Clean Language Fields Check';
	}

	public function getDescription() {
		return 'Clean unneeded language fields (i.e. which are empty and whose language is not set anymore)';
	}

	public function executeCheck() {

		$aFields = (new \Core\Helper\Bundle())->readBundleFile('Ts', 'db')['language_fields'];

		// Array mit [<table> => [<columns>]
		$aColumnsToDelete = [];

		foreach($aFields as $aField) {
			[$sTable, $sField] = $aField;
			// Überprüfen, ob es zu diesem Feld Spalten zu löschen gibt, und diese ins $aColumnsToDelete setzen
			$this->checkColumns($sTable, $sField, $aColumnsToDelete);
		}

		foreach($aColumnsToDelete as $sTable => $aColumns) {
			$this->dropColumns($sTable, $aColumns);
		}

		return true;

	}

	/**
	 * Checkt ob die Spalten mit dem übergebenen Feld(Präfix) übereinstimmen und ob die Sprache nicht mehr gesetzt ist.
	 * Außerdem füllt die gegebenenfalls das $aColumnsToDelete mit den Spalten, die gelöscht werden müssen.
	 *
	 * @param string $sTable
	 * @param string $sField
	 * @param array $aColumnsToDelete
	 */
	private function checkColumns($sTable, $sField, array &$aColumnsToDelete) {

		$aLanguages = (array)Ext_TS_Config::getInstance()->frontend_languages;
		$aLocales = (new Core\Service\LocaleService())->getInstalledLocales();

		// Namen der Tabellenspalten zusammenstellen
		$aTableColumns = array_keys(DB::describeTable($sTable, true));

		foreach($aTableColumns as $sColumn) {

			// Überprüfen, ob der Spaltenname den Feld-Präfix enthält. Wenn nicht, dann weiter zur nächsten Spalte
			if(
				!empty($sField) && strpos($sColumn, $sField.'_') === false || (
					// system_translations hat keinen Präfix
					empty($sField) && !isset($aLocales[$sColumn])
				)
			) {
				continue;
			}

			// Spalten können Unterstriche haben und Sprachen können auch Unterstriche haben (z.B. family_description_ksh_DE)
			$sLanguage = Str::after($sColumn, $sField.'_');

			// Checken, ob die Sprache der Spalte immer noch zu den Frontend-Sprachen gehört oder der Sonderfall "name_short" zutrifft.
			if(
				$sLanguage === 'short' ||
				in_array($sLanguage, $aLanguages)
			) {
				continue;
			}

			$aValues = $this->getColumnValues($sTable, $sColumn);

			// Wenn es Werte unter dieser Spalte gibt, kann diese nicht gelöscht werden, daher weiter zur Nächsten
			if(!empty($aValues)) {
				continue;
			}

			// Die Spalten unter ihre Tabelle hinzufügen
			if(!array_key_exists($sTable, $aColumnsToDelete)) {
				$aColumnsToDelete[$sTable] = [];
			}

			$aColumnsToDelete[$sTable][] = $sColumn;

		}

	}

	/**
	 * Die existierenden Werte der Spalte holen
	 *
	 * @param string $sTable
	 * @param string $sColumn
	 *
	 * @return array|null
	 */
	private function getColumnValues($sTable, $sColumn) {

		$sSql = "
			SELECT
				`{$sColumn}`
			FROM
				`{$sTable}`
			WHERE 
				`{$sColumn}` IS NOT NULL AND
				`{$sColumn}` != ''
		";

		return DB::getQueryRows($sSql);
	}

	/**
	 * Alle unnötigen sprachabhöngigen Spalten einer Tabelle entfernen
	 *
	 * @param $sTable
	 * @param $aColumns
	 */
	private function dropColumns($sTable, $aColumns) {

		Util::backupTable($sTable);

//		$sDrop = collect($aColumns)->map(function ($sColumn) {
//			return 'DROP `'.$sColumn.'`';
//		})->implode(',');
//
//		$sSql = "
//			ALTER TABLE
//				`{$sTable}`
//			{$sDrop}
//		";
//
//		DB::executeQuery($sSql);

		foreach ($aColumns as $sColumn) {
			try {
				DB::executeQuery("ALTER TABLE `$sTable` DROP `$sColumn`");
			} catch (DB_QueryFailedException) {

			}
		}

		WDCache::delete('wdbasic_table_description_'.$sTable);
		WDCache::delete('db_table_description_'.$sTable);

		$this->logInfo('Dropped columns of table '.$sTable.': '.implode(' ,', $aColumns));

	}

}