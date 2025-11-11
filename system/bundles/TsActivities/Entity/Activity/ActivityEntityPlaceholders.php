<?php

namespace TsActivities\Entity\Activity;

class ActivityEntityPlaceholders extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'activityEntity'
	];

	protected $_aPlaceholders = [
		'activity_name' => [
			'label' => 'Aktivitätsname',
			'type' => 'method',
			'source' => 'getName',
			'pass_language' => true
		],
		'activity_shortname' => [
			'label' => 'Aktivitätsabkürzung',
			'type' => 'field',
			'source' => 'short',
		],
	];
}