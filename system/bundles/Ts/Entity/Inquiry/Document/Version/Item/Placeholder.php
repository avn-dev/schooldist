<?php

namespace Ts\Entity\Inquiry\Document\Version\Item;

class Placeholder extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'inquiryDocumentVersionItem'
	];

	protected $_aPlaceholders = [
		'additional_fee' => [
			'label' => 'Bezeichnung der Gebühr',
			'type' => 'field',
			'source' => 'description'
		],
		'additional_fee_amount' => [
			'label' => 'Betrag der Gebühr',
			'type' => 'field',
			'source' => 'amount'
		],
		'additional_fee_id' => [
			'label' => 'ID der Gebühr',
			'type' => 'method',
			'source' => 'getAdditionalFeeID',
		],
		'additional_fee_type' => [
			'label' => 'Typ der Gebühr',
			'type' => 'method',
			'source' => 'getTypeName',
			'pass_language_object' => true
		],
	];

}
