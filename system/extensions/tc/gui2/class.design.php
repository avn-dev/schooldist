<?php

class Ext_TC_Gui2_Design extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_gui2_designs';
	
	protected static $aTabElementsCache = array();
	
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
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'tabs' => array(
			'class'=>'Ext_TC_Gui2_Design_Tab',
			'key'=>'design_id',
			'type'=>'child',
			'check_active'=>true,
			'orderby'=>'position'
		)
	);
	
	protected $_aJoinTables = array(
		'dialog_i18n' => array(
			'table' => 'tc_gui2_designs_dialog_i18n',
			'foreign_key_field' => array('language_iso', 'title_new', 'title_edit'),
			'primary_key_field' => 'design_id'
		)
		
	);
		
	
	/**
	 * get All Elements of the Dialog Tabs
	 * @return Ext_TC_Gui2_Design_Tab_Element[] 
	 */
	public function getTabElements(){

		$sCacheKey = 'Ext_TC_Gui2_Design::getTabElements'.$this->id;
		
		if(!isset(self::$aTabElementsCache[$sCacheKey])) {

			$sSql = " SELECT
							`tc_gdte_1`.`id`,
							`tc_gdte_1`.*
						FROM
							`tc_gui2_designs_tabs` `tc_gdt` INNER JOIN
							`tc_gui2_designs_tabs_elements` `tc_gdte_1` ON
								`tc_gdte_1`.`tab_id` = `tc_gdt`.`id`
						WHERE
							`tc_gdt`.`design_id` = :id AND
							`tc_gdt`.`active` = 1 AND
							`tc_gdte_1`.`active` = 1
						GROUP BY
							`tc_gdte_1`.`id`
					";
			$aSql = array('id' => (int)$this->id);
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			self::$aTabElementsCache[$sCacheKey] = array();

			foreach((array)$aResult as $aData){
				$oElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($aData['id']);
				self::$aTabElementsCache[$sCacheKey][$oElement->id] = $oElement;
			}

		}
		
		return self::$aTabElementsCache[$sCacheKey];
		
	}
	
	/**
	 * Find all Double Tab Elements
	 * @return Ext_TC_Gui2_Design_Tab_Element[]
	 */
	public function findDoubleTabElements(){

		$sSql = " SELECT
						`tc_gdte_1`.`id`
					FROM
						`tc_gui2_designs_tabs` `tc_gdt` INNER JOIN
						`tc_gui2_designs_tabs_elements` `tc_gdte_1` ON
							`tc_gdte_1`.`tab_id` = `tc_gdt`.`id` INNER JOIN
						`tc_gui2_designs_tabs_elements` `tc_gdte_2` ON
							`tc_gdte_2`.`special_type` = `tc_gdte_1`.`special_type` AND
							`tc_gdte_2`.`id` != `tc_gdte_1`.`id`
					WHERE
						`tc_gdt`.`design_id` = :id AND
						`tc_gdte_1`.`special_type` != '' AND
						`tc_gdt`.`active` = 1 AND
						`tc_gdte_1`.`active` = 1
					GROUP BY
						`tc_gdte_1`.`id`
				";
		$aSql = array('id' => (int)$this->id);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach((array)$aResult as $aData){
			$oElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($aData['id']);
			$aBack[$oElement->generateDesignerID()] = $oElement;
		}

		return $aBack;
	}
	
	/**
	 * get the Dialog title
	 * @param boolean $bNew
	 * @return string 
	 */
	public function getDialogTitle($bNew = false){
		
		$aData = $this->dialog_i18n;

		foreach((array)$aData as $aLanguage) {
			
			if($aLanguage['language_iso'] == Ext_TC_System::getInterfaceLanguage()) {
				if($bNew){
					$sName = $aLanguage['title_new'];
				} else {
					$sName = $aLanguage['title_edit'];
				}
				
			}
			
		}
		
		return $sName;
	}

	public function save($bLog = true) {

		//Ext_TC_Gui2_Designer_Session::reset($this->id);
		//WDCache::delete('Ext_TC_Gui2_Design::getTabElements');
		//WDCache::delete('Ext_TC_Gui2_Design::findDoubleTabElements');
		//WDCache::delete('Ext_TC_Gui2_Design::getFilterElements');

		return parent::save($bLog);
	}
	
	/**
	 * sucht das Gui-Design anhand der Section
	 * @param string $sSection
	 * @return int|boolean
	 */
	public static function searchBySection($sSection, $bFirstElement = true){
		
		$sSql = "SELECT 
					`id` 
				FROM 
					`tc_gui2_designs`
				WHERE 
					`section` = :section AND
					`active` = 1";
		$aSql = array('section' => $sSection);
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		if(empty($aResult)){
			return false;
		}

		$mReturn = $aResult;
		if($bFirstElement == true) {
			$mReturn = (int)$aResult[0]['id'];
		}
		
		return $mReturn;
	}

	/**
	 * liefert die SubObjects, mit denen dieses Design verknüpft ist
	 * @return array
	 */
	public function getSubObjects() {
		$aReturn = array();
		return $aReturn;
	}
	
}
?>
