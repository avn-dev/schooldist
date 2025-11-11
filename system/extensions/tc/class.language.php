<?php

/**
 * @see Ext_TC_Basic::getArrayList()
 */
class Ext_TC_Language {
	
	protected static $_aSelectOptionCache = array();
	protected static $_aListCache = null;

	/**
	 * Holt sich alle Sprachen für ein Select
	 * 
	 * @return array
	 */
	public static function getSelectOptions($sIso = '') {

		$aCache = WDCache::get('Ext_TC_Language::getSelectOptions');

		if(empty($sIso)){
			$sIso = Ext_TC_System::getInterfaceLanguage();
		}

		if(!isset($aCache[$sIso])){

			$sSql = " SELECT * FROM `data_languages` ";
			$aResult = DB::getQueryData($sSql);

			$aReturn = array();

			// data_languages dient als Filter, da es hunderte Sprachen mehr in den Locales gibt
			$oLocaleService = new Core\Service\LocaleService();
			$aLocales = $oLocaleService->getInstalledLocales($sIso);

			foreach($aResult as $aLanguage) {

				if(empty($aLanguage['name_'.$sIso])) {

					// Schauen, ob es die Sprache in den Locales gibt, bevor der Fallback Englisch greift
					if(!empty($aLocales[$aLanguage['iso_639_1']])) {
						$sLanguageName = $aLocales[$aLanguage['iso_639_1']];
					} else {
						$sLanguageName = $aLanguage['name_en'];
					}

				} else {
					$sLanguageName = $aLanguage['name_'.$sIso];
				}

				$aReturn[$aLanguage['iso_639_1']] = $sLanguageName;
			}

			/*
			 * TODO Einbauen, wenn man sicher sein kann, dass bei asiatischen Sprachen alles übersetzt ist
			 * Ansonsten würden hier die englischen Fallback-Übersetzungen oben stehen und das betrifft dann nur komische Sprachen
			 * -> Hab es wieder aktiviert. Falls das Problem bei asiatischen und anderen unvollständigen Sprachen auftritt, müssen wir die Übersetzungen manuell ergänzen.
			 */
			asort($aReturn);

			$aCache[$sIso] = $aReturn;
			WDCache::set('Ext_TC_Language::getSelectOptions', 86400, $aCache);
		}

		return $aCache[$sIso];
		
	}
	
	public static function getList(){
		
		if(self::$_aListCache == null){

			$sSql = " SELECT * FROM `data_languages` ";
			$aResult = DB::getQueryData($sSql);

			self::$_aListCache = $aResult;
		}

		return self::$_aListCache;
		
	}
	
}
