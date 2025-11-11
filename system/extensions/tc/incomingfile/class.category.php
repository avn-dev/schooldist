<?php

/**
 * Kategorien der »eingehenden Dokumente« (virtuelle Dokumente)
 */
class Ext_TC_IncomingFile_Category extends Ext_TC_Basic {

	protected $_sTable = 'tc_incomingfiles_categories';
	
	protected $_sTableAlias = 'tc_idc';
	
	protected $_aJoinTables = array(
		'tc_idc_i18n' => array(
			'table' => 'tc_incomingfiles_categories_i18n',
			'foreign_key_field'=> array('language_iso', 'name'),
			'primary_key_field' => 'category_id'
		)
	);
		
	public static function getSelectOptions()
	{
		$oSelf = new self;
		$aList = $oSelf->getArrayListI18N(array('name'));
		return $aList;
	}
	
}