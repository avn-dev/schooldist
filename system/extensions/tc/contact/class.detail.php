<?php

use Communication\Interfaces\Notifications\NotificationRoute;

/**
 * @property integer $id
 * @property ? $created
 * @property integer $creator_id
 * @property integer $editor_id
 * @property integer $contact_id
 * @property string $type siehe Ext_TC_Contact_Detail::TYPE_* Konstanten
 * @property string $value
 */

class Ext_TC_Contact_Detail extends Ext_TC_Basic implements Ext_TC_Frontend_Form_Entity_Interface, NotificationRoute {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_contacts_details';

	/**
	 * @var array
	 */
	protected $_aMappingClass = array(
		'frontend_form' => 'Ext_TC_Contact_Detail_Mapping_Frontend'	
	);

	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_Detail';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_cd';

	/**
	 * @var boolean
	 */
	protected $bFormatPhoneNumber = true;

	/**
	 * @var boolean
	 */
	protected $bValidatePhoneNumber = true;
	
	/**
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'contact' => [
			'class'=>'Ext_TC_Contact',
			'key'=>'contact_id',
			'type' => 'parent',
			'readonly' => true,
			'bidirectional' => true
		]
	];
	
	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_PHONE_MOBILE = 'phone_mobile';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_PHONE_PRIVATE = 'phone_private';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_PHONE_OFFICE = 'phone_office';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_PHONE_EMERGENCY = 'phone_emergency';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_SKYPE = 'skype';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_FAX = 'fax';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_TAX_CODE = 'tax_code';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_VAT_NUMBER = 'vat_number';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const TYPE_RECIPIENT_CODE = 'recipient_code';

	public function __construct($iDataID = 0, $sTable = null) {
		
		if(\System::d('auto_format_phone_numbers', 1) != 1) {
			$this->bFormatPhoneNumber = false;
		}

		parent::__construct($iDataID, $sTable);

	}
	
	public function __get($sName) {

		// Man kann leider nicht ordentlich mit einem abstrakten Mapping arbeiten wegen $oContact->getDetail()
		if(
			isset($this->_aData['type']) && 
			$this->_aData['type'] === $sName
		) {
			return $this->_aData['value'];
		}

		return parent::__get($sName);

	}

	public function __set($sName, $mValue) {

		// Man kann leider nicht ordentlich mit einem abstrakten Mapping arbeiten wegen $oContact->getDetail()
		if ($sName === $this->type) {
			$this->value = $mValue;
			return;
		}

		parent::__set($sName, $mValue);

	}


	/**
	 * Alle verfügbaren Beschreibungen für Details
	 * 
	 * @param string $sLanguage
	 * @param boolean $bFrontendTranslation
	 * @return array
	 */
	public static function getTypes($sLanguage = '', $bFrontendTranslation = false) {

		$aTypes = array(
			self::TYPE_PHONE_MOBILE => 'Tel. Mobil',
			self::TYPE_PHONE_PRIVATE => 'Tel. Privat',
			self::TYPE_PHONE_OFFICE => 'Tel. Büro',
			self::TYPE_PHONE_EMERGENCY => 'Tel. Notfall',
			self::TYPE_SKYPE => 'Skype',
			self::TYPE_FAX => 'Fax',
			self::TYPE_TAX_CODE => 'Steuernummer',
			self::TYPE_VAT_NUMBER => 'USt.-ID',
			self::TYPE_RECIPIENT_CODE => 'Empfängercode'
		);

		$aReturnTypes = array();
		foreach($aTypes as $sKey => $sValue) {
			if($bFrontendTranslation) {
				// Frontend Übersetzung
				$aReturnTypes[$sKey] = Ext_TC_Placeholder_Abstract::translateFrontend($sValue, $sLanguage);
			} else {
				// Backend Übersetzung
				$aReturnTypes[$sKey] = Ext_TC_L10N::t($sValue, $sLanguage);
			}
		}

		return $aReturnTypes;

	}
	
	public function getFormattedValue() {
				
		switch($this->type){
			case 'passport_valid_from':
			case 'passport_valid_until':
			case 'passport_date_of_issue':
				$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
				$mValue = $oFormat->format($this->value);
				break;
			case 'vegetarian':
			case 'smoker':
			case 'muslim_diet':
				$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_YesNo');
				$mValue = $oFormat->format($this->value);
				break;
			default:
				$mValue = $this->value;
				break;
		}
		return $mValue;
	}

	/**
	 * Liefert die Beschreibung für ein Detail (z.B. Tel. Büro)
	 *
	 * @param string $sLanguage
	 * @param boolean $bFrontendTranslation
	 * @return string
	 */
	public function getDescription($sLanguage = '', $bFrontendTranslation = false) {
		
		$aTypes = Ext_TC_Contact_Detail::getTypes($sLanguage, $bFrontendTranslation);

		$sDescription = '';
		if(isset($aTypes[$this->type])) {
			$sDescription = $aTypes[$this->type];
		}

		return $sDescription;

	}
	
	/**
	 * {@inheritdoc}
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if(
			$mValidate === true &&
			$this->bFormatPhoneNumber === true && (
				$this->type === self::TYPE_PHONE_OFFICE ||
				$this->type === self::TYPE_PHONE_PRIVATE ||
				$this->type === self::TYPE_FAX ||
				$this->type === self::TYPE_PHONE_MOBILE	 ||
				$this->type === self::TYPE_PHONE_EMERGENCY
			) &&
			$this->value != ''
		) {

			$this->value = $this->formatPhonenumber($this->value);

			if($this->bValidatePhoneNumber) {
				$sCheck = 'PHONE_ITU';

				$oValidate = new WDValidate();
				$oValidate->check = $sCheck;
				$oValidate->value = $this->value;
				$bValid = $oValidate->execute();

				$sErrorKey = $this->_getErrorFieldKey();

				if(!$bValid) {
					$mValidate = array(
						$sErrorKey => 'INVALID_'.$sCheck // INVALID_PHONE_ITU
					);
				}
			}

		}

		return $mValidate;
	}

	/**
	 * @TODO Redundanz mit \WDValidate::formatPhonenumber()!
	 *
	 * @param string $sNumber
	 */
	public function formatPhonenumber($sNumber) {
		
		// Land holen falls möglich
		$oContact = $this->getJoinedObject('contact');
		
		if($oContact instanceof Ext_TC_Contact) {
		
			$oAddress = $oContact->getFirstAddress(false);
			
			if($oAddress instanceof Ext_TC_Address) {
				$sCountryIso = $oAddress->country_iso;
			}
		
			// Fallback
			if(empty($sCountryIso)) {
				$sCountryIso = $oContact->nationality;
			}
		
			if(empty($sCountryIso)) {
				System::wd()->executeHook('tc_phone_number_default_country_iso_hook', $sCountryIso);
			}

			if(!empty($sCountryIso)) {

				$oPhonenumberUtil = libphonenumber\PhoneNumberUtil::getInstance();
				
				try {
					
					$oPhonenumber = $oPhonenumberUtil->parse($sNumber, $sCountryIso);

					$sCountryCallingCode = $oPhonenumber->getCountryCode();
					$sRegionCode = $oPhonenumberUtil->getRegionCodeForCountryCode($sCountryCallingCode);

					if(\libphonenumber\PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY === $sRegionCode) {
						$oMetadata = $oPhonenumberUtil->getMetadataForNonGeographicalRegion($sCountryCallingCode);
					} else {
						$oMetadata = $oPhonenumberUtil->getMetadataForRegion($sRegionCode);
					}

					$aIntlNumberFormats = $oMetadata->intlNumberFormats();

					if(count($aIntlNumberFormats) == 0) {
						$aAvailableFormats = $oMetadata->numberFormats();
					} else {
						$aAvailableFormats = $aIntlNumberFormats;
					}

					/*
					 * Die internationalen Format können abweichen.
					 * Um immer dasselbe Format zu bekommen, wird der String entsprechend manipuliert
					 */
					foreach($aAvailableFormats as $oNumberFormat) {
						if(strpos($oNumberFormat->getFormat(), '-') !== false) {
							$sNewFormat = str_replace('-', ' ', $oNumberFormat->getFormat());
							$sNewFormat = str_replace('/', ' ', $sNewFormat);
							$sNewFormat = str_replace('(', '', $sNewFormat);
							$sNewFormat = str_replace(')', '', $sNewFormat);

							$oNumberFormat->setFormat($sNewFormat);
						}
					}

                    $aData = [
                        'aAvailableFormats'  => $aAvailableFormats,
                        'iPhoneNumberFormat' => \libphonenumber\PhoneNumberFormat::INTERNATIONAL
                    ];


                    System::wd()->executeHook('tc_phone_number_format_hook', $aData);


                    $sFormattedNumber = $oPhonenumberUtil->formatByPattern($oPhonenumber, $aData['iPhoneNumberFormat'], $aData['aAvailableFormats']);

					if(!empty($sFormattedNumber)) {
						return $sFormattedNumber;
					}
					
				} catch (Exception $e) {
					// No valid phone number
				}

			}
			
		}

		return $sNumber;
	}
	
	/**
	 * Liefert den Error Key im Error Array der Validate Funktion (wird für die Schule abgeleitet)!
	 *
	 * @return string 
	 */
	protected function _getErrorFieldKey(){

		$sFieldPrefix = '';
		if($this->_sTableAlias) {
			$sFieldPrefix = $this->_sTableAlias.'.';
		}

		$sFieldPrefix = $sFieldPrefix.'value';

		return $sFieldPrefix;

	}

	/**
	 * {@inheritdoc}
	 */
	public function addChild($sChildIdentifier, $sJoinedObjectCacheKey) {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function removeChild($sChildIdentifier, $iCount) {
	}

	public function getContact(): ?\Ext_TC_Contact {
		if ($this->contact_id > 0) {
			return $this->getJoinedObject('contact');
		}
		return null;
	}

	public function toNotificationRoute(string $channel)
	{
		if ($channel === 'sms') {
			return [preg_replace('/\s+/', '', $this->value), $this->getContact()?->getName()];
		}

		return null;
	}

	public function getNotificationName(string $channel): ?string
	{
		if ($channel === 'sms') {
			return $this->value;
		}

		return null;
	}

}
