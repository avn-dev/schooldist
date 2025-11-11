<?php

class Ext_TC_Placeholder_Example_Entry extends Ext_TC_Basic {
	
	/**
	 * The DB table name
	 * 
	 * @var string
	 */
	protected $_sTable = 'tc_placeholder_examples_entries';

	/**
	 * Alias der Tabelle (Optional)
	 * @var <string> 
	 */
	protected $_sTableAlias = 'tc_pee';

	
	/**
	 * Eine Liste mit Verknüpfungen (1-n)
	 *
	 * array(
	 *		'items'=>array(
	 *				'table'=>'',
	 *				'foreign_key_field'=>'',
	 *				'primary_key_field'=>'id',
	 *				'sort_column'=>'',
	 *				'class'=>'', // funktioniert nut wenn bei foreign_key_field ein String angegeben ist mit dem Feldname der die ID der angegebenen Klasse enthält
	 *				'autoload'=>true,
	 *				'check_active'=>true,
	 *				'delete_check'=>false,
	 *				'cloneable' => true,
	 *				'static_key_fields'=>array(),
	 *				'join_operator' => 'LEFT OUTER JOIN' // aktuell nur bei getListQueryData,
	 *				'i18n' => false // hierbei wird pro Sprache ein Join erzeugt im Query per getListQuery Data
	 *			)
	 * )
	 *
	 * foreign_key_field kann auch ein Array sein
	 *
	 * @var <array>
	 */
	protected $_aJoinTables = array(
		'tc_pee_i18n' => array(
			'table' => 'tc_placeholder_examples_entries_i18n',
	 		'foreign_key_field'=> array('language_iso', 'name', 'description'),
	 		'primary_key_field'=> 'example_entry_id',
			'i18n' => true
		),
		'pdf_applications' => array(
			'table' => 'tc_placeholder_examples_entries_to_applications',
	 		'foreign_key_field'=> 'application',
	 		'primary_key_field'=> 'example_entry_id',
			'static_key_fields'=> array('type' => 'pdf'),
		),
		'email_applications' => array(
			'table' => 'tc_placeholder_examples_entries_to_applications',
	 		'foreign_key_field'=> 'application',
	 		'primary_key_field'=> 'example_entry_id',
			'static_key_fields'=> array('type' => 'email'),
		)
	);
	
	/**
	 * Liste mit allen Einträgen
	 * @param string $sLanguage
	 * @return array 
	 */
	public static function getSelectOptions($sLanguage = ''){
		$oTemp = new self();
		$aList = $oTemp->getArrayListI18N(array('name'), true, $sLanguage);
		return $aList;
	}

	/**
	 * PDF Applications
	 * @param bool $bForSelect
	 * @return array 
	 */
	public static function getPdfApplications($bForSelect = false){		
		$aReturn = array();		
		$aReturn['test1'] = 'Test 1';
		$aReturn['test2'] = 'Test 2';
		return $aReturn;		
	}
	
	/**
	 * Communication Applications
	 * @param bool $bForSelect
	 * @return array 
	 */
	public static function getEmailApplications($bForSelect = false){		
		$aReturn = array();	
		$aReturn['test1'] = 'Test 1';
		$aReturn['test2'] = 'Test 2';
		return $aReturn;		
	}
	
	/**
	 * gibt den I18N-Namen zurück
	 * @param string $sLanguage
	 * @return string 
	 */
	public function getName($sLanguage = '') {
		
		$sReturn = $this->getI18NName('tc_pee_i18n', 'name', $sLanguage);
		return $sReturn;
		
	}
	
	/**
	 * gibt die I18N-Beschreibung zurück
	 * @param string $sLanguage
	 * @return string 
	 */
	public function getDescription($sLanguage = '') {
		
		$sReturn = $this->getI18NName('tc_pee_i18n', 'description', $sLanguage);
		return $sReturn;
		
	}
	
}