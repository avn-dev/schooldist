<?php

/**
 * @param string $name
 */

class Ext_TC_Extrafield extends Ext_TC_Basic
{
	/** 
	 * The DB table name
	 * 
	 * @var string 
	 */
	protected $_sTable = 'tc_frontend_checkboxes';

	/**
	 * The DB table alias
	 * 
	 * @var string 
	 */
	protected $_sTableAlias = 'tc_fc';

	/**
	 * The format array
	 * 
	 * @var array 
	 */
	protected $_aFormat = array(
		'valid_until' => array(
			'format' => 'DATE'
		)
	);
	
	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC
	 *			'query' => false,
	 *			'cloneable' => true,
	 *			'on_delete' => 'cascade' / '' ( nur bei "childs" möglich )
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'content'=>array(
			'class'=>'Ext_TC_Frontend_Extrafield_Content',
			'key'=>'checkbox_id',
			'type'=>'child',
			'on_delete' => 'cascade'
		)
	);	
	
	/**
	 * Array mit verfügbaren Feldern für Select
	 * @param boolean $bForSelect
	 * @return array 
	 */
	
	public static function getFieldList($bForSelect = false){
		
		$aList = array(
			'input'		=> L10N::t('Einzeiliges Eingabefeld', Ext_TA_School::sTranslationPath),
			'html'		=> L10N::t('HTML-Eingabe', Ext_TA_School::sTranslationPath),
			'textarea'		=> L10N::t('Mehrzeiliges Eingabefeld', Ext_TA_School::sTranslationPath),	
		);
		
		if($bForSelect){
			$aList = Ext_TC_Util::addEmptyItem($aList);
		}
		
		return $aList;
		
	}
	
	/**
	 * get the Content Object for the Current Object
	 * @param int $iOffice
	 * @return Ext_TA_Office|boolean 
	 */
	public function getContent($iObject){
		return false;
	}
}
