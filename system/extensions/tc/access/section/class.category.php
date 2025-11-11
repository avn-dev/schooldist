<?php

class Ext_TC_Access_Section_Category extends Ext_TC_Basic { 

	// Tabellenname
	protected $_sTable = 'tc_access_sections_categories';

	
	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent'
	 *			'check_active'=>true,
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'sections' => array(
			'class' => 'Ext_TC_Access_Section',
			'key' => 'category_id',
			'type' => 'child',
			'check_active' => true
		)
	);

	public function getArrayList($bForSelect = false, $sNameField = 'name', $bCheckValid = true, $bIgnorePosition = false) {
		
		$aReturn = parent::getArrayList($bForSelect, $sNameField, $bCheckValid, $bIgnorePosition);
		
		$aTypes = Ext_TC_Licence_Module::getTypes();
		
		foreach($aReturn as $iId=>&$sName) {
			$oSelf = self::getInstance($iId);
			$sName = $aTypes[$oSelf->type].' &raquo; '.$sName;
		}
		
		return $aReturn;
		
	}

}
