<?php

class Logs extends GUI_Ajax_Table {

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
		// Userspezifisches WHERE
		if( is_numeric($_VARS['filter_sort'])){ 
			$sWhereAddon .= $sWhereTemp." su.id = ".$_VARS['filter_sort'];
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

		return $aResult;
	}
	
}

?>