<?php 

class Ext_Thebing_Nationality extends Ext_Thebing_Basic {
	
	protected $_sTable = 'data_countries';
	
	protected $_aFormat = array(
							);

	public static $_aCacheNationalities = array();

	/**
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @param bool $bUseId
	 * @return array|mixed
	 */
	public static function getNationalities($bForSelect = true, $sLanguage = 'en', $bUseId = false) {

		// TODO Alte Argumente müssten mal entfernt werden
		if(
			!$bForSelect ||
			$bUseId
		) {
			throw new InvalidArgumentException('Not implemented anymore');
		}

		if(empty($sLanguage)) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		// Cache
		$sCacheKey = __METHOD__.'_'.$sLanguage;
		$aReturn = WDCache::get($sCacheKey);
		if(!empty($aReturn)) {
			return $aReturn;
		}

		$sLanguageField = 'nationality_'.$sLanguage;

		// Tabelle enthält mehr Spalten als allowed_languages
		$bFieldExists = DB::getDefaultConnection()->checkField('data_countries', $sLanguageField);

		if($bFieldExists) {
			$sLanguageFieldSql = "`{$sLanguageField}`";
		} else {
			$sLanguageFieldSql = "''";
		}

		$sSql = "
			SELECT
				`cn_iso_2` `iso`,
				`nationality_en`,
				{$sLanguageFieldSql} `{$sLanguageField}`
			FROM
				`data_countries`
			WHERE
				/* War schon früher so und entfernt einige Länder, die als Nationalität auch keinen Sinn machen */
				`nationality_en` != ''
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$oLocaleService = new Core\Service\LocaleService();
		$aCountries = $oLocaleService->getCountries($sLanguage);

		$aReturn = [];
		foreach($aResult as $aNationality) {

			// Spalte leer oder existiert nicht: Über Countries versuchen, ansonsten Fallback EN
			if(empty($aNationality[$sLanguageField])) {
				if(!empty($aCountries[$aNationality['iso']])) {
					$sNationality = $aCountries[$aNationality['iso']];
				} else {
					$sNationality = $aNationality['nationality_en'];
				}
			} else {
				$sNationality = $aNationality[$sLanguageField];
			}

			$aReturn[$aNationality['iso']] = $sNationality;

		}

		asort($aReturn);

		WDCache::set($sCacheKey, 86400, $aReturn);

		return $aReturn;

	}
	
	public static function getMotherTonguebyNationality($sNationality) {

		$sSql = "SELECT
					`mothertonge_id`
				FROM
					`data_countries`
				WHERE
					`cn_iso_2` = :nationality
				";
		$aSql = array();
		$aSql['nationality'] = $sNationality;
		
		$iMothertongueId = DB::getQueryOne($sSql, $aSql);

		return $iMothertongueId;
	}
	
	public static function getMotherTonguesByNationality() {

		$sSql = "SELECT
					`cn_iso_2`,
					`mothertonge_id`
				FROM
					`data_countries`
				";
		$aReturn = DB::getQueryPairs($sSql);

		return $aReturn;
	}
	
//	public static function getCorrespondenceTonguebyMotherTongue($iMothertongue, $iSchool = 0){
//
//		if($iSchool <= 0){
//			$iSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');;
//		}
//
//		$sSql = "SELECT
//					`correspondence_id`
//				FROM
//					`kolumbus_language_mothertonge_correspondencetonge`
//				WHERE
//					`mothertonge_id` = :mothertongue AND
//					`active` = 1 AND
//					`school_id` = :school";
//		$aSql = array();
//		$aSql['mothertongue'] = $iMothertongue;
//		$aSql['school'] = (int)$iSchool;
//
//		$sCorrespondence = DB::getQueryOne($sSql,$aSql);
//
//		return $sCorrespondence;
//	}
	
}
