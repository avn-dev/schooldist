<?php

class Ext_TS_Inquiry_Journey_Activity_Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'journeyActivity'
	);

	protected $_aPlaceholders = array(
		'activity' => array(
			'label' => 'Aktivität',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'activity',
			'variable_name' => 'oActivity',
			'class' => TsActivities\Entity\Activity::class
		),
		'activity_start' => array(
			'label' => 'Beginn',
			'type' => 'field',
			'source' => 'from',
			'format' => 'Ext_Thebing_Gui2_Format_Date',
		),
		'activity_end' => array(
			'label' => 'Ende',
			'type' => 'field',
			'source' => 'until',
			'format' => 'Ext_Thebing_Gui2_Format_Date',
		),
		'activity_comment' => array(
			'label' => 'Kommentar',
			'type' => 'field',
			'source' => 'comment'
		),
		'activity_blocks' => array(
			'label' => 'Blöcke',
			'type' => 'field',
			'source' => 'blocks'
		),
		'activity_weeks' => array(
			'label' => 'Wochen',
			'type' => 'field',
			'source' => 'weeks'
		),
		'activity_assignment_loop' => array(
			'label' => 'Zugewiesene Aktivitäten',
			'type' => 'loop',
			'loop' => 'child',
			'child' => 'joined_object',
			'source' => 'allocations', // hieß schon so
			'variable_name' => 'oActivityAssignment',
			'class' => TsActivities\Entity\Activity\BlockTraveller::class,
			'exclude_placeholders' => ['inquiry']
		),
	);

}
