<?php

namespace TsSponsoring\Entity\InquiryGuarantee;

use Ext_TC_Placeholder_Abstract;
use Ext_Thebing_Gui2_Format_Date;

class Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'inquiryGuarantee'
	);

	protected $_aPlaceholders = array(
		'inquiry_guarantee_number' => array(
			'label' => 'Nummer',
			'type' => 'field',
			'source' => 'number',
		),
		'inquiry_guarantee_start' => [
			'label' => 'Start',
			'type' => 'field',
			'source' => 'from',
			'format' => Ext_Thebing_Gui2_Format_Date::class,
		],
		'inquiry_guarantee_end' => [
			'label' => 'Ende',
			'type' => 'field',
			'source' => 'until',
			'format' => Ext_Thebing_Gui2_Format_Date::class,
		],
	);

}
