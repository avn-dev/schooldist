<?php

class Data_Languages {

	protected $_aData = array();

	static $cache = [];
	
	public function __construct($sCode) {

		$sSql = "
			SELECT
				*
			FROM
				`data_languages`
			WHERE
				`iso_639_1` = :code
		";
		$aSql = array('code'=>strtolower($sCode));
		$this->_aData = DB::getQueryRow($sSql, $aSql);

	}

	public function  __get($sName) {

		if(isset($this->_aData[$sName])) {
			return $this->_aData[$sName];
		}

	}

	public static function getList($sLanguage='en') {

		if(empty(self::$cache[$sLanguage])) {
		
			$sField = 'name_'.$sLanguage;

			$oDb = DB::getDefaultConnection();
			$bCheck = $oDb->checkField('data_languages', $sField);

			if(!$bCheck) {
				$sField = 'name_en';
			}

			$sSql = "
				SELECT
					`iso_639_1`,
					#label_field
				FROM
					`data_languages`
				WHERE
					1
				ORDER BY
					#label_field
				";
			$aSql = array('label_field'=>$sField);

			self::$cache[$sLanguage] = DB::getQueryPairs($sSql, $aSql);
			
		}

		return self::$cache[$sLanguage];
	}

	public static function search($sSearch) {

		$sSearchField = 'name_';

		$aLanguageCodes = array_flip(System::d('allowed_languages'));

		$sWhere = "";

		$aSql = array();
		$aSql['search_string'] = '%'.trim($sSearch).'%';

		$aWhereParts = array();
		foreach($aLanguageCodes as $sLanguageCode) {

			$sFieldName = 'search_field_'.$sLanguageCode;
			$aSql[$sFieldName] = $sSearchField.$sLanguageCode;

			$aWhereParts[] = "#".$sFieldName." LIKE :search_string";

		}

		$sWhere .= implode(' OR ', $aWhereParts);

		$sQuery = "	
			SELECT
				*
			FROM
				`data_languages`
			WHERE
				".$sWhere."
			ORDER BY
				`iso_639_1` ASC
			";

		$aCountryList = DB::getQueryRows($sQuery, $aSql);

		return $aCountryList;

	}

} 