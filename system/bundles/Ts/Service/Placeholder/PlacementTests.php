<?php

namespace Ts\Service\Placeholder;

class PlacementTests extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'placement_test'
	];

	protected $_aPlaceholders = [
		'placement_test_correct_answers' => [
			'label' => 'Korrekte Antworten',
			'type' => 'method',
			'source' => 'getFormattedTotalCorrectAnswers'
		],
		'inquiry' => [
			'label' => 'Buchung',
			'type' => 'parent',
			'source' => 'getInquiry',
			'parent' => 'method',
			'class' => \Ext_TS_Inquiry::class,
			'variable_name' => 'inquiry',
			'exclude_placeholders' => ['placement_test_loop']
		],
	];

}