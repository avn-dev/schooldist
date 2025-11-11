<?php

namespace Ts\Service\Placeholder\Booking;

class Payment extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'oInquiryPayment'
	];

	protected $_aPlaceholders = [
		'payment_amount' => [
			'label' => 'Betrag der Bezahlung',
			'type' => 'method',
			'source' => 'getPayedAmountObject',
			'format' => \Ext_Thebing_Gui2_Format_Amount::class,
		],
		'payment_date' => [
			'label' => 'Datum der Bezahlung',
			'type' => 'field',
			'source' => 'date',
			'format' => \Ext_Thebing_Gui2_Format_Date::class
		],
		'payment_method' => [
			'label' => 'Methode der Bezahlung',
			'type' => 'method',
			'source' => 'getMethodName',
		],
		'payment_inquiry' => [
			'label' => 'Buchung',
			'type' => 'parent',
			'source' => 'getInquiry',
			'parent' => 'method',
			'class' => \Ext_TS_Inquiry::class,
			'variable_name' => 'oPaymentInquiry'
		],
		'payment_documents_loop' => [
			'label' => 'Rechnungen der Bezahlung',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getDocuments',
			'class' => \Ext_Thebing_Inquiry_Document::class
		]
	];
}