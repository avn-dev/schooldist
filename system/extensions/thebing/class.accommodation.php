<?php

/**
 * Class Ext_Thebing_Accommodation
 *
 * @property string $bank_account_iban
 * @property string $bank_account_bic
 *
 */

use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use TsAccommodation\Service\CheckRequirement;

class Ext_Thebing_Accommodation extends Ext_Thebing_Basic implements \TsPrivacy\Interfaces\Entity, HasCommunication, CommunicationContact {

	use \Ts\Traits\Numberrange,
		\Ts\Traits\BankAccount,
		\Tc\Traits\Placeholder,
		\Admin\Traits\GravatarTrait,
		\Illuminate\Notifications\RoutesNotifications;
	
	/**
	 * @var string
	 */
	protected $_sTable = 'customer_db_4';

	/**
	 * @var string
	 */
    protected $_sTableAlias = 'customer_db_4';

	/**
	 * TODO #9834 - wird das überhaupt verwendet?
	 *
	 * @var mixed
	 */
    protected $_aSaisons = false;

	protected $sNumberrangeClass = 'Ext_TS_Numberrange_Accommodation';

	/**
	 * @var mixed[]
	 */
	protected $_aFormat = [
		'email'	=> [
			'validate' =>['MAIL', 'UNIQUE']
		],
		'bank_account_iban' => [
			'validate' => 'IBAN'
		],
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'rooms' => [
			'table' => 'kolumbus_rooms',
			'class' => 'Ext_Thebing_Accommodation_Room',
			'primary_key_field' => 'accommodation_id',
			'autoload' => true, // soll in den Query eingebaut werden!
			'check_active' => true,
			'query' => true,
			'readonly' => true // Es gibt hierfür auch ein JoinedObject
		],
		'meals' => [
			'table' => 'ts_accommodation_providers_to_accommodation_meals',
			'class' => 'Ext_Thebing_Accommodation_Meal',
			'primary_key_field' => 'accommodation_provider_id',
			'foreign_key_field' => 'meal_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		// TODO: Tabelle umbenennen (Weist man die Provider in der Schule zu?)
		'schools' => [
			'table' => 'ts_accommodation_providers_schools',
			'class' => 'Ext_Thebing_School',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'accommodation_provider_id',
			'on_delete' => 'no_action'
		],
		// TODO: Tabelle umbenennen (Weist man die Provider in der Kategorie zu?)
		'accommodation_categories' => [
			'table' => 'ts_accommodation_categories_to_accommodation_providers',
			'class' => 'Ext_Thebing_Accommodation_Category',
			'foreign_key_field' => 'accommodation_category_id',
			'primary_key_field' => 'accommodation_provider_id',
			'on_delete' => 'no_action'
		],
		'numbers' => [
			'table' => 'ts_accommodations_numbers',
			'foreign_key_field' => array('number', 'numberrange_id'),
			'primary_key_field' => 'accommodation_id',
			'autoload' => true,
			'on_delete' => 'no_action'
		],
		'members' => [
			'table' => 'ts_accommodation_providers_to_contacts',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'accommodation_provider_id',
			'class' => '\TsAccommodation\Entity\Member',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'contracts' => [ // Purge
			'table' => 'kolumbus_contracts',
			'primary_key_field' => 'item_id',
			'autoload' => false,
			'check_active' => true,
			'static_key_fields' => [
				'item' => 'accommodations'
			],
			'on_delete' => 'no_action'
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinedObjects = [
		'salary' => [
			'class' => 'Ext_Thebing_Accommodation_Salary',
			'key' => 'accommodation_id',
			'check_active' => true,
			'type' => 'child',
		],
		'billing_terms' => [ // Purge
			'class' => \Ts\Entity\AccommodationProvider\Payment\Category\Validity::class,
			'key' => 'provider_id',
			'check_active' => true,
			'type' => 'child',
		],
		'payments' => [
			'class' => 'Ext_Thebing_Accommodation_Payment',
			'key' => 'accommodation_id',
			'check_active' => true,
			'type' => 'child',
		],
		'rooms' => [
			'class' => 'Ext_Thebing_Accommodation_Room',
			'key' => 'accommodation_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		],
		'payed_payment_groupings' => [
			'class' => 'Ext_TS_Accounting_Provider_Grouping_Accommodation',
			'key' => 'accommodation_id',
			'type' => 'child',
			'check_active' => true,
		],
		// Notes
		'visits' => [ // Purge
			'class' => 'Ext_Thebing_Accommodation_Visit',
			'key' => 'acc_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		// Uploads + Pictures
		'uploads' => [ // Purge
			'class' => 'Ext_Thebing_Accommodation_Upload',
			'key' => 'accommodation_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'requirement_documents' => [
			'class' => '\TsAccommodation\Entity\Requirement\Document',
			'key' => 'accommodation_provider_id',
			'check_active' => true,
			'type' => 'child',
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'accommodation_providers_bank' => [],
		'accommodation_providers_general' => [],
		'accommodation_providers_info' => [],
	];

	protected $_sPlaceholderClass = \TsAccommodation\Service\AccommodationPlaceholder::class;

	public function getMembers(): array
	{
		return $this->getJoinTableObjects('members');
	}

	public function getMembersWithEmail(): array
	{
		return array_filter(
			$this->getMembers(),
			fn (\TsAccommodation\Entity\Member $member) => !empty($member->email) && \Util::checkEmailMx($member->email)
		);
	}

	/**
	 * @param bool $bEmptyItem
	 * @return array $aReturn
	 */
	public static function getSelectOptions($bEmptyItem = true) {
		
		$aList = self::getRepository()->findAll();
		/** @var Ext_Thebing_Accommodation[] $aList */

		if(!Ext_Thebing_System::isAllSchools()) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aList = array_filter(
				$aList,
				function(Ext_Thebing_Accommodation $oAccommodationProvider) use ($oSchool) {
					return $oAccommodationProvider->belongsToSchool($oSchool);
				}
			);
		}

		$aReturn = [];
		foreach($aList as $oAccommodationProvider) {
			$aReturn[$oAccommodationProvider->id] = $oAccommodationProvider->getName();
		}

		asort($aReturn);

		if($bEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}

		return $aReturn;

	}

	/**
	 * Gibt die Einträge mit den angegebenen IDs zurück.
	 *
	 * Die Keys sind die IDs der Einträge.
	 *
	 * @param int[] $aIds
	 * @return Ext_Thebing_Accommodation[]
	 */
	public static function getListByIds(array $aIds) {

		$aReturn = [];

		foreach($aIds as $iAccommodationProviderId) {
			$oAccommodationProvider = Ext_Thebing_Accommodation::getInstance($iAccommodationProviderId);
			$aReturn[$oAccommodationProvider->id] = $oAccommodationProvider;
		}

		return $aReturn;

	}

	/**
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation[]
	 */
	public static function getListBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`cdb4`.`id`
			FROM
				`customer_db_4` `cdb4`
			INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps`
			ON
				`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
				`ts_aps`.`school_id` = :school_id
			WHERE
				`cdb4`.`active` = 1
		";
		$aSql = [
			'school_id' => (int)$oSchool->id,
		];
		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		$aIds = array_map(
			function(array $aRow) {
				return $aRow['id'];
			},
			$aResult
		);

		return self::getListByIds($aIds, false);

	}
	
	public static function getListBySessionSchool() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		$sSql = "
			SELECT
				`cdb4`.`id`,
				`cdb4`.`ext_33`
			FROM
				`customer_db_4` `cdb4` INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `cdb4`.`id`
			WHERE
				`cdb4`.`active` = 1
		";
		
		$aSql = [];
		
		if($oSchool->exist()) {
			$sSql .= " AND `ts_aps`.`school_id` = :school_id";
			$aSql['school_id'] = (int)$oSchool->id;
		}
		
		$aResult = (array)DB::getQueryPairs($sSql, $aSql);

		return $aResult;
	}

	/**
	 * Alle Unterkunftsanbieter zurückliefern, welche eine der Transfer-Checkboxen aktiv haben.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return mixed[]
	 */
	public static function getAllProvidersWithTransferOption(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`cdb4`.`id`,
				`cdb4`.`ext_33`
			FROM
				`customer_db_4` `cdb4`
			INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps`
			ON
				`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
				`ts_aps`.`school_id` = :school_id
			WHERE
				`cdb4`.`active` = 1 AND (
					`cdb4`.`transfer_arrival` = 1 OR
					`cdb4`.`transfer_departure` = 1
				)
		";
		$aSql = [
			'school_id' => $oSchool->id,
		];
		$aResult = DB::getQueryPairs($sSql, $aSql);

		return $aResult;

	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);

		switch($sField) {
			case 'phone':
				$mValue = $this->ext_67;
				break;
			case 'phone2':
				$mValue = $this->ext_76;
				break;
			case 'mobile_phone':
				$mValue = $this->ext_77;
				break;
			case 'name':
				$mValue = $this->ext_33;
				break;
			case 'street':
				$mValue = $this->ext_63;
				break;
			case 'zip':
				$mValue = $this->ext_64;
				break;
			case 'city':
				$mValue = $this->ext_65;
				break;
			case 'state':
				$mValue = $this->ext_99;
				break;
			case 'country':
			case 'country_id':
				$mValue = $this->ext_66;
				break;
			case 'name_of_bank':
				$mValue = $this->ext_69;
				break;
			case 'adress_of_bank':
				$mValue = $this->ext_71;
				break;
			case 'account_number':
				$mValue = $this->ext_70;
				break;
			case 'account_holder':
				$mValue = $this->ext_68;
				break;
			case 'firstname':
				$mValue = $this->ext_103;
				break;
			case 'lastname':
				$mValue = $this->ext_104;
				break;
			case 'gender':
			case 'salutation':
				$mValue = $this->ext_105;
				break;
			case 'contact':
				$mValue = $this->ext_104.', ';
				$mValue .= $this->ext_103;
				break;
			case 'birthday':
			case 'additional_address':
				$mValue = '';
				break;
			case 'number':
				$mValue = $this->getNumber();
				break;
			default:
				$mValue = parent::__get($sField);
				break;
		}

		switch($sField) {
			case 'ext_52':
				$mValue = (array)json_decode($mValue);
				break;
		}

		return $mValue;

	}

	/**
	 * {@inheritdoc}
	 */
	public function __set($sField, $mValue){

		switch($sField) {
			case 'ext_52':
				$mValue = json_encode($mValue);
				break;
		}

		parent::__set($sField, $mValue);

	}

	/**
	 * @param string $sLang
	 * @param bool $bFormatted
	 * @return mixed[]|Ext_Thebing_Accommodation_Upload[]
	 */
	public function getUploadedPDFs($sLang = '', $bFormatted = true) {

		$aUploads = Ext_Thebing_Accommodation_Upload::getList($this->id, 'pdf');
		$aBack = [];

		foreach($aUploads as $oUpload) {

			if($sLang != '' && $sLang != $oUpload->lang) {
				continue;
			}

			if($oUpload->isFileExisting()) {
				if($bFormatted) {
					$sPath = str_replace(\Util::getDocumentRoot(), '', $oUpload->getPath());
					$aBack[$sPath] = $oUpload->filename;
				} else {
					$aBack[] = $oUpload;
				}
			}

		}

		return $aBack;
	}

	/**
	 * @param bool $bFormatted
	 * @param string $sReleasedColumn
	 * @return mixed[]|Ext_Thebing_Accommodation_Upload[]
	 */
	public function getUploadedImages($bFormatted = true, $sReleasedColumn = 'published') {

		$aUploads = Ext_Thebing_Accommodation_Upload::getList($this->id, 'picture', $sReleasedColumn);
		$aBack = [];

		foreach($aUploads as $oUpload) {

			if($oUpload->isFileExisting()) {
				if($bFormatted) {
					$aBack[$oUpload->getPath()] = $oUpload->filename;
				} else {
					$aBack[] = $oUpload;
				}
			}

		}

		return $aBack;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getListQueryData($oGui = null) {

		$sFormat = $this->_formatSelect();

		$aQueryData = [
			'data' => [],
			'sql' => '',
		];

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
			$sAliasString .= $sTableAlias.'.';
		}

		$aQueryData['sql'] = "
			SELECT
				".$sAliasString."*,
				`ts_appc`.`name` `payment_category`,
				`kacc`.`name` `cost_category`,
				`kas`.`costcategory_id`,
				`ts_aptam`.`meal_id`,
				`numbers`.`number`
				{FORMAT},
				GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`,
				GROUP_CONCAT(DISTINCT `accommodation_categories`.`accommodation_category_id`) AS `accommodation_categories`
			FROM
				`{TABLE}` ".$sTableAlias." LEFT JOIN
				`kolumbus_rooms` `rooms` ON
					`rooms`.`active` = 1 AND
					`rooms`.`accommodation_id` = ".$sAliasString."`id` LEFT JOIN
				`ts_accommodation_providers_payment_categories_validity` `ts_appcv` ON
					`ts_appcv`.`provider_id` = ".$sAliasString."`id` AND
					`ts_appcv`.`active` = 1 AND
					`ts_appcv`.`valid_until` = '0000-00-00' LEFT JOIN
				`ts_accommodation_providers_payment_categories` `ts_appc` ON
					`ts_appc`.`id` = `ts_appcv`.`category_id` AND
					`ts_appc`.`active` = 1 LEFT JOIN
				`kolumbus_accommodations_salaries` `kas` ON
					`kas`.`accommodation_id` = ".$sAliasString."`id` AND
					`kas`.`valid_until` = '0000-00-00' AND
					`kas`.`active` = 1 LEFT JOIN
				`kolumbus_accommodations_costs_categories` `kacc` ON
					`kacc`.`id` = `kas`.`costcategory_id` AND
					`kacc`.`active` = 1 LEFT JOIN
				`ts_accommodation_providers_to_accommodation_meals` `ts_aptam` ON
					`ts_aptam`.`accommodation_provider_id` = ".$sAliasString."`id` LEFT OUTER JOIN
				`ts_accommodation_providers_schools` `schools` ON
					`schools`.`accommodation_provider_id` = ".$sAliasString."`id` LEFT OUTER JOIN
				`ts_accommodation_providers_schools` `filter_schools` ON
					`filter_schools`.`accommodation_provider_id` = ".$sAliasString."`id` LEFT OUTER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `accommodation_categories` ON
					`accommodation_categories`.`accommodation_provider_id` = ".$sAliasString."`id` LEFT OUTER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `filter_accommodation_categories` ON
					`filter_accommodation_categories`.`accommodation_provider_id` = ".$sAliasString."`id` LEFT JOIN
				`ts_accommodations_numbers` `numbers` ON
					`numbers`.`accommodation_id` = ".$sTableAlias.".`id`
			WHERE
				".$sAliasString."`active` = 1
			GROUP BY
				".$sAliasString."`id`
			ORDER BY
				".$sAliasString."`id` ASC
		";

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	/**
	 * @return string
	 */
	public function createPassword() {

		$sPassword = \Util::generateRandomString(6);
		$this->password = md5($sPassword);
		$this->save();

		return $sPassword;

	}

	/**
	 * @return bool
	 */
	public function sendPassword() {

		$oClient = Ext_Thebing_Client::getFirstClient();

		$sPassword = $this->createPassword();

		$sSubject = L10N::t('family_send_password_subject');
		$sMessage = L10N::t('family_send_password_body');

		$sMessage = str_replace('{client}', $oClient->name, $sMessage);
		$sMessage = str_replace('{username}', $this->nickname, $sMessage);
		$sMessage = str_replace('{password}', $sPassword, $sMessage);

		$sFrom = $oClient->getEmailFrom();
		$bSuccess = wdmail($this->email, $sSubject, $sMessage, $sFrom);

		return $bSuccess;

	}

	/**
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getRoomList($bForSelect = false, $sLanguage = '') {

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sSql = "
			SELECT 
				`kr`.*,
				`kar`.#type_field `type`
			FROM
				`kolumbus_rooms` `kr`
			LEFT OUTER JOIN
				`kolumbus_accommodations_roomtypes` `kar`
			ON
				`kr`.`type_id` = `kar`.`id`
			WHERE
				`kr`.`accommodation_id` = :accommodation_id AND
				`kr`.`active` = 1
			ORDER BY
				`kr`.`name`
		";
		$aSql = [
			'accommodation_id' => $this->id,
			'type_field' => 'name_'.$sLanguage,
		];
		$aRooms = DB::getPreparedQueryData($sSql, $aSql);

		if(!$bForSelect) {
			return $aRooms;
		}

		$aBack = [];
		foreach((array)$aRooms as $aData) {
			$aBack[$aData['id']] = $aData['name'];
		}
		return $aBack;

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation provider can be assigned to multiple schools. '.Util::getBacktrace();
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation provider can be assigned to multiple schools. '.Util::getBacktrace();
		throw new LogicException($sMsg);

	}

	/**
	 * Gibt die Kommunikationssprache der Unterkunft (Schulsprache)
	 * @return <string>
	 */
	public function getLanguage() {
		
		$firstSchoolId = reset($this->schools);
		
		$school = Ext_Thebing_School::getInstance($firstSchoolId);
		$language = $school->getLanguage();
		
		return $language;
	}

	/**
	 * Name der Familie.
	 */
	public function getName($sLang = '') {
		return $this->_aData['ext_33'];
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getName();
	}

	public function getSalary($sDate) {

		$sSql = "
				SELECT
					*
				FROM
					`kolumbus_accommodations_salaries`
				WHERE
					`active` = 1 AND
					`accommodation_id` = :accommodation_id AND
					`valid_from` <= DATE(:date) AND
					(
						`valid_until` = '0000-00-00' OR
						`valid_until` >= DATE(:date)
					)
				LIMIT 1
					";
		$aSql = array('accommodation_id'=> (int)$this->id, 'date'=>$sDate);
		$aSalary = DB::getQueryRow($sSql, $aSql);
		return $aSalary;

	}

	/**
	 * @param string $sLanguage
	 * @return string
	 */
	public function getWayDescription($sLanguage) {
		$sKey = 'way_description_'.$sLanguage;
		return Ext_TC_Purifier::p($this->$sKey);
	}

	/**
	 * @param string $sLanguage
	 * @return string
	 */
	public function getFamilyDescription($sLanguage) {
		$sKey = 'family_description_'.$sLanguage;
		return Ext_TC_Purifier::p($this->$sKey);
	}
	
	/*
	 * Liefert die Kostenkategorien der Familie
	 * optional mit Zeitfilter zu einem Zeitpunkt
	 */
	public function getCostCategories($sFrom = '', $sUntil = ''){
		
		return Ext_Thebing_Costcategory::getCostCategories($this, $sFrom, $sUntil);
		
	}

	public function save($bLog = true) {

		// Pflichtfeld im Dialog und ohne das hier verschwindet der Anbieter
		if(empty($this->schools)) {
			throw new RuntimeException('JoinTable schools is empty!');
		}

		// Pflichtfeld im Dialog
		if(empty($this->accommodation_categories)) {
			throw new RuntimeException('JoinTable categories is empty!');
		}

		// Nummernkreis erzeugen
		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		// Im Unterkunftsportal ist "Land" ein Select, im Fidelodialog aber ein Textfeld, also Text in Key umwandeln, damit
		// die Formatklasse den Wert wieder formatieren kann (für das Unterkunftsportalfeld im Fidelodialog)
		$countries = Ext_Thebing_Country_Search::getLocalizedCountries(Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getInterfaceLanguage());
		$countryKey = array_search($this->ext_66, $countries);
		if ($countryKey !== false) {
			$this->ext_66 = $countryKey;
		}

		// TODO Funktioniert das im Cronjob mit getObjectFromArray() überhaupt?
		$aCategories = $this->_aJoinData['accommodation_categories'];
		$aOriginalCategories = $this->_aOriginalJoinData['accommodation_categories'];
		$aDiffCategories = array_diff($aCategories, $aOriginalCategories);

		System::wd()->executeHook('ts_accommodation_provider_save', $this);
		
		parent::save($bLog);

		// Muss nach dem save() passieren, da für den Status-Update die Werte in der DB geändert sein müssen.
		if(!empty($aDiffCategories)) {
			$this->updateRequirementStatus();
		}
		
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function purge($bAnonymize = false) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		if(!$bAnonymize) {
			$this->enablePurgeDelete();
		}

		// Mitglieder immer löschen
		/** @var \TsAccommodation\Entity\Member[] $aMembers */
		$aMembers = $this->getJoinTableObjects('members');
		foreach($aMembers as $oMember) {
			$oMember->purge(false);
		}

		// Notes / Visits immer löschen
		$aComments = $this->getJoinedObjectChilds('visits', false); /** @var Ext_Thebing_Accommodation_Visit[] $aComments */
		foreach($aComments as $oComment) {
			$oComment->enablePurgeDelete();
			$oComment->delete();
		}

		// Uploads immer löschen
		$aUploads = $this->getJoinedObjectChilds('uploads', false); /** @var Ext_Thebing_Accommodation_Upload[] $aUploads */
		foreach($aUploads as $oUpload) {
			$oUpload->enablePurgeDelete();
			$oUpload->delete();
		}

		/** @var \TsAccommodation\Entity\Requirement\Document[] $aRequirementDocuments */
		$aRequirementDocuments = $this->getJoinedObjectChilds('requirement_documents', false);
		foreach($aRequirementDocuments as $oRequirementDocument) {
			$oRequirementDocument->enablePurgeDelete();
			$oRequirementDocument->delete();
		}

		// E-Mails immer löschen
		$oMessageRepository = Ext_TC_Communication_Message::getRepository();
		$aLogs = $oMessageRepository->searchByEntityRelation($this);
		foreach($aLogs as $oLog) {
			$oLog->enablePurgeDelete();
			$oLog->delete();
		}

		if(!$bAnonymize) {
			$this->delete();
		} else {

			$this->ext_33 = 'Anonym '.ucfirst(strtolower(Util::generateRandomString(8, ['no_numbers' => true])));
			$this->ext_63 = ''; // Adresse
			$this->address_addon = '';
			$this->ext_105 = ''; // Salutation
			$this->ext_103 = ''; // Firstname
			$this->ext_104 = ''; // Lastname
			$this->ext_67 = ''; // Phone
			$this->ext_76 = ''; // Phone 2
			$this->ext_101 = ''; // Fax
			$this->ext_77 = ''; // Mobile phone
			$this->ext_78 = ''; // Skype
			$this->email = '';

			$this->ext_68 = ''; // Account holder
			$this->ext_70 = ''; // Account number
			$this->ext_71 = ''; // Bank code
			$this->ext_69 = ''; // Bank
			$this->ext_72 = ''; // Bank address
			$this->bank_account_iban = '';
			$this->bank_account_bic = '';

			$this->anonymized = 1;
			$this->save();

		}

	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeLabel() {
		return L10N::t('Unterkunftsanbieter', \TsPrivacy\Service\Notification::TRANSLATION_PATH);
	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeSettings() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return [
			'action' => $oClient->privacy_provider_action,
			'quantity' => $oClient->privacy_provider_quantity,
			'unit' => $oClient->privacy_provider_unit,
			'basedon' => 'valid_until'
		];
	}

	/**
	 * Nicht unterstützt, kann mehreren Kategorien zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 * @return Ext_Thebing_Accommodation_Category
	 */
	public function getCategory() {

		$sMsg = 'An accommodation provider can be assigned to multiple categories.';
		throw new LogicException($sMsg);

	}

	/**
	 * Liste mit allen der Unterkunft zugeordneten Kategorien.
	 *
	 * Die Einträge sind nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @return Ext_Thebing_Accommodation_Category[]
	 */
	public function getCategories() {
		return $this->getJoinTableObjects('accommodation_categories');
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);
		
		if(
			$aErrors === true
		)
		{
			$aErrors = array();
		}

		// Wenn Eintrag gelöscht wird: valid_until auf 1970 setzen, damit wirklich alles geprüft wird
		$sValidUntil = $this->valid_until;
		if($this->active == 0) {
			$sValidUntil = '1970-01-01';
		}

		$aInvalidMatchingEntries = Ext_Thebing_Accommodation_Allocation::getInvalidEntries($sValidUntil, $this->id);
		
		if(
			!empty($aInvalidMatchingEntries)
		){
			$aErrors['valid_until'][] = 'ALLOCATIONS_EXISTS';
		}
		
		if(empty($aErrors))
		{
			$aErrors = true;
		}
		
		return $aErrors;
	}

	/**
	 * Liefert die formatierte Mailadresse für die Kommunikation.
	 *
	 * @return string 
	 */
	public function getEmailformatForCommunication(){
		return $this->name.' (' . $this->email . ')';
	}

	/**
	 * Liefert alle bezahlten Gruppierungseinträge dieses Unterkunftsanbieters.
	 *
	 * @return Ext_TS_Accounting_Provider_Grouping_Accommodation[]
	 */
	public function getPayedPaymentGroupings() {
		return (array)$this->getJoinedObjectChilds('payed_payment_groupings');
	}

	/**
	 * @return string
	 */
	public function getContactInfo() {

		$oSalutation = new Ext_TC_GUI2_Format_Salutation();
		$iSalutation = $this->salutation;
		$sSalutation = $oSalutation->format($iSalutation);

		$sInfo = '';
		$sInfo .= $sSalutation;
		$sInfo .= ' ';
		$sInfo .= $this->firstname;
		$sInfo .= ' ';
		$sInfo .= $this->lastname;

		return $sInfo;

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @return bool
	 */
	public function belongsToSchool(Ext_Thebing_School $oSchool) {

		$aSchoolIds = $this->getJoinTableData('schools');
		return in_array($oSchool->id, $aSchoolIds);

	}

	/**
	 * @return Ext_Thebing_Inquiry_Document_Numberrange|null
	 */
	public static function getDocumentNumberrangeObject() {

		$oNumberrange = null;
		$oConfig = new Ext_TS_Config();
		$iNumberrangeId = (int)$oConfig->getValue('ts_accommodations_documents_numbers');

		if($iNumberrangeId != 0) {
			$oTmpNumberRange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberrangeId);
			if($oTmpNumberRange->exist()) {
				$oNumberrange = $oTmpNumberRange;
			}
		}

		return $oNumberrange;

	}

	/**
	 * Aktualisiert den Voraussetzung-Status des Unterkunftsanbieters
	 *
	 * save() muss selber aufgerufen werden!
	 *
	 * @return bool
	 */
	public function updateRequirementStatus() {

		// Kein getOriginalData, da das nicht von getObjectFromArray befüllt wird
		$iMissing = (int)$this->requirement_missing;
		$iExpired = (int)$this->requirement_expired;

		$this->requirement_missing = 0;
		$this->requirement_expired = 0;

		$aRequirements = \TsAccommodation\Entity\Requirement::getRepository()->findByAccommodation($this);

		foreach($aRequirements as $oRequirement) {

			$oCheckRequirement = new CheckRequirement($this, $oRequirement);
			$oCheckRequirement->check();

			if($oCheckRequirement->hasMissingDocument() === true) {
				$this->requirement_missing = 1;
			}

			if($oCheckRequirement->hasExpiredDocument() === true) {
				$this->requirement_expired = 1;
			}

		}

		if(
			$iMissing !== $this->requirement_missing ||
			$iExpired !== $this->requirement_expired
		) {

			if(!$this->exist()) {
				throw new RuntimeException('Update requirement status only allowed with existing accommodations.');
			}

			$aUpdate = [
				'changed' => $this->changed,
				'requirement_missing' => $this->requirement_missing,
				'requirement_expired' => $this->requirement_expired
			];
			$this->_oDb->update($this->_sTable, $aUpdate, ['id'=>$this->id]);

			return true;
		}

		return false;

	}

	/**
	 * @param $dEnd
	 * @return bool
	 */
	public function checkRequirementValidationDate($dEnd) {

		$aRequirements = \TsAccommodation\Entity\Requirement::getRepository()->findByAccommodation($this);

		foreach($aRequirements as $oRequirement) {

			$oCheckRequirement = new CheckRequirement($this, $oRequirement);
			$oCheckRequirement->check($dEnd);

			if($oCheckRequirement->hasExpiredDocument() === true) {
				return false;
			}

		}

		return true;
	}

	public function getResetPasswordLink() {

		$oCustomerDb = new \Ext_CustomerDB_DB(4);
		$sToken = $oCustomerDb->createActivationCode($this->id);

		$oRouting = new \Core\Helper\Routing;
		$sForgotPasswordLink = $oRouting->generateUrl('TsAccommodationLogin.accommodation_reset_password_link', ['sToken'=>$sToken]);

		// Die Route kann bereits die Domain beinhalten
		if(strpos($sForgotPasswordLink, 'http') === false) {
			$sForgotPasswordLink = \System::d('domain').$sForgotPasswordLink;
		}

		return $sForgotPasswordLink;
	}
	
	/**
	 * 
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @return \Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getAllocations(array $additionalWhere=[], $orderBy='') {
	
		$query = \Ext_Thebing_Accommodation_Allocation::query()
			->select('kaal.*')
			->join('kolumbus_rooms as kr', 'kaal.room_id', '=', 'kr.id')
			->where('kr.accommodation_id', (int)$this->id)
			->where('kaal.status', 0);
			
		if(!empty($additionalWhere)) {
			$query->where($additionalWhere);
		}

		if(!empty($orderBy)) {
			$query->orderBy($orderBy);
		}
		
		$allocations = $query->get();
		
		return $allocations;
	}

	public function delete() {

		// Feld ist UNIQUE
		$this->email = \Ext_TC_Util::generateRandomString(8).'_'.$this->email;

		return parent::delete();

	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'mail' => [$this->email => $this->getName()],
			default => null,
		};
	}

	public static function getCommunicationChannels(\Tc\Service\LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'sms' => [],
			'notice' => []
		];
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		if (!\Ext_Thebing_System::isAllSchools()) {
			return \Ext_Thebing_School::getSchoolFromSession();
		}

		$firstSchoolId = \Illuminate\Support\Arr::first($this->schools);

		return Ext_Thebing_School::getInstance($firstSchoolId);
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccommodation\Communication\Application\Accommodation::class;
	}

	public function getCommunicationLabel(LanguageAbstract $l10n): string
	{
		return sprintf('%s: %s', $l10n->translate('Unterkunftsanbieter'), $this->getName());
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes(string $channel): ?Collection
	{
		return match ($channel) {
			'mail' => (!empty($this->email)) ? collect([[$this->email, $this->getName()]]) : null,
			'sms' => (!empty($this->ext_77)) ? collect([[$this->ext_77, $this->getName()]]) : null,
			default => null,
		};
	}

	public function getCorrespondenceLanguages(): array
	{
		return [
			$this->getLanguage()
		];
	}

}
