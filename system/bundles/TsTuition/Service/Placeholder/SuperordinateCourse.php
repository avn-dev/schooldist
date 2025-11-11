<?php

namespace TsTuition\Service\Placeholder;

class SuperordinateCourse extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oSuperordinateCourse'
	);

	protected $_aPlaceholders = array(
		'superordinate_course_name' => array(
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getI18NName',
			'pass_language_last' => true,
			'method_parameter' => [
				'ts_sc_i18n',
				'name'
			]
		)
	);
	
}
