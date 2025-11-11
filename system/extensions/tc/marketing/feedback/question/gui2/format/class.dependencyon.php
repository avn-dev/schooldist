<?php

class Ext_TC_Marketing_Feedback_Question_Gui2_Format_DependencyOn extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$sReturn = '';
		$aDependencyTypes = (array) Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Question', 'getDependencies');
		
		if(isset($aDependencyTypes[$mValue])) {
			$sReturn = $aDependencyTypes[$mValue];
		}

		return $sReturn;
	}
	
}
