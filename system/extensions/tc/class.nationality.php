<?php

class Ext_TC_Nationality {
	
	/**
	 * Holt sich alle Länder für ein Select
	 * 
	 * @return array
	 */
	public static function getSelectOptions($sLang = ''){
		
		if(empty($sLang)) {
			$sLang = Ext_TC_System::getInterfaceLanguage();
		}
				
		$sSql = "SELECT
						`cn_iso_2`,
						`nationality_".$sLang."`
					FROM
						`data_countries`
					WHERE
						`nationality_".$sLang."` != ''
					ORDER BY
						`nationality_".$sLang."`
		";
		
		$aResult = DB::getQueryPairs($sSql);
		
		return $aResult;
		
	}
	
}
