<?php

use Communication\Interfaces\Model\CommunicationContact;
use Illuminate\Notifications\RoutesNotifications;

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property string $valid_until (DATE)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property string $firstname
 * @property string $lastname
 * @property string $salutation
 * @property string $title
 * @property string $birthday (DATE)
 * @property string $image
 * @property int $gender
 * @property string $nationality
 * @property string $language
 * @property string $corresponding_language
 *
 * @method static Ext_TC_ContactRepository getRepository()
 */
class Ext_TC_Contact extends Ext_TC_Basic implements Ext_TC_Frontend_Form_Entity_Interface, CommunicationContact {
	use \Tc\Traits\Entity\HasSystemTypes,
		RoutesNotifications;

	const MAPPING_TYPE = 'contacts';

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_contacts';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_c';

	/**
	 * @var array
	 */
	protected $_aMappingClass = array(
		'customer_index' => 'Ext_TC_Contact_Mapping',
		'frontend_form' => 'Ext_TC_Contact_Mapping_Frontend'	
	);

	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_Contact';

	/**
	 * The INPUT/OUTPUT format settings to use in extended classes
	 *
	 * @var array
	 */
	protected $_aFormat = array(
		'birthday' => array(
			'validate'	=> array(
				'DATE',
				'DATE_PAST'
			),
			'validate_separate' => true
		)
	);
	
	/**
	 * @todo Die Kontaktdetails gibt es auch als JoinTable und beide sind schreibbar! Das darf nicht sein!
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'details' => array(
			'class' => 'Ext_TC_Contact_Detail',
			'key' => 'contact_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade',
			'cloneable' => false,
			'bidirectional' => true
		)
	);

	/**
	 * Eine Liste mit Verknüpfungen (1-n)
	 *
	 * array(
	 *		'items'=>array(
	 *				'table'=>'',
	 *				'foreign_key_field'=>'',
	 *				'primary_key_field'=>'id',
	 *				'sort_column'=>'',
	 *				'class'=>'',
	 *				'autoload'=>true,
	 *				'check_active'=>true,
	 *				'delete_check'=>false,
	 *				'static_key_fields'=>array(),
	 *				'join_operator' => 'LEFT OUTER JOIN' // aktuell nur bei getListQueryData
	 *			)
	 * )
	 *
	 * foreign_key_field kann auch ein Array sein
	 *
	 * WICHTIG! 
	 * Wenn änderungen gemacht werden, 
	 * müssen alle Klassen die hiervon ableiten mussen ebenfalls ergänzt werden!
	 * 
	 * @var[]
	 */
	protected $_aJoinTables = array(
		'contacts_to_addresses' => array(
			'table' => 'tc_contacts_to_addresses',
			'foreign_key_field' => 'address_id',
			'primary_key_field' => 'contact_id',
			'class' => 'Ext_TC_Address',
 			'autoload' => true,
			'on_delete' => 'no_action'
		),
		'contacts_details' => array(
			'table' => 'tc_contacts_details',
			'foreign_key_field' => 'id',
			'primary_key_field' => 'contact_id',
			'check_active'=>true,
			'class' => 'Ext_TC_Contact_Detail',
			'autoload' => false,
			'readonly' => true,
			'on_delete' => 'no_action'
		),
		'contacts_to_emailaddresses' => array(
			'table' => 'tc_contacts_to_emailaddresses',
			'foreign_key_field' => 'emailaddress_id',
			'primary_key_field' => 'contact_id',
			'class' => 'Ext_TC_Email_Address',
			'autoload' => true,
			'on_delete' => 'no_action'
		),
		'system_types' => array(
			'table' => 'tc_contacts_to_system_types',
			'class' => \Tc\Entity\SystemTypeMapping::class,
			'foreign_key_field' => 'mapping_id',
			'primary_key_field' => 'contact_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		),
		'numbers' => [
			'table'					=> 'tc_contacts_numbers',
			'foreign_key_field'		=> ['number', 'numberrange_id'],
			'primary_key_field'		=> 'contact_id',
			'autoload'				=> false,
		]
	);

	protected $aDetailPropertyWhitelist = [
		'phone_private',
		'phone_office',
		'phone_mobile',
		'fax',
		'comment',
		'skype',
		'website',
		'tax_code',
		'vat_number',
		'google_maps',
		'recipient_code',
		'newsletter',
	];

	public function __get($sKey) {

		$mValue = null;

		if($sKey === 'email') {

			$aEmailAddresses = $this->getJoinTableObjects('contacts_to_emailaddresses');

			if(!empty($aEmailAddresses)) {
				$oEmailAddress = reset($aEmailAddresses);
				$mValue = $oEmailAddress->email;
			}

		} elseif(strpos($sKey, 'address_') === 0) {

			list($sDetail, $sKey) = explode('_', $sKey, 2);

			$aAddresses = $this->getJoinTableObjects('contacts_to_addresses');

			if(!empty($aAddresses)) {
				$oAddress = reset($aAddresses);
				$mValue = $oAddress->$sKey;
			}

		} elseif(strpos($sKey, 'detail_') === 0) {

			list($sDetail, $sKey) = explode('_', $sKey, 2);

			if(in_array($sKey, $this->aDetailPropertyWhitelist) === true) {

				$oDetail = $this->getJoinedObjectChildByValue('details', 'type', $sKey);

				if($oDetail !== null) {
					$mValue = $oDetail->value;
				}

			} else {
				throw new RuntimeException('Invalid detail type "'.$sKey.'"');
			}

		} else {
			$mValue = parent::__get($sKey);
		}

		return $mValue;
	}

	public function __set($sKey, $mValue) {

		if($sKey === 'email') {

			$aEmailAddresses = $this->getJoinTableObjects('contacts_to_emailaddresses');

			if(!empty($aEmailAddresses)) {
				$oEmailAddress = reset($aEmailAddresses);
			} else {
				$oEmailAddress = $this->getJoinTableObject('contacts_to_emailaddresses');
			}

			$oEmailAddress->email = $mValue;

		} elseif(strpos($sKey, 'address_') === 0) {

			list($sDetail, $sKey) = explode('_', $sKey, 2);

			$aAddresses = $this->getJoinTableObjects('contacts_to_addresses');

			if(!empty($aAddresses)) {
				$oAddress = reset($aAddresses);
			} else {
				$oAddress = $this->getJoinTableObject('contacts_to_addresses');
			}

			$oAddress->$sKey = $mValue;

		} elseif(strpos($sKey, 'detail_') === 0) {

			list($sDetail, $sKey) = explode('_', $sKey, 2);

			if(in_array($sKey, $this->aDetailPropertyWhitelist) === true) {

				$oDetail = $this->getJoinedObjectChildByValueOrNew('details', 'type', $sKey);

				$oDetail->value = $mValue;

			} else {
				throw new RuntimeException('Invalid detail type "'.$sKey.'"');
			}

		} else {
			parent::__set($sKey, $mValue);
		}

	}

	/**
	 *
	 * @return Ext_TC_Address[]
	 */
	public function getAddresses() {

		$aAddreses = $this->getJoinTableObjects('contacts_to_addresses');

		return $aAddreses;
	}

	/**
	 * @param bool $bCreateEmptyObject
	 * @return Ext_TC_Address 
	 */
	public function getFirstAddress($bCreateEmptyObject = true) {

		$aAddresses = $this->getAddresses();
		$oAddress = reset($aAddresses);

		if(!$oAddress && $bCreateEmptyObject){
			$oAddress = $this->getJoinTableObject('contacts_to_addresses');
		}

		return $oAddress;
	}
	
	/**
	 * @param bool $bSortByMaster
	 * @return Ext_TC_Email_Address[]
	 */
	public function getEmailAddresses($bSortByMaster = false) {
		
		$aAddresses = $this->getJoinTableObjects('contacts_to_emailaddresses');

		// E-Mail-Adressen mit master-Flag nach oben sortieren
		if($bSortByMaster) {
			uasort($aAddresses, function($oEmail1, $oEmail2) {
				return $oEmail1->master < $oEmail2->master;
			});
		}

		return $aAddresses;

	}

	/**
	 * @return ?Ext_TC_Email_Address
	 */
	public function getFirstEmailAddress($bCreateEmptyObject = true) {

		$aAddresses = $this->getEmailAddresses(true);
		$oAddress = reset($aAddresses);

		if(!$oAddress && $bCreateEmptyObject) {
			$oAddress = $this->getJoinTableObject('contacts_to_emailaddresses');
		}

		if (!$oAddress) {
			return null;
		}
		
		return $oAddress;
	}

	/**
	 * @return Ext_TC_Email_Address
	 */
	public function getFirstPrivateEmailAddress($bCreateEmptyObject = true) {

		$aAddresses = $this->getEmailAddresses(true);

		foreach($aAddresses as $oAddress) {
			if($oAddress->type == 'private') {
				return $oAddress;
				break;
			}
		}
		
		$oAddress = null;
		if($bCreateEmptyObject) {
			$oAddress = $this->getJoinTableObject('contacts_to_emailaddresses');
			$oAddress->type = 'private';
		}
		
		return $oAddress;
	}

	/**
	 * @return Ext_TC_Email_Address
	 */
	public function getFirstBusinessEmailAddress($bCreateEmptyObject = true) {

		$aAddresses = $this->getEmailAddresses(true);

		foreach($aAddresses as $oAddress) {
			if($oAddress->type == 'business') {
				return $oAddress;
				break;
			}
		}
		
		$oAddress = null;
		if($bCreateEmptyObject) {
			$oAddress = $this->getJoinTableObject('contacts_to_emailaddresses');
			$oAddress->type = 'business';
		}

		return $oAddress;
	}

	/**
	 * @return Ext_TC_Contact_Detail[]
	 */
	public function getDetails() {
		$aDetails = (array)$this->getJoinedObjectChilds('details');
		return $aDetails;
	}

	public function getDetailsByType($type)
	{
		return array_filter($this->getDetails(), fn($detail) => $detail->type === $type);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasDetail(string $key): bool {
		$found = \Illuminate\Support\Arr::first(
			$this->getJoinedObjectChilds('details', true),
			fn ($detail) => $detail->type === $key
		);
		return $found !== null;
	}



	/**
	 * Liefert alle Mobilfunknummern des Objektes für die Kommunikation
	 */
	public function getMobileNumbers() {
		
		$aResult = array();
		
		$aDetails = $this->getDetails();
		
		foreach($aDetails as $oDetail) {
			
			if(
				$oDetail->active == 1 &&
				$oDetail->type === 'phone_mobile'
			) {
				$aResult[] = $oDetail->value;
			}
			
		}
		
		return $aResult;
	}

	/**
	 * @return array
	 */
	public function getPrivateNumbers() {		
		return array_column($this->getPrivateNumberObjects(), 'value');
	}

	public function getPrivateNumberObjects() {
		
		$aResult = array();
		
		$aDetails = $this->getDetails();
		
		foreach($aDetails as $oDetail) {
			
			if(
				$oDetail->active == 1 &&
				$oDetail->type === 'phone_private'
			) {
				$aResult[] = $oDetail;
			}
			
		}
		
		return $aResult;
		
	}
	
	/**
	 * @param null|string $mLanguage
	 * @return string
	 */
	public function getGender($mLanguage = null) {

		$iGender = $this->gender;
		$aGenders = Ext_TC_Util::getGenders(true, '', $mLanguage);
		$sGender = (string)$aGenders[$iGender];

		return $sGender;
	}
	
	/**
	 * Gibt das Geschlecht für Frontend aus
	 *
	 * @param string $sLanguage
	 * @return string 
	 */
	public function getFrontendGender($sLanguage = ''){
		
		$sFrontendGender = '';
		
		$iGender = (int)$this->gender;
		
		if($iGender > 0) {

			if(empty($sLanguage)) {
				$sLanguage = \System::getInterfaceLanguage();
			}

			$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);

			$aGenders = Ext_TC_Util::getGenders(false, '', $oLanguage);

			$sFrontendGender = $aGenders[$iGender];
		}
			
		return $sFrontendGender;
	}
	
	/**
	 * Alter berechnen
	 *
	 * @param DateTime $dDate Anderer Zeitpunkt für Berechnung des Alters (ansonsten heute)
	 * @return int
	 */
	public function getAge(\DateTime $dDate = null) {

		if(!\Core\Helper\DateTime::isDate($this->birthday, 'Y-m-d')) {
			return 0;
		}

		if($dDate === null) {
			$dDate = new DateTime();
		}

		$dBirthday = new DateTime($this->birthday);
		$oDiff = $dDate->diff($dBirthday, true);

		return $oDiff->y;

	}

	/**
	 * @return string|null
	 */
	public function getFirstPhoneNumber() {

		$aDetails = $this->getDetails();

		foreach($aDetails as $oDetail) {
			if(
				(
					$oDetail->type == 'phone_private' ||
					$oDetail->type == 'phone_mobile' ||
					$oDetail->type == 'phone_office' ||
					$oDetail->type == 'phone_emergency'
				) &&
				!empty($oDetail->value)
			) {				
				return $oDetail->value;
			}
		}

	}

	/**
	 * @return string
	 */
	public function getFirstPrivatePhoneNumber() {

		$aDetails = $this->getDetails();

		foreach($aDetails as $oDetail) {
			if($oDetail->type == 'phone_private') {
				return $oDetail->value;
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getFirstMobilePhoneNumber() {

		$aDetails = $this->getDetails();

		foreach($aDetails as $oDetail) {
			if($oDetail->type == 'phone_mobile') {
				return $oDetail->value;
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getFirstOfficePhoneNumber() {

		$aDetails = $this->getDetails();

		foreach($aDetails as $oDetail) {
			if($oDetail->type == 'phone_office') {
				return $oDetail->value;
			}
		}

		return '';
	}

	/**
	 * @return string|null
	 */
	public function getFirstFaxNumber() {

		$aDetails = $this->getDetails();

		foreach($aDetails as $oDetail) {
			if($oDetail->type == 'fax') {
				return $oDetail->value;
			}
		}

	}

	/**
	 * @return string
	 */
	public function getName() {
		
		$aNames = array();
		if($this->lastname != '') {
			$aNames[] = (string)$this->lastname;
		}
		if($this->firstname != '') {
			$aNames[] = (string)$this->firstname;
		}
		$sName = implode(', ', $aNames);

		return $sName;
	}

	/**
	 * APIs arbeiten evtl. nicht mit dem RAK-Namen
	 *
	 * Namensformate haben keine Bezeichnungen und diese Form taucht auch als »Alltagsform« auf.
	 *
	 * @return string
	 */
	public function getEverydayName(): string {

		$sName = (string)$this->lastname;
		if ($this->firstname !== '') {
			$sName = $this->firstname.' '.$this->lastname;
		}

		return $sName;

	}

	/**
	 * Liefert das Geburtsdatum des Kontaktes für den Index
	 * 
	 * @return string|null
	 */
	public function getBirthdayForIndex() {
		
		$sBirthday = $this->birthday;
		
		if(
			$sBirthday == '0000-00-00' ||
			$sBirthday == ''
		) {
			$sBirthday = null;
		}
		
		return $sBirthday;
	}

	/**
	 * @deprecated -> getCorrespondenceLanguages()
	 * @return string
	 */
	public function getCorrespondenceLanguage() {
		return $this->corresponding_language;
	}

	/**
	 * Kommunikation
	 * @return string
	 */
	public function getCorrespondenceLanguages(): array {
		if (!empty($this->corresponding_language)) {
			return [$this->corresponding_language];
		}
		return [];
	}

	/**
	 * nötig da mein IDE sonst rummeckert, keine ahnung warum...
	 * @see parent
	 * @param bool $bThrowExceptions
	 * @return bool
	 */
	public function validate($bThrowExceptions = false) {
		return parent::validate($bThrowExceptions);
	}
	
	/**
	 * add a child object by indentifier an return it
	 * @param string $sChildIdentifier
	 * @return Ext_TC_Frontend_Form_Entity_Interface|false
	 */
	public function addChild($sChildIdentifier, $iKey) {
		
		$oObject = false;

		switch ($sChildIdentifier) {
			case 'address':
				$oObject = $this->getJoinTableObject('contacts_to_addresses', $iKey);
				break;
			case 'email':
				$oObject = $this->getJoinTableObject('contacts_to_emailaddresses', $iKey);
				break;
			case 'detail':
				$oObject = $this->getJoinedObjectChild('details', 0, $iKey);
				break;
		}

		if($oObject instanceof Ext_TC_Frontend_Form_Entity_Interface) {
			return $oObject;
		}
		
		return false;
	}
	
	/**
	 * @see Interface
	 */
	public function removeChild($sChildIdentifier, $iCount) {
		
		switch ($sChildIdentifier) {
			case 'address':
				$this->removeJoinTableObject('contacts_to_addresses', $iCount);
				break;
			case 'email':
				$this->removeJoinTableObject('contacts_to_emailaddresses', $iCount);
				break;
			case 'detail':
				$this->deleteJoinedObjectChild('details', $iCount);
				break;
		}
		
	}

	/**
	 * @param string $sMappingType
	 * @return array
	 */
	public function getMappingFields($sMappingType) {
		
		$aContactMapping = parent::getMappingFields($sMappingType);
		
		$oAddress = $this->addChild('address', null);
		$aAddressSchema	= $oAddress->getMappingFields($sMappingType);
		
		$oEmail = $this->addChild('email', null);
		$aEmailSchema = $oEmail->getMappingFields($sMappingType);
		
		$oDetail = $this->addChild('detail', null);
		$aDetailSchema = $oDetail->getMappingFields($sMappingType);
		
		$aFinalFields = array_merge(
			$aContactMapping, 
			$aAddressSchema, 
			$aEmailSchema,
			$aDetailSchema
		);

		return $aFinalFields;
	}

	/**
	 * Liefert die Anrede für den Kontakt
	 *
	 * @param int $iGender
	 * @param string $sLanguage
	 * @return string mixed
	 */
	public static function getSalutationForFrontend($iGender, $mLanguage) {

		if(!$mLanguage instanceof Tc\Service\LanguageAbstract) {
			$mLanguage = new \Tc\Service\Language\Frontend($mLanguage);
		}

		$aPersonTitles = Ext_TC_Util::getPersonTitles($mLanguage);

		$sSalutation = '';
		if(isset($aPersonTitles[$iGender])) {
			$sSalutation = $aPersonTitles[$iGender];
		}

		return $sSalutation;
	}

	public function save($bLog = true) {
		
		System::wd()->executeHook('tc_contact_save', $this);
		
		return parent::save($bLog);
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= "
			, `tc_cn`.`number`
			, GROUP_CONCAT( DISTINCT `tc_ctst`.`mapping_id` SEPARATOR '{||}') `system_types`
			, GROUP_CONCAT( DISTINCT `tc_ea`.`email` SEPARATOR ', ') `email_addresses`
			, GROUP_CONCAT( DISTINCT CONCAT (`tc_cd`.`type`, '{|}', `tc_cd`.`value`) ORDER BY `tc_cd`.`type` SEPARATOR '{||}') `contact_details`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` INNER JOIN
			`tc_contacts_to_system_types` `tc_ctst` ON
				`tc_ctst`.`contact_id` = `tc_c`.`id` INNER JOIN
			`tc_system_type_mapping` `tc_stm` ON
			    `tc_stm`.`id` = `tc_ctst`.`mapping_id` AND
			    `tc_stm`.`active` = 1 LEFT JOIN
			`tc_system_type_mapping_to_system_types` `tc_stmtst` ON
				`tc_stmtst`.`mapping_id` = `tc_stm`.`id` LEFT JOIN 
			`tc_contacts_details` `tc_cd` ON
				`tc_cd`.`contact_id` = `tc_c`.`id` AND
				`tc_cd`.`active` = 1 AND
				`tc_cd`.`value` != '' LEFT JOIN 
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `contacts_to_emailaddresses`.`emailaddress_id` AND
				`tc_ea`.`active` = 1
		";

		$aSqlParts['where'] .= "
			AND (
				`tc_c`.`firstname` != '' OR
				`tc_c`.`lastname` != ''
			)
		";

		$aSqlParts['groupby'] = '`tc_c`.`id`';
	}

	public function getSubObject() {
		return null;
	}

	protected function getEntityTypeForSystemTypes(): string {
		return self::MAPPING_TYPE;
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'mail', 'sms' => $this->getCommunicationRoutes($driver)->first(),
			default => null,
		};
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes(string $channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'mail' => collect($this->getEmailAddresses())
				->filter(fn ($address) => !empty($address->email))
				->mapWithKeys(fn ($address) => [$address->id => $address]),
			'sms' => collect($this->getDetailsByType('phone_mobile'))
				->filter(fn ($detail) => !empty($detail->value))
				->mapWithKeys(fn ($detail) => [$detail->id => $detail]),
			default => null,
		};
	}

	/**
	 * Liefert die Kundennummer
	 *
	 * @return bool|string
	 */
	public function getCustomerNumber() {

		$aNumbers = $this->numbers;

		if(!empty($aNumbers)) {
			$aNumber = reset($aNumbers);
			// z.B. durch Import kann es hier leere Datensätze geben
			if(!empty($aNumber['number'])) {
				return $aNumber['number'];
			}
		}

		return null;
	}

	public function saveCustomerNumber($sCustomerNumber, $iNumberId, $bForce = false, $bSave = true) {

		$number = $this->getCustomerNumber();
		$iSelfId = (int)$this->id;

		if(
			(empty($number) || $bForce) &&
			($iSelfId > 0 || !$bSave)
		) {

			$aSaveNumbers = array(
				'contact_id' => $iSelfId,
				'number' => $sCustomerNumber,
				'numberrange_id' => $iNumberId
			);

			$this->numbers = array($aSaveNumbers);
			if($bSave) {
				$this->save();
			}

		}

		return $this;
	}
}

