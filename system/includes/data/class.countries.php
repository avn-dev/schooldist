<?php
 
class Data_Countries {

	protected $_aData = array();

	public function __construct($sCode) {

		$sSql = "
			SELECT
				*
			FROM
				`data_countries`
			WHERE
				`cn_iso_2` = :code
		";
		$aSql = array('code'=>strtoupper($sCode));
		$this->_aData = DB::getQueryRow($sSql, $aSql);

	}

	public function  __get($sName) {

		if(isset($this->_aData[$sName])) {
			return $this->_aData[$sName];
		}

	}

	public static function getList($sLanguage='en') {

		$sField = 'cn_short_'.$sLanguage;
		
		$oDb = DB::getDefaultConnection();
		$bCheck = $oDb->checkField('data_countries', $sField);

		if(!$bCheck) {
			$sField = 'cn_short_en';
		}

		$sSql = "
			SELECT
				`cn_iso_2`,
				#label_field
			FROM
				`data_countries`
			WHERE
				1
			ORDER BY
				#label_field
			";
		$aSql = array('label_field'=>$sField);

		$aLanguages = DB::getQueryPairs($sSql, $aSql);

		return $aLanguages;
	}

	public static function search($sSearch, $sSearchField='cn_short_', $bStrict=false) {

		if(empty($sSearch)) {
			return [];
		}

		if($bStrict === false) {
			$result = self::search($sSearch, $sSearchField, true);
			if(!empty($result)) {
				return $result;
			}
		}

		$aLanguageCodes = System::d('backend_languages');
		
		$sWhere = "";
			
		$aSql = array();
		if($bStrict === true) {
			$aSql['search_string'] = trim($sSearch);
		} else {
			$aSql['search_string'] = '%'.trim($sSearch).'%';
			$aSql['search_string2'] = trim($sSearch);
		}
		
		$aWhereParts = array();
		foreach($aLanguageCodes as $sLanguageCode) {

			$sFieldName = 'search_field_'.$sLanguageCode;
			$aSql[$sFieldName] = $sSearchField.$sLanguageCode;
			
			$aWhereParts[] = "#".$sFieldName." LIKE :search_string";

			if($bStrict !== true) {
				$aWhereParts[] = "(:search_string2 like concat('%', #".$sFieldName.", '%') AND #".$sFieldName." != '')";
			}
			
		}
		
		$sWhere .= implode(' OR ', $aWhereParts);
		
		$sQuery = "	
			SELECT
				*
			FROM
				`data_countries`
			WHERE
				".$sWhere."
			ORDER BY
				`cn_iso_2` ASC
			";

		$aCountryList = (array)DB::getQueryRows($sQuery, $aSql);
		
		return $aCountryList;
	}

}