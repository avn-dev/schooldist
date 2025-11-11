<?php

class Ext_TC_Contact_Mapping_Frontend extends Ext_TC_Frontend_Mapping {
	
	/**
	 * Felder die benutzt werden
	 * @var type 
	 */
	protected $_aUsedFields = array(
		'salutation',
		'firstname',
		'lastname',
		'gender',
		'birthday'
	);
	
	protected function _configure()
	{		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		$oFormatGender = new Ext_TC_GUI2_Format_Gender();
		$this->addFormat('gender', $oFormatGender);
		
		$oFormatSalutation = new Ext_TC_GUI2_Format_Salutation();
		$this->addFormat('salutation', $oFormatSalutation);
		
		$oSelection = new Ext_TC_Gui2_Selection_Salutation();
		$oField		= $this->getField('salutation');
		$oField->addConfig('parent_label', L10N::t('Kontakt'));
		$oField->addConfig('label', L10N::t('Anrede'));
		$oField->addConfig('allowed_input_types', array(
			'select' => $aTypes['select'],
			'radio' => $aTypes['radio'],
		));
		$oField->addConfig('saver', Ext_TC_Contact_Mapping_Saver_Salutation::class);
		$oField->setSelection($oSelection);
		
		$oField		= $this->getField('firstname');
		$oField->addConfig('parent_label', L10N::t('Kontakt'));
		$oField->addConfig('label', L10N::t('Vorname'));
		
		$oField		= $this->getField('lastname');
		$oField->addConfig('parent_label', L10N::t('Kontakt'));
		$oField->addConfig('label', L10N::t('Nachname'));
		
		$oSelection = new Ext_TC_Gui2_Selection_Gender();
		$oField		= $this->getField('gender');
		$oField->addConfig('parent_label', L10N::t('Kontakt'));
		$oField->addConfig('label', L10N::t('Geschlecht'));
		$oField->addConfig('saver', Ext_TC_Contact_Mapping_Saver_Gender::class);
		$oField->setSelection($oSelection);
		
		$oField		= $this->getField('birthday');
		$oField->addConfig('parent_label', L10N::t('Kontakt'));
		$oField->addConfig('label', L10N::t('Geburtstag'));
		$oField->addConfig('allowed_input_types', array(
			'input'				=> $aTypes['input'],
			'birthdate_date'	=> $aTypes['birthdate_date'],
			'birthdate_select'	=> $aTypes['birthdate_select']
		));

	}
}
