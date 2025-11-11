<?php

class Ext_TC_Gui2_Design_Tab extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_gui2_designs_tabs';
	
	protected $bReadonly = false;
	
	protected $bDisabledByReadonly = true;
	
	protected $_aFormat = array(
		'design_id' => array(
			'validate' => 'INT_NOTNEGATIVE'
		)
	);

	protected $_aJoinTables = array(
		'i18n' => array(
			'table' => 'tc_gui2_designs_tabs_i18n',
			'foreign_key_field' => array('language_iso', 'name'),
			'primary_key_field' => 'tab_id'
		)
		
	);
	
	protected $_aJoinedObjects = array(
		'elements' => array(
			'class' => 'Ext_TC_Gui2_Design_Tab_Element',
			'key' => 'tab_id',
			'type' => 'child',
			'check_active' => true,
			'orderby' => 'position',
			// Standardmäßig nicht klonen, da die Elemente durch die parent_element_id noch untereinander verknüpft sind
			'cloneable' => false
		)
	);

	public function __set($sName, $mValue) {
		
		if(strpos($sName, 'name_') === 0) {
			
			$sLanguage = str_replace('name_', '', $sName);
			$this->setI18NName($mValue, $sLanguage);
			
		} else {
			parent::__set($sName, $mValue);
		}
		
	}

	public function setReadonly($bReadonly) {
		$this->bReadonly = (bool) $bReadonly;
	}
	
	public function setDisabledByReadonly($bDisabledByReadonly) {
		$this->bDisabledByReadonly = (bool) $bDisabledByReadonly;
	}
	
	/**
	 *
	 * @return Ext_TC_Gui2_Design
	 */
	public function getDesign(){
		$oDesign = Ext_TC_Gui2_Design::getInstance($this->design_id);
		return $oDesign;
	}
	
	/**
	 * get all MAIN Elements, Elements without parent_element_id
	 * @return Ext_TC_Gui2_Design_Tab_Element[]
	 */
	public function getMainElements(){

		$aMainElements = array();

		if($this->exist()){

			$aElements = $this->getJoinedObjectChilds('elements');

			foreach((array)$aElements as $oElement){
				$oElement->readonly = $this->bReadonly;
				$oElement->disabled = $this->bDisabledByReadonly;

				if($oElement->parent_element_id <= 0) {
					$aMainElements[] = $oElement;
				}
			}
		}

		return $aMainElements;
	}
	
	/**
	 * Delete and delete all Child Elements (joined Object)
	 */
	public function delete() {
		$bSuccess = parent::delete();
		
		$aElements = $this->getJoinedObjectChilds('elements');
		
		foreach((array)$aElements as $oChild){
			$oChild->delete();
		}
		
		return $bSuccess;
		
	}
	
	/**
	 * get the corrent i18n Name for the Ext_TC_System::getInterfaceLanguage()
	 * @return type 
	 */
	public function getName(){
		
		$aData = $this->i18n;

		foreach((array)$aData as $aLanguage) {
			
			if($aLanguage['language_iso'] == Ext_TC_System::getInterfaceLanguage()) {
				$sName = $aLanguage['name'];
			}
			
		}
		
		return $sName;
	}

	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		if ($oClone->exist()) {
			/* @var \Ext_TC_Gui2_Design_Tab_Element[] $oRootElements */
			$oRootElements = collect($this->getJoinedObjectChilds('elements'))->filter(function (\Ext_TC_Gui2_Design_Tab_Element $oElement) {
				return ((int)$oElement->parent_element_id === 0);
			});

			foreach ($oRootElements as $oElement) {
				$oElement->createCopy('tab_id', $oClone->getId(), $aOptions);
			}
		}

		return $oClone;

	}

}
