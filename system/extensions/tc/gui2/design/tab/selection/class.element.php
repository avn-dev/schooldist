<?php

class Ext_TC_Gui2_Design_Tab_Selection_Element extends Ext_Gui2_View_Selection_Abstract {
	
	protected $_sOnlyType = '';


	public function __construct($sOnlyType = '') {
		$this->_sOnlyType = $sOnlyType;
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$sOnlyType = $this->_sOnlyType;
		
		$oTab = Ext_TC_Gui2_Design_Tab::getInstance($oWDBasic->tab_id);	

		$aElements = $oTab->getJoinedObjectChilds('elements');
		
		$aReturn = array( 0 => L10N::t('kein Element'));

		foreach ((array)$aElements as $oElement) { 

			if(
				$oElement->id != $oWDBasic->id &&
				(
					$sOnlyType == '' ||		
					$oElement->type == $sOnlyType
				)
			){
				$aReturn[$oElement->id] = $oElement->getName();
			}
		}

		return $aReturn;
		
	}
	
}