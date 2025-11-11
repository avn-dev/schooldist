<?php

/**
 * Kommunikation: Templates
 * E-Mail und SMS
 */
class Ext_TC_Communication_Block extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_communication_blocks';
	
	protected $_sTableAlias = 'tc_cb';
	
	protected $_aJoinedObjects = array(
		'contents' => array(
			'class' => 'Ext_TC_Communication_Block_Content',
			'key' => 'block_id',
			'type' => 'child'
		)
	);
	
	protected $_aJoinTables = array(
		'languages' => array(
			'table' => 'tc_communication_blocks_languages',
			'foreign_key_field' => 'language_iso',
			'primary_key_field' => 'block_id'
		)
	);
	
	public function __get($sName) {
		
		if(
			mb_strpos($sName, 'text_') !== false ||
			mb_strpos($sName, 'html_') !== false
		) {
			
			$sField = mb_substr($sName, 0, mb_strrpos($sName, '_'));
			$aParts = explode('_', $sName);
			$sIso = end($aParts);
			
			$oContent = $this->getJoinedObjectChildByValue('contents', 'language_iso', $sIso);
			
			$mValue = $oContent->$sField;
			
		} else {
			$mValue = parent::__get($sName);
		}
		
		return $mValue;
		
	}
	
	public function __set($sName, $mValue) {
		
		if(
			mb_strpos($sName, 'text_') !== false ||
			mb_strpos($sName, 'html_') !== false
		) {
			
			$sField = mb_substr($sName, 0, mb_strrpos($sName, '_'));
			$aParts = explode('_', $sName);
			$sIso = end($aParts);
			
			$oContent = $this->getJoinedObjectChildByValue('contents', 'language_iso', $sIso);
			
			if(
				is_null($oContent) ||
				$oContent->id < 1)
			{
				$oContent = new Ext_TC_Communication_Block_Content();
			}
			
			$oContent->$sField = $mValue;
			$oContent->block_id = $this->id;
			$oContent->language_iso = $sIso;
			
			if($oContent->validate(true)) {
				$oContent->save();
			}
			
		} else {
			parent::__set($sName, $mValue);
		}
		
	}
	
}