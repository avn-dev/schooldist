<?php

namespace TsSponsoring\Entity\Placeholder;

class Sponsor extends \Ext_TC_Placeholder_Abstract {

	protected $_aPlaceholders = [
		'sponsor_name' => [
			'label' => 'Sponsor: Name',
			'type' => 'field',
			'source' => 'name'
		],
		'sponsor_abbreviation' => [
			'label' => 'Sponsor: Abkürzung',
			'type' => 'field',
			'source' => 'abbreviation'
		],
		'sponsor_number' => [
			'label' => 'Sponsor: Nummer',
			'type' => 'field',
			'source' => 'number'
		],
		'sponsor_address_object' => [
			'label' => 'Sponsor: Adresse',
			'type' => 'parent',
			'source' => 'address'
		],
		'sponsor_note' => [
			'label' => 'Sponsor: Kommentar',
			'type' => 'field',
			'source' => 'comment'
		],
		'sponsor_staffmember_loop' => [
			'label' => 'Sponsor: Ansprechpartner',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'contacts'
		],
		'sponsor_expiration_date' => [
			'label' => 'Sponsoring gültig bis',
			'type' => 'field',
			'source' => 'valid_until'
		],
	];

}