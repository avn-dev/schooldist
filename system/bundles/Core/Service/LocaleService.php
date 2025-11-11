<?php

namespace Core\Service;

class LocaleService {
	
	public function __construct() {
		
		$bMemcache = \WDCache::isMemcacheConnected();

		if($bMemcache === true) {
			$oZendCache = $this->getMemcachedZendCache();
		} else {
			$oZendCache = $this->getFileZendCache();
		}

		\Zend_Locale::setCache($oZendCache);

	}

	private function getZendCacheFrontendOptions() {
		
		$aFrontendOptions = array(
			'lifetime' => 24*60*60,
			'automatic_serialization' => true
		);
 
		return $aFrontendOptions;
	}
	
	private function getMemcachedZendCache() {

		$aBackendOptions = array();
 
		$oCacheBackend = new ZendCache;
		
		$oZendCache = \Zend_Cache::factory(
			'Core',
            $oCacheBackend,
            $this->getZendCacheFrontendOptions(),
            $aBackendOptions
		);
		
		return $oZendCache;
	}

	private function getFileZendCache() {

		$sDir = \Util::getDocumentRoot().'storage/cache/zend';
		
		\Util::checkDir($sDir);
		
		$aBackendOptions = array(
			'cache_dir' => $sDir
		);
 
		$oZendCache = \Zend_Cache::factory(
			'Core',
            'File',
            $this->getZendCacheFrontendOptions(),
            $aBackendOptions
		);
		
		return $oZendCache;
	}

	/**
	 * Gibt alle bei Zend_Locale verfügbaren Locales zurück
	 * @param string $sLanguage
	 * @param bool $bShowIsoInLabel
	 * @return array
	 */
	public function getInstalledLocales($sLanguage = null, $bShowIsoInLabel = false) {

		if(empty($sLanguage)) {
			$sLanguage = \System::getInterfaceLanguage();
		}

		$sCacheKey = sprintf('%s_%s_%d', __METHOD__, $sLanguage, $bShowIsoInLabel);

		$aReturn = \WDCache::get($sCacheKey);

		if($aReturn === null) {

			$aLocales = \Zend_Locale::getLocaleList();

			$aReturn = array();
			foreach($aLocales as $sLocale=>$iOne) {

				$oZendLocale = new \Zend_Locale($sLocale);
				$sLocaleLanguage = $oZendLocale->getLanguage();
				$sLocaleRegion = $oZendLocale->getRegion();

				$sLocaleName = \Zend_Locale::getTranslation($sLocaleLanguage, 'language', $sLanguage);
				if(!empty($sLocaleRegion)) {
					$sLocaleName .= ' ('.\Zend_Locale::getTranslation($sLocaleRegion, 'country', $sLanguage).')';
				}

				if($bShowIsoInLabel) {
					$sLocaleName .= ' ('.$sLocale.')';
				}

				$aReturn[$sLocale] = $sLocaleName;
			}

			// Doppelte Sprachen rauswerfen
			unset($aReturn['en_001']); // English (World)
			unset($aReturn['en_150']); // English (Europe)
			unset($aReturn['en_Dsrt']); // English (Mormonen-Alphabet)
			unset($aReturn['en_Dsrt_US']); // English (United States)
			unset($aReturn['en_US_POSIX']); // English (United States)

			// Wert nicht größer als 28 Tage setzen, da das sonst als Unix-Timestamp interpretiert wird!
			\WDCache::set($sCacheKey, (28*24*60*60), $aReturn);

		}

		return $aReturn;
	}
	
	/**
	 * Abruf von lokalisierten Daten
	 * 
	 * $sPath kann zum Beispiel sein: months, days, country, language, am, pm, date, time, datetime
	 * 
	 * @param string $sLocale
	 * @param string $sPath
	 * @return string|array
	 */
	public function getLocaleData($sLocale, $sPath) {
		
		$sCacheKey = 'LocaleService::getLocale_'.$sLocale.'_'.$sPath;

		$aData = \WDCache::get($sCacheKey);
		
		if($aData === null) {
			
			$aData = \Zend_Locale::getTranslationList($sPath, $sLocale);

			\WDCache::set($sCacheKey, (7*24*60*60), $aData);
			
		}

		return $aData;
	}
	
	public function getLocaleValue($sLocale, $sValue, $sType) {
		
		$sCacheKey = 'LocaleService::getLocaleValue_'.$sLocale.'_'.implode('-', (array)$sValue).'_'.$sType;

		$sReturn = \WDCache::get($sCacheKey);
		
		if($sReturn === null) {
			
			$sReturn = \Zend_Locale::getTranslation($sValue, $sType, $sLocale);

			\WDCache::set($sCacheKey, (7*24*60*60), $sReturn);
			
		}

		return $sReturn;
	}
	
	/**
	 * 
	 * @param string $sLocale
	 * @param string $sDay
	 * @return type
	 */
	public function getDay($sLocale, $sDay, $sType='wide') {

		$sAbbreviation = $this->getLocaleValue($sLocale, array('gregorian', 'stand-alone', $sType, $sDay), 'Day');
				
		// Fallback
		if(empty($sAbbreviation)) {
			$sAbbreviation = $this->getLocaleValue($sLocale, array('gregorian', 'format', $sType, $sDay), 'Day');
		}
		
		return $sAbbreviation;
	}
	
	/**
	 * @param string $sLocale
	 * @return array
	 */
	public function getCountries($sLocale) {
		
		$sCacheKey = __METHOD__.'_'.$sLocale;

		$aData = \WDCache::get($sCacheKey);
		
		if($aData === null) {
			
			$aData = \Zend_Locale::getTranslationList('territory', $sLocale);

			$aData = array_filter($aData, function($mKey) {
				return !is_numeric($mKey);
			}, ARRAY_FILTER_USE_KEY);

			// Unknown Region
			unset($aData['ZZ']);

			asort($aData);

			\WDCache::set($sCacheKey, (7*24*60*60), $aData);
			
		}

		return $aData;
	}

	/**
	 * Pendant zu \Zend_Locale_Format::convertPhpToIsoFormat() - konnte keine Methode finden
	 *
	 * @param string $isoFormat
	 * @return void
	 */
	public static function convertIsoToPhpFormat(string $isoFormat): string {

		$prefix = '%';
		
		$convert = [
			'dd' => 'd'  , 'EE' => 'D'  , 'd' => 'd'   , 'EEEE' => 'l',
			'eee' => 'N' , 'SS' => 'S'  , 'e' => 'w'   , 'D' => 'z'   ,
			'ww' => 'W'  , 'MMMM' => 'F', 'MM' => 'm'  , 'MMM' => 'M' ,
			'M' => 'n'   , 'ddd' => 't' , 'l' => 'L'   , 'YYYY' => 'o',
			'y' => 'Y'    , 'yyyy' => 'Y', 'yy' => 'y'  , 'a' => 'a'   ,
			'B' => 'B'   , 'h' => 'g'   , 'H' => 'G'   , 'hh' => 'h'  ,
			'HH' => 'H'  , 'mm' => 'i'  , 'ss' => 's'  , 'zzzz' => 'e',
			'I' => 'I'   , 'Z' => 'O'   , 'ZZZZ' => 'P', 'z' => 'T'   ,
			'X' => 'Z'   , 'yyyy-MM-ddTHH:mm:ssZZZZ' => 'c', 'r' => 'r',
			'U' => 'U',
		];

		$possiblePartsSorted = array_keys($convert);
		usort($possiblePartsSorted, function($a, $b) { return strlen($b) <=> strlen($a); });

		$separatorFormat =  str_replace($possiblePartsSorted, '{|}', $isoFormat);
		$separators = array_filter(explode('{|}', $separatorFormat), fn ($value) => !empty($value));

		$partsFormat = str_replace(array_unique($separators), '{|}', $isoFormat);
		$parts = explode('{|}', $partsFormat);

		$phpFormat = '';
		foreach ($parts as $partIndex=>$part) {
			$phpFormat .= $prefix.($convert[$part] ?? $part).($separators[$partIndex+1] ?? '');
		}

		return $phpFormat;
	}


}
