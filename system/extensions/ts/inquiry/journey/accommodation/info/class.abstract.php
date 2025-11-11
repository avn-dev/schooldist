<?php


abstract class Ext_TS_Inquiry_Journey_Accommodation_Info_Abstract extends Ext_Thebing_Basic {

	abstract protected function _getInfoKey();

	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if(
			$aErrors === true
		)
		{
			$aErrors = array();
		}

		$aInvalidEntries = $this->getInvalidJourneyAccommodations();

		if(!empty($aInvalidEntries)) {
			$aErrors['valid_until'][] = 'JOURNEY_ACCOMMODATIONS_EXISTS';
		}

		if(empty($aErrors)) {
			$aErrors = true;
		}

		return $aErrors;
	}

	public function getInvalidJourneyAccommodations() {

		$aFilter = array(
			$this->_getInfoKey() => (int)$this->id
		);

		// Wenn Eintrag gelöscht wird: valid_until auf 1970 setzen, damit wirklich alles geprüft wird
		$sValidUntil = $this->valid_until;
		if($this->active == 0) {
			$sValidUntil = '1970-01-01';
		}

		$aInvalidEntries = Ext_TS_Inquiry_Journey_Accommodation::getInvalidEntriesByFilter($sValidUntil, $aFilter);

		return $aInvalidEntries;
	}

	protected static function getListCacheKey() {
		return static::class.'::getList';
	}

	public static function getList($bForSelect = true, $sLanguage = null) {

		$aResult = WDCache::get(static::getListCacheKey());

		if($aResult === null) {

			$oSelf = new static();

			$aQueryData = $oSelf->getListQueryData();

			// Wenn hier schon so etwas gemacht wird, muss auch manipulateSqlParts() ausgeführt werden!
			$aSqlParts = DB::splitQuery($aQueryData['sql']);
			$oSelf->manipulateSqlParts($aSqlParts, null);

			if($oSelf->hasSortColumn()) {
				$aQueryData['data']['sort_column'] = $oSelf->getSortColumn();
				$aSqlParts['orderby'] = "".$oSelf->getTableAlias().".#sort_column ASC";
			}
			
			$sCurrentDate = date('Y-m-d'); // Damit MySQL den Query vielleicht cachen kann
			$aSqlParts['where'] .= " AND (".$oSelf->getTableAlias().".`valid_until` = '0000-00-00' OR ".$oSelf->getTableAlias().".`valid_until` >= '".$sCurrentDate."') ";

			$sSql = DB::buildQueryPartsToSql($aSqlParts);

			$aResult = DB::getPreparedQueryData($sSql, $aQueryData['data']);

			WDCache::set(static::getListCacheKey(), (60*60*24*7), $aResult);

		}

		if(!$bForSelect) {
			return $aResult;
		}

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$aBack = [];

		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['name_'.$sLanguage];
		}

		return $aBack;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {

		parent::save($bLog);

		WDCache::deleteGroup(Ext_Thebing_School::ACCOMMODATION_COMBINATION_CACHE);
		WDCache::delete(static::getListCacheKey());

		return $this;
	}

}