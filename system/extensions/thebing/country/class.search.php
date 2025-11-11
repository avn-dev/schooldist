<?php

/**
 * @deprecated
 */
class Ext_Thebing_Country_Search {

	// Cache
	public static $aList = null;
	
	public static $aSelectList = array();
	
	public static function search($sSearch=false, $sSearchField='cn_short_') {

		if(
			self::$aList === null
		){
			
			$aCountryList = (array)WDCache::get('country_list');
			
			if(
				empty($aCountryList)
			){
				$sWhere = "";
		
				if($sSearch !== false) {
					$sWhere .= "
							AND (
										`".$sSearchField."de` LIKE '%".DB::escapeQueryString($sSearch)."%'
									OR
										`".$sSearchField."en` LIKE '%".DB::escapeQueryString($sSearch)."%'
									OR
										`".$sSearchField."es` LIKE '%".DB::escapeQueryString($sSearch)."%'
							)
					";
				}

				$sQuery = "	
							SELECT 
								*
							FROM
								`data_countries`
							WHERE
								1
								".$sWhere."
							ORDER BY
								`cn_iso_2` ASC
							";

				$aCountryList = DB::getQueryData($sQuery);
				
				self::$aList = $aCountryList;
				
				WDCache::set('country_list', 86400, $aCountryList);
			}else{
				self::$aList = $aCountryList;
			}
			
		}else{
			$aCountryList = self::$aList;
		}

		return $aCountryList;

	}

	/**
	 * Kann nur im Backend verwendet werden
	 *
	 * @deprecated
	 */
	public static function getLocalizedCountries($sLanguage=false, $sSearch=false) {
			
		if(
			$sLanguage === false || 
			empty($sLanguage)
		) {
			$sLanguage = System::getInterfaceLanguage();
		}
		$sLanguage = substr($sLanguage, 0, 2);
		$sKey = 'localized_contries_' . $sLanguage . '_' . (string)$sSearch;
		
		if(
			isset(self::$aSelectList[$sKey])
		){
			return self::$aSelectList[$sKey];
		}
		
		$aCache = WDCache::get($sKey);

		// Auf empty prüfen, da manchmal ein leeres Array im Cache landet
		if(
			empty($aCache)
		) {
			
			$aCountries		= array();

			/**
			 * @todo Hier muss geschaut werden, warum hier manchmal ein leeres 
			 * Array zurückgegeben wird
			 */
			$aCountryData	= self::search($sSearch);

			foreach ((array)$aCountryData as $aCountry) {
				$aCountries[$aCountry['cn_iso_2']] = $aCountry['cn_short_'.$sLanguage];
			}

			self::$aSelectList[$sKey] = $aCountries;
			
			WDCache::set($sKey, 86400, $aCountries);
			
		}else{
			
			$aCountries = $aCache;
			
			self::$aSelectList[$sKey] = $aCache;
		}

		asort($aCountries);

		return $aCountries;
		
	}

	/**
	 * Zend-Locale-Länder abgleichen mit data_countries, da es ansonsten Deleted Entry zwischen Frontend/Backend geben kann
	 *
	 * @param $sLanguage
	 * @return array
	 */
	public static function getCountriesForFrontend($sLanguage): array {

		$aAvailableIsoCodes = DB::getQueryCol("SELECT cn_iso_2 FROM data_countries");

		$oLocaleService = new Core\Service\LocaleService();
		$aOptions = $oLocaleService->getCountries($sLanguage);

		$aOptions = array_filter($aOptions, function ($sIso) use ($aAvailableIsoCodes) {
			return in_array($sIso, $aAvailableIsoCodes);
		}, ARRAY_FILTER_USE_KEY);

		return $aOptions;

	}

}