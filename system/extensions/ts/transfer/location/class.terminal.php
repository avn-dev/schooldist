<?php

class Ext_TS_Transfer_Location_Terminal extends Ext_TC_Basic {

//	use Ext_TS_Transfer_Location_Trait;

	protected $_sTable = 'ts_transfer_locations_terminals';

	protected $_sTableAlias = 'ts_tlt';

	//protected $_sEditorIdColumn = 'editor_id';

	protected $_aJoinTables = [
		'i18n' => [
			'table' => 'ts_transfer_locations_terminals_i18n',
	 		'foreign_key_field' => ['language_iso', 'name'],
	 		'primary_key_field'	=> 'location_terminal_id',
			'autoload' => true
		]
	];

	/**
	 * @param string $sLanguage
	 * @return string
	 */
	public function getName($sLanguage = null) {
		return $this->getI18NName('i18n', 'name', $sLanguage);
	}

	/**
	 * Alle Terminals gruppiert nach Locations
	 *
	 * @return array
	 */
	public static function getGroupedTerminals() {

		$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		$sLanguage = $oSchool->getInterfaceLanguage($sLanguage);

		$sSql = "
			SELECT
				`ts_tlt`.`id`,
				`ts_tlt`.`location_id`,
				`ts_tlt_i18n`.`name`
			FROM
				`ts_transfer_locations_terminals` `ts_tlt` INNER JOIN
				`ts_transfer_locations` `ts_tl` ON
					`ts_tl`.`id` = `ts_tlt`.`location_id` AND
					`ts_tl`.`active` = 1 LEFT JOIN
				`ts_transfer_locations_terminals_i18n` `ts_tlt_i18n` ON
					`ts_tlt_i18n`.`location_terminal_id` = `ts_tlt`.`id` AND
					`ts_tlt_i18n`.`language_iso` = '{$sLanguage}'
			WHERE
				`ts_tlt`.`active` = 1
		";

		$aTerminals = (array)DB::getQueryRows($sSql);

		$aReturn = [];
		foreach($aTerminals as $aTerminal) {
			$aReturn['location_'.$aTerminal['location_id']][$aTerminal['id']] = $aTerminal['name'];
		}

		return $aReturn;

	}

}
