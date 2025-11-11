<?php

namespace TsScreen\Entity;

class Screen extends \Ext_Thebing_Basic {

	use \Core\Traits\UniqueKeyTrait;
	
	protected $_sTable = 'ts_screens';
	protected $_sTableAlias = 'ts_scr';

	protected $_aJoinedObjects = [
		'schedule' => [
			'class' => '\TsScreen\Entity\Schedule',
			'key' => 'screen_id',
			'type' => 'child',
			'bidirectional' => true
		]
	];

	/**
	 * Absichtlich altes Schema, damit ich es direkt bei Atlantic einspielen kann.
	 * @todo Auf neue Struktur umstellen
	 * @var array
	 */
	protected $_aAttributes = [
		'css' => [
			'class' => 'WDBasic_Attribute_Type_Text'
		]
	];
	
	public function save($bLog = true) {
		
		if(empty($this->key)) {
			$this->uniqueKeyLength = 32;
			$this->key = $this->getUniqueKey();
		}
		
		return parent::save($bLog);
	}
	
}
