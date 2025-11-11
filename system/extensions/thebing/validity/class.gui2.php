<?php


class Ext_Thebing_Validity_Gui2 extends Ext_Thebing_Gui2_Data
{
	/**
	 * @todo Entfernen und Query Zusatz anders lösen!!!
	 * @global <type> $_VARS
	 * @param <type> $aFilter
	 * @param <type> $aOrderBy
	 * @param <type> $aSelectedIds
	 * @param <type> $bSkipLimit
	 * @return <type>
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false)
	{
		global $_VARS;

		$sSql = '';
		$aSql = array();
		$iLimit = 0;
		$aSqlParts = array();

		$this->setFilterValues($aFilter);
		
		$this->_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		// Filter in den Where Part einbauen
		$this->setQueryFilterDataByRef($aFilter, $aSqlParts, $aSql);

		// IDs mit filtern falls übergeben
		$this->setQueryIdDataByRef($aSelectedIds, $aSqlParts, $aSql);

		// WHERE an den SELECT anhängen
		$sSql .= $aSqlParts['where'];

		// Query um den GROUP BY Teil erweitern
		$this->setQueryGroupByDataByRef($sSql, $aSqlParts['groupby']);

		// HAVING an den SELECT anhängen
		$sSql .= $aSqlParts['having'];

		$aColumnList = $this->_oGui->getColumnList();

		// Query um den ORDER BY Teil erweitern und den Spalten die sortierung zuweisen
		$this->setQueryOrderByDataByRef($sSql, $aOrderBy, $aColumnList, $aSqlParts['orderby']);

		$iEnd = 0;

		if(!$bSkipLimit) {
			// LIMIT anhängen!
			$this->setQueryLimitDataByRef($iLimit, $iEnd, $sSql);
		}

		$oDataParent = $this->_getParentDataObject();
		if(is_object($oDataParent) && method_exists($oDataParent, 'addValiditySql'))
		{
			$oDataParent->addValiditySql($aSqlParts, $aSql);
			$sSql = '';
			$sSql .= 'SELECT AUTO_SQL_CALC_FOUND_ROWS '.$aSqlParts['select'];
			$sSql .= ' FROM '.$aSqlParts['from'];
			$sSql .= ' '.$aSqlParts['where'];
			$sSql .= ' '.$aSqlParts['groupby'];
			$sSql .= ' '.$aSqlParts['having'];
			$sSql .= ' '.$aSqlParts['orderby'];
			$sSql .= ' '.$aSqlParts['limit'];
		}

		$aResult = $this->_getTableQueryData($sSql, $aSql, $iEnd, $iLimit);

		return $aResult;

	}

	public function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$aData = (array)parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);
		$oParentDataObject = $this->_getParentDataObject();
		
		if(
			is_object($oParentDataObject) && 
			method_exists($oParentDataObject, 'getValidityOptions')
		) {
			foreach($aData as $iKey => $aItem) {
				if(
					$aItem['db_column'] == 'item_id' &&
					$aItem['db_alias'] == 'kv'
				) {
					$aOptions = (array)$oParentDataObject->getValidityOptions($aSelectedIds);
					$aData[$iKey]['select_options'] = $aOptions;
				}
			}
		}

		return $aData;
	}
	
}
