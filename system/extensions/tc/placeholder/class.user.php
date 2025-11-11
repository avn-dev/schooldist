<?php

class Ext_TC_Placeholder_User extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oUser'
	);
	
	protected $_aPlaceholders = array(
		'user_firstname' => array(
			'label' => 'Mitarbeiter: Vorname',
			'type' => 'field',
			'source' => 'firstname'
		),
		'user_lastname' => array(
			'label' => 'Mitarbeiter: Nachname',
			'type' => 'field',
			'source' => 'lastname'
		),
		'user_email' => array(
			'label' => 'Mitarbeiter: E-Mail',
			'type' => 'field',
			'source' => 'email'
		),
		'user_phone' => array(
			'label' => 'Mitarbeiter: Telefon',
			'type' => 'field',
			'source' => 'phone'
		),		
		/*'signature_object' => array(
			'label' => 'Signaturen',
			'type' => 'loop',
			'loop' => 'joined_object',
			'source' => 'signatures',
			'variable_name' => 'aSignatures'
		) */
	);
	
}
