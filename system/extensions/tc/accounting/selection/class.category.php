<?php

/**
 *  Selection Klasse der Katgorien 
 *  Es dürfen nur die Kategorien zur Auswahl stehen, die der Art des Kontos entsprechen 
 */

class Ext_TC_Accounting_Selection_Category extends Ext_Gui2_View_Selection_Abstract {

	protected $_bForSelect;
	
	public function  __construct($bForSelect = false) {
		$this->_bForSelect = $bForSelect;
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic){
		
		$aReturn = $oWDBasic->getCategoriesByType();

		if($this->_bForSelect){
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}
		
		return $aReturn;
				
	}

}
