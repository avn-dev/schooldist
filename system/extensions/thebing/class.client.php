<?php

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property string $name
 * @property string $licence
 * @property string $timezone
 * @property int $execution_time_index_refreshing
 * @property int $execution_time_accommodation_provider_payments
 * @property int $execution_time_depuration
 * @property int $customernumber_start
 * @property string $customernumber_format
 * @property int $customernumber_digits
 * @property int $customernumber_offset
 * @property int $customernumber_for_schools
 * @property int $show_customer_without_invoice
 * @property string $synergee_email
 * @property string $synergee_pop3_server
 * @property string $synergee_username
 * @property string $synergee_password
 * @property int $synergee_flex_course
 * @property int $synergee_flex_accommodation
 * @property int $insurance_price_method
 * @property int $inquiry_payments_receipt
 * @property int $inquiry_payments_invoice
 * @property int $inquiry_payments_overview
 * @property int $inquiry_payments_creditnote_receipt
 * @property int $inquiry_payments_creditnote
 * @property int $inquiry_payments_creditnote_overview
 * @property int $flex_user_based
 * @property int $depuration_logs
 * @property int $depuration_backuptables
 * @property string $system_color
 * @property int $execution_time_statistics_update
 */
class Ext_Thebing_Client extends Ext_Thebing_Basic {

	const BOOKING_AUTO_CONFIRM_NO = 0;
	const BOOKING_AUTO_CONFIRM_ALL = 1;
	const BOOKING_AUTO_CONFIRM_ONLY_SYSTEM = 2;

	protected $_aItem = array(
		'id'=>0
	);
	protected $_sTable = 'kolumbus_clients';

	protected $_aFormat = [
		'created' => [
			'format' => 'TIMESTAMP'
		],
		'changed' => [
			'format' => 'TIMESTAMP'
		]
	];
	protected $_sOrderby = 'name';

	static protected $aSchoolListCache = array();
	static protected $aCheckSchoolCache = array();

	// Cache array for users
	protected static $_aUsers;

	// @todo: Tabelle clients_configs erstellen
	protected $_aConfigFields = array(
		'show_customer_without_invoice',
		'accounting',
		'execution_time_index_refreshing',
		'execution_time_depuration'
	);

	protected $_oLicense = null;

	protected static $aCache = [];

	/**
	 * @inheritdoc
	 */
	public function __get($sName) {

		// licence-DB-Spalte ist obsolet
		if($sName === 'licence') {
			// Wird noch in der Ext_Thebing_Access_Client benutzt
			return System::d('license');
		}

		// Ext_TC_Basic wegen Registry überspringen
		return WDBasic::__get($sName);
	}

	/**
	 * Returns the instance of an object by data ID
	 *
	 * @param int : The data ID
	 * @param string : The name of the table
	 * @return Ext_Thebing_Client
	 */
	static public function getInstance($iDataID=null) {

		// Alten Schrott am Leben erhalten
		if($iDataID === null) {
			$oClient = self::getFirstClient();
			$iDataID = $oClient->id;
		}

		return parent::getInstance($iDataID);

	}

	/**
	 * Nur verwenden, wenn wirklich das Objekt benötigt wird!
	 * @internal
	 *
	 * Liefert den ersten (und einzigen) Mandanten
	 * @return Ext_Thebing_Client
	 */
	public static function getFirstClient() {

		if(!isset(self::$aCache['getFirstClient'])) {

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_clients`
				WHERE
					`active` = 1
			";

			self::$aCache['getFirstClient'] = DB::getQueryRows($sSql);

			// Hier darf eigentlich nur noch ein Ergebnis rauskommen!
			if(count(self::$aCache['getFirstClient']) > 1) {
				Ext_TC_Util::reportError('Ext_Thebing_Client::getFirstClient() call: There is more than one client!');
			}
		}

		$oClient = self::getObjectFromArray(self::$aCache['getFirstClient'][0]);

		return $oClient;
	}

	/**
	 * @return int
	 */
	public static function getClientId() {

		return (int)System::d('ts_client_id');

	}

	public function validate($bThrowExceptions = false)
	{
		// TODO Zu helle Farben rausfiltern
		//if(str_starts_with(strtolower($this->system_color), '#f')) {
		//	return ['system_color' => ['TOO_BRIGHT_COLOR']];
		//}

		return parent::validate($bThrowExceptions);
	}

	/**
	 * Get all categories from all agencies by Client-ID
	 */
	public function getAgenciesCategoriesList($bAsObjects = false, $iTest = 0)
	{
		$aCategories = array();

		$aAgencies = $this->getAgencies();

		foreach((array)$aAgencies as $aAgency)
		{
			// Hier muss immer mit getInstance bearbeitet werden, da es Schulen mit >5k Agenturen gibt!
			$oCategory	= Ext_Thebing_Agency_Category::getInstance($aAgency['ext_39']);

			if($bAsObjects){
				$aCategories[] = $oCategory;
			}else{
				if($oCategory->id > 0)
				{
					$aCategories[$oCategory->id] = $oCategory->name;
				}
			}

		}

		if(!$bAsObjects){
			asort($aCategories);
		}

		return $aCategories;
	}

	/**
	 * Get all countries from all agencies by Client-ID
	 */
	public function getAgenciesCountriesList()
	{
		global $system_data;

		$sLang = 'cn_short_' . $system_data['sLanguage'];

		$aCountries = $aShorts = array();

		$aAgencies = $this->getAgencies();

		foreach((array)$aAgencies as $aAgency)
		{
			$aShorts[] = "'" . $aAgency['ext_6'] . "'";
		}

		$aShorts = array_unique($aShorts);

		if(!empty($aShorts))
		{
			$sSQL = "
				SELECT
					`id`,
					#sLangName
				FROM
					`data_countries`
				WHERE
					`cn_iso_2` IN (" . implode(',', $aShorts) . ")
				ORDER BY
					#sLangName
			";
			$aSQL = array('sLangName' => $sLang);
			$aCountries = DB::getQueryPairs($sSQL, $aSQL);
		}

		return $aCountries;
	}

	public function getAllSchoolsCurrencies(){
		$aCurrencies = array();

		$aSchools = $this->getSchoolList();
		foreach((array)$aSchools as $aSchool)
		{
			$oSchool	= Ext_Thebing_School::getInstance($aSchool['id']);
			$aSchoolCurrencys = $oSchool->getSchoolCurrencyList();
			$aCurrencies = $aCurrencies + $aSchoolCurrencys;
		}

		$aCurrencies = array_unique($aCurrencies);

		return $aCurrencies;;
	}

	public function getCourseCategories() {

		return Ext_Thebing_Tuition_Course_Category::query()
			->orderBy('ktcc.position')
			->pluck('name_'.$this->getLanguage(), 'id')
			->all();
	}

	public function getLevels() {

		return Ext_Thebing_Tuition_Level::query()
			->where('ktul.type', 'internal')
			->orderBy('ktul.position')
			->orderBy('ktul.id')
			->pluck('name_'.$this->getLanguage(), 'id')
			->all();
	}

	public function getCourseLanguages() {

		return Ext_Thebing_Tuition_LevelGroup::query()
			->orderBy('ktlg.position')
			->pluck('name_'.$this->getLanguage(), 'id')
			->all();
	}

	public function getTeachers($forSelect = false) {

		$schools = $this->getSchoolListByAccess();

		$schoolIds = [];
		foreach ($schools as $school) {
			$schoolIds[] = $school['id'];
		}

		$teachers = Ext_Thebing_Teacher::query()
			->select('kt.*')
			// keine Abfrage nach NULL, weil es das nicht gibt (date-Typ in der Datenbank)
			->where(function ($query) {
				$query
					->where('kt.valid_until', '0000-00-00')
					->orWhereRaw('`kt`.`valid_until` >= CURDATE()');
			})
			->join('ts_teachers_to_schools as ts_tts', function($join) use($schoolIds) {
				$join->on('ts_tts.teacher_id', '=', 'kt.id')
					->whereIn('ts_tts.school_id', $schoolIds);
			})
			->orderBy('kt.lastname')
			->get();

		if(!$forSelect)
		{
			return $teachers;
		}

		$oFormat = new Ext_Gui2_View_Format_Name();

		return $teachers
			->mapWithKeys(function (Ext_Thebing_Teacher $oTeacher) use ($oFormat) {
				return [$oTeacher->id => $oFormat->formatByResult($oTeacher->getData())];
			})
			->toArray();
	}


	/**
	 * Get all currencies from all schools by Client-ID
	 */
	public function getSchoolsCurrencies($bIso = false)
	{
		$aCurrencies = array();

		$aSchools = $this->getSchoolList();

		foreach((array)$aSchools as $aSchool)
		{
			$oSchool	= Ext_Thebing_School::getInstance($aSchool['id']);
			$aCurr = $oSchool->getSchoolCurrencyList($bIso);
			$aCurrencies = $aCurr + $aCurrencies;

		}

		return $aCurrencies;
	}

	public function getReasons()
	{

		$aReasons = array();

		$oReason	= new Ext_Thebing_Admin_Reason();
		$aQueryData = $oReason->getListQueryData();
		$aQueryData['data']['client_id'] = \Ext_Thebing_Client::getClientId();
		$aQueryData['sql'] = str_replace('WHERE', 'WHERE `client_id` = :client_id AND', $aQueryData['sql']);

		$aResult = DB::getPreparedQueryData($aQueryData['sql'], $aQueryData['data']);

		foreach((array)$aResult as $aData){
			$aReasons[$aData['id']] = $aData['name'];
		}

		return $aReasons;
	}

	public function getAgenciesOfCountry($mCountry){
		$sSql = " SELECT * FROM `ts_companies` WHERE `type` & ".\TsCompany\Entity\AbstractCompany::TYPE_AGENCY." AND ext_6 = :country AND idClient = :idClient AND active = 1 ";
		$aSql = array('country'=>$mCountry,'idClient'=>$this->id);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		return $aResult;
	}

	public static function checkSchool($iSchoolId, $iClientId=false) {
		global $user_data;

		if($iClientId === false) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
			$iClientId = $oSchool->idClient;
		}

		// $user_data['client'] ist in request dateien nicht vorhanden !! ( wird beim laden der Tabs gesetzt , in requestdateien gibt es das aber nichT!)
		if(
			$iClientId != $user_data['client'] &&
			$iSchoolId > 0 &&
			$user_data['client'] > 0
		) {
			throw new Exception('No right to access school '.$iSchoolId.'!');
		}

		return true;

	}

	public function getSchools($bForSelect = false) {
		return self::getSchoolList($bForSelect, $this->id);
	}

	public static function getLangList($bLabels=false){

		$aSchools = self::getSchoolList();
		$aBack = array();
		foreach($aSchools as $aSchool){
			$oSchool = Ext_Thebing_School::getInstance($aSchool['id']);
			$aLangs = $oSchool->getLanguageList($bLabels);
			foreach($aLangs as $sKey => $sLang){
				$aBack[$sKey] = $sLang;
			}
		}

		return $aBack;

	}

	/**
	 * @return array
	 */
	public static function getLanguages() {
		return self::getLangList(true);
	}

	/**
	 * @param string $sAccess
	 * @return Ext_Thebing_School
	 */
	public static function getFirstSchool($sAccess = ''){

		$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		// Wenn Schule gewählt gebe diese zurück
		if(
			$iSchoolId > 0 && (
				$sAccess == '' ||
				Ext_Thebing_Access::hasRight($sAccess, $iSchoolId)
			)
		) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);

			return $oSchool;
		}

		// Sonst nehme die erste Schule auf die man Zugriff hat
		$aSchools = self::getSchoolList(false, 0, true);

		foreach((array)$aSchools as $oSchool){
			if(
				$sAccess == '' ||
				Ext_Thebing_Access::hasRight($sAccess, $oSchool->id)
			){
				return $oSchool;
			}
		}

		return false;

	}

	/**
	 * @TODO Überprüfen, wie oft die Methode in einem Request verwendet wird und ggf. statischen Cache einbauen
	 *
	 * @param bool $bForSelect
	 * @param int $iClient
	 * @param bool $bAsObjects
	 * @return Ext_Thebing_School[]|string[]|array
	 */
	public static function getSchoolList($bForSelect = false, $iClient = 0, $bAsObjects = false, $bShort=false) {

		if(
			$iClient instanceof \Ext_Gui2 || # Sonderfall für Verwendung in der GUI (pragmatisch, nicht schön)
			empty($iClient)
		) {
			$oClient = Ext_Thebing_Client::getFirstClient();
			$iClient = $oClient->id;
		}

		$bForSelect = (bool)$bForSelect;
		$iClient = (int)$iClient;
		$bAsObjects = (bool)$bAsObjects;

		if($iClient <= 0) {
			return [];
		}

		$cacheKey = $iClient.'_'.$bShort;

		if(empty(self::$aSchoolListCache[$cacheKey])) {

			if($bShort) {
				$sOrderBy = "`position`, `short`";
			} else {
				$sOrderBy = "`position`, `ext_1`";
			}

			$sSql = "
				SELECT
					*
				FROM
					`customer_db_2`
				WHERE
					idClient = :client_id AND
					active = 1
				ORDER BY
					{$sOrderBy}
				";
			$aSql = array(
				'client_id'=>(int)$iClient
			);

			$aResult = DB::getPreparedQueryData($sSql,$aSql);
			self::$aSchoolListCache[$cacheKey] = $aResult;
		} else {
			$aResult = self::$aSchoolListCache[$cacheKey];
		}


		$aBack = array();
		if($bForSelect) {

			foreach((array)$aResult as $aData){
				if($bShort) {
					$aBack[$aData['id']] = $aData['short'];
				} else {
					$aBack[$aData['id']] = $aData['ext_1'];
				}
			}
		} elseif($bAsObjects){
			// Objecte
			foreach((array)$aResult as $aData){
				$aBack[] = Ext_Thebing_School::getObjectFromArray($aData);
			}
		} else {
			$aBack = $aResult;
		}

		return $aBack;
	}

	public static function getStaticSchoolListByAccess($bCheckInboxAccess = false, $bForSelect=true, $bAsObjects = false){
		$oClient					= Ext_Thebing_System::getClient();
		$aSchools					= $oClient->getSchoolListByAccess($bForSelect, $bAsObjects, $bCheckInboxAccess);
		return $aSchools;
	}

	/**
	 * liefert die erste Schule auf die man Zugriff hat
	 *
	 * @return Ext_Thebing_School|boolean
	 */
	public static function getFirstSchoolWithAccess() {

		$aSchools = self::getStaticSchoolListByAccess(false, false, true);

		if(!empty($aSchools)) {
			$oSchool = reset($aSchools);
			return $oSchool;
		}

		return false;
	}

	/**
	 * @param bool $bForSelect
	 * @param bool $bAsObjects
	 * @param bool $bCheckInboxAccess
	 * @return Ext_Thebing_School[]|mixed
	 */
	public function getSchoolListByAccess($bForSelect=false, $bAsObjects=false, $bCheckInboxAccess = false) {

		$oAccess = Access::getInstance();

		if(
			!$oAccess instanceof Access_Backend ||
			$oAccess->checkValidAccess() !== true
		) {
			return [];
		}

		$iClientId		= (int)$this->id;

		$aSchoolList	= self::getSchoolList($bForSelect, $iClientId, $bAsObjects);
		$oAccess		= new Ext_Thebing_Access();

		// Hier muss immer ein Eintrag vorhanden sein, sonst funktioniert die komische Abfrage unten nicht
		$aInboxList		= array(array('id' => 0));

		if($bCheckInboxAccess)
		{
			$oClient	= Ext_Thebing_System::getClient();

			$aInboxList	= $oClient->getInboxList();
		}

		$iCountInbox	= count($aInboxList);

		$aAccessSchools = array();

		foreach($aSchoolList as $mKey => $mSchool)
		{
			if(
				$bForSelect
			){
				$iSchoolId = $mKey;
			}elseif(
				is_object($mSchool) &&
				$mSchool instanceof Ext_Thebing_School
			){
				$iSchoolId = $mSchool->id;
			}elseif(
				is_array($mSchool)
			){
				$iSchoolId = $mSchool['id'];
			}else{
				continue;
			}

			$iSchoolRights = $oAccess->countSchoolRights($iSchoolId);

			if($iSchoolRights > 0){

				// Inboxrechte zählen für die Schule, wenn nichts mehr übrig bleibt die Schule auch nicht anzeigen
				$iCountInboxRights = $iCountInbox;

				foreach($aInboxList as $aInboxData)
				{
					if($aInboxData['id'] > 0)
					{
						$sRightKey = 'thebing_invoice_inbox_' . $aInboxData['id'];

						if(!Ext_Thebing_Access::hasRight($sRightKey, $iSchoolId))
						{
							$iCountInboxRights--;
						}
					}
				}

				if($iCountInboxRights > 0)
				{
					if($bForSelect){
						$aAccessSchools[$mKey] = $mSchool;
					}else{
						//für den unwahrscheinlichen fall, dass man mit dem ergebnis eine "for" schleife bilden will :)
						$aAccessSchools[] = $mSchool;
					}
				}

			}
		}

		return $aAccessSchools;
	}

	public function searchSchoolByName($sName) {

		$sSql = "
				SELECT 
					`id`
				FROM 
					`customer_db_2` 
				WHERE 
					idClient = :idClient AND 
					`ext_1` LIKE :name AND
					active = 1
				";
		$aSql = array(
			'idClient'=>(int)$this->id,
			'name'=>'%'.$sName.'%'
		);

		$aSchool = DB::getQueryRow($sSql,$aSql);

		if(!empty($aSchool)) {
			$oSchool = Ext_Thebing_School::getInstance($aSchool['id']);
			return $oSchool;
		}

		return false;

	}

//	public function getMasterUser() {
//
//		$sSql = "SELECT
//					 su.*
//				FROM
//					`system_user` su JOIN
//					`kolumbus_clients_users` kcu ON
//						 su.id = kcu.user_id
//				WHERE
//					`kcu`.`idClient` = :idClient AND
//					`master` = 1
//				LIMIT 1";
//		$aSql['idClient'] = $this->id;
//		$aResult = DB::getPreparedQueryData($sSql,$aSql);
//
//		return $aResult[0];
//
//	}

	public function getMaterialOrderUsers() {

		$sSql = "SELECT
					 * 
				FROM 
					`system_user` su JOIN
					`kolumbus_clients_users` kcu ON
						 su.id = kcu.user_id
				WHERE
					`kcu`.`idClient` = :idClient AND
					`thebing_material_orders` = 1
				";
		$aSql['idClient'] = $this->id;
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult;

	}

	public function getEmailFrom() {
		global $user_data;

		// TODO: Hier muss die Schule übergeben werden
		$oUser = Ext_Thebing_User::getInstance($user_data['id']);
		if($oUser->getSchoolSetting(0)['use_setting'] == 1) {
			$sFrom = ''.$oUser->firstname.' '.$oUser->lastname.' <'.$oUser->email.'>';
		} else {

			$oUserRepo = Ext_Thebing_User::getRepository(); /** @var Ext_Thebing_UserRepository $oUserRepo */
			$aMasterUsers = $oUserRepo->getMasterUsers();

			// Hier wurde früher schon der erstbeste Master-User benutzt
			$oUser = reset($aMasterUsers);
			$sFrom = ''.$this->name.' <'.$oUser->email.'>';

		}

		$sFrom = "From: ".$sFrom;

		return $sFrom;

	}

	/**
	 * Liefert eine Liste mit allen Inboxen
	 *
	 * @param bool|string $bPrepareForSelect
	 * @param bool $bCheckAccess
	 * @param bool $bCheckStatus
	 * @return array
	 */
	public function getInboxList($bPrepareForSelect = false, $bCheckAccess = false, $bCheckStatus = false) {

		$aBack = array();

		$sSql = "
				SELECT
					*
				FROM
					`kolumbus_inboxlist`
				WHERE
					active = 1
					";

		if($bCheckStatus) {
			$sSql .= " AND status = 1 ";
		}

		$sSql .= " ORDER BY position, id ";

		$aResult = DB::getPreparedQueryData($sSql, []);

		foreach($aResult as $aRowData) {

			$sRightKey = 'thebing_invoice_inbox_' . $aRowData['id'];

			if(
				!$bCheckAccess ||
				(
					$bCheckAccess &&
					Ext_Thebing_Access::hasRight($sRightKey)
				)
			)
			{
				if($bPrepareForSelect)
				{
					if(is_string($bPrepareForSelect) && $bPrepareForSelect == 'use_id')
					{
						$mKey	= $aRowData['id'];
					}
					else
					{
						$mKey	= $aRowData['short'];
					}

					$mData	= $aRowData['name'];
				}
				else
				{
					$mKey	= $aRowData['id'];
					$mData	= $aRowData;
				}

				$aBack[$mKey] = $mData;
			}
		}

		if(
			// Bei Rechteprüfung keine Exception werfen (wegen Inbox-Filter in 100en GUIs usw.)
			!$bCheckAccess &&
			empty($aBack)
		) {
			throw new RuntimeException('No inboxes found for client!');
		}

//		if(empty($aResult)){
//			$aBack = array(0=>array('id'=>0,'name'=>'','short'=>''));
//		} else {
//			if(!$bPrepareForSelect){
//				foreach($aResult as $aData){
//					$aBack[$aData['id']] = $aData;
//				}
//			}else{
//				foreach($aResult as $aData){
//					$aBack[$aData['short']] = $aData['name'];
//				}
//
//				if(empty($aBack)){
//					// Ganz wichtig Anfragen umwandeln
//					$aBack = Ext_Thebing_Util::addEmptyItem($aBack);
//				}
//
//			}
//		}

		return $aBack;
	}

	/**
	 * Prüft, ob der Client echte Inboxen benutzt oder keine angelegt hat
	 *
	 * @deprecated
	 * @return bool
	 */
	public function checkUsingOfInboxes() {

		$aInboxes = $this->getInboxList();

		// Wenn nur eine Inbox angelegt ist, wird das Feature nicht benutzt
		if(count($aInboxes) < 2) {
			return false;
		}

		$aFirstInbox = reset($aInboxes);
		$bReturn = false;

		if($aFirstInbox['id'] > 0) {
			$bReturn = true;
		}

		return $bReturn;
	}

	public function getUsers($bPrepareSelect=true, $bWithDisabled=true) {

		if(empty(self::$_aUsers[$bPrepareSelect]))
		{
			$oUser = new Ext_Thebing_User();
			$aQueryData = $oUser->getListQueryData();

			$aUsers = DB::getPreparedQueryData($aQueryData['sql'], $aQueryData['data']);

			if(!$bWithDisabled) {
				$aUsers = array_filter($aUsers, function($aUser) {
					return $aUser['status'] != 0;
				});
			}

			if($bPrepareSelect) {

				$aReturn = array();

				$oFormat = new Ext_Gui2_View_Format_UserName();
				foreach((array)$aUsers as $aUser) {
					$aReturn[$aUser['id']] = $oFormat->format('', $aDummy, $aUser);
				}

				self::$_aUsers[$bPrepareSelect] = $aReturn;

			} else {
				self::$_aUsers[$bPrepareSelect] = $aUsers;
			}
		}

		return self::$_aUsers[$bPrepareSelect];
	}

	public function getFilePath($bDocumentRoot = true, $bWithSecureMedia = true){

		$sOrdner = "";
		if($bDocumentRoot == true){
			$sOrdner .= Util::getDocumentRoot(false);
		}
		$sOrdner .= "/storage";
		if($bWithSecureMedia !== true){
			$sOrdner .= "/public";
		}
		$sOrdner .= "/clients";
		$sOrdner .= "/client_".$this->id;
		$sOrdner .= "/";

		return $sOrdner;

	}

	/*
	 * Liefert alle Provisionsgruppen des Clients
	 */
	public function getProvisionGroups($bForSelect = false){

		$sSql = "SELECT
						*
					FROM
						`ts_commission_categories`
					WHERE
						`active` = 1
					ORDER BY
						`position` ASC
					";
		$aSql = array();

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bForSelect){
			$aBack = array();
			foreach((array)$aResult as $aData){
				$aBack[$aData['id']] = $aData['name'];
			}
			return $aBack;
		}else{
			$aBack = array();
			foreach((array)$aResult as $aData){
				$aBack[] = Ext_Thebing_Util::convertDataIntoObject($aData, 'Ext_Thebing_Provision_Group');
			}
			return $aBack;
		}
	}

	public function getAgencies($bForSelect = false, $bShort = false, $bInUse = false, Ext_Thebing_School $school=null): array {

		$sNameField = $bShort ? 'ext_2' : 'ext_1';

		$oQuery = Ext_Thebing_Agency::query()
			->select('ka.*')
			->where('status', 1)
			->orderBy($sNameField);

		if ($bInUse) {
			$oQuery->whereIn('id', function (\Core\Database\Query\Builder $oQuery) {
				$oQuery->select('agency_id')
					->from('ts_inquiries')
					->where('active', '=', 1);
			});
		}

		if($school !== null) {
			$oQuery->leftJoin('ts_agencies_to_schools as schools', 'schools.agency_id', 'ka.id')
				->where(
					function($query) use ($school) {
						$query->where('schools.school_id', '=', $school->id)
							->orWhere('ka.schools_limited', '=', 0);
					}
				);
		}

		$aAgencies = $oQuery->get();

		if ($bForSelect) {
			return $aAgencies->mapWithKeys(fn(Ext_Thebing_Agency $oAgency) => [$oAgency->id => $oAgency->{$sNameField}])->toArray();
		}

		return $aAgencies->transform(fn(Ext_Thebing_Agency $oAgency) => $oAgency->getData())->toArray();

	}

	/*
	 * Liefert alle Agenturlisten dieses Clients zurück
	 */
	public function getAgencyLists($bForSelect = false){

		$query = \Ext_Thebing_Agency_List::query();

		if ($bForSelect) {
			$collection = $query->pluck('name', 'id');
		} else {
			$collection = $query->get();
		}

		// TODO ->toArray() entfernen
		return $collection->toArray();
	}

	/**
	 * Liefert alle Cronjob E-Mail Layouts zu diesem Client
	 */
	public function getCronjobLayouts(){

		$sSql = "SELECT
						*
					FROM
						`tc_communication_automatictemplates`
					WHERE
						`active` = 1
				";
		$aSql = array();

		$aLayouts = DB::getPreparedQueryData($sSql, $aSql);
		$aBack = array();
		foreach((array)$aLayouts as $aData){
			$aBack[] = Ext_Thebing_Email_TemplateCronjob::getInstance($aData['id']);
		}

		return $aBack;
	}

	public function getFonts() {

		$aFonts = array();

		$aFonts['courier'] = 'Courier';
		$aFonts['dejavusans'] = 'DejaVuSans';
		$aFonts['dejavuserif'] = 'DejaVuSerif';
		$aFonts['freemono'] = 'FreeMono';
		$aFonts['freesans'] = 'FreeSans';
		$aFonts['freeserif'] = 'FreeSerif';
		$aFonts['helvetica'] = 'Helvetica';
		$aFonts['times'] = 'Times New Roman';

		// Read individuell fonts
		$aFontsCustom	= $this->getCustomFonts();
		$aFonts	+= $aFontsCustom;

		return $aFonts;
	}

	/**
	 *
	 * @todo caching
	 * @param <type> $bForSelect
	 * @return <type>
	 */
	public function getCustomFonts($bForSelect=true) {

		$sSql = "
				SELECT
					*
				FROM
					`tc_fonts`
				WHERE
					`active` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql);
		if($bForSelect) {
			$aBack = array();
			foreach($aResult as $aRowData) {
				$sName					= $aRowData['name'];
				$aBack[$aRowData['id']] = $sName;
			}
		} else {
			$aBack = $aResult;
		}

		return $aBack;
	}

	/**
	 * Holt alle hochgeladenen Schulfiles eines Types und einer/oder aller Sprachen
	 */
	public function getUploads(int $iType, $aLanguages = null, $bOnlySecurePath = false, $bNoGlobalFiles = false) {

		$uploads = \Ext_Thebing_Upload_File::query()
			->select('tc_u.*')
			->join('tc_upload_languages as tc_ul', function ($join) use ($aLanguages) {
				$join->on('tc_ul.upload_id', '=', 'tc_u.id');
				if($aLanguages !== null) {
					$join->whereIn('tc_ul.language_iso', \Illuminate\Support\Arr::wrap($aLanguages));
				}
			})
			->where('tc_u.category', $iType)
			->where('tc_u.filename', '!=', '')
			->where('tc_u.description', '!=', '')
			->get();

		$list = [];
		foreach($uploads as $upload) {

			$file = \Ext_Thebing_Upload_File::buildPath($upload->filename);

			if (is_file($file)) {
				if ($bOnlySecurePath) {
					$file = '/uploads/'. $upload->filename;
				}

				$list[$upload->id] = [
					'id' => $upload->id,
					'object' => $upload,
					'path' => $file,
					'file' => $upload->filename,
					'description' => $upload->description,
				];
			}
		}

		return $list;
	}

	// Alle Agenturgruppen des clients
	public function getAgencyGroups($bForSelect = false){

		$sSql = "SELECT
						`id`,
						`name`
					FROM
						`kolumbus_agency_groups`
					WHERE
						`active` = 1
			";
		$aSql = array();

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$aBack = array();
		foreach((array)$aResult as $aData){
			if($bForSelect){
				$aBack[$aData['id']] = $aData['name'];
			}else{
				$aBack[] = Ext_Thebing_Agency_Group::getInstance($aData['id']);
			}
		}

		return $aBack;
	}

	/**
	 * @todo: clients_config table erstellen
	 * @param <string> $sConfigKey
	 * @return <mixed>
	 */
	public function getConfig($sConfigKey)
	{
		$aConfigs = (array)$this->_aConfigFields;
		if(in_array($sConfigKey,$aConfigs))
		{
			return $this->$sConfigKey;
		}

		return false;
	}

	/**
	 * Array mit Schulen
	 * @param type $bSelect
	 */
	public static function getSubObjects($bSelect) {

		$aObjects = self::getSchoolList($bSelect);

		return $aObjects;

	}

	/**
	 * Schule
	 * @return type
	 */
	public static function getSubObjectLabel(bool $bPlural=true) {

		if($bPlural === true) {
			$sLabel = L10N::t('Schulen');
		} else {
			$sLabel = L10N::t('Schule');
		}

		return $sLabel;

	}

	/**
	 * Liefert die Monate nach denen eine Backuptabelle gelöscht werden kann
	 *
	 * @return int
	 */
	public static function getBackupTablesDepurationMonth() {
		$oClient = self::getInstance();

		return (int) $oClient->depuration_backuptables;
	}

	/**
	 * Lizenz Objekt setter injection, wird benötigt beim unit-test
	 *
	 * @param Object $oLicense
	 */
	public function setLicense($oLicense)
	{
		$this->_oLicense = $oLicense;
	}

	/**
	 * @inheritdoc
	 */
//	public static function getObjectFromArray(array $aData) {
//
//		try {
//			return parent::getObjectFromArray($aData);
//		} catch(UnexpectedValueException $e) {
//			// Da der Client überall geholt wird, würde eine neue Spalte zum totalen Absturz der Software führen
//			WDCache::delete('wdbasic_table_description_kolumbus_clients');
//			__out('Caught UnexpectedValueException from getObjectFromArray() - Cache cleared, reload!');
//			throw $e;
//		}
//
//	}

	/**
	 * Fix P32-E-Mail zurückliefern, da es keinen Konfigurationswert dafür in der Schulsoftware gibt
	 *
	 * @return string
	 */
	public function getErrorEmailAddress() {
		return 'tc_senderrormessage@p32.de';
	}

	/**
	 * @return boolean
	 */
	static public function hasStudentApp() {

		$fcmServerKey = $serverKey = \System::d('fcm_server_key');

		if(empty($fcmServerKey)) {
			return false;
		}

		return true;
	}

	public static function getCommunicationName() {
		return self::getFirstClient()->name;
	}

	public static function getSystemTypes(string $entityType) {

		return match ($entityType) {
			\Ext_TC_User::MAPPING_TYPE => \Factory::executeStatic(Ext_TC_User::class, 'getAvailableFunctions')
		};

	}

	static public function getAdditionalServices($type, \Ext_Thebing_School $school=null, $optional=true, $chargeProvider=false, $language=null, $forSelect=false, $checkValid = true) {

		if($language === null) {
			$language = Ext_Thebing_School::fetchInterfaceLanguage();
		}

		$cacheKey = __METHOD__.'_'.$type.'_'.$optional.'_'.$chargeProvider.'_'.$language.'_'.$checkValid;

		if($school === null) {
			$school = Ext_Thebing_School::getSchoolFromSession();
			if(!$school->exist()) {
				$school = null;
			}
		}

		if($school !== null) {
			$cacheKey .= '_'.$school->id;
		}

		$result = WDCache::get($cacheKey);

		if($result === null) {

			$where = $join = $keySelect = "";
			$sqlData = [];

			if($school !== null) {
				$where .= " AND
					`kc`.`idSchool` = :school_id ";
				$sqlData['school_id'] = (int)$school->id;
			}

			switch($type) {
				case 'course':
					$join = ' LEFT JOIN `kolumbus_costs_courses` `kcc` ON `kc`.`id` = `kcc`.`kolumbus_costs_id`';
					$keySelect = 'GROUP_CONCAT(`kcc`.`customer_db_3_id`) `keys`';
					$sqlData['type'] = Ext_Thebing_School_Additionalcost::TYPE_COURSE;
					break;
				case 'accommodation':
					$join = ' LEFT JOIN `kolumbus_costs_accommodations` `kca` ON `kc`.`id` = `kca`.`kolumbus_costs_id`';
					$keySelect = "GROUP_CONCAT(CONCAT_WS('_', `kca`.`customer_db_8_id`, `kca`.`roomtype_id`, `kca`.`meal_id`)) `keys`";
					$sqlData['type'] = Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION;
					break;
				default:
					throw new RuntimeException('Unknow additional service type "'.$type.'".');
			}

			if($optional === true) {
				$where .= " AND
					`kc`.`charge` = 'semi' ";
			} elseif($optional === false) {
				$where .= " AND
					`kc`.`charge` = 'auto' ";
			}

			if($chargeProvider === true) {
				$where .= " AND
					(
						`kc`.`credit_provider` = ".(int)Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ALL." OR 
						`kc`.`credit_provider` = ".(int)Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ONLY_PROVIDER."
					) ";
			} elseif($chargeProvider === false) {
				$where .= " AND
					(
						`kc`.`credit_provider` = ".(int)Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ALL." OR 
						`kc`.`credit_provider` = 0
					) ";
			} else {

			}

			if ($checkValid) {
				$where .= " AND (
						`kc`.`valid_until` = '0000-00-00' OR
						`kc`.`valid_until` >= CURDATE()
					)";
			} else {
				// Beim Student Record, da wird im JS nach valid_until geschaut, weil da nach dem Kursenddatum geschaut wird,
				// wenn vorhanden. Deswegen auch deaktivierte Zusatzleistungen holen und mit valid_until natürlich
				// -> sonst könnte man keine Vergangenheits-Buchung buchen, mit Zusatzleistungen, die damals aktiv waren.
				$keySelect .= ", `kc`.`valid_until`";
			}

			$sqlQuery = "
				SELECT
					`kc`.`id`,
					`kc`.#name_field `name`,
					`kc`.`credit_provider`,
					`kc`.`charge`,
					".$keySelect."
				FROM
					`kolumbus_costs` `kc`
					".$join."
				WHERE
					`kc`.`type` = :type AND
					`kc`.`active` = 1
					".$where."
				GROUP BY
					`kc`.`id`
				ORDER BY
					`kc`.#name_field
				";

			$sqlData['name_field'] = 'name_'.$language;

			$result = (array)DB::getQueryRows($sqlQuery, $sqlData);

			array_walk($result, function(&$item) {
				$item['keys'] = explode(',', $item['keys']);
			});

			WDCache::set($cacheKey, (24*60*60), $result, false, Ext_Thebing_School::ADDITIONAL_SERVICES_CACHE_GROUP);

		}

		if($forSelect) {
			$result = \Illuminate\Support\Arr::pluck($result, 'name', 'id');
		}

		return $result;
	}

	/**
	 * True, wenn Rechnungen unveränderlich sind.
	 * Definiert in config.php.
	 *
	 * @return bool
	 */
	public static function immutableInvoicesForced(): bool
	{
		return defined('IMMUTABLE_INVOICES') && IMMUTABLE_INVOICES;
	}

	/**
	 * True, wenn Rechnungen als Entwürfe in allen Schulen erzwungen werden.
	 *
	 * @return bool
	 */
	public static function draftInvoicesForced(): bool
	{
		return self::immutableInvoicesForced();
	}

}
