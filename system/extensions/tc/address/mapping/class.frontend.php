<?php

class Ext_TC_Address_Mapping_Frontend extends Ext_TC_Frontend_Mapping
{
	
	/**
	 * Felder, die benutzt werden
	 * 
	 * @var array
	 */
	protected $_aUsedFields = array(
		'country_iso',
		'label_id',
		'company',
		'address',
		'address_addon',
		'address_additional',
		'zip',
		'city',
		'state'
	);

	protected function _configure()
	{		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();

		$oSelection = new Ext_TC_Gui2_Selection_Country();
		$oField		= $this->getField('country_iso');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Land'));
		$oField->addConfig('css', 'thebing_address_country');
		$oField->addConfig('allowed_input_types', array(
			'select' => $aTypes['select']
		));
		$oField->setSelection($oSelection, true);

		$oSelection = new Ext_TC_Address_Selection_Label();
		$oField		= $this->getField('label_id');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Adresslabel'));
		$oField->addConfig('css', 'thebing_address_label');
		$oField->addConfig('allowed_input_types', array(
			'select' => $aTypes['select'],
			'radio' => $aTypes['radio']
		));
		$oField->setSelection($oSelection, true);
		
		$oField		= $this->getField('company');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Firma'));

		$oField		= $this->getField('address');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Anschrift'));
		
		$oField		= $this->getField('address_addon');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Adresszusatz'));
		
		$oField		= $this->getField('address_additional');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Sonstiges'));
		
		$oField		= $this->getField('zip');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('PLZ'));
		
		$oField		= $this->getField('city');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Stadt'));

		$oField		= $this->getField('state');
		$oField->addConfig('parent_label', L10N::t('Adresse'));
		$oField->addConfig('label', L10N::t('Bundesland'));
		$oField->addConfig('allowed_input_types', array(
			'input' => $aTypes['input']
		));

	}
	
}
