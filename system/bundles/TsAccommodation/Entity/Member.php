<?php

namespace TsAccommodation\Entity;

/**
 * @method static MemberRepository getRepository()
 */
class Member extends \Ext_TS_Contact {

	protected $_sTableAlias = 'tc_c';

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
		'accommodation_providers' => [
			'table' => 'ts_accommodation_providers_to_contacts',
			'foreign_key_field' => 'accommodation_provider_id',
			'primary_key_field' => 'contact_id',
			'class' => 'Ext_Thebing_Accommodation',
			'autoload' => true,
			'on_delete' => 'no_action'
		],
		'documents' => [
			'table' => 'ts_accommodation_providers_requirements_documents_to_members',
			'foreign_key_field' => 'document_id',
			'primary_key_field' => 'contact_id',
			'class' => '\TsAccommodation\Entity\Requirement\Document',
			'autoload' => true,
			'on_delete' => 'no_action'
		]
	);

	// TODO Das sollte höchstens per Konstruktor verändert werden, aber nicht komplett überschrieben werden
	protected $aDetailPropertyWhitelist = [
		'phone_private',
		'phone_office',
		'phone_mobile',
		'fax',
		'comment',
		'skype'
	];

	protected $_aFlexibleFieldsConfig = [
		'accommodation_providers_members' => []
	];

	public function __get($name) {
		
		switch($name) {
			case 'accommodation_id':
				$accommodations = $this->getJoinTableObjects('accommodation_providers');
				if(!empty($accommodations)) {
					return reset($accommodations)->id;
				}
				return null;
		}

		return parent::__get($name);
	}
	
	/**
	 * Ableitung damit die Parts von Ext_TS_Contact nicht übernommen werden, weil hier ein anderer Alias benutzt wird :-(
	 * 
	 * @param array $aSqlParts
	 * @param string $sView
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		
	}

	public function save($bLog = true)
	{
		\System::wd()->executeHook('ts_accommodation_member_save', $this);
		return parent::save($bLog);
	}

}