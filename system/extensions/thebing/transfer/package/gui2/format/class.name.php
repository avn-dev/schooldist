<?php

class Ext_Thebing_Transfer_Package_Gui2_Format_Name extends Ext_Gui2_View_Format_Abstract {
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {
		
		$aReturn = array();

		$aReturn['path']	= '/thebing/transfer/package/gui2/details';
		
		$aReturn['values'] = array(
			'id'	=> $aResultData['id']
		);
		 
		$aReturn['tooltip'] = 'wdmvc';
		
		return $aReturn;
		
	}

}
