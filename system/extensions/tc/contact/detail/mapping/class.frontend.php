<?php

class Ext_TC_Contact_Detail_Mapping_Frontend extends Ext_TC_Frontend_Mapping
{
	
	/**
	 * Felder die benutzt werden
	 * @var type 
	 */
	protected $_aUsedFields = array(
		'type',
		'value'
	);
	
	protected function _configure()
	{		
		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		$oField		= $this->getField('type');

		$oField->addConfig('parent_label', L10N::t('Kontakt Information'));
		$oField->addConfig('label', L10N::t('Art'));
		$oField->addConfig('allowed_input_types', array(
			'select' => $aTypes['select']
		));
		
		$oField		= $this->getField('value');
		$oField->addConfig('parent_label', L10N::t('Kontakt Information'));
		$oField->addConfig('label', L10N::t('Wert'));
		$oField->addConfig('allowed_input_types', array(
			'input' => $aTypes['input'],
			'textarea' => $aTypes['textarea'],
			'phone' => $aTypes['phone']
		));
	}
	
}