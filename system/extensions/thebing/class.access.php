<?php

class Ext_Thebing_Access {
	
 	static protected $oInstance = NULL;
	static protected $aCacheMasterUserId = null;
	static protected $aCacheSchoolGroups = array();

	protected $oAccess;
	
	/**
 	 * Holt eine Instance
 	 * und läd die daten in den cache
 	 * @return Ext_Thebing_Access
 	 */
	static public function getInstance(){
		
		if (self::$oInstance === NULL)  {
			self::$oInstance = new Ext_Thebing_Access();
		}

		return self::$oInstance;
		
	}
	
	
	public function __construct(){
		
		$oAccess = Access::getInstance();

		if(
			!$oAccess instanceof Access_Backend ||
			$oAccess->checkValidAccess() !== true
		) {
			throw new RuntimeException('No valid login!');
		}

		// TODO Wird das noch benötigt?
		global $user_data;
		$user_data = $oAccess->getUserData();

		$this->oAccess = $oAccess;
		
	}
	
	public function getGroupOfSchool($iSchoolId, $iUserId=false) {

		if($iUserId === false) {
			$iUserId = (int)Access::getInstance()->id;
		}

		if(!isset(self::$aCacheSchoolGroups[$iUserId])) {

			$sSql = " SELECT
							school_id, `group_id`
						FROM
							`kolumbus_access_user_group`
						WHERE
							user_id = :user_id ";
			$aSql = array(
						'user_id'=>(int)$iUserId
					);
				
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData){
				self::$aCacheSchoolGroups[$iUserId][$aData['school_id']] = (int)$aData['group_id'];
			}

		}

		return self::$aCacheSchoolGroups[$iUserId][$iSchoolId];

	}
	
	/**
	 * Prüft die Lizenz,Rechtedatei und Version der Rechtedatei
	 *
	 * @param $oAccess
	 * @return bool
	 */
	public function checkLoginData(&$oAccess){
		global $user_data;

		$bValidAccess = $oAccess->checkValidAccess();
				
		if($bValidAccess === true) {

			// Wird vom Hook user_data_backend übernommen
//			$aUserData = $oAccess->getUserData();
//			$aClientData = $this->searchClientData($aUserData['data']['id']);
//			$user_data['client'] = $aClientData['idClient'];

		} else {

			Ext_TC_Error::log('CMS Login - Missing user-id!', $oAccess->getUserData());
			return false;  

		}
	
		if(true) { //$user_data['client'] > 0) {

			$sCMSLicense = System::d('license');

			// prüfen ob der Zugangsserver (Lizenzserver) erreichbar ist
			$sAccessServer = Ext_Thebing_Access_Config::get('access_file_server');
			$bServerOnline = Util::checkUrl($sAccessServer);

			if($bServerOnline){
				$bFile = Ext_Thebing_Access_File::create($sCMSLicense);
			} else {
				$bFile = true;
			}

			if($bFile){
			
				$bCheck = false;
				
				try {
					$bCheck = Ext_Thebing_Access_Client::checkVersion();
				}
				catch (Exception $e)
				{
					Ext_TC_Error::log('Error while starting Access File!',$e); 
					return false;    
				}
				
				if(!$bCheck){
					Ext_TC_Error::log('wrong Access File Version!', $sCMSLicense);
					return false;   
				}
			
			} else {
				Ext_TC_Error::log('Faild to create File!', $sCMSLicense);
				return false;  
			}
 		
		} else {
			Ext_TC_Error::log('No Client Id', $user_data);
			return false;  
		}

		return true;
		
	}
		
	public function getAccessSortRightList(){
		
		$aAccessList = Ext_Thebing_Access_Client::getSortRightList();

		return $aAccessList;
	}
	/**
	 * Holt eine Liste mit Allen Rechten des eingeloggten Mandanten
	 * @return Array
	 */
	public function getAccessRightList(){
		
		$aAccessList = Ext_Thebing_Access_Client::getRightList();

		return $aAccessList;
	}

	/**
	 * Holt alle Benutzergruppen des Mandantens
	 * @return Array
	 */
	public function getAccessGroups(){
		$sSql = " SELECT * FROM 
						`kolumbus_access_group` 
					WHERE 
						client_id = :client_id AND 
						active = 1";
		$aSql = array('client_id'=>\Ext_Thebing_Client::getClientId());
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		$aList = array();
		foreach($aResult as $aData){
			$aList[$aData['id']] = $aData['name'];
		}
		return $aList;
	}
	
	// Prüft den Login auf Gültigen Lizenzschlüssel und Rechtedatei
	static public function checkLogin(Access_Backend &$oAccessBackend) {

		$oAccess = self::getInstance();

		$bCheck = $oAccess->checkLoginData($oAccessBackend);
		
		if(!$bCheck){
			$oAccessBackend->destroyAccess();
			__pout(Ext_TC_Error::lastLog());
		}

		return $bCheck;

	}
	
	public function countSchoolRights($iSchool) {

		$oUser = System::getCurrentUser();
		$iCount = 0;

		$oAccess = Access::getInstance();

		// Master id des Mandanten hat IMMER zugriff ( damit dieser sich selbst nicht ausperren kann )
		$aMasterUserIds = self::getMasterUserIds();

		// Master User hat immer alle rechte
		if(in_array($oAccess->id, $aMasterUserIds)) {
			return 999;
		}

		$oUser = new Ext_Thebing_Access_User($oUser->id);
		$aUserAccess = $oUser->getAccessList();
		$aUserAccess = $aUserAccess[$iSchool];

		// Da gelöschte Rechte auf dem Status 0 verweilen, dürfen diese nicht einfach gezählt werden!
		// Redmine-Ticket #3823
		foreach((array)$aUserAccess as $iStatus) {
			if($iStatus) {
				++$iCount;
			}
		}

		$iGroup = $this->getGroupOfSchool($iSchool);

		if($iGroup > 0){
			$oGroup = new Ext_Thebing_Access_Group($iGroup);
			$aAccess = $oGroup->getAccessList();
			$iCount += count($aAccess);
		}

		return $iCount;

	}
	
	public function getSchoolsWithAccess($sAccess){
		$aSchool = Ext_Thebing_Client::getSchoolList(true);
		foreach($aSchool as $iSchoolID => $mTemp){
			if(!self::hasRight($sAccess,$iSchoolID)){
				unset($aSchool[$iSchoolID]);
			}
		}
		return $aSchool;
	}
	
	static public function hasLicenceRight($sAccess){
		return Ext_Thebing_Access_Client::check($sAccess);
	}

	/**
	 * @deprecated
	 *
	 * @see Ext_Thebing_UserRepository::getMasterUsers()
	 * @return array
	 */
	public static function getMasterUserIds() {

		if(self::$aCacheMasterUserId === null) {

			$aMasterUsers = Ext_Thebing_User::getRepository()->getMasterUsers();
			self::$aCacheMasterUserId = array_column($aMasterUsers, 'id');

		}

		return self::$aCacheMasterUserId;
	}
	
	/**
	 * Prüft auf ein Recht
	 * $iSchool == 0 => jede schulle muss das recht haben
	 * @param $sAccess
	 * @param $iSchoolId
	 * @return Bool
	 */
	static public function hasRight($sAccess, $iSchoolId = null){
		global $_VARS;
		
		if($iSchoolId === null) {
			$iSchoolId = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		if(is_array($sAccess)) {
			$sAccess = implode('-', $sAccess);
		}

		// Wenn Recht »thebing_invoice_inbox« _mit_ ID, dann ID erst mal entfernen
		if(strpos($sAccess, 'thebing_invoice_inbox_') !== false) {
			$iOrignalInboxId = substr($sAccess, strrpos($sAccess, '_') + 1);
			$sAccess = 'thebing_invoice_inbox';
			$bAccess = Ext_Thebing_Access_Client::check('thebing_invoice_icon');
		} else {
			if(strpos($sAccess, '-') !== false) {
				list($sSection, $sRight) = explode('-', $sAccess, 2);
			} else {
				$sSection = $sAccess;
				$sRight = null;
			}
			// checken ob unser/gruppenrechte da sind und ob diese mit den mandanten rechten übereinstimmen
			$bAccess = Ext_Thebing_Access_Client::check($sSection, $sRight);
		}

		// Beim Inbox-Recht ID der Inbox anhängen, wenn Inboxen benutzt werden
		if($sAccess === 'thebing_invoice_inbox') {

			$iInboxId = null;
			
			if(!empty($iOrignalInboxId)) {
				// ID wurde oben abgeschnitten
				$iInboxId = $iOrignalInboxId;
			} else {
				// ID muss ermittelt werden
				$oClient = Ext_Thebing_Client::getInstance();
				if($oClient->checkUsingOfInboxes()) {

					if(empty($_VARS['inbox_id'])) {
						throw new Exception('No inbox ID given but using inboxes!');
					}

					$iInboxId = (int)$_VARS['inbox_id'];
					
				}
			}

			// Keine Inbox, nur allgemeines Recht prüfen
			if($iInboxId !== null) {

				$oInboxAccessMatrix = new \Ts\Gui2\Inbox\AccessMatrix();
				$aInboxes = $oInboxAccessMatrix->getListByUserRight();

				// Wenn kein Zugriff auf Inbox, dann
				if(!array_key_exists($iInboxId, $aInboxes)) {
					$bAccess = false;
				}
			
			}
						
			$sAccess = 'thebing_invoice_icon';
						
		}

		// Wenn der Mandant das Recht grundsätzlich hat
		if($bAccess) {
			
			$oAccess = Access::getInstance();
			
			// Master id des Mandanten hat IMMER zugriff ( damit dieser sich selbst nicht ausperren kann )
			$aMasterUserIds = self::getMasterUserIds();

			if(
				$oAccess instanceof Access_Backend &&
				in_array($oAccess->id, $aMasterUserIds)
			) {
				return true;
			}

			// Schauen ob der User das Recht für die Aktuelle Schule (oder von allen Schulen) hat
			$bUser = Ext_Thebing_Access_User::check($sAccess, $iSchoolId);

			if(!$bUser) {
				return false;
			} else {
				return true;
			}
			
		}

		return $bAccess;
	}

	static public function accesschecker($sAccess,$iSchool = null) {
		$bCheck = self::hasRight($sAccess,$iSchool);
		if(!$bCheck){
			$objPage = new GUI_Page();
			$objPage->appendElement(L10N::t('Sie haben keine Berechtigung, diese Seite zu betreten!'));
			echo $objPage->generateHTML();
			die();
		}
		return true;
	}

	/**
	 * Holt eine Liste mit Allen Rechten des eingeloggten Mandanten
	 * @return Array
	 */
	static public function getRightList(){
		$oAccess = self::getInstance();
		return $oAccess->getAccessRightList();
	}
	
	/**
	 * Sortiert die Rechteliste von Live0, anhand der position spalte für die Rechtedarstellung
	 *
	 * @param array $aList
	 * @return array
	 */
	public static function sortRightAccessList($aList) {

		// Oberpunkte sortieren
		uasort($aList, function($aTabData1, $aTabData2) {
			$aFirstRight1 = reset($aTabData1);
			$aFirstRight2 = reset($aTabData2);
			return $aFirstRight1['position'] > $aFirstRight2['position'];
		});

		// Rechte innerhalb der Tabs sortieren
		foreach($aList as &$aTabData) {
			uasort($aTabData, function($aRight1, $aRight2) {
				return $aRight1['position'] > $aRight2['position'];
			});
		}

		return $aList;

	}

}
