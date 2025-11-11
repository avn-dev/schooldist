<?php

class Ext_TC_State {
	
	/**
	 * Holt sich alle Länder für ein Select
	 * 
	 * @return array
	 */
	public static function getSelectOptions($sIso_639_1 = '')
	{
		
		if(empty($sIso_639_1)){
			$sIso_639_1 = Ext_TC_System::getInterfaceLanguage();
		}
		
		$sSql = " SELECT * FROM `tc_states` ";
		$aResult = DB::getQueryData($sSql);
		
		$aReturn = array();
		
		foreach($aResult as $aLanguage) {
			//$aReturn[$aLanguage['iso']] = $aLanguage['cn_short_'.$sIso_639_1];
		}
		
		return $aReturn;
		
	}
	
}
?>
