<?php

namespace TsScreen\Entity;

class Schedule extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_screens_schedule';
	protected $_sTableAlias = 'ts_scrs';
	
	protected $_aJoinedObjects = [
		'screen' => [
			'class' => '\TsScreen\Entity\Screen',
			'key' => 'screen_id',
			'type' => 'parent',
			'bidirectional' => true
		]
	];
	
	/**
	 * Absichtlich altes Schema, damit ich es direkt bei Atlantic einspielen kann.
	 * @todo Auf neue Struktur umstellen
	 * @var array
	 */
	protected $_aAttributes = [
		'content' => [
			'class' => 'WDBasic_Attribute_Type_Text'
		],
		'html' => [
			'class' => 'WDBasic_Attribute_Type_Text'
		],
		'school_id' => [
			'class' => 'WDBasic_Attribute_Type_Int'
		],
		'buildings' => [
			'type' => 'array'
		],
		'autoplay_speed' => [
			'class' => 'WDBasic_Attribute_Type_Int'
		],
	];
	
	public function __set($sName, $mValue) {
		
		if(
			strpos($sName, 'date_') === 0 && 
			(
				empty($mValue) || 
				$mValue == '0000-00-00'
			)
		) {
			$mValue = null;
		}

		if(
			strpos($sName, 'time_') === 0 && 
			empty($mValue)
		) {
			$mValue = null;
		}
		
		parent::__set($sName, $mValue);
	}
	
}
