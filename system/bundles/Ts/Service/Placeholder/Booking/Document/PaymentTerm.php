<?php

namespace Ts\Service\Placeholder\Booking\Document;

class PaymentTerm extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'paymentterm'
	];

	protected $_aPlaceholders = [
		'paymentterm_type' => [
			'label' => 'Typ der Zahlungsbedingung',
			'type' => 'field',
			'source' => 'type',
			'only_final_output' => true
		],
		'paymentterm_amount' => [
			'label' => 'Betrag der Zahlungsbedingung',
			'type' => 'method',
			'source' => 'getAmount',
			'format' => \Ext_Thebing_Gui2_Format_Amount::class,
			'only_final_output' => true
		],
		'paymentterm_date' => [
			'label' => 'Datum der Zahlungsbedingung',
			'type' => 'field',
			'source' => 'date',
			'format' => \Ext_Thebing_Gui2_Format_Date::class,
			'only_final_output' => true
		]
	];
}