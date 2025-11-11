<?php

class Ext_Manual_Manual{
	
	public $sHtml = "";
	
	protected $_sTable = "manual_pages";
	protected $_iLastInsertId = 0;
	protected $_aData = array();
	protected $_aPages = array();
	protected $_sPreparedFieldList = "";
	protected $_aPreparedFieldList = array();
	
	public function checkTitle($sTitle = ""){
		if ($sTitle == ""){
			$sTitle = L10N::t('Neuer Eintrag');
		}
		return $sTitle;
	}
	
	public function getInnerHtmlOfLi($iId,$sTitle = "",$iAdministration= 0){
		
		$sTitle = $this->checkTitle($sTitle);
		
		$sOnClick = "return false;";
		$sHtml = "";
		if($iAdministration == 0){
			
			$sOnClick = "?action=show&page=".$iId;
			$sHtml .= "<a href='".$sOnClick."' >";
		} else {
			$sHtml .= "<span>";
		}
		
		$sHtml .= $sTitle;
		if($iAdministration == 0){
			$sHtml .= "</a>";
		} else {
			$sHtml .= "</span>";
		}
		
		if($iAdministration == 1){
			$sHtml .= " <img onclick='loadEdit(".$iId.");' src='/admin/media/pencil.png'>";
			$sHtml .= " <img onclick='deleteTreeBranch(".$iId.");' src='/admin/media/cross.png'>";
		}	
		return $sHtml;
	}
	
	public function getDefaultLiStart($iId,$sTitle = ""){
		$this->_getDefaultLiStart($iId,$sTitle);
	}
	
	public function saveNewTreeBranch($iParentId){
		return $this->_saveNewTreeBranch($iParentId);
	}
	
	public function deleteTreeBranch($iId){
		$this->_deleteTreeBranch($iId);
	}
	
	public function saveTree($aTree){
		
		$this->_saveTree($aTree);
	}
	
	public function getTrack($iId){
		return $this->_getTrack($iId);
	}
	
	public function display($bAdministration = 0,$iParentId = 0){
		
		$aData = $this->_aData;
		$sHtml = $this->sHtml;
		if($bAdministration == 0 && $iParentId != 0 ){
			$aNextPage = $this->_getNextPage();
			$this->_aData = $aData;
			$aPreviousPage = $this->_getPreviousPage();
			$this->_aData = $aData;
			echo "<div> <a href='".$_SERVER['PHP_SELF']."?page=".$aPreviousPage['id']."'> ".L10N::t('Zurück')." </a> - ".$aData['title']." - <a href='".$_SERVER['PHP_SELF']."?page=".$aNextPage['id']."'> ".L10N::t('Weiter')." </a> </div>";
		}
		
		if($bAdministration == 0 && $iParentId != 0 && $sHtml != ""){
			echo "<br/>".L10N::t('Enthaltene Kapitel');
		}
		
		echo '<ul id="tree" class="page-list" style="padding-top: 6px; padding-bottom: 6px;">';
		echo $sHtml;
		echo '</ul>';
	}
	public function getPageArray(){
		
		
		
	}
	public function getPages($iAdministration = 0,$iParentId = 0){
		if($iAdministration == 1){
			return $this->_getPagesForEdit($iParentId);
		}elseif($iAdministration == 2) {
			return $this->_getPages($iParentId,array(),1);
		} else {
			return $this->_getPages($iParentId);
		}
		
	}
	
	public function searchPages($sSearch){
		return $this->_searchPages($sSearch);
	}
	
	public function getPageData($iId){
		
		$sSql = "SELECT * FROM #table WHERE id = :id LIMIT 1";
		$aSql = array('table'=>$this->_sTable,'id'=>$iId);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		$this->_aData = $aResult[0];
		return $aResult[0];
	}
	
	/**
	 * Setzt den Inhalt des Feldes
	 */
	public function setField($sField,$mValue){
		$this->_aData[$sField] = $mValue;
	}
	
	/**
	 * Leifert den Inhalt des Feldes
	 */
	public function getField($sField){
		return $his->aData[$sField];
	}
	
	/**
	 * Speichert die mit setField gesetzten Felder in die DB
	 * Falls eine ID per setField eingetragen wurde wird geupdated
	 */
	public function save(){
		return $this->_save();
	}
	
	
	
	
	/**
	 * Bereitet die Liste für den SQL vor.
	 * Speichert die Daten in $_sPreparedFieldList und $_aPreparedFieldList
	 * @param string $_sPreparedFieldList
	 * @param array $_aPreparedFieldList
	 */
	protected function _prepareFieldList(){
		
		$sSql = "";
		$aSql = array();

		// Geht das Array mit den Feldern durch und baut den SQL Teil String zusammen

		foreach($this->_aData as $sField => $sValue){
			
			$sSql .= " `".$sField."` = :".$sField." ,";
			$aSql[$sField] = $sValue;

		}
		$sSql .= " 	`changed` = NOW()";
		$this->_sPreparedFieldList = $sSql;
		$this->_aPreparedFieldList = $aSql;
		
	}
	
	/**
	 * Macht einen Insert mit allen Daten die in $_aData stehen
	 * @return int LastInsertId
	 */
	protected function _save(){
		
		$this->_prepareFieldList();
		$sSql = $this->_sPreparedFieldList;
		$aSql = array();
		$aSql = $this->_aPreparedFieldList;
		$aSql['table'] = $this->_sTable;

		$sSqlStart = "";
		$sSqlWherePart = "";
		$sSqlFinal = "";
		
		if($this->_aData['id'] > 0){
			$sSqlStart = "	UPDATE #table
							SET 
								`created` = NOW(), ";
			$sSqlWherePart = " WHERE `id` = :id LIMIT 1";
		} else {
			$sSqlStart = "	INSERT INTO #table
							SET 
								`created` = NOW(), ";
		}
		
		$sSqlFinal = $sSqlStart.$sSql.$sSqlWherePart;

		DB::executePreparedQuery($sSqlFinal,$aSql);
		
		if($this->_aData['id'] > 0){
			$this->_iLastInsertId = DB::fetchInsertId();
		} else {
			$this->_iLastInsertId = $this->_aData['id'];
		}
		
		return $this->_iLastInsertId;
		
	}
	

	
	protected function _getDefaultLiStart($iId,$sTitle = "",$iAdministration = 0){
		
		$sTitle = $this->checkTitle($sTitle);		
		
		$sHtml = '<li class="clear-element page-item sort-handle left" style="-moz-user-select: none;" id="page_'.$iId.'">'; 
		$sHtml .= $this->getInnerHtmlOfLi($iId,$sTitle,$iAdministration);
		
		return $sHtml;
	}
	
	protected function _getPages($iParentId = 0,$aPath = array(),$bWithTree = 0){
		
		DB::setResultType(MYSQL_ASSOC);
		
		$sSql = "SELECT * FROM #table WHERE `parent_id` = :parent_id AND `active` = 1 ORDER BY `position`";
		$aSql = array('table'=>$this->_sTable,'parent_id'=>$iParentId);
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$this->_buildPageCode($aResult,$bWithTree);
		
		return $aResult;
			
	}
	
	protected function _buildPageCode($aResult,$bWithTree = 0){
		
		foreach($aResult as $aPage){
			
			$this->sHtml .= $this->_getDefaultLiStart($aPage['id'],$aPage['title']);
			if($bWithTree == 1){
				$this->sHtml .= '<ul class="edit_list" id="droppoint_'.$aPage['id'].'">';
				$aTemp = $this->_getPages($aPage['id']);
				$this->sHtml .= '</ul>';
			}
			$this->sHtml .= '</li>'; 
		}	
		
	}
	
	protected function _getPagesForEdit($iParentId = 0,$aPath = array()){
		
		DB::setResultType(MYSQL_ASSOC);
		
		$sSql = "SELECT * FROM #table WHERE `parent_id` = :parent_id AND `active` = 1 ORDER BY `position`";
		$aSql = array('table'=>$this->_sTable,'parent_id'=>$iParentId);
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		$iTemp = 1;

		foreach($aResult as $aPage){
			
			$this->sHtml .= $this->_getDefaultLiStart($aPage['id'],$aPage['title'],1);
			$aPages[$aPage['id']] = $aPage;
			$this->sHtml .= '<ul class="edit_list" id="droppoint_'.$aPage['id'].'">';
			$this->sHtml .= '<li class="spacer_li"></li>'; 
			$aPages[$aPage['id']]['childs'] = $this->_getPagesForEdit($aPage['id']);
			$this->sHtml .= '</ul>';
			$this->sHtml .= '</li>'; 
		}
		
		if($iParentId == 0){
			$this->_aPages = $aPages;
		}
			
		return 	$aPages;
			
						
	}
	
	protected function _saveTree($aTree,$iParentId = 0){
		
		$iPosition = 0;
		foreach($aTree as $aData){
			$id = $aData['id'];
			$sSql = "UPDATE #table SET position = :position,parent_id = :parent_id WHERE id = :id LIMIT 1";
			$aSql = array('table'=>$this->_sTable,'position'=>$iPosition,'parent_id'=>$iParentId,'id'=>$id);
			DB::executePreparedQuery($sSql,$aSql);
			
			unset($aData['id']);
			$this->_saveTree($aData,$id);
			$iPosition++;
		}
		
	}
	
	protected function _saveNewTreeBranch($iParentId,$iPosition = 0){
				
		$sSql = "INSERT INTO #table SET parent_id = :parent_id,title = :title,position = :position";
		$sTitle = $this->checkTitle();
		$aSql = array('table'=>$this->_sTable,'parent_id'=>$iParentId,'title'=>$sTitle,'position'=>$iPosition);
		DB::executePreparedQuery($sSql,$aSql);
		
		$iId = DB::fetchInsertId();
		return $iId;
		
	}
	
	protected function _deleteTreeBranch($iId){
		
		$sSql = "UPDATE #table SET active = 0 WHERE id = :id";
		$aSql = array('table'=>$this->_sTable,'id'=>$iId);
		DB::executePreparedQuery($sSql,$aSql);
		
	}

	/**
	 * Get Track Data
	 * @param $iId Id Of The Current Page
	 * @param $bFlag 0 => return array , 1 => return String , 2 => return current Page Data
	 */
	protected function _getTrack($iId,$bFlag = 1){
		global $_VARS;
		$aPage = $this->getPageData($iId);
		$aTrack[] = $aPage;
		if($aPage['parent_id'] > 0){
			$aTrack[] = $this->_getTrack($aPage['parent_id'],2);
		}
		if($bFlag == 2){
			return $aPage;
		} else if($bFlag == 1) {
			$sTrack = "";
			if(!empty($aTrack)){
				foreach($aTrack as $aPage){
					if(!empty($aPage)){
						$sTrack =  " - <a href='".$_SERVER['PHP_SELF']."?page=".$aPage['id']."'>".$aPage['title']."</a>".$sTrack;
					}
				}
			}
			return $sTrack;
		} else {
			return $aTrack;
		}
	}

	protected function _searchPages($sSearch){
		
		DB::setResultType(MYSQL_ASSOC);
		
		$sSql = "SELECT * FROM #table WHERE ( `title` LIKE :sSearch OR `content` LIKE :sSearch ) AND `active` = 1 ORDER BY `id`";
		$aSql = array('table'=>$this->_sTable,'sSearch'=>"%".$sSearch."%");
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		$this->_buildPageCode($aResult);
		
		return $aResult;
	}

	protected function _getPreviousPage($bCheckChilds = 1){
				
		$aData = $this->_aData;
		
		if($aData['parent_id'] == 0 && $aData['position'] == 0){
			return false;
		}
		
		$sSql = "SELECT * FROM #table WHERE parent_id = :parent_id AND position = :position AND active = 1 LIMIT 1";
		$aSql = array('table'=>$this->_sTable,'parent_id'=>$aData['parent_id'],'position'=>($aData['position']-1));
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		
		if(count($aResult) > 0){
			
			if($bCheckChilds == 1){
				$aChilds = $this->_getPages($aResult[0]['id']);
		
				if(!empty($aChilds)){
					$aLastChild = array_pop($aChilds);
					$this->_aData = $aLastChild;
					return $aLastChild;
				}
			}
			$this->_aData = $aResult[0];
			
			return $aResult[0];
		} else {
			
			$aPrevious = $this->getPageData($aData['parent_id']);
			
			return $aPrevious;

		}
		
		
	}

	protected function _getNextPage($bCheckChilds = 1){
		
		$aData = $this->_aData;
		if($bCheckChilds == 1){
			$aChilds = $this->_getPages($aData['id']);
	
			if(!empty($aChilds)){
				return $aChilds[0];
			}
		}
		$sSql = "SELECT * FROM #table WHERE parent_id = :parent_id AND position = :position AND active = 1 LIMIT 1";
		$aSql = array('table'=>$this->_sTable,'parent_id'=>$aData['parent_id'],'position'=>($aData['position']+1));
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		if(count($aResult) > 0){
			
			return $aResult[0];
		} else {
			
			if($aData['parent_id'] == 0){
				return false;
			}
			
			$aNext = $this->getPageData($aData['parent_id']);

			return $this->_getNextPage($aNext,0);

		}
		
	}

}
?>
