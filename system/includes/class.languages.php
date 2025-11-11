<?php

class Languages extends GUI_Ajax_Table
{
	/**
	 * Deletes a company
	 * 
	 * @param int : The company ID
	 */
	public function deleteRow($iRowID)
	{
		if((int)$iRowID > 0)
		{
			// get tablename
			$sTable = $this->aConfigData['query_data'][1]['table'];
			
			// get Entry by id
			$aSql = array(
				'table'	=> $sTable,
				'id'	=> $iRowID
			);
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :id
				LIMIT 1
			";
			DB::executePreparedQuery($sSql, $aSql);	
		}
	}


	/**
	 * Saves a company
	 * 
	 * @return array : The update data
	 */
	public function saveRowData()
	{
		global $_VARS;

		if($_SESSION['language_translation'] === 1) // BACKEND
		{
			$oTranslation = new WDBasic((int)$_VARS['id'], 'language_data');

			if((int)$_VARS['id'] == 0)
			{
				$oTranslation->key 	= \Util::generateRandomString(32);
				$oTranslation->code = $_VARS['save']['code'];
			}

			$oTranslation->file_id 	= $_VARS['save']['file_id'];
			$oTranslation->de 		= $_VARS['save']['de'];
			$oTranslation->en 		= $_VARS['save']['en'];
			$oTranslation->fr 		= $_VARS['save']['fr'];
			$oTranslation->nl 		= $_VARS['save']['nl'];
			$oTranslation->es 		= $_VARS['save']['es'];
			$oTranslation->it 		= $_VARS['save']['it'];

			$oTranslation->save();

			$aID = array($oTranslation->id);
		}
		else if ($_SESSION['language_translation'] === 0) // FRONTEND
		{
			$aID = parent::saveRowData();
		}
//		// get tablename
//		$sTable = $this->aConfigData['query_data'][1]['table'];
//
//		// set id
//		$iID = (int)$_VARS['id'];
//
//		// save frontend
//		if($_SESSION['language_translation'] === 0) // FRONTEND
//		{
//			// get translation obj
//			$oTranslation = new WDBasic($iID, $sTable);
//
//			// save data
//			foreach($_VARS['save'] as $sColumn => $sEntry)
//			{
//				$oTranslation->$sColumn = $sEntry;
//			}
//
//			// save extra html
//			if(!isset($_VARS['save']['html']))
//			{
//				$oTranslation->html = 0;
//			}
//			else
//			{
//				$oTranslation->html = 1;
//			}
//
//			// save object
//			$oTranslation->save();
//		}
//		else if($_SESSION['language_translation'] === 1) // BACKEND
//		{
//			if($iID > 0) //update via WDBASIC
//			{
//				// get translation obj
//				$oTranslation = new WDBasic($iID, $sTable);
//				
//				// save data
//				foreach($_VARS['save'] as $sColumn => $sEntry)
//				{
//					$oTranslation->$sColumn = $sEntry;
//				}			
//				
//				if($iID == 0)
//				{
//					$oTranslation->key = \Util::generateRandomString(32);
//				}
//				
//				$oTranslation->save();
//				// get new? id
//				$iID = $oTranslation->id;
//			}
//			else if ($iID == 0) // insert normal
//			{
//				$sColumns = $this->_getAllTableColumns();
//				foreach($sColumns as $iKey => $aColumn)
//				{
//					// continue column id
//					if($aColumn['Field'] == 'id')
//					{
//						continue;
//					}
//					
//					if($iKey != 0 && $sColumns[$iKey-1]['Field'] != 'id')
//					{
//						$sQueryColumns	.= ",";
//						$sNewValues		.= ",";
//					}
//					$sQueryColumns	.= "`".$aColumn['Field']."`";
//					if(isset($_VARS['save'][$aColumn['Field']]) && !$_VARS['save']['id'])
//					{
//						$sNewValues		.= "'".mysql_real_escape_string($_VARS['save'][$aColumn['Field']])."'";
//					}
//					else
//					{
//						if($aColumn['Field'] == 'key')
//						{
//							$sNewValues .= "'".generateRandomString(32)."'";
//						}
//						else
//						{
//							$sNewValues = "''";
//						}
//					}
//				}
//				$sSql = "
//					INSERT INTO
//						`".$sTable."`
//						(".$sQueryColumns.")
//					VALUES
//						(".$sNewValues.")
//				";
//				DB::executeQuery($sSql);
//				$iID = DB::fetchInsertID();
//			}
//		}
//
		return array($aID);
	}


	public function getEditData($id){

		$aSqlString = $this->split_sql();
		$aSql = $this->getSqlArray();

		$sId_Column_Alias = "";
		if($this->aQueryData['filter']['id']['alias'] != ""){
			$sId_Column_Alias = "`".$this->aQueryData['filter']['id']['alias']."`.";
		}

		$sWhereAddon = "";
		$sWhereTemp = " WHERE ";
		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		$sWhereAddon .= $sWhereTemp." ".$sId_Column_Alias."`".$this->aQueryData['filter']['id']['column']."` = :id";
		$sWhereTemp = " AND ";
		$aSql['id'] = $id;
		$sSelectNew = "";
		
		$aEditData = $this->aEditData;
		$i=1;
		foreach((array)$aEditData as $aEdit){
			if($aEdit['type'] == "h1" || $aEdit['type'] == "h2" || $aEdit['type'] == "h3" || $aEdit['type'] == "text" || $aEdit['type'] == "tab" ){
				
				if($i == count($aEditData) && strrpos($sSelectNew,' , ') == strlen($sSelectNew)-3){
					$sSelectNew = substr($sSelectNew,0,strlen($sSelectNew)-3);
				}
				$i++;
				continue;
			}
			$sAliasAddon = "";
			if($aEdit['alias'] != ""){
				$sAliasAddon = "`".$aEdit['alias']."`.";
			}
			if($aEdit['type'] == "date" || $aEdit['type'] == "time" || $aEdit['type'] == "calendar" ){
				$sSelectNew.= " UNIX_TIMESTAMP(".$sAliasAddon."`".$aEdit['column']."`) as `".$aEdit['column']."`";
			} else {
				$sSelectNew.= " ".$sAliasAddon."`".$aEdit['column']."` ";
			}
			if($i < count($aEditData)){
				$sSelectNew.= " , ";
			}
			$i++;
		}
		if($aSqlString['groupby']){
			$aSqlString['groupby'] = " GROUP BY ".$aSqlString['groupby'];
		}
		if($aSqlString['orderby']){
			$aSqlString['orderby'] = " ORDER BY ".$aSqlString['orderby'];
		}
		if($aSqlString['limit']){
			$aSqlString['limit'] = " LIMIT ".$aSqlString['limit'];
		}
		$sSql_new = "SELECT ".$sSelectNew." FROM ".$aSqlString['from']." ".$sWhereAddon." ".$aSqlString['groupby']." ".$aSqlString['orderby']." ".$aSqlString['limit'];

		$aResult = DB::getPreparedQueryData($sSql_new, $aSql);
			
		foreach((array)$aEditData as $aEdit) {
			if($aEdit['type'] == "date" || $aEdit['type'] == "calendar") {
				
				if($aResult[0][$aEdit['column']] <= 0) {
					$aResult[0][$aEdit['column']] = "";
				} else {
					$aResult[0][$aEdit['column']] = $this->convertTimestampToDate($aResult[0][$aEdit['column']]);
				}
			}
			if($aEdit['type'] == "time") {
				if($aResult[0][$aEdit['column']] <= 0) {
					$aResult[0][$aEdit['column']] = "";
				} else {
					$aResult[0][$aEdit['column']] = $this->convertTimestampToDateTime($aResult[0][$aEdit['column']]);
				}
			}
		}
		$aBack = $aResult[0];
		return $aBack;
	}


	/**
	 * Build the Data Array for the Ajax Table List
	 * @return array Data Array with DB Results
	 */
	public function getTableListData() {
		global $_VARS;
		
		if(isset($_VARS['guilty_file_id'])) {
			$_SESSION['language_guilty_file_id'] = $_VARS['guilty_file_id'];
		}

		$aResult = $this->_getTableList();

		$aTableData = array();
		$iFileCol = 0;
		foreach((array)$aResult as $key => $aColumn) {

			$aTableData['icon'][(string)$aColumn['id']][0] = 'new';
			$aTableData['icon'][(string)$aColumn['id']][1] = 'edit';
			$aTableData['icon'][(string)$aColumn['id']][2] = 'delete';
			$aTableData['icon'][(string)$aColumn['id']][3] = 'export_csv';
			$aTableData['icon'][(string)$aColumn['id']][4] = 'export_xls';

			$aTableData['data'][$key][0] = $aColumn['id'];
			$i = 1;
			foreach($this->aHeaderData as $aHead){
				if($aHead['type'] == "date" ){
					if(!$aHead['format']) {
						$aHead['format'] = "%x";
					}
					$aTableData['data'][$key][$i] = strftime($aHead['format'], (int)$aColumn[$aHead['column']]);
					if($aColumn[$aHead['column']] <= 0) {
						$aTableData['data'][$key][$i] = " --- ";
					}
				} elseif($aHead['type'] == "date_time" ) {
					if(!$aHead['format']) {
						$aHead['format'] = "%x %X";
					}
					$aTableData['data'][$key][$i] = strftime($aHead['format'], (int)$aColumn[$aHead['column']]);
					if($aColumn[$aHead['column']] <= 0){
						$aTableData['data'][$key][$i] = " --- ";
					}
				} elseif($aHead['type'] == "time" ) {
					if(!$aHead['format']) {
						$aHead['format'] = "%X";
					}
					$aTableData['data'][$key][$i] = strftime($aHead['format'], (int)$aColumn[$aHead['column']]);
					if($aColumn[$aHead['column']] <= 0){
						$aTableData['data'][$key][$i] = " --- ";
					}
				} elseif($aHead['type'] == "function" ) {

					$aTableData['data'][$key][$i] = call_user_func($aHead['function'], $aColumn[$aHead['column']]);

				} else {

					$aTableData['data'][$key][$i] = strip_tags($aColumn[$aHead['column']]);

				}
				
				if(
					$iFileCol == 0 &&
					$aHead['column'] == 'file_id'
				) {
					$iFileCol = $i;
				}
				
				$i++;
			}

		}

		$aTableData['pagination']['offset'] = (int)$this->iPaginationOffset;
		$aTableData['pagination']['end'] 	= (int)$this->iPaginationEnd;
		$aTableData['pagination']['total'] 	= (int)$this->iPaginationTotal;
		$aTableData['pagination']['show'] 	= (int)$this->iPaginationShow;

		if(
			$_SESSION['language_translation'] === 1 &&
			$iFileCol > 0
		) {
			// get all files from table
			$sSql = "
				SELECT
					*
				FROM
					`language_files`
				ORDER BY `file`
			";
			$aLanguageFiles = DB::getQueryData($sSql);
			$aDataArray = array(
				0	=> L10N::t('GLOBAL VERFÜGBAR')
			);
			foreach($aLanguageFiles as $iKey => $aValue)
			{
				$aDataArray[$aValue['id']] = $aValue['file'];
			}
			
			foreach((array)$aTableData['data'] as $iKey => $aValue)
			{
				$aTableData['data'][$iKey][$iFileCol] = $aDataArray[$aValue[$iFileCol]];
			}
		}

		return $aTableData;

	}
	
	protected function _getTableList(){
		global $_VARS;

		$aSqlString = $this->split_sql();
		$aSql = $this->getSqlArray();
		
		$sWhereAddon = "";
		$sWhereTemp = " WHERE ";

		if(is_array($this->aQueryData['filter']['additional']) && !empty($this->aQueryData['filter']['additional'])){

			foreach((array)$this->aQueryData['filter']['additional'] as $iFilter=>$aAdditional) {
				if(!empty($_VARS[$aAdditional['variable']])) {
					$sFrom_Column_Alias = "";
					if($aAdditional['alias'] != ""){
						$sFrom_Column_Alias = "`".$aAdditional['alias']."`.";
					}
					if(empty($aAdditional['operator'])) {
						$aAdditional['operator'] = '=';
					}
					$sWhereAddon .= $sWhereTemp." ".$sFrom_Column_Alias."#field_".$iFilter." ".$aAdditional['operator']." :value_".$iFilter."";
					$sWhereTemp = " AND ";
					$aSql['field_'.$iFilter] = $aAdditional['column'];
					$aSql['value_'.$iFilter] = $_VARS[$aAdditional['variable']];
				}
			}

		}
		// Falls der Filter From gesetzt ist
		if($_VARS['filter_from'] != ""){
			$sFrom_Column_Alias = "";
			if($this->aQueryData['filter']['from']['alias'] != ""){
				$sFrom_Column_Alias = "`".$this->aQueryData['filter']['from']['alias']."`.";
			}
			$sWhereAddon .= $sWhereTemp." ".$sFrom_Column_Alias."#from_column >= :from";
			$sWhereTemp = " AND ";
			$aSql['from_column'] = $this->aQueryData['filter']['from']['column'];
			$aSql['from'] = date("YmdHis",$this->convertDateToTimestamp($_VARS['filter_from']));
		}
		// Falls der Filter To gesetzt ist
		if($_VARS['filter_to'] != ""){
			$sTo_Column_Alias = "";
			if($this->aQueryData['filter']['to']['alias'] != ""){
				$sTo_Column_Alias = "`".$this->aQueryData['filter']['to']['alias']."`.";
			}
			$sWhereAddon .= $sWhereTemp." ".$sTo_Column_Alias."#to_column <= :to";
			$sWhereTemp = " AND ";
			$aSql['to_column'] = $this->aQueryData['filter']['to']['column'];
			$aSql['to'] = date("YmdHis",$this->convertDateToTimestamp($_VARS['filter_to']));
		}
		
		// Falls Das Suchfeld gesetzt ist
		if($_VARS['filter_search'] != ""){
			$sSearch_Column_Alias = "";

			$aTmpFields = @explode(',', $this->aQueryData['filter']['search']['column']);
			$aTmpAlias = @explode(',', $this->aQueryData['filter']['search']['alias']);

			//if($this->aQueryData['filter']['search']['alias'] != ""){
			//	$sSearch_Column_Alias .= "`".$this->aQueryData['filter']['search']['alias']."`.";
			//}

			$sWhereAddon .= $sWhereTemp." ( ";
			$sLastSearchColumnAlias = '';
			foreach((array)$aTmpFields as $iKey => $sValue)
			{
				
				$sSearch_Column_Alias = trim($aTmpAlias[$iKey]);
				if($sSearch_Column_Alias == ""){
					$sSearch_Column_Alias = $sLastSearchColumnAlias;
				} else {
					$sLastSearchColumnAlias = $sSearch_Column_Alias;
				}
				
				$sSearch_Column_Alias = '`'.$sSearch_Column_Alias.'`.';
				
				$sValue = trim($sValue);

				$sWhereAddon .= $sSearch_Column_Alias."#search_column".$iKey." LIKE :search".$iKey."";
				if(isset($aTmpFields[$iKey+1]))
				{
					$sWhereAddon .= " OR ";
				}

				$aSql['search_column'.$iKey] = $sValue;
				$aSql['search'.$iKey] = "%".$_VARS['filter_search']."%";
			}

			$sWhereAddon .= " ) ";

			$sWhereTemp = " AND ";
		}
		
		// Falls wir im backEND mode sind, und auf "file" beschränken 
		if(isset($_VARS['guilty_file_id']))
		{
			if($_VARS['guilty_file_id'] != '')
			{
				$aSql['guilty_file_id'] = $_VARS['guilty_file_id'];
				$sWhereAddon .= $sWhereTemp.' `file_id` = :guilty_file_id ';
				$sWhereTemp = " AND ";
			}
		}

		if(
			isset($_VARS['empty']) &&
			(int)$_VARS['empty'] == 1
		) {
			$sWhereAddon .= $sWhereTemp." 
								(
									`code` != '' AND 
									`de` = '' AND 
									`en` = '' AND 
									`fr` = '' AND 
									`nl` = '' AND 
									`es` = '' AND 
									`it` = ''
								) ";
			$sWhereTemp = " AND ";
		}

		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		$sId_Column_Alias = "";
		if($this->aQueryData['filter']['id']['alias'] != ""){
			$sId_Column_Alias = "`".$this->aQueryData['filter']['id']['alias']."`.";
		}
		if(substr_count($aSqlString['select'],"*") <= 0){
			$sSelectNew = " ".$sId_Column_Alias."`".$this->aQueryData['filter']['id']['column']."`,".$aSqlString['select'];
		} else {
			$sSelectNew = $aSqlString['select'];
		}
		if($aSqlString['groupby']){
			$aSqlString['groupby'] = " GROUP BY ".$aSqlString['groupby'];
		}
		if($aSqlString['orderby']){
			$aSqlString['orderby'] = " ORDER BY ".$aSqlString['orderby'];

		}
		if($this->aConfigData['layout_data']['sortable'] == 1){
			$aSqlString['orderby'] = " ORDER BY `position`";
		}
		
		/* =================== */
		
		$iOffset = $_VARS['offset'];
		if($iOffset <= 0){
			$iOffset = 0;
		}
		if($aSqlString['limit']){
			$iEnd = (int)$aSqlString['limit'];
			$aSqlString['limit'] = " LIMIT ".$iOffset.",".$aSqlString['limit'];
		} else {
			$aSqlString['limit'] = " LIMIT ".$iOffset.",20";
			$iEnd = '20';
		}
		$sSql_new = "SELECT SQL_CALC_FOUND_ROWS  ".$sSelectNew." FROM ".$aSqlString['from']." ".$sWhereAddon." ".$aSqlString['groupby']." ".$aSqlString['orderby']." ".$aSqlString['limit'];
		$aResult = DB::getPreparedQueryData($sSql_new, $aSql);

 		$aCount = DB::getQueryData('SELECT FOUND_ROWS() as `count`');
		$this->iPaginationTotal 	= $aCount[0]['count'];
		$this->iPaginationOffset 	= (int)$iOffset;
		$this->iPaginationShow 		= (int)$iEnd;

		if(($iOffset + $iEnd) > $aCount[0]['count']){
			$iEnd = $aCount[0]['count'] - $iOffset;
		}

		$this->iPaginationEnd = $iOffset + $iEnd;

		if($this->iPaginationEnd < $this->iPaginationOffset) {
		  	$_VARS['offset'] = 0;
		   	return $this->_getTableList();
		}
		
		/* =================== */
		
		return $aResult;
	}

	/* ==================================================================================================== */

	protected function _getAllTableColumns()
	{
		// get tablename
		$sTable = $this->aConfigData['query_data'][1]['table'];

		// get all table columns
		$sSql = "DESCRIBE `".$sTable."`";
		$aTableDescription = DB::getQueryData($sSql);
		
		return $aTableDescription;
	}

	protected function _checkRand()
	{
		if($this->sRandString == "rand_0" || $this->sRandString == NULL)
		{
			$this->sRandString = "rand_".md5(uniqid(rand(), true));
		}
	}
}

?>