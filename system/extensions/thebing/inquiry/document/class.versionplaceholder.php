<?php

class Ext_Thebing_Inquiry_Document_VersionPlaceholder extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'version'
	];

	protected $_aPlaceholders = [
		'document' => [
			'label' => 'Dokument',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'document',
			'variable_name' => 'oDocument'
		],
		'vat_rates' => [
			'label' => 'Steuersätze',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getVatRates',
			'variable_name' => 'aVatRates',
			'class' => '\Ts\Model\Document\Version\VatRate',
			'pass_language' => true
		],
		// TODO PHP8: Nullsafe ergänzen
		'service_from' => [
			'label' => 'Leistungsbeginn',
			'type' => 'method',
			'source' => 'getServicePeriod()->getStartDate',
			'format' => Ext_Thebing_Gui2_Format_Date::class
		],
		// TODO PHP8: Nullsafe ergänzen
		'service_until' => [
			'label' => 'Leistungsbeginn',
			'type' => 'method',
			'source' => 'getServicePeriod()->getEndDate',
			'format' => Ext_Thebing_Gui2_Format_Date::class
		],
		'company' => [
			'label' => 'Firma',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'company',
			'variable_name' => 'company'
		],
		'paymentterm_loop' => [
			'label' => 'Wiederholt alle Zahlungsbedingungen',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getPaymentTerms',
			'class' => \Ext_TS_Document_Version_PaymentTerm::class,
			'variable_name' => 'aVersionPaymentterms',
			'only_final_output' => true
		],
	];
	
}
