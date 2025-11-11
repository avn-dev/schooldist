<?php

/**
 * @see Ext_TC_Basic::getArrayList()
 */
class Ext_TC_Country {
	
	/**
	 * Holt sich alle Länder für ein Select
	 *
	 * Sollte generell ersetzt werden mit \Core\Service\LocaleService::getCountries()!
	 *
	 * @deprecated
	 * @return array
	 */
	public static function getSelectOptions($sIso = '')
	{

		if(empty($sIso)){
			$sIso = Ext_TC_System::getInterfaceLanguage();
		}

		$sCacheKey = 'Ext_TC_Country::getSelectOptions';
		
		$aCache = WDCache::get($sCacheKey);

		if(!isset($aCache[$sIso])) {

			$aSql = array(
				'iso' => 'cn_short_'.$sIso
			);

			$sSql = "
				SELECT
					`cn_iso_2`, #iso
				FROM
					`data_countries`
				ORDER BY
					#iso
			";

			$aResult = DB::getQueryPairs($sSql, $aSql);

			$aCache[$sIso] = $aResult;

			WDCache::set($sCacheKey, 68400, $aCache);

		}

		return $aCache[$sIso];

	}
	
	public static function getCountryByIso($sISO)
	{
		
		$sCacheKey = 'Ext_TC_Country::getCountryByIso_' . $sISO;
		
		$aCountries = WDCache::get($sCacheKey);
		
		if($aCountries === null) {
			$aSql = array(
				'iso' => $sISO
			);

			$sSql = "
				SELECT
					*
				FROM
					`data_countries`
				WHERE
					`cn_iso_2` = :iso
			";
			
			$aCountries = DB::getQueryRow($sSql, $aSql);
			
			WDCache::set($sCacheKey, 86400, $aCountries);
		}
		
		return $aCountries;
	}
	
}
?>
