<?php

namespace TsAccommodation\Service\Placeholder\Request;

class RecipientPlaceholder extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oRecipient'
	];

	protected $_aPlaceholders = [
		// Muss als erstes stehen, da sonst die Anbieter-Platzhalter über request geholt werden und da ist noch kein Anbieter zugewiesen
		'provider' => [
			'label' => 'Unterkunftsanbieter',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'provider',
			'variable_name' => 'oAccommodationProvider'
		],
		'request' => [
			'label' => 'Verfügbarkeitsanfrage',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'request',
			'variable_name' => 'oRequest'
		],
		'link_accept' => [
			'label' => 'Link zum Annehmen der Anfrage',
			'type' => 'method',
			'source' => 'getAcceptLink'
		],
		'link_reject' => [
			'label' => 'Link zum Ablehnen der Anfrage',
			'type' => 'method',
			'source' => 'getRejectLink'
		],
	];

}
