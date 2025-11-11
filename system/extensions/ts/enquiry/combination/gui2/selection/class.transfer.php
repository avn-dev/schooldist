<?php

class Ext_TS_Enquiry_Combination_Gui2_Selection_Transfer extends Ext_Gui2_View_Selection_Abstract {
	
	protected $_aSelectOptions = array();
	
	public function __construct($aSelectOptions) {
		$this->_aSelectOptions = $aSelectOptions;
	}
	
	/**
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array 
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$aReturn = $this->_aSelectOptions;
		
		if(
			$this->oJoinedObject
		) {		
			if(
				isset($aReturn[$this->oJoinedObject->start]) && 
				$this->oJoinedObject->start != 0
			) {		
				unset($aReturn[$this->oJoinedObject->start]);
			}		
		}
		return $aReturn;
	}
	
}
