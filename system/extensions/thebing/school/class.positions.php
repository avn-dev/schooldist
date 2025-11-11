<?php

/**
 * @property $id
 * @property $created 	
 * @property $changed 	
 * @property $active 	
 * @property $creator_id 	
 * @property $user_id 	
 * @property $school_id 	
 * @property $position_key
 * @property $title 	
 * @property $position
 * 
 */


class Ext_Thebing_School_Positions extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_positions_order';

	protected $_sTableAlias = 'kpo';

	protected $_aFormat = array(
			'changed' => array(
							'format' => 'TIMESTAMP'
			),
			'created' => array(
							'format' => 'TIMESTAMP'
			)

	);

	/**
	 * Komplette Liste der Positionstypen, die sortiert werden dürfen
	 * @return array
	 */
	public static function getAllPositions(){

		$aPositionsAll = array(
			'additional_course'                 => '{$name} {$description}',
			'additional_accommodation'          => '{$name} {$description}',
			'course'							=> '{$weeks_units} {$course} {$from} - {$until}',
			'accommodation'						=> '{$weeks} {$accommodation} ({$roomtype}/{$meal}) {$from} - {$until}',
			'additional_general'                => '{$name}',
			'extra_night'						=> '{$nights}',
			'extra_week'						=> '{$weeks}',
			'transfer'							=> '{$transfer}: {$from} - {$to} ({$weekday} {$date} {$time})',
			'insurance'							=> '{$insurance} {$from} - {$until}',
			'special'							=> '{$special}',
			'activity'							=> '{$weeks_units} {$name} {$from} - {$until}',
		);

		return $aPositionsAll;
	}
	
}