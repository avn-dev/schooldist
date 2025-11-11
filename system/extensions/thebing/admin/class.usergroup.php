<?php


/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Admin_Usergroup extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_access_group';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kag';

	/**
	 * @var bool
	 */
	protected $bForceUpdateUser = true;

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui = null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					* {FORMAT}
				FROM
					`{TABLE}` ".$sTableAlias."
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	public function  __get($sName) {
		if($sName=='description'){
			return $this->_aData['name'];
		}else{
			return parent::__get($sName);
}

	}

	/**
	 * @return array
	 */
	public function getAccessList() {

		$sSql = " SELECT
						`kaga`.`access`
						FROM
							`kolumbus_access_group_access` `kaga`
						WHERE
							`kaga`.`group_id` = :group_id ";
		$aSql = array('group_id'=>(int)$this->id);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		$aAccess = array();
		foreach($aResult as $aData){
			$aAccess[] = $aData['access'];
		}

		return $aAccess;

	}

	public function saveAccessList($aAccess){

		$iId = (int)$this->id;

		$sSql = "
			DELETE FROM
				`kolumbus_access_group_access`
			WHERE
				`group_id` = :group_id
		";
		$aSql = array('group_id' => $iId);

		DB::executePreparedQuery($sSql, $aSql);

		foreach($aAccess as $sKey => $iValue){
			if($iValue==1){
				
				$aSaveData = array(
					'group_id'	=> $iId,
					'access'	=> $sKey,
				);

				$rRes = DB::insertData('kolumbus_access_group_access', $aSaveData);
			}
		}
	}

	public static function getList() {
		
		$oClient = Ext_Thebing_Client::getInstance();

		$sSQL = "
			SELECT
				`id`,
				`name`
			FROM
				`kolumbus_access_group`
			WHERE
				`active` = 1 AND
				`client_id` = :client_id
			ORDER BY
				`name`
		";
		$aSQL = array(
			'client_id'	=> (int)$oClient->id
		);
		$aGroups = DB::getQueryPairs($sSQL, $aSQL);

		return $aGroups;
		
	}

}
