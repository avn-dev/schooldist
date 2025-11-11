<?php

class Ext_TS_Inquiry_Contact_Traveller_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'traveller'
	];

	protected $_aPlaceholders = [
		'firstname' => [
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname'
		],
		'surname' => [
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname'
		],
		'customer_number' => [
			'label' => 'Kundennummer',
			'type' => 'method',
			'source' => 'getCustomerNumber'
		],
		'birthday' => [
			'label' => 'Geburtstag',
			'source' => 'birthday',
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'gender' => [
			'label' => 'Geschlecht',
			'source' => 'gender',
			'format' => 'Ext_Thebing_Gui2_Format_Gender'
		],
		'nationality' => [
			'label' => 'Nationalität',
			'source' => 'nationality',
			'format' => 'Ext_Thebing_Gui2_Format_Nationality'
		],
		'is_leader' => [
			'label' => 'Ist Gruppenleiter',
			'type' => 'method',
			'source' => 'isLeader',
			'format' => 'Ext_Gui2_View_Format_YesNo'
		],
		'vat_id' => [
			'label' => 'USt.-ID',
			'source' => 'detail_vat_number',
		],
		'contactdetail_loop' => [
			'label' => 'Wiederholt alle Kontaktdatenplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_details',
			'variable_name' => 'aDetails'
		],
		'email_loop' => [
			'label' => 'Wiederholt alle E-Mailplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_emailaddresses',
			'variable_name' => 'aEmailAddresses',
			'invisible' => true
		],
		'address_loop' => [
			'label' => 'Wiederholt alle Adressplatzhalter pro Adresse',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_addresses',
			'variable_name' => 'aAddresses',
			'invisible' => true
		],
	];
	
}
