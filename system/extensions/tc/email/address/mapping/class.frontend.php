<?php

class Ext_TC_Email_Address_Mapping_Frontend extends Ext_TC_Frontend_Mapping
{
	
	/**
	 * Felder die benutzt werden
	 * @var type 
	 */
	protected $_aUsedFields = array(
		'email',
		'type'
	);
	
	protected function _configure()
	{		
		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		$oField		= $this->getField('email');
		$oField->addConfig('label', L10N::t('E-Mail'));
		$oField->addConfig('allowed_input_types', array(
			'input' => $aTypes['input']
		));
		
		$oField		= $this->getField('type');
		$oField->addConfig('label', L10N::t('E-Mail Typ'));
		$oField->addConfig('allowed_input_types', array(
			'select' => $aTypes['select']
		));
		
	}
	
}