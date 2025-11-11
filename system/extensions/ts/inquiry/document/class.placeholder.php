<?php

class Ext_TS_Inquiry_Document_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'document'
	];

	protected $_aPlaceholders = [
		'document_number' => [
			'label' => 'Nummer',
			'type' => 'field',
			'source' => 'document_number'
		],
		'document_date' => [
			'label' => 'Rechnungsdatum',
			'type' => 'field',
			'source' => 'getLastVersion()->date',
			'format' => Ext_Thebing_Gui2_Format_Date::class
		],
		'document_main_type' => [
			'label' => 'Haupt-Typ',
			'type' => 'field',
			'source' => 'type',
			'format' => Ext_Thebing_Gui2_Format_Document_MainType::class,
		],
		'document_type' => [
			'label' => 'Typ',
			'type' => 'field',
			'source' => 'type',
			'format' => Ext_TS_Document_Release_Gui2_Format_DocType::class,
		],
		'document_latest_version' => [
			'label' => 'Aktuellste Dokumentenversion',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getLastVersion',
			'class' => \Ext_Thebing_Inquiry_Document_Version::class,
			'exclude_placeholders' => ['document']
		],
		'payment_due_date' => [
			'label' => 'Fälligkeitsdatum',
			'type' => 'method',
			'source' => 'getDuePayment',
			'format' => \Ts\Gui2\Format\ExpectedPayment::class,
			'format_parameter' => ['date']
		],
		'payment_due_amount' => [
			'label' => 'Fälliger Betrag',
			'type' => 'method',
			'source' => 'getDuePayment',
			'format' => \Ts\Gui2\Format\ExpectedPayment::class,
			'format_parameter' => ['amount']
		],
		'payment_process_key_next' => [
			'label' => 'Key für Zahlungsprozess erzeugen',
			'type' => 'method',
			'source' => 'getInquiry()->generatePaymentProcessKey',
			'method_parameter' => ['next'],
			'only_final_output' => true
		],
		'inquiry' => [
			'label' => 'Buchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getInquiry',
			'class' => \Ext_TS_Inquiry::class,
			'variable_name' => 'oInquiry',
		],
		'additional_fee_loop' => [
			'label' => 'Zusatzgebühren',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getAdditionalFees',
			'variable_name' => 'additionalFees',
			'class' => \Ext_Thebing_Inquiry_Document_Version_Item::class,
		],
		/**
		 * Deprecated
		 */
		'number' => [
			'label' => 'Nummer',
			'type' => 'field',
			'source' => 'document_number',
			'invisible' => true
		],
		'main_type' => [
			'label' => 'Haupt-Typ',
			'type' => 'field',
			'source' => 'type',
			'format' => Ext_Thebing_Gui2_Format_Document_MainType::class,
			'invisible' => true
		],
		'type' => [
			'label' => 'Typ',
			'type' => 'field',
			'source' => 'type',
			'format' => Ext_TS_Document_Release_Gui2_Format_DocType::class,
			'invisible' => true
		],
	];

}
