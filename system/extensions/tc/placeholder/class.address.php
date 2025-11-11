<?php

class Ext_TC_Placeholder_Address extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oAddress'
	);

	protected $_aPlaceholders = array(
		'company_name' => array(
			'label' => 'Firmenname',
			'type' => 'field',
			'source' => 'company'
		),
		'address_label' => array(
			'label' => 'Adressbezeichnung',
			'type' => 'method',
			'source' => 'getName',
			'pass_language' => true
		),
		'address1' => array(
			'label' => 'Adresse',
			'type' => 'field',
			'source' => 'address'
		),
		'address2' => array(
			'label' => 'Adresszusatz',
			'type' => 'field',
			'source' => 'address_addon'
		),
		'address_additional' => array(
			'label' => 'Adresszusatz 2',
			'type' => 'field',
			'source' => 'address_additional'
		),
		'zip' => array(
			'label' => 'PLZ',
			'type' => 'field',
			'source' => 'zip'
		),
		'city' => array(
			'label' => 'Stadt',
			'type' => 'field',
			'source' => 'city'
		),
		'country' => array(
			'label' => 'Land',
			'type' => 'method',
			'source' => 'getCountry', 
			'pass_language' => true			
		),
	);
	
}