<?php

class Ext_TC_Address_Label extends Ext_TC_Basic {

	protected $_sTable = 'tc_addresslabels';

	protected $_aJoinTables = array(
		'i18n'		=> array(
			'table'				=> 'tc_addresslabels_i18n',
	 		'foreign_key_field'	=> array('language_iso', 'name'),
	 		'primary_key_field'	=> 'label_id'
		),
		'objects'	=> array(
			'table'				=> 'tc_addresslabels_to_objects',
	 		'foreign_key_field'	=> 'object_id',
	 		'primary_key_field'	=> 'label_id'
		),
		'fields'	=> array(
			'table'				=> 'tc_addresslabels_fields',
	 		'foreign_key_field'	=> array('field', 'display', 'type', 'position'),
	 		'primary_key_field'	=> 'label_id',
			'sort_column'		=> 'position'
		)
	);

	public static function getSelectOptions(){ 
		$oTemp = new self();
		$aList = $oTemp->getArrayListI18N(array('name'), true);
		return $aList;
	}

	public function getName($sLang = '')
	{
		$sName = $this->getI18NName('i18n', 'name', $sLang);
		return $sName;
	}

}
?>
