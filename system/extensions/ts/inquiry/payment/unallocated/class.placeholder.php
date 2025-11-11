<?php

class Ext_TS_Inquiry_Payment_Unallocated_Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'unallocatedPayment'
	];

	protected $_aPlaceholders = [
		'transaction_code' => [
			'label' => 'Transaktion',
			'type' => 'field',
			'source' => 'transaction_code'
		],
		'transaction_comment' => [
			'label' => 'Kommentar',
			'type' => 'field',
			'source' => 'comment'
		],
		'transaction_firstname' => [
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname'
		],
		'transaction_lastname' => [
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname'
		],
		'transaction_amount' => [
			'label' => 'Betrag',
			'type' => 'field',
			'source' => 'amount',
			'format' => \Ext_Thebing_Gui2_Format_Amount::class
		],
		'transaction_currency' => [
			'label' => 'Währung',
			'type' => 'field',
			'source' => 'amount',
			'format' => \Ext_Thebing_Gui2_Format_Currency::class
		]
	];

}
