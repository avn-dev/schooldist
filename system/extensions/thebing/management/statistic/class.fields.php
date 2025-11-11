<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Mehmet Durmaz
 */
class Ext_Thebing_Management_Statistic_Fields extends Ext_Thebing_Basic{

	// Tabellenname
	protected $_sTable			= 'kolumbus_statistic_cols_definitions';

	// joined table
	protected $_sTableCategory	= 'kolumbus_statistic_cols_groups';

	// Alias kolumbus_statistic_cols_definitions
	protected $_sTableAlias = 'kstcd';

	// Alias kolumbus_statistic_cols_groups
	protected $_sTableAliasCategory = 'kstcg';

	protected $_aJoinTables = array(
		'period'=>array(
		'table'=>'kolumbus_statistic_cols_definition_options',
		'foreign_key_field'=>'period_id',
	 	'primary_key_field'=>'definition_id'
		)
	);

	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		$sTableAliasCategory = '';

		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}
		if(!empty($this->_sTableAliasCategory)) {
			//$sTableAliasCategory .= '`'.$this->_sTableAliasCategory.'`.';
			$sTableAliasCategory .= '`'.$this->_sTableAliasCategory.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sTableAlias.".*,".$sTableAliasCategory.".`title` as category {FORMAT}
				FROM
					`{TABLE}` ".$sTableAlias." LEFT OUTER JOIN
					`{TABLE_CATEGORY}` ".$sTableAliasCategory." ON
						".$sTableAliasCategory.".`id` = ".$sTableAlias.".`group_id`
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE_CATEGORY}', $this->_sTableCategory, $aQueryData['sql']);

		return $aQueryData;
	}
}
?>
