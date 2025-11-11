<?php

use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Model\CommunicationSender;
use Communication\Interfaces\Model\CommunicationSubObject;
use Tc\Traits;

class Ext_TC_User extends User implements CommunicationContact, CommunicationSender {

	use Traits\Flexibility,
		Traits\Placeholder,
		Traits\Entity\HasSystemTypes;

	const MAPPING_TYPE = 'users';

	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_User';

	// ID of current OFFICE or SCHOOL for ACCESS CHECK
	public static $iAccessGroupForeignId = 0;
	// LIST of all OFFICE/SCHOOLs for the Dialog Select
	public static $aAccessGroupForeignOptions = array(0 => 'alle');

	protected $_aJoinTables = array(
		'groups' => array(
			'table' => 'tc_system_user_to_groups',
			'foreign_key_field'=>array('group_id', 'foreign_id'),
			'primary_key_field'=>'user_id',
	 		'check_active'=>true
		),
		'accessJoin' => array(
			'table' => 'tc_system_user_to_access',
			'foreign_key_field' => array('access_id', 'status'),
			'primary_key_field' => 'user_id'
		),
		'communication_emailaccounts' => array(
			'table' => 'tc_system_user_to_communication_emailaccounts',
			'foreign_key_field' => 'account_id',
			'primary_key_field' => 'user_id'
		),
		'email_identities' => array(
			'table' => 'tc_system_user_to_identities',
			'foreign_key_field' => 'identity_id',
			'primary_key_field' => 'user_id'
		),
		'export' => array(
			'table' => 'tc_system_user_export',
			'foreign_key_field' => array('csv_separator', 'csv_charset'),
			'primary_key_field' => 'user_id'
		),
		'system_types' => [
			'table' => 'tc_employees_to_categories',
			'foreign_key_field' => 'category_id',
			'primary_key_field' => 'employee_id',
			'class' => \Tc\Entity\SystemTypeMapping::class,
			'autoload' => false
		],
		'devices' => [
			'table' => 'system_user_devices',
			'foreign_key_field' => ['device_id', 'last_login', 'created'],
			'primary_key_field' => 'user_id',
			'autoload' => false
		]
	);

	protected $_aJoinedObjects = [
		'signatures' => [
			'class' => 'Ext_TC_User_Signature',
			'key' => 'user_id',
			'type' => 'child'
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
	 * Get an Array List of all active User
	 * @return array 
	 */
	public static function getList($bOnlyMasterUser=false) {

		$sSql = "
			SELECT 
				*
			FROM
				`system_user`
			WHERE
				`active` = 1";
		
		if($bOnlyMasterUser === true) {
			$sSql .= " AND 
				`access` = 'admin' 
				";
		}
		
		$aUsers = DB::getQueryData($sSql);

		return $aUsers;

	}
	

	/**
	 * If the Password was set with an empty value, dont change it!
	 */
	public function  __set($sName, $sValue) {

		if(
			$sName === 'send_email_account'
		) {
			if(is_numeric($sValue)) {
				$this->tc_send_email_type = 'account';
				$this->communication_emailaccounts = array((int)$sValue);
			} else {
				$this->tc_send_email_type = $sValue;
			}
		}elseif(
			$sName == 'csv_separator' ||
			$sName == 'csv_charset'
		){
			$aExport = (array)$this->export;
			if(!empty($aExport)){
				$aExport = reset($aExport);
			}
			
			$aExport[$sName] = $sValue;
			$this->export = array($aExport);
		} else {
			parent::__set($sName, $sValue);
		}

	}

	/**
	 * If you will get the Password than you get an empty string ( needed for Dialogs )
	 * If you will get the Name, it will be formated with the Username format Class
	 */
	public function  __get($sName) {
		
		$aDummy = array();
		
		if($sName == 'name') {
			if($this->_aData['id'] > 0) {
				$oFormat = new Ext_Gui2_View_Format_UserName();
				$sValue = $oFormat->format('', $aDummy, $this->_aData);
			} else {
				$sValue = '';
			}
		} elseif(
			$sName === 'send_email_account'
		) {
			if($this->tc_send_email_type === 'account') {
				$sValue = reset($this->communication_emailaccounts);
			} else {
				$sValue = $this->tc_send_email_type;
			}
		}elseif(
			$sName == 'csv_separator' ||
			$sName == 'csv_charset'
		){		
			$aExport = $this->export;		
			if(!empty($aExport)){
				$aExport = reset($aExport);
				$sKey = $aExport[$sName];
			}
		
			return $sKey;
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}

	public function getEmailSignatureContent(string $contentType, string $language, \Communication\Interfaces\Model\CommunicationSubObject $subObject): string
	{
		$signatureTexts = $subObject->communication_emailsignatures;

		$signature = '';

		foreach ((array)$signatureTexts as $signatureText) {
			if ($signatureText['language_iso'] === $language) {
				if($contentType === 'html') {
					$signature = $signatureText['html'];
				} else {
					$signature = $signatureText['text'];
				}
				break;
			}
		}

		return $signature;
	}

	public function getSignatureForObject(\WDBasic $oObject) {
		
		$aSignatures = $this->getJoinedObjectChilds('signatures', true);
		
		foreach($aSignatures as $oSignature) {
			if($oSignature->object_id == $oObject->getId()) {
				return $oSignature;
			}
		}
		
		return null;
	}
	
	/**
	 * Liefert den Arrayschlüssel für den Separator
	 * @return string 
	 */
	public function getSeparatorForExport(){ 
		$aExport = $this->export;
		$sKey = '';
		if(!empty($aExport)){
			$aExport = reset($aExport);
			$sKey = $aExport['csv_separator'];
		}
	
		// Wenn kein Trennzeichen gespeichert ist, dann das der Agentur nehmen
		if(empty($sKey)){
			$oConfig = \Factory::getInstance('Ext_TC_Config');
			$sKey = $oConfig->getValue('csv_separator');
		}
		
		// Wenn kein Trennzeichen gefunden wird, wird der Standard genommen
		$sSeparator = Ext_TC_Export::getSeparator($sKey);
		
		return $sSeparator;
	}
	
	/**
	 * Liefert den Zeichensatz für den CSV Export
	 * @return string 
	 */
	public function getCharsetForExport(){
		$aExport = $this->export;
		$sCharset = '';
		if(!empty($aExport)){
			$aExport = reset($aExport);
			$sCharset = $aExport['csv_charset'];
		}

		// Wenn kein Trennzeichen gespeichert ist, dann das der Agentur nehmen
		if(empty($sCharset)){
			$oConfig = \Factory::getInstance('Ext_TC_Config');
			$sCharset = $oConfig->getValue('csv_charset');
		}
		
		// Wenn kein Zeichensatz gefunden wurde, dann wird der Standard genommen
		if(empty($sCharset)){
			$sCharset = 'CP1252';
		}
		
		return $sCharset;
	}

	/**
	 * Get the Group Tab for the Current System and User Dialog
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 * @return Ext_Gui2_Dialog_Tab
	 */
	public static function getGroupTab($oGui, $oDialog){
		
		$oTab = $oDialog->createTab($oGui->t('Benutzerrollen'));
		$oTab->access = array('core_admin_user', 'group');;

		$aGroups = (new Ext_TC_User_Group())->getArrayList(true);
		
		$aForeignOptions = self::$aAccessGroupForeignOptions;

        $oInnerGui = $oGui->createChildGui(md5('tc_admin_user_office'), \Ext_TC_Gui2_Data::class);

		#$oInnerGui						= new Ext_TC_Gui2(md5('tc_admin_user_office'));
		$oInnerGui->gui_description		= Factory::executeStatic(\Ext_TC_System_Navigation::class, 'tp');
		$oInnerGui->gui_title			= $oGui->t('Benutzergruppen');
		$oInnerGui->access				= array('core_admin_user', 'group');
		$oInnerGui->multiple_selection	= 0;
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'tc_sutg';
		$oInnerGui->foreign_key			= 'user_id';
		$oInnerGui->foreign_key_alias	= 'tc_sutg';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->setWDBasic(\Ext_TC_User_ToGroup::class);
				
		$oDialog = $oInnerGui->createDialog($oInnerGui->t('Benutzergruppe {name} editieren'), $oInnerGui->t('Neue Benutzergruppe erstellen'));
		$oDialog->save_as_new_button  = true;
		$oDialog->save_bar_options   = true;
		$oDialog->save_bar_default_option = 'open';
		$oDialog->setElement($oDialog->createRow($oInnerGui->t('Gruppe'), 'select', array('db_column' => 'group_id', 'select_options' => $aGroups, 'required' => 1)));
		$oDialog->setElement($oDialog->createRow($oInnerGui->t('Gültig für'), 'select', array('db_column' => 'foreign_id', 'select_options' => $aForeignOptions)));
		
		// Buttons
		$oBar			= $oInnerGui->createBar();
		$oIcon			= $oBar->createNewIcon($oInnerGui->t('Neuer Eintrag'), $oDialog, $oInnerGui->t('Neuer Eintrag'));
		$oIcon->access = array('core_admin_user', 'group');
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($oInnerGui->t('Editieren'), $oDialog, $oInnerGui->t('Editieren'));
		$oIcon->access = array('core_admin_user', 'group');
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($oInnerGui->t('Löschen'), $oInnerGui->t('Löschen'));
		$oIcon->access = array('core_admin_user', 'group');
		$oBar->setElement($oIcon);
		$oInnerGui->setBar($oBar);


		// Paginator
		$oBar			= $oInnerGui->createBar();
		$oBar->position	= 'top';
		$oPagination	= $oBar->createPagination();
		$oBar->setElement($oPagination);
		$oLoading		= $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oInnerGui->setBar($oBar);

		//Spalten
		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'group_id';
		$oColumn->db_alias		= 'tc_sutg';
		$oColumn->title			= $oInnerGui->t('Gruppe');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oColumn->format		= new Ext_TC_User_Format_Group();
		$oInnerGui->setColumn($oColumn);
		
		//Spalten
		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'foreign_id';
		$oColumn->db_alias		= 'tc_sutg';
		$oColumn->title			= $oInnerGui->t('Gültig für');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oColumn->format		= new Ext_Gui2_View_Format_Selection($aForeignOptions);
		$oInnerGui->setColumn($oColumn);
				
		$oInnerGui->addDefaultColumns();

		$oTab->setElement($oInnerGui);

		return $oTab;
		
	}
	
	/**
	 * Check if the User have Access for the given Section and Access
	 * If not the Methode DIE
	 * @param string $sSectionKey
	 * @param string $sAccessKey 
	 */
	public static function accesschecker($sSectionKey, $sAccessKey = ''){
		$bAccess = self::hasRight($sSectionKey, $sAccessKey);
		if(!$bAccess){
			die();
		}
	}

	public function getUserGroups() {
		return $this->groups;
	}

	/**
	 * Check if the user have the usergroup x
	 * @global array $user_data
	 * @param int $iGroup
	 * @return boolean 
	 */
	public static function hasGroup($aCheckGroupIDs){

		$oAccess = Access_Backend::getInstance();
		$aUserData = $oAccess->getUserData();

		$oUser = Ext_TC_User::getInstance($aUserData['id']);
		$aHaveGroupIds = $oUser->groups;

		foreach((array)$aHaveGroupIds as $aGroup){
			if(in_array($aGroup['group_id'], $aCheckGroupIDs)){
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check if the User have Access for the given Section and Access
	 * @global type $user_data
	 * @param string $sSectionKey
	 * @param string $sAccessKey
	 * @return boolean 
	 */
	public static function hasRight($sSectionKey, $sAccessKey = ''){
		
		if(
			empty($sSectionKey) &&
			empty($sAccessKey)
		) {
			return true;
		}
		
		$oAccess = Access::getInstance();
	
		$bAccess = false;

		if(
			$oAccess instanceof Access_Backend &&
			$oAccess->checkValidAccess() === true
		) {
			$oUser = Ext_TC_User::getInstance($oAccess->id);
			$bAccess = $oUser->checkAccess($sSectionKey, $sAccessKey);
		}
		
		return $bAccess;
	}

	public function getIndividualAccess() {
		
		$aReturn = array();
		foreach($this->accessJoin as $aAccess) {
			$aReturn[$aAccess['access_id']] = (int)$aAccess['status'];
		}
		
		return $aReturn;
	}
	
	public static function resetAccessCache() {
		WDCache::deleteGroup('Ext_TC_User::getAccessData');
	}
	
	public function getAccessData($iForeignId=null) {

		$sCacheKey = 'Ext_TC_User::getAccessData-'.$this->id.'-'.$iForeignId;
		
		$aAccessData = WDCache::get($sCacheKey);

		if($aAccessData === null) {

			$aAccessData = array();

			/*
			 * Gruppenrechte
			 */
			$aGroups = (array)$this->groups;

			foreach($aGroups as $aGroup) {
				
				// Wenn die Gruppe einem Office/Schule zugeordnet ist
				if(
					!empty($iForeignId) &&
					$aGroup['foreign_id'] > 0 &&
					$aGroup['foreign_id'] != $iForeignId
				) {
					continue;
				}

				$oGroup = Ext_TC_User_Group::getInstance($aGroup['group_id']);
				$aGroupAccess = $oGroup->access;

				foreach($aGroupAccess as $iGroupAccessId) {
					$aAccessData[$iGroupAccessId] = 1;
				}

			}
		
			/*
			 * Individuelle Rechte
			 */
			$aIndividualAccess = $this->getIndividualAccess();
			foreach($aIndividualAccess as $iIndividualAccessId=>$iStatus) {
				if($iStatus === 1) {
					$aAccessData[$iIndividualAccessId] = 1;
				} elseif(
					$iStatus === 0 &&
					isset($aAccessData[$iIndividualAccessId])
				) {
					unset($aAccessData[$iIndividualAccessId]);
				}
			}

			WDCache::set($sCacheKey, 7*24*60*60, $aAccessData, false, 'Ext_TC_User::getAccessData');

		}
		
		return $aAccessData;
	}
	
	/**
	 * Check if the User have Access for the given Section and Access
	 * @param string $sSectionKey
	 * @param string $sAccessKey
	 * @return boolean 
	 */
	public function checkAccess($sSectionKey, $sAccessKey = '') {
		
		// Gibt es das Recht überhaupt in der Lizenz?
		$bAccessExists = Ext_TC_Access::hasRight($sSectionKey, $sAccessKey);

		if($bAccessExists !== true) {
			return false;
		}

		$oAccess = Access::getInstance();

		// Wenn das Framework-Recht "admin" vergeben ist, darf man alles.
		if(
			$oAccess->checkValidAccess() === true &&
			$oAccess->id == $this->id &&
			$oAccess->hasRight('admin')
		) {
			return true;
		}
		
		$aAccessData = $this->getAccessData(self::$iAccessGroupForeignId);
 
		$aAccessSectionRepository = \Ext_TC_Access_Section::getRepository();
		
		$aCheckAccessIds = array();
		
		if(!empty($sAccessKey)) {
			$iAccessId = $aAccessSectionRepository->getAccessId($sSectionKey, $sAccessKey);
			$aCheckAccessIds[$iAccessId] = $iAccessId;
		} else {
			$aCheckAccessIds = $aAccessSectionRepository->getAccessIdsforSection($sSectionKey);
		}

		$bAccess = false;

		foreach($aCheckAccessIds as $iCheckAccessId) {
			if(isset($aAccessData[$iCheckAccessId])) {
				$bAccess = true;
				break;
			}
		}

		// Ansonsten gibt es das recht und die gruppe hat zurgiff! => true
		return $bAccess;
	}
	
	/**
	 * Check if one of The Group of the current User have access
	 * 
	 * @deprecated Wird nicht mehr verwendet, da zentral die Userrechte ermittelt werden
	 * 
	 * @param string $sSectionKey
	 * @param string $sAccessKey
	 * @return boolean 
	 */
	public function checkGroupAccess($sSectionKey, $sAccessKey) {

		$bAccess = false;

		$aGroups = $this->groups;
		// Alle Gruppen durchlaufen
		foreach((array)$aGroups as $aGroup) {
			
			// Wenn die Gruppe einem Office/Schule zugeordnet ist
			if(
				$aGroup['foreign_id'] > 0 &&
				self::$iAccessGroupForeignId > 0 &&
				$aGroup['foreign_id'] != self::$iAccessGroupForeignId
			) {
				continue;
			}

			$oGroup = Ext_TC_User_Group::getInstance($aGroup['group_id']);
			$bAccess = $oGroup->checkAccess($sSectionKey, $sAccessKey);
			
			if($bAccess) {
				break;
			}
		}

		return $bAccess;
	}
	
	/**
	 * get all Access IDs from all groups of the current User
	 * @return type 
	 */
	public function getGroupAccessData(){
		$aList = array();
		$aGroups = $this->groups;
		foreach((array)$aGroups as $aGroup){
			$oGroup = Ext_TC_User_Group::getInstance($aGroup['group_id']);
			$aGroupAccess = $oGroup->access;
			
			foreach((array)$aGroupAccess as $iAccess){
				$aList[] = $iAccess;
			}
		}
		return $aList;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ",
			IF(`su`.`blocked_until` > NOW(), 1, 0) AS `blocked`,
			IF(`su`.`access` = 'admin', 1, 0) `master`
		";

		$bDebugIp = \Ext_TC_Util::isDebugIP();

		if($bDebugIp !== true) {
			foreach (config('app.intern.emails.domains') as $domain) {
				$aSqlParts['where'] .= " AND email NOT LIKE '%".$domain."%'";
			}
		}

	}

	public static function getSelectOptions($bAddEmptyItem = false)
	{
		$oFormat = new Ext_Gui2_View_Format_UserName;
		$aUsers = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getUsers', [false]);
		$aReturn = array();

		foreach($aUsers as $aUser) {
			$aReturn[$aUser['id']] = $oFormat->format($aUser['id']);
		}
		
		if($bAddEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn, L10N::t('Bitte wählen'));
		}
		
		asort($aReturn);
		return $aReturn;
		
	}
	
	/**
	 * Liefert die Identitäten eines Nutzers
	 * 
	 * @return array
	 */
	public function getIdentities(string $channel, $bForSelect = false, $bIncludeUser = false, CommunicationSubObject $subObject = null) {

		$identities = $this->email_identities;

		if($bIncludeUser && !in_array($this->id, $identities)) {
			$identities[] = (int)$this->id;
		}

		$users = static::query()
			->where('status', 1)
			->findMany($identities)
			->mapWithKeys(function($user) use ($bForSelect) {
				return [$user->id => $bForSelect ? $user->name : $user];
			});

		if ($bForSelect) {
			return $users->sort()->toArray();
		}

		return $users->toArray();
	}

	public static function getListWithGroups() {
		
		$sSql = "
			SELECT
				`su`.`id` AS `user_id`,
				`su`.`firstname`,
				`su`.`lastname`,
				GROUP_CONCAT(DISTINCT `tc_sutg`.`group_id` SEPARATOR '|') AS `groups`
			FROM
				`system_user` AS `su` LEFT OUTER JOIN
				`tc_system_user_to_groups` AS `tc_sutg` ON
					`su`.`id` = `tc_sutg`.`user_id`
			WHERE
				`su`.`active` = 1
			GROUP BY
				`su`.`id`
			ORDER BY
				`su`.`lastname`,
				`su`.`firstname`
		";
		$aSql = array(
		);
		$aUsers = DB::getPreparedQueryData($sSql, $aSql);
		
		return $aUsers;
		
	}

	public static function getMasterUser($bOnlyIds=true) {
		
		$aMasterUsers = Ext_TC_User::getList(true);
		$aMaster = array();
		foreach($aMasterUsers as $aMasterUser) {
			if($bOnlyIds === true) {
				$aMaster[] = $aMasterUser['id'];
			} else {
				$aMaster[] = $aMasterUser;
			}
		}

		return $aMaster;
		
	}

	/**
	 * Gibt die Benutzerrechte zurück
	 *
	 * @return array
	 */
	public function getUserRights() {
		return [];
	}

	public static function getAvailableFunctions() {
		return [];
	}

	public static function getArrayByFunction($function) {
		
		$sqlQuery = " 
			SELECT 
				`su`.`id`, 
				CONCAT(`su`.`lastname`, ', ', `su`.`firstname`) `user_name`
			FROM 
				`system_user` `su` INNER JOIN 
				`tc_employees_to_categories` `tc_etc` ON 
					`su`.`id` = `tc_etc`.`employee_id` INNER JOIN	
				`tc_system_type_mapping_to_system_types` `tc_ectf` ON
					`tc_etc`.`category_id` = `tc_ectf`.`mapping_id`
			WHERE 
				`su`.`active` = 1 AND
				`tc_ectf`.`type` = :function
			GROUP BY
				`su`.`id`
			ORDER BY
				`su`.`lastname`, 
				`su`.`firstname`
		";
		
		$sqlParam = [
			'function' => $function
		];

		$aResult = (array)\DB::getQueryPairs($sqlQuery, $sqlParam);

		return $aResult;
	}

	protected function getEntityTypeForSystemTypes(): string {
		return self::MAPPING_TYPE;
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes($channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'mail' => collect([
				[$this->email, $this->getCommunicationName($channel)]
			]),
			default => null,
		};
	}

	public function getCorrespondenceLanguages(): array
	{
		return [];
	}

	public function getCommunicationSenderName(string $channel, CommunicationSubObject $subObject = null): string
	{
		return $this->getName();
	}

	public function getCommunicationEmailAccount(CommunicationSubObject $subObject = null): ?\Ext_TC_Communication_EmailAccount
	{
		// TODO
		return null;
	}
}
