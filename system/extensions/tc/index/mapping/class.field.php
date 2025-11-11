<?php

/**
 * UML - https://redmine.thebing.com/redmine/issues/272 
 */
class Ext_TC_Index_Mapping_Field extends Ext_TC_Mapping_Field
{
	/**
	 * Mögliche Konfigurationen 
	 */
	protected $_aConfigFields = array(
		'store',
		'type',
		'index',
		'analyzer',
		'term_vector',
	);
	
	public function __construct(array $aConfig, $bOriginal = false) {
		
		$this->addConfig('store', true);
		$this->addConfig('index', false);
		
		parent::__construct($aConfig, $bOriginal);
	}
	
}