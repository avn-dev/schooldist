<?php

namespace Poll\Entity;

class Plausicheck extends \WDBasic {

	protected $_sTable = 'poll_plausichecks';
	protected $_sTableAlias = 'ppc';

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC,
	 *			'orderby_set'=>false
	 *			'query' => false,
     *          'readonly' => false,
	 *			'cloneable' => true,
	 *			'static_key_fields' = array('field' => 'value'),
	 *			'on_delete' => 'cascade' / '' ( nur bei "childs" möglich ),
	 *			'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'poll' => array(
			'class' => 'Ext_Poll_Poll',
			'key' => 'idPoll',
			'type' => 'parent'
		)
	);

	protected $_aFormat = array(
		'idPoll' => array('required' => true),
		'idParagraph' => array('required' => true)
	);

	
	public function __get($sName) {
		if($sName=='messages'){
			$mValue = (array)json_decode($this->_aData['messages'], true);
			return $mValue;
		}else{
			return parent::__get($sName);
		}
	}

	public function __set($sName, $mValue) {
		if($sName=='messages'){
			if(!empty($mValue)){
				$this->_aData['messages'] = json_encode($mValue);
			}
		}else{
			parent::__set($sName, $mValue);
		}
	}
	
	public function getMessage($sLanguage) {
		
		$aMessages = $this->messages;

		return (string)$aMessages[$sLanguage];
	}
	
}