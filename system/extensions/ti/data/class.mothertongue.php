<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TI_Data_Mothertongue extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'data_countries';

	// Tabellenalias
	protected $_sTableAlias = 'dc';

	protected $_sEditorIdColumn = 'user_id';

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {
		global $system_data;

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sTableAlias.".*,
					`kls`.#language_field `mothertongue`
					{FORMAT}
				FROM
					`{TABLE}` ".$sTableAlias." LEFT OUTER JOIN
					`data_languages` `kls` ON
						".$sTableAlias.".`mothertonge_id` = `kls`.`iso_639_1`
				WHERE
					#label_field != ''
			";

		$aQueryData['data']['label_field'] = 'nationality_'.$system_data['systemlanguage'];
		$aQueryData['data']['language_field'] = 'name_'.$system_data['systemlanguage'];

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

}
