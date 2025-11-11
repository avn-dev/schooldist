<?php

class Ext_Thebing_Access_Group {

	static protected $aGroup = array();
	static protected $aCache = array();
	protected $aAccess = array();
	protected $_iId = 0;
	
 	/**
 	 * Holt eine Instance
 	 * und läd die daten in den cache
 	 * @return Ext_Thebing_Access_Group
 	 */
	static public function getInstance($iId = 0){
		
		if (self::$aGroup[$iId] === NULL)  {
			self::$aGroup[$iId] = new Ext_Thebing_Access_Group($iId);
		}

		return self::$aGroup[$iId];
		
	}
	
	public function __construct($iId = 0){
		$this->_iId = $iId;
		$this->aAccess = $this->getAccessList();
	}
	
	static public function check($sAccess, $iSchool, $iUserId) {

		if(empty($iUserId)) {
			throw new InvalidArgumentException('No user id for '.__CLASS__);
		}

		if($iSchool > 0) {

			$oAccess = new Ext_Thebing_Access();

			$iGroup = $oAccess->getGroupOfSchool($iSchool, $iUserId);

			if((int)$iGroup > 0){
				$oGroup = self::getInstance($iGroup);
				return $oGroup->checkAccess($sAccess);
			}

			return false;

		} else {
			
			$aSchool	= Ext_Thebing_Client::getSchoolList(true);
			$bCheck		= false;

			foreach((array)$aSchool as $iSchoolID => $mTemp){

				$oAccess = new Ext_Thebing_Access();
				$iGroup = $oAccess->getGroupOfSchool($iSchoolID);

				if($iGroup > 0){
					$oGroup = self::getInstance($iGroup);
					// Wenn mind. 1 Schule das Recht hat => True
					if($oGroup->checkAccess($sAccess)) {
						$bCheck = true;
					}
					// Wenn keine Gruppe dann FALSE ( da User schon gecheckt wurde muss nichts weiter geprüft werden )
				} 
			}

			return $bCheck;
		}
		
		return true;
	}
	
	public function checkAccess($sAccess) {
				
		$sSection = $sAccess;
		$sRight = '';
		if(strpos($sAccess, '-') !== false) {
			list($sSection, $sRight) = explode('-', $sAccess);
		}
		
		if(
			(
				empty($sRight) &&
				isset($this->aAccess[$sSection])
			) ||
			(
				!empty($sRight) &&
				isset($this->aAccess[$sSection][$sRight])
			)
		) {
			return true;
		}

		return false;
	}
	
	public function getAccessList($bUseCache=true) {

		if(
			$bUseCache &&
			self::$aCache[(int)$this->_iId]['access']
		) {
			return self::$aCache[(int)$this->_iId]['access'];
		}
		
		$sSql = " SELECT 
						`kaga`.`access`
						FROM 
							`kolumbus_access_group_access` `kaga`
						WHERE 
							`kaga`.`group_id` = :group_id ";
		$aSql = array('group_id'=>(int)$this->_iId);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		$aAccess = array();
		foreach($aResult as $aData) {
			
			$sSection = $aData['access'];
			$sRight = 'dummy';
			if(strpos($aData['access'], '-') !== false) {
				list($sSection, $sRight) = explode('-', $aData['access']);
			}

			$aAccess[$sSection][$sRight] = 1;
		}

		self::$aCache[(int)$this->_iId]['access'] = $aAccess;
		
		return $aAccess;
	}

	public function setAccess($sAccess, $bStatus) {
		
		$aSql = array('access'=>$sAccess,'group_id'=>(int)$this->_iId);
		$sSql = " DELETE FROM 
							`kolumbus_access_group_access` 
						WHERE 
							`access` = :access AND
							`group_id` =:group_id";
		DB::executePreparedQuery($sSql,$aSql);
		if($bStatus){
			$sSql = " INSERT INTO 
							`kolumbus_access_group_access` 
						SET 
							`access` = :access,
							`group_id` =:group_id";
			DB::executePreparedQuery($sSql,$aSql);
		}
		
	}
	
}