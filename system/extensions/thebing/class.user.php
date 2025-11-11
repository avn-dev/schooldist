<?php

use Communication\Interfaces\Model\CommunicationSubObject;

/**
 * ACHTUNG! Wenn hier vom Core abgeleitet wird muss die save methode beachtet werden ( user rolle )
 *
 * @method static Ext_Thebing_UserRepository getRepository()
 */
class Ext_Thebing_User extends Ext_TC_User {

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'emailtemplate' => [
			'class' => 'Ext_Thebing_Email_Template',
			'key' => 'default_identity_id',
			'type' => 'child',
			'on_delete' => 'detach'
		],
		'salespersonssettings' => [
			'class' => 'Ext_Thebing_Salesperson_Setting',
			'key' => 'user_id',
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'passkeys' => [
			'class' => \Admin\Entity\User\Passkey::class,
			'key' => 'user_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];

	/**
	 * @var array
	 */
	protected $_aJoinTables = [
		'usergroups' => [
			'table' => 'kolumbus_access_user_group',
			'foreign_key_field' => ['group_id', 'school_id'],
			'primary_key_field' => 'user_id'
		],
		'school_settings' => [
			'table' => 'ts_system_user_schoolsettings',
			'foreign_key_field' => ['school_id', 'use_setting', 'emailaccount_id'],
			'primary_key_field' => 'user_id',
			'autoload' => false,
			'on_delete' => 'delete'
		],
		'sender_identities' => [
			'table' => 'kolumbus_user_identities',
			'primary_key_field' => 'user_id',
			'foreign_key_field' => ['school_id', 'identity_id'],
			'autoload' => false,
			'on_delete' => 'delete'
		],
		'system_types' => [
			'table' => 'tc_employees_to_categories',
			'foreign_key_field' => 'category_id',
			'primary_key_field' => 'employee_id',
			'class' => \Tc\Entity\SystemTypeMapping::class,
			'autoload' => false
		],
		'client_user' => [
			'table'=>'kolumbus_clients_users',
			'primary_key_field'	=> 'user_id',
		],
		'devices' => [
			'table' => 'system_user_devices',
			'foreign_key_field' => ['device_id', 'last_login', 'created'],
			'primary_key_field' => 'user_id',
			'autoload' => false
		]
	];

	/**
	 * @TODO Entfernen
	 * @var array
	 */
	protected $_aAdditional = array();

	/**
	 * @var array
	 */
	protected $_aListCacheFields = array(
		'firstname',
		'lastname'
	);

	/**
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [
		'admin_users' => [],
	];


	public static function getSelectOptions($bAddEmptyItem = false) {

		$aReturn = self::getList(true);

		if($bAddEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn, L10N::t('Bitte wählen'));
		}

		return $aReturn;
	}

	/**
	 * @param bool $bForSelect
	 * @return array
	 */
	public static function getList($bForSelect=false, $bIncludeInactive=false) {

		$sSql = "
			SELECT *
			FROM `system_user`";

		if($bIncludeInactive === false) {
			$sSql .= "
				WHERE `active` = 1
			";
		}

		$aResult = DB::getQueryData($sSql);

		if($bForSelect){
			$aUsers = self::prepareUserListArray($aResult);
		}else{
			$aUsers = $aResult;
		}

		return $aUsers;
	}

	/**
	 * @param array $aResult
	 * @return array
	 */
	public static function prepareUserListArray(array $aResult){

		$aUsers		= array();
		$mDummy		= null;
		$oFormat	= new Ext_Gui2_View_Format_UserName();

		foreach($aResult as $aRowData){
			$sName						= $oFormat->format(null,$oDummy,$aRowData);
			$aUsers[$aRowData['id']]	= $sName;
		}

		asort($aUsers);

		return $aUsers;
	}

	/**
	 * @param string $sName
	 * @param mixed $sValue
	 */
	public function  __set($sName, $sValue) {

		if(strpos($sName, 'signature_') === 0) {
			// @TODO Anders lösen
			$this->_aAdditional[$sName] = (string)$sValue;
			
		} elseif($sName == 'master') {
			
			$clientUser = [
				'idClient' => Ext_Thebing_Client::getClientId(),
				'user_id' => $this->id,
				'master' => $sValue
			];
			
			$this->client_user = [$clientUser];
			
		} elseif($sName == 'thebing_email_identities') {
			throw new InvalidArgumentException();
			$this->_aAdditional[$sName] = (array)$sValue;
		} else {
			parent::__set($sName, $sValue);
		}

	}

	/**
	 * @param string $sName
	 * @return array|mixed|string
	 */
	public function  __get($sName) {

		if($sName == 'name') {
			if($this->_aData['id'] > 0) {
				$oFormat = new Ext_Gui2_View_Format_UserName();
				$sValue = $oFormat->format('', $aDummy, $this->_aData);
			} else {
				$sValue = '';
			}
		} elseif($sName == 'master') {
			
			$clientUsers = $this->client_user;
			if(!empty($clientUsers)) {
				$clientUser = reset($clientUsers);
				$sValue = $clientUser['master'];
			}
			
		} elseif($sName == 'thebing_email_identities') {
			throw new InvalidArgumentException();
			$sValue = (array)$this->_aAdditional[$sName];
		} elseif(strpos($sName, 'signature_') === 0) {
			// @TODO Anders lösen
			$sValue = (string)$this->_aAdditional[$sName];
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	/**
	 * @param $iDataID
	 */
	protected function _loadData($iDataID) {
		
		parent::_loadData($iDataID);

		if($iDataID > 0) {
			
			$this->_getUserData();

		}

	}

	/**
	 * @TODO Entfernen und anders lösen (auf getSchoolSetting() umstellen?)
	 */
	protected function _getUserData() {

		$sSql = ' SELECT
						`data`,
						`value`
					FROM
						`kolumbus_user_data`
					WHERE
						`user_id` = :user_id
							';
		$aSql = array('user_id' => (int)$this->id);

		$this->_aAdditional = array_merge($this->_aAdditional, (array)DB::getQueryPairs($sSql, $aSql));

	}

	public function getListQueryData($oGui = null) {

		// !! falls was verändert wird, bitte auch Ext_Thebing_Client::getUserList() überprüfen !!!

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$aQueryData['sql'] = "
				SELECT
					`su`.*,
					`kcu`.`master`,
					IF(`su`.`blocked_until` > NOW(), 1, 0) AS `blocked`
					{FORMAT}
				FROM
					`{TABLE}` `su` LEFT JOIN
					`kolumbus_clients_users` `kcu` ON
						`su`.`id` = `kcu`.`user_id` LEFT JOIN
					`tc_employees_to_categories` `categories` ON
						`su`.`id` = `categories`.`employee_id`
				WHERE
					`su`.`active` = 1
				GROUP BY
					`su`.`id`
				ORDER BY
					`su`.`lastname`,
					`su`.`firstname`
			";

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;
	}

	public function save($bLog = true) {
		
		// Alter oder neuer Eintrag
		if($this->_aData['id'] > 0) {
			$bInsert = false;
		} else {
			$bInsert = true;
		}
		
		$aAdditional = $this->_aAdditional;

		// Standardwerte beim Anlegen eines neuen Users
		if($bInsert) {
			$this->role 			= 10;
			$this->ext_role 		= "|";
			$this->toolbar_size 	= "32";
			$this->access			= "|";
			$this->access_denied	= "|";
			#$this->tab_data			= "0";
		}

		if($this->hasSystemType('salesperson')) {
			$this->ts_is_sales_person = 1;
		} else {
			$this->ts_is_sales_person = 0;
			// Salesperson-Einstellungen löschen wenn der Benutzer gelöscht wurde oder nicht mehr als Salesperson agiert
			$salespersonSettings = $this->getJoinedObjectChilds('salespersonssettings');
			foreach ($salespersonSettings as $salespersonSetting) {
				$salespersonSetting->delete();
			}
		}

		if(!$this->hasSystemType('user')) {
			$this->status = 0;
		}

		parent::save($bLog);

		// Wenn neuer Eintrag, Verknüpfung zu Client eintragen
		if($bInsert) {
			$aInsert = array();
			$aInsert['user_id']		= $this->id;
			$aInsert['idClient']	= Ext_Thebing_Client::getClientId();
			$aInsert['master']		= 0;
			DB::insertData('kolumbus_clients_users', $aInsert);
		}

		// @TODO Alles um $aAdditional entfernen
		Ext_Thebing_User_Data::deleteData((int)$this->id);

		$aLangs = Ext_Thebing_Client::getLangList();
		$aSchools = Ext_Thebing_Client::getSchoolList();
		foreach($aSchools as $aSchool){
			Ext_Thebing_User_Data::saveData((int)$this->id, 'signature_img_'.$aSchool['id'], $aAdditional['signature_img_'.$aSchool['id']]);
			foreach((array)$aLangs as $sLang) {
				Ext_Thebing_User_Data::saveData((int)$this->id, 'signature_email_text_'.$sLang.'_'.$aSchool['id'], $aAdditional['signature_email_text_'.$sLang.'_'.$aSchool['id']]);
				Ext_Thebing_User_Data::saveData((int)$this->id, 'signature_email_html_'.$sLang.'_'.$aSchool['id'], $aAdditional['signature_email_html_'.$sLang.'_'.$aSchool['id']]);
			}
		}

		foreach((array)$aLangs as $sLang) {
			Ext_Thebing_User_Data::saveData((int)$this->id, 'signature_pdf_'.$sLang, $aAdditional['signature_pdf_'.$sLang]);
		}

		WDCache::deleteGroup('email_identities');

		$this->_loadData($this->id);

		return $this;
	}

	public function getIdentities(string $channel, $bForSelect = false, $bIncludeUser = false, CommunicationSubObject $subObject = null) {

		if (!$subObject instanceof \Ext_Thebing_School) {
			$subObject = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}
		
		$users = $this->getCommunicationIdentities($channel, $subObject->id, $bForSelect);
		
		return $users;
	}
	
	/**
	 * Identitäten des Benutzers
	 *
	 * Anmerkung: ID 0 ist All-Schools
	 *
	 * @param int $iSchoolId
	 * @param bool|false $bForSelect
	 * @return array
	 */
	public function getCommunicationIdentities(string $channel, $iSchoolId = 0, $bForSelect = false) {

		if($bForSelect === true) {
			$sCacheKey = $channel.'_identities_' . $this->id.'_'.$iSchoolId;

			$aUsers = WDCache::get($sCacheKey);
		} else {
			$aUsers = null;
		}

		if($aUsers === null) {

			if($bForSelect === true) {
				Ext_Thebing_Mail::$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
			}

			$sSql = "
				SELECT
					`identity_id` `key`,
					`identity_id`
				FROM
					`kolumbus_user_identities`
				WHERE
					`user_id` = :user_id AND
					`school_id` = :school_id
			";

			$aIdentities = DB::getQueryPairs($sSql, [
				'user_id' => $this->id,
				'school_id' => $iSchoolId
			]);

			// Sich selber immer hinzufügen
			$aIdentities[$this->id] = $this->id;

			$sSql = "
				SELECT
					*
				FROM
					`system_user`
				WHERE
					`id` IN ( :user_ids ) AND
					`status` = 1 AND
					`active` = 1
			";
			$aResult = (array)DB::getQueryRows($sSql, ['user_ids' => $aIdentities]);

			foreach($aResult as $aUser) {
				$aUsers[$aUser['id']] = Ext_Thebing_User::getObjectFromArray($aUser);
			}

			if($bForSelect) {
				foreach($aUsers as &$oUser) {
					if ($channel === \Communication\Notifications\Channels\MailChannel::CHANNEL_KEY) {
						$sEmail = Ext_Thebing_Mail::g('', $oUser)->email;
						$oUser = $oUser->getName().' ('.$sEmail.')';
					} else {
						$oUser = $oUser->getName();
					}
				}
				//TODO Cache vorrübergehend deaktiviert - Ticket 13721
				WDCache::set($sCacheKey, 86400, $aUsers, false, 'email_identities');
			}


		}

		return $aUsers;
	}

	/**
	 * Gibt die Benutzergruppen und deren Zuweisung zu der Schule zurück
	 *
	 * @return array
	 */
	public function getUserGroups() {
		return $this->usergroups;
	}

	/**
	 * @return array
	 */
	public static function getListWithGroups() {
		
		$oClient = Ext_Thebing_Client::getInstance();

		$sSql = "
			SELECT
				`su`.`id` AS `user_id`,
				`su`.`firstname`,
				`su`.`lastname`,
				GROUP_CONCAT(DISTINCT `kaug`.`group_id` SEPARATOR '|') AS `groups`
			FROM
				`kolumbus_clients_users` AS `kcu` INNER JOIN
				`system_user` AS `su` ON
					`kcu`.`user_id` = `su`.`id` LEFT OUTER JOIN
				`kolumbus_access_user_group` AS `kaug` ON
					`kcu`.`user_id` = `kaug`.`user_id`
			WHERE
				`su`.`active` = 1 AND
				`kcu`.`idClient` = :client_id
			GROUP BY
				`kcu`.`user_id`
			ORDER BY
				`su`.`lastname`,
				`su`.`firstname`
		";
		$aSql = array(
			'client_id'	=> (int)$oClient->id
		);
		$aUsers = DB::getPreparedQueryData($sSql, $aSql);

		return $aUsers;
		
	}

	/**
	 * Wird nur von der Ext_TC_Access_Matrix aufgerufen und gibt MEHRERE Hauptbenutzer zurück
	 *
	 * @see Ext_TC_Access_Matrix::_setMasterUser()
	 * @param bool $bOnlyIds
	 * @return array
	 */
	public static function getMasterUser($bOnlyIds=true) {

		if($bOnlyIds) {
			return Ext_Thebing_Access::getMasterUserIds();
		} else {
			throw new BadMethodCallException('Not supported anymore');
		}

	}
	
	/**
	 * Liefert alle Inboxen, auf die der Nutzer Zugriff hat
	 *
	 * @param string $sReturnType
	 * @return array
	 * @throws UnexpectedValueException
	 */
	public function getInboxes($sReturnType = 'array') {
		$oClient = Ext_Thebing_Client::getFirstClient();
		$aInboxes = $oClient->getInboxList();
		$aReturn = array();

		foreach($aInboxes as $aInbox) {
			if(Ext_Thebing_Access::hasRight('thebing_invoice_inbox_'.$aInbox['id'])) {

				if($sReturnType === 'array') {
					$aReturn[] = $aInbox;
				} elseif($sReturnType === 'id') {
					$aReturn[] = $aInbox['id'];
				} else {
					throw new UnexpectedValueException('Unknown $sReturnType "'.$sReturnType.'"');
				}
			}
		}

		return $aReturn;
	}

	/**
	 * Diese Methode ist Rendundant zur Thebing Gui2 Data
	 *
	 * Liefert den Arrayschlüssel für den Separator
	 * @return string
	 */
	public function getSeparatorForExport() {

		$oSchool	= Ext_Thebing_Client::getFirstSchool();
		$sSeperator	= $oSchool->export_delimiter;

		if(empty($sSeperator))
		{
			//Standardfall
			$sSeperator = ';';
		}

		return $sSeperator;
	}

	/**
	 * Diese Methode ist Rendundant zur Thebing Gui2 Data
	 *
	 * Liefert den Zeichensatz für den CSV Export
	 * @return string
	 */
	public function getCharsetForExport() {

		$oSchool	= Ext_Thebing_Client::getFirstSchool();
		$sCharset	= $oSchool->getCharsetForExport();

		return $sCharset;
	}

	/**
	 * Wert aus Zwischentabelle
	 *
	 * @param int $iSchoolId
	 * @return array
	 */
	public function getSchoolSetting($iSchoolId) {

		foreach((array)$this->school_settings as $aSetting) {
			if((int)$aSetting['school_id'] == $iSchoolId) {
				return $aSetting;
			}
		}

		return [
			'school_id' => $iSchoolId,
			'use_setting' => 0,
			'emailaccount_id' => 0
		];

	}

	/**
	 * @inheritdoc
	 */
	public static function getObjectFromArray(array $aData) {
		$oUser = parent::getObjectFromArray($aData);
		$oUser->_getUserData(); // Der $_aAdditional-Müll muss geladen werden
		return $oUser;
	}

	/**
	 * Gibt zurück ob es sich bei diesem Benutzer um ein Sales Person handelt.
	 *
	 * @return bool
	 */
	public function isSalesPerson() {
		return (bool)$this->ts_is_sales_person;
	}

	/**
	 * @param bool $bIsSalesPerson
	 * @return void
	 */
	public function setIsSalesPerson($bIsSalesPerson) {
		$this->ts_is_sales_person = (int)$bIsSalesPerson;
	}

	/**
	 * Gibt den Namen des Benutzers wieder
	 *
	 * @param bool $bFirstnameAtFirst
	 *
	 * @return string
	 */
	public function getName($bFirstnameAtFirst = false) {

		$sName = '';
		if(
			!empty($this->firstname) &&
			!empty($this->lastname)
		) {
			if($bFirstnameAtFirst) {
				$sName = $this->firstname.' '.$this->lastname;
			} else {
				$sName = $this->lastname.', '.$this->firstname;
			}
		}

		return $sName;
	}

	/**
	 * Gibt alle Benutzer die ein Sales Person sind für ein Selektierfilter zurück
	 *
	 * @return array
	 */
	public static function getSalesPersonsForSelect() {

		$aTmpUsers = Ext_Thebing_User::getRepository()->getSalesPersons();
		$aUsers = [];

		foreach($aTmpUsers as $oUser) {
			$aUsers[$oUser->getId()] = $oUser->getName();
		}

		asort($aUsers);
		return $aUsers;
	}

	/**
	 * Gibt die Benutzerrechte zurück
	 *
	 * @return array
	 */
	public function getUserRights() {
		return self::getRepository()->getUserRights($this);
	}

	/**
	 * Gibt die Funktionen der Mitarbeiter zurück
	 *
	 * @return array
	 */
	public static function getAvailableFunctions() {

		$aFunctions= [
			'user' => L10N::t('Benutzer'),
			'activity_guide' => L10N::t('Aktivitätsbegleiter'),
			'salesperson' => L10N::t('Vertriebsmitarbeiter'),
			'course_contact' => L10N::t('Kurs-Ansprechpartner')
		];

		asort($aFunctions);
		
		return $aFunctions;
	}

	/**
	 * Abgeleitet, damit der Select-Part belassen werden konnte
	 * 
	 * @param string $aSqlParts
	 * @param type $sView
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$bDebugIp = \Ext_TC_Util::isDebugIP();

		// Fidelo-User nicht anzeigen beim Kunden
		if($bDebugIp !== true) {
			foreach (config('app.intern.emails.domains') as $domain) {
				$aSqlParts['where'] .= " AND email NOT LIKE '%".$domain."%'";
			}
		}
		$aSqlParts['select'] .= "
			, GROUP_CONCAT(DISTINCT`tc_stm`.`name` SEPARATOR ', ') `type` 
		";

		$aSqlParts['from'] .= " LEFT JOIN
				`tc_employees_to_categories` `tc_etc` ON
				`tc_etc`.`employee_id` = `su`.`id` LEFT JOIN
				 `tc_system_type_mapping` `tc_stm` ON
				`tc_stm`.`id` = `tc_etc`.`category_id` 
		";

	}

	public function getEmailSignatureContent(string $contentType, string $language, \Communication\Interfaces\Model\CommunicationSubObject $subObject): string
	{
		if($contentType === 'html') {
			$signatureKey = 'signature_email_html_'.$language.'_'.$subObject->id;
		} else {
			$signatureKey = 'signature_email_text_'.$language.'_'.$subObject->id;
		}

		$signature = $this->$signatureKey;

		return $signature;
	}

	/**
	 * @todo Hierfür muss man die Signaturen auf Core umstellen
	 * 
	 * @param \WDBasic $oObject
	 * @return \Ext_TC_User_Signature
	 */
	public function getSignatureForObject(\WDBasic $oObject) {

		$signature = new \Ext_TC_User_Signature();
		$signature->user_id = $this->id;
		$signature->email = $this->email;
		$signature->phone = $this->phone;
		$signature->fax = $this->fax;
		return $signature;

//		$aSignatures = $this->getJoinedObjectChilds('signatures', true);
//		
//		foreach($aSignatures as $oSignature) {
//			if($oSignature->object_id == $oObject->getId()) {
//				return $oSignature;
//			}
//		}
		
		//return null;
	}

	public function updateRoles(array $roles, bool $save = true) {
		
		$userRoles = Ext_Thebing_Admin_Usergroup::getList();
		
		$schools = Ext_Thebing_Client::getSchoolList(true);
		
		$userRolesMapping = array_flip($userRoles);
		
		$userGroups = [];
		
		$return = [
			'found' => [],
			'not_found' => []
		];

		foreach($roles as $role) {
			
			if(isset($userRolesMapping[trim($role)])) {
				foreach($schools as $schoolId=>$schoolName) {
					$userGroups[] = [
						'group_id' => $userRolesMapping[trim($role)],
						'school_id' => $schoolId
					];
				}
				$return['found'][] = $role;
			} else {
				$return['not_found'][] = $role;
			}
			// Nur erste Rolle berücksichtigen. Man kann nicht mehr als eine Rolle beachten.
			break;
		}

		$this->usergroups = $userGroups;

		$userCategoryId = $this->getUserCategory();
		
		if(!in_array($userCategoryId, $this->system_types)) {
			$this->system_types = array_merge($this->system_types, [$userCategoryId]);
		}

		if ($save) {
			$this->save();
		}
		
		return $return;
	}

	public function addSchool(\Ext_Thebing_School $school, array $roles) {
		$userRoles = Ext_Thebing_Admin_Usergroup::getList();
		$userRolesMapping = array_flip($userRoles);

		$userGroups = $this->usergroups;

		foreach($roles as $role) {
			if(!empty($groupId = $userRolesMapping[trim($role)])) {
				$found = \Illuminate\Support\Arr::first($userGroups, fn ($group) => $group['school_id'] == $school->id && $group['group_id'] == $groupId);
				if ($found === null) {
					$userGroups[] = [
						'group_id' => $groupId,
						'school_id' => $school->id
					];
				}
			}
		}

		$this->usergroups = $userGroups;

		return $this;
	}

	protected function getUserCategory() {
				
		$sqlQuery = " 
			SELECT 
				`tc_ec`.`id`
			FROM 
				`tc_system_type_mapping` `tc_ec` INNER JOIN	
				`tc_system_type_mapping_to_system_types` `tc_ectf` ON
					`tc_ec`.`id` = `tc_ectf`.`mapping_id`
			WHERE 
				`tc_ec`.`active` = 1 AND
				`tc_ectf`.`type` = 'user'
		";
		
		$categoryId = \DB::getQueryOne($sqlQuery);
		
		return $categoryId;
	}

	public function getCommunicationSenderName(string $channel, CommunicationSubObject $subObject = null): string
	{
		if ($subObject instanceof \Ext_Thebing_School) {
			return sprintf('%s - %s %s', $subObject->getCommunicationSenderName($channel, $subObject), $this->firstname, $this->lastname);
		}

		return $this->getName();
	}

	public function getCommunicationEmailAccount(CommunicationSubObject $subObject = null): ?\Ext_TC_Communication_EmailAccount
	{
		if ($subObject instanceof \Ext_Thebing_School) {
			\Ext_Thebing_Mail::$oSchool = $subObject;
		}

		return \Ext_Thebing_Mail::g(oUser: $this);
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		if ($driver === 'database' && !$this->hasSystemType('user')) {
			return null;
		}

		return parent::routeNotificationFor($driver, $notification);
	}
}
