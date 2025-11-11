<?php

class Ext_TC_Placeholder_Example extends Ext_TC_Basic {
	
	/**
	 * The DB table name
	 * 
	 * @var string
	 */
	protected $_sTable = 'tc_placeholder_examples';

	/**
	 * Alias der Tabelle (Optional)
	 * @var <string> 
	 */
	protected $_sTableAlias = 'tc_pe';
	
	/**
	 * Ein Array mit JoinedObjectChilds-Keys, welches von createCopy() gefüllt wird.
	 * 
	 * Der erste Key ist der Key des JoinedObjectChilds, 
	 * der ein Array enthält mit der alten ID als Schlüssel und der neuen ID als Wert.
	 * 
	 * array(
	 *		'<Joined Object Key>' => array(
	 *				'<alte ID>' => '<neue ID>',
	 *				'<alte ID>' => '<neue ID>'
	 *			)
	 * )
	 * 
	 * 
	 * @see self::createCopy()
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'entries' => array(
			'class' => 'Ext_TC_Placeholder_Example_Entry',
			'key' => 'example_id',
			'type' => 'child',
			'check_active' => true,
			'query' => false,
			'on_delete' => 'cascade',
			'orderby' => 'position',
	 		'orderby_type' => 'ASC',
		)
	);
	
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
		'tc_pe_i18n' => array(
			'table' => 'tc_placeholder_examples_i18n',
	 		'foreign_key_field'=> array('language_iso', 'name'),
	 		'primary_key_field'=> 'example_id',
			'i18n' => true
		)
	);
	
	/**
	 * Liste mit allen Beipielen
	 * @param string $sLanguage
	 * @return array 
	 */
	public static function getSelectOptions($sLanguage = ''){
		$oTemp = new self();
		$aList = $oTemp->getArrayListI18N(array('name'), true, $sLanguage);
		return $aList;
	}

}