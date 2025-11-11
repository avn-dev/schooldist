<?php

namespace TsSponsoring\Entity\Placeholder\Sponsor;

class Contact extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'oSponsorContact'
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
		'sponsor_contact_email_object' => array(
			'label' => 'E-Mail-Adresse',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getFirstEmailAddress'
		),
		'contactdetail_loop' => array(
			'label' => 'Wiederholt alle Kontaktdatenplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_details',
			'variable_name' => 'aDetails'
		),
		'email_loop' => array(
			'label' => 'Wiederholt alle E-Mailplatzhalter',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_emailaddresses',
			'variable_name' => 'aEmailAddresses',
			'invisible' => true
		),
		'address_loop' => array(
			'label' => 'Wiederholt alle Adressplatzhalter pro Adresse',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts_to_addresses',
			'variable_name' => 'aAddresses',
			'invisible' => true
		),
	);

}
