<?php

namespace Ts\Service\Placeholder\Booking;

class OtherContact extends \Ext_TC_Placeholder_Contact {

	protected $_aSettings = [
		'variable_name' => 'oOtherContact'
	];

	protected $_aPlaceholders = array(
		'type' => array(
			'label' => 'Beziehung',
			'type' => 'field',
			'source' => 'type'
		),
		'last_name' => array(
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname'
		),
		'first_name' => array(
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname'
		),
		'email' => array(
			'label' => 'E-Mail',
			'type' => 'field',
			'source' => 'email'
		),
		'phone_private' => array(
			'label' => 'Telefon',
			'type' => 'field',
			'source' => 'detail_phone_private'
		)
	);
	
}
