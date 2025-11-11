<?php

namespace Poll\Entity;

class Question extends \WDBasic {

	protected $_sTable = 'poll_questions';
	protected $_sTableAlias = 'pq';

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
	
	/**
	 * Gibt true zurück bei Feldern, bei denen feste Werte hinterlegt sind
	 * @return boolean
	 */
	public function isOptionField() {
		
		return self::checkOptionField($this->template);
		
	}
	
	public static function checkOptionField($sTemplate) {
		$aOptionFields = array("select", "list", "radio", "check", "block_start", "block_item");
			
		if (in_array($sTemplate, $aOptionFields)) {
			return true;
		}
		return false;
	}
	
	/**
	 * True wenn Frage Fragen-Matrix ist
	 * @return boolean
	 */
	public function isMatrix() {

		if($this->template === 'matrix') {
			return true;
		}

		return false;
	}
	
	public function isStars() {
		return ($this->template === 'stars');
	}

	public function isCheckbox() {
		return ($this->template === 'check');
	}

	public function getOptions($sLanguage) {
		
		$aData = \Util::decodeSerializeOrJson($this->data);
		
		$aOptions = array();
		
		if(!empty($aData)) {
			
			foreach($aData as $aItem) {
				$aOptions[$aItem['value']] = $aItem[$sLanguage];
			}
			
		}
		
		return $aOptions;
		
	}

	/**
	 * Liefert die codierten Daten als decodiertes Array zurück
	 * @return array 
	 */
	public function getDataArray() {
		
		$aData = \Util::decodeSerializeOrJson($this->data);
		
		return $aData;
	}
	
}