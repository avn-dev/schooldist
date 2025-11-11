<?php

class Ext_TC_Placeholder_Contact extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oContact'
	);

	protected $_aPlaceholders = array(
		'firstname' => [
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname'
		],
		'lastname' => [
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname'
		],
		'customer_number' => [
			'label' => 'Kundennummer',
			'type' => 'method',
			'source' => 'getCustomerNumber'
		],
		'first_name' => array(
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname',
			'invisible' => true
		),
		'last_name' => array(
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname',
			'invisible' => true
		),
		// TODO Das gehört mal wieder nur nach TA
		'agency_contact_salutation' => array(
			'label' => 'Anrede',
			'type' => 'method',
			'source' => 'getSalutationFrontend',
			'pass_language' => true,
			'invisible' => true
		),
		'email_loop' => array(
			'label' => 'Wiederholt alle E-Mailplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_emailaddresses',
			'variable_name' => 'aEmailAddresses'
		),
		'address_loop' => array(
			'label' => 'Wiederholt alle Adressplatzhalter pro Adresse',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_addresses',
			'variable_name' => 'aAddresses'
		),
		'contactdetail_loop' => array(
			'label' => 'Wiederholt alle Kontaktdatenplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_details',
			'variable_name' => 'aDetails'
		)
	);
	
}

