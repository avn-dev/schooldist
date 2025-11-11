<?php

/**
 * @property integer $id
 * @property ? $created
 * @property string $country_iso
 * @property string $state
 * @property integer $state_id
 * @property integer $label_id
 * @property string $company
 * @property string $address
 * @property string $address_addon
 * @property string $address_additional
 * @property string $zip
 * @property string $city
 * @property ? $valid_until
 */
class Ext_TC_Address extends Ext_TC_Basic implements Ext_TC_Frontend_Form_Entity_Interface {

	protected $_sTable = 'tc_addresses';
	
	protected $_sTableAlias = 'tc_a';
	
	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_Address';
	
	protected $_aMappingClass = array(
		'customer_index' => 'Ext_TC_Address_Mapping',	
		'frontend_form' => 'Ext_TC_Address_Mapping_Frontend'	
	);

	public $aAddressFields = array(
		'country_iso',
		'state',
		'state_id',
		'company',
		'address',
		'address_addon',
		'address_additional',
		'zip',
		'city'
	);

	protected $_aJoinedObjects = array(
		'label'=>array(
			'class'				=>'Ext_TC_Address_Label',
			'key'				=>'label_id',
			'check_active'		=> true
		)
	);

	protected $_aJoinTables = [
		'contacts' => [
			'table' => 'tc_contacts_to_addresses',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'address_id',
			'class' => 'Ext_TC_Contact',
 			'autoload' => false,
			'on_delete' => 'no_action'
		],
	];


	public function getName($sLang = ''){

        if (empty($sLang)) {
            $sLang = \System::getInterfaceLanguage();
        }

        if ($this->label_id > 0) {
            $oLabel = Ext_TC_Address_Label::getInstance($this->label_id);
            return $oLabel->getName($sLang);
        }

        $countries = (new \Core\Service\LocaleService())->getCountries($sLang);
        $country = $countries[$this->country_iso];

        return sprintf('%s, %s %s, %s', $this->address, $this->zip, $this->city, $country);
	}
	
	public function getCountry($sLanguage = null){
		
		if(!$sLanguage){
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}
		
		$aCountries = Ext_TC_Country::getCountryByIso($this->country_iso);
		return $aCountries['cn_short_'.substr((string)$sLanguage, 0, 2)] ?? null;
	}

	public function addChild($sChildIdentifier, $sJoinedObjectCacheKey) {
		return array();
	}

	public function validate($bThrowExceptions = false) {
		return parent::validate($bThrowExceptions);
	}

	/**
	 * @see Interface
	 */
	public function removeChild($sChildIdentifier, $iCount) {
		
	}

	/**
	 * Prüft, ob irgendein Wert vorhanden ist
	 * @return bool
	 */
	public function isEmpty()
	{
		foreach($this->aAddressFields as $sField) {
			$mData = $this->$sField;
			if(!empty($mData)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Prüfen, ob relevante Felder befüllt sind
	 *
	 * @return bool
	 */
	public function isFilled() {

		return collect(['address', 'zip', 'city', 'country_iso'])->every(function ($sField) {
			return !empty($this->$sField);
		});

	}

	/**
	 * Ruft getCountry() im statischen Kontext auf
	 * Wird von TS benötigt.
	 *
	 * @param string $sCountryIso
	 * @param string $sLanguage
	 * @return string
	 */
	public static function getCountryStatic($sCountryIso, $sLanguage = null)
	{
		$oSelf = new self();
		$oSelf->country_iso = $sCountryIso;
		return $oSelf->getCountry($sLanguage);
	}
	
	/**
	 * @param boolean $bLog
	 * @return \Ext_TC_Address
	 */
	public function save($bLog = true) {
		
		$this->latitude = null;
		$this->longitude = null;

		parent::save($bLog);
		
		return $this;
	}

}