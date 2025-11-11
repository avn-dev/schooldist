<?php

use Communication\Interfaces\Model\CommunicationSubObject;

/**
 * @property $id 	
 * @property $company_id
 * @property $email 	
 * @property $gender 	
 * @property $firstname 	
 * @property $lastname 	
 * @property $group 	
 * @property $phone 	
 * @property $mobile 	
 * @property $fax 	
 * @property $skype 	
 * @property $transfer 	
 * @property $accommodation 	
 * @property $reminder 	
 * @property $image 	
 * @property $master_contact 	
 * @property $user_id 	
 * @property $created 	
 * @property $changed 	
 * @property $active 	
 * @property $creator_id
 */
class Ext_Thebing_Agency_Contact extends \TsCompany\Entity\Contact implements \Communication\Interfaces\Model\HasCommunication {

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array( 
        'inquiries' => array(
			'class'	=> 'Ext_TS_Inquiry',
			'key' => 'agency_contact_id',
			'check_active' => true,
			'type' => 'child'
        )
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'activation_codes' => [
			'table' => 'ts_agencies_activation_codes',
			'foreign_key_field' => ['activation_code', 'expired'],
			'primary_key_field' => 'contact_id',
			'autoload' => false,
			'readonly' => true
		],
	);

    /**
     * @var array
     */
	protected $_aFlexibleFieldsConfig = [
		'agencies_users_details' => []
	];

	public function isMasterContact(): bool
	{
		return (bool)$this->master_contact;
	}

	public function isResponsibleFor(array $sections): bool
	{
		foreach ($sections as $section) {
			if (isset($this->_aData[$section]) && (bool)$this->_aData[$section]) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return null|Ext_Thebing_Agency
	 */
	public function getParentObject() {

		if($this->company_id > 0) {
			return Ext_Thebing_Agency::getInstance($this->company_id);
		}

		return null;
	}

	/**
	 * @param bool $bLog
	 * @return $this|Ext_TC_Basic
	 */
	public function save($bLog = true) {

		$bSave = parent::save($bLog);

		System::wd()->executeHook('ts_agency_contact_save', $this);

		return $bSave;
	}

	/**
	 * Mögliche Zuständichkeiten die eine Agenturkontaktperson haben haben kann
	 *
	 * @return array
	 */
	public static function getFlags($oLanguage=null) {

		if(!$oLanguage instanceof Tc\Service\LanguageAbstract) {
			$sLanguage = System::getInterfaceLanguage();
			$oLanguage = new \Tc\Service\Language\Backend($sLanguage);
			$oLanguage->setPath(Ext_Thebing_Agency_Gui2::getDescriptionPart());
		}

		$aFlags = array();
		$aFlags['transfer'] = $oLanguage->translate('Transfer');
		$aFlags['accommodation'] = $oLanguage->translate('Unterkunft');
		$aFlags['reminder'] = $oLanguage->translate('Mahnung');

		return $aFlags;
	}

	/**
	 * @return Ext_TS_Inquiry[]
	 */
	public function getInquiries() {

		/** @var Ext_TS_Inquiry[] $aInquiries */
		$aInquiries = $this->getJoinedObjectChilds('inquiries');
		
		return $aInquiries;
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsCompany\Communication\Application\AgencyContact::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		$agency = $this->getParentObject();
		return sprintf('%s: %s', $agency->getName(), $this->getName());
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getParentObject()->getCommunicationSubObject();
	}
}
