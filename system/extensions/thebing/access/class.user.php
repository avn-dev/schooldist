<?php

class Ext_Thebing_Access_User {
	
	static protected $aCache = array();
	static protected $aInstance = array();
	static protected $aAccess = array();
	
	protected $_iUserId = 0;
	
	static public function getInstance($iUserId){
		if(
			!isset(self::$aInstance[$iUserId]) ||
			self::$aInstance[$iUserId] == NULL
		) {
			self::$aInstance[$iUserId] = new Ext_Thebing_Access_User($iUserId);
		}
		return self::$aInstance[$iUserId];
	}
	
	public function __construct($iUserId){
		$this->_iUserId = $iUserId;
		$this->_setAccessList();
	}

	protected function _setAccessList(){
		
		if(!isset(self::$aAccess[(int)$this->_iUserId])) {
			
			$sSql = " 
				SELECT 
					`kaua`.`access`,
					`kaua`.`school_id`, 
					`kaua`.`status` `status`  
				FROM 
					`kolumbus_access_user_access` `kaua`
				WHERE 
					`kaua`.`user_id` =:user_id 
			";
			$aSql = array('user_id'=>(int)$this->_iUserId);
			$aResult = DB::getPreparedQueryData($sSql,$aSql);
			
			self::$aAccess[(int)$this->_iUserId] = array();
			foreach($aResult as $aData) {
				
				$sSection = $aData['access'];
				$sRight = 'dummy';
				if(strpos($aData['access'], '-') !== false) {
					list($sSection, $sRight) = explode('-', $aData['access']);
				}
				
				self::$aAccess[(int)$this->_iUserId][$aData['school_id']][$sSection][$sRight] = $aData['status'];
			}

		}

	}
	
	public function getAccessList(){

		return self::$aAccess[(int)$this->_iUserId];
		
	}
	
	/**
	 * Prüft ob das Recht vorhanden ist
	 * @param $sAccess
	 * @param $iSchool
	 * @return Bool
	 */
	static public function check($sAccess, $iSchool = null) {
		$oUser = System::getCurrentUser();
		$oAccess = self::getInstance($oUser->id);
		return $oAccess->checkAccess($sAccess, $iSchool);
	}

	/**
	 * Prüft ausschliesslich das Setting des Users
	 * @param string $sAccess
	 * @param int $iSchool
	 */
	public function checkUserAccess(string $sAccess, int $iSchool = null) {
		
		$bIssetAccess = isset(self::$aAccess[(int)$this->_iUserId][$iSchool][$sAccess]);

		// Wenn eine Schule angegeben ist und es gefunden wird => OK
		if(
			$iSchool > 0 &&
			$bIssetAccess &&
			(int)self::$aAccess[(int)$this->_iUserId][$iSchool][$sAccess] == 1
		) {
			return true;
		// Wenn Eine Schule angegeben ist und es nicht gefunden wird => FALSE
		} else if(
			$iSchool > 0 &&
			$bIssetAccess &&
			(int)self::$aAccess[(int)$this->_iUserId][$iSchool][$sAccess] == 0
		){
			return false;
		// Wenn eine Schule angegeben wurde und er für ALLE Schulen das Recht hat => OK
		} else if(
			$iSchool > 0 &&
			!$bIssetAccess
		){
			return null;
		// Wenn keine Schule angegeben wurde und er für ALLE Schulen das Recht hat => OK
		} else if($iSchool == 0) {
			
			$oClient = Ext_Thebing_System::getClient();
			$aSchool = $oClient->getSchoolList(true);
			
			$mAccess = true;
			
			foreach($aSchool as $iSchoolID => $mTemp) {
				if(
					isset(self::$aAccess[(int)$this->_iUserId][$iSchoolID][$sAccess]) &&
					(int)self::$aAccess[(int)$this->_iUserId][$iSchoolID][$sAccess] == 0
				){
					$mAccess = false;
					break;
				} else if(
					!isset(self::$aAccess[(int)$this->_iUserId][$iSchoolID][$sAccess])
				) {
					// wenn es nicht gesetzt ist steht es auf "wie gruppe" daher
					// machen wir true damit er zur gruppen prüfung geht
					$mAccess = null;
				}
			}

			return $mAccess;
			
		}
		
		return null;
	}
	
	private function checkSchoolAccess(int $iSchool, string $sSection, string $sRight=null): ?bool {
		
		$bStatus = null;
		
		$aAccess = [];

		if(isset(self::$aAccess[(int)$this->_iUserId][$iSchool])) {
			$aAccess = self::$aAccess[(int)$this->_iUserId][$iSchool];
		}

		if(
			(
				empty($sRight) &&
				isset($aAccess[$sSection])
			) ||
			(
				!empty($sRight) &&
				isset($aAccess[$sSection][$sRight])
			)
		) {
			
			if(empty($sRight)) {
				// Mindestens ein Recht muss gesetzt sein
				$bStatus = false;
				foreach($aAccess[$sSection] as $sRight=>$iStatus) {
					if($iStatus == 1) {
						$bStatus = (bool)$iStatus;
					}
				}
			} else {
				$bStatus = (bool)$aAccess[$sSection][$sRight];
			}

		}
		
		return $bStatus;
	}
	
	public function checkAccess($sAccess, $iSchool = null) {

		$sSection = $sAccess;
		$sRight = '';
		if(strpos($sAccess, '-') !== false) {
			list($sSection, $sRight) = explode('-', $sAccess);
		}
		
		if($iSchool > 0) {

			$bAccess = $this->checkSchoolAccess($iSchool, $sSection, $sRight);
			
			// Wenn eine Schule angegeben ist und es gefunden wird => OK
			if($bAccess === true) {
				return true;
			// Wenn Eine Schule angegeben ist und es nicht gefunden wird => FALSE
			} else if($bAccess === false){
				return false;
			// Wenn eine Schule angegeben wurde und er für ALLE Schulen das Recht hat => OK
			} else {
				return Ext_Thebing_Access_Group::check($sAccess, $iSchool, $this->_iUserId);
			}
			
		// Wenn keine Schule angegeben wurde und er für ALLE Schulen das Recht hat => OK
		} else {
			
			/*
			 * Achtung: Diese Abfrage bezieht sich auf den aktuell eingeloggten User
			 * und nicht auf den User des Objektes!
			 */
			$oClient = Ext_Thebing_System::getClient();
			$aSchool = $oClient->getSchoolListByAccess(true);
			
			$mAccess = null;
			
			foreach($aSchool as $iSchoolID => $mTemp) {
				
				$bAccess = $this->checkSchoolAccess($iSchoolID, $sSection, $sRight);
				
				// Wenn mind. 1 Schule das Recht hat => True
				if($bAccess === true){
					// wenn es explizit auf ja steht können wir direkt true zrurückliefern
					return true;
				} else if(
					$bAccess === false &&
					$mAccess !== true
				){
					// wenn es ein false gibt und es noch nicht auf true steht müssen wir es uns merken
					$mAccess = false;
				} else if($bAccess === null) {
					// wenn es nicht gesetzt ist steht es auf "wie gruppe" daher
					// machen wir true damit er zur gruppen prüfung geht
					$mAccess = true;
				}
			}
				
			// wenn $bAccess auf fals steht waren alle Schulen false
			// die muss so geprüft werden da wir nicht in der schleife return false machen können
			// da z.b erst die dritte schule das recht haben könnte und nur mind. 1 Schule true braucht
			// daher darf das fals erst am ende zurück gegeben werden wenns wirklich kein true gab
			if(!$mAccess) {
				return false;
			}
			
			return Ext_Thebing_Access_Group::check($sAccess, 0, $this->_iUserId);

		}

		return false;
	}

	public static function clearSchoolsListByAccessRight($aSchools) {
	
		$oAccess = new Ext_Thebing_Access();

		foreach((array)$aSchools as $iSchoolID => $sSchool) {
			
			$iCount = $oAccess->countSchoolRights($iSchoolID);

			if($iCount <= 0) {
				unset($aSchools[$iSchoolID]);
			}
		}

		return $aSchools;
	}


	public function setAccess($iSchool, $sAccess, $bStatus){

		$aSql = array(
					'access'=>$sAccess,
					'user_id'=>(int)$this->_iUserId,
					'school_id'=>(int)$iSchool
				);
		
		$sSql = " DELETE FROM 
							`kolumbus_access_user_access` 
						WHERE 
							`access` = :access AND
							`user_id` =:user_id AND
							`school_id` =:school_id ";
		DB::executePreparedQuery($sSql,$aSql);

		if((int)$bStatus >= 0){
			$sSql = " INSERT INTO 
							`kolumbus_access_user_access` 
						SET 
							`access` = :access,
							`user_id` =:user_id ,
							`school_id` =:school_id,
							`status` = :status";
			$aSql['status'] = (int)$bStatus;
			DB::executePreparedQuery($sSql, $aSql);
		}

		self::$aAccess = array();
		$this->_setAccessList();

	}
	
	public function getGroupIdOfSchool($iSchool){

		$sSql = " SELECT group_id FROM
							`kolumbus_access_user_group` 
						WHERE 
							`user_id` =:user_id AND
							`school_id` =:school_id ";
		$aSql = array('user_id'=>(int)$this->_iUserId,'school_id'=>(int)$iSchool);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult[0]['group_id'];

	}
	
	public function setGroupForSchool($iSchool, $iGroup){
		
		$aSql = array(
					'group_id'=>(int)$iGroup,
					'user_id'=>(int)$this->_iUserId,
					'school_id'=>(int)$iSchool
				);
		
		$sSql = " DELETE FROM 
							`kolumbus_access_user_group` 
						WHERE 
							`user_id` =:user_id AND
							`school_id` =:school_id ";
		DB::executePreparedQuery($sSql, $aSql);
		
		if($iGroup > 0){
			$sSql = " INSERT INTO 
							`kolumbus_access_user_group` 
						SET 
							`group_id` = :group_id,
							`user_id` =:user_id ,
							`school_id` =:school_id";
			DB::executePreparedQuery($sSql, $aSql);
		}

		self::$aAccess = array();
		$this->_setAccessList();

	}
	
	public function deleteAllAccess(){
		$sSql = " DELETE FROM 
							`kolumbus_access_user_access` 
						WHERE 
							`user_id` =:user_id ";
		$aSql = array('user_id'=>(int)$this->_iUserId);
		DB::executePreparedQuery($sSql,$aSql);
		self::$aAccess = array();
	}

}