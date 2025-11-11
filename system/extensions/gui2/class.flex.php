<?php


class Ext_Gui2_Flex  {

	protected $_sHash = '';
	protected $_aFlexData = array();
	protected $_iUserId = 0;
	protected $_sItem = 'user';

	static protected $aInstance = array();

	/**
	 *
	 * @global type $user_data
	 * @global type $objWebDynamics
	 * @param type $sHash
	 * @return Ext_Gui2_Flex
	 */
	static public function getInstance($sHash) {
		global $user_data, $objWebDynamics;

		$iUserId = $user_data['id'];

		$aUser = array('id'=>$iUserId, 'item'=>'user');

		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $aUser);

		if(empty(self::$aInstance[$sHash][$aUser['id']][$aUser['item']])){
			self::$aInstance[$sHash][$aUser['id']][$aUser['item']] = new self($sHash);
		}

		return self::$aInstance[$sHash][$aUser['id']][$aUser['item']];
	}

	public function  __construct($sHash) {
		global $user_data, $objWebDynamics;

		$this->_sHash = $sHash;

		$iUserId = $user_data['id'];

		$aUser = array('id'=>$iUserId, 'item'=>'user');

		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $aUser);

		$this->_iUserId = $aUser['id'];
		$this->_sItem = $aUser['item'];

	}

	public function prepareColumnArray($aArray, $sType = 'list') {

		$aNewArray = array();

		$aFlexData = $this->getAllFlexDataForHash();

		if($sType === 'api') {
			return array_values($aArray);
		} elseif(empty($aFlexData[$sType])) {
			$aArray = array_filter($aArray, function($oColumn) {
				if($oColumn->default === true) {
					return true;
				}
				return false;
			});
			return array_values($aArray);
		}

		foreach($aFlexData[$sType] as $aData) {
			foreach($aArray as $oColumn){
				if(
					$oColumn->db_column == $aData['db_column'] &&
					$oColumn->db_alias	== $aData['db_alias']
				) {
					if(
						$aData['visible'] == 1 || 
						$oColumn->flexibility === false
					) {
						$aNewArray[] = $oColumn;
					}
					break;
				}
			}
		}

		// Wenn etwas schiefgeht, dann komplette Spalten zurückgeben
		if(empty($aNewArray)) {
			return $aArray;
		}

		return $aNewArray;
	}

	public function checkForFlexData($sType, $sDbColumn, $sDbAlias){

		$aFlexData = $this->getAllFlexDataForHash();

		foreach($aFlexData[$sType] as $aData){
			if(
				$aData['db_column'] == $sDbColumn &&
				$aData['db_alias'] == $sDbAlias
			){
				return $aData;
			}
		}

		return false;
	}

	public function getAllFlexDataForHash(){
 
		if(empty($this->_aFlexData)) {

			$sSql = " SELECT
							*
						FROM
							`system_gui2_flex_data`
						WHERE
							`gui_hash` = :hash AND
							`user_id`	= :user_id AND
							`item`	= :item
						ORDER BY
							`position`";
			$aSql = array();
			$aSql['hash'] = $this->_sHash;
			$aSql['user_id']	= (int)$this->_iUserId;
			$aSql['item']		= $this->_sItem;
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$this->_aFlexData['list']	= array();
			$this->_aFlexData['pdf']	= array();
			$this->_aFlexData['csv']	= array();
			$this->_aFlexData['excel']	= array();

			foreach($aResult as $aData){
				if($aData['type'] == 1){
					$this->_aFlexData['list'][]	= $aData;
				} else if($aData['type'] == 2){
					$this->_aFlexData['pdf'][]	= $aData;
				} else if($aData['type'] == 3){
					$this->_aFlexData['csv'][]	= $aData;
				} else if($aData['type'] == 4){
					$this->_aFlexData['excel'][]= $aData;
				}
			}
		}

		return $this->_aFlexData;
	}

	static public function getNumberForType($sType = 'list'){
		if($sType == 'list'){
			return 1;
		} else if($sType == 'pdf'){
			return 2;
		} else if($sType == 'csv'){
			return 3;
		} else if($sType == 'excel'){
			return 4;
		}
	}

	static public function sortFlexData($a, $b){
		if($a['position'] < $b['position']){
			return -1;
		} else if($a['position'] > $b['position']){
			return 1;
		} else {
			return 0;
		}
	}

	public function deleteFlexData($sType = 'list'){

		$iType = self::getNumberForType($sType);

		$sSql = " DELETE FROM
						`system_gui2_flex_data`
					WHERE
						`gui_hash`	= :hash AND
						`user_id`	= :user_id AND
						`item`		= :item
						";
		if($iType > 0){
			$sSql .= ' AND `type`	= :type';
		}
		$aSql = array();
		$aSql['hash'] = $this->_sHash;
		$aSql['type'] = (int)$iType;
		$aSql['user_id']	= (int)$this->_iUserId;
		$aSql['item']		= $this->_sItem;

		$bReturn = DB::executePreparedQuery($sSql, $aSql);

	}

	public function saveFlexData($sDbColumn, $sDbAlias, $sType = 'list', $iPosition = 0, $iVisible = 1){

		$iType = self::getNumberForType($sType);

		$sSql = " REPLACE INTO
						`system_gui2_flex_data`
					SET
						`gui_hash`	= :hash ,
						`db_column` = :column ,
						`db_alias`	= :alias ,
						`type`		= :type ,
						`position`	= :position ,
						`visible`	= :visible ,
						`user_id`	= :user_id ,
						`item`		= :item";
		$aSql = array();
		$aSql['hash']		= $this->_sHash;
		$aSql['type']		= (int)$iType;
		$aSql['column']		= $sDbColumn;
		$aSql['alias']		= $sDbAlias;
		$aSql['position']	= (int)$iPosition;
		$aSql['visible']	= (int)$iVisible;
		$aSql['user_id']	= (int)$this->_iUserId;
		$aSql['item']		= $this->_sItem;

		DB::executePreparedQuery($sSql, $aSql);

		$this->_aFlexData = array();

	}

}