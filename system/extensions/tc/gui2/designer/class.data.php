<?php
/**
 * Klasse mit definition ALLER VERFÜGBAREN ELEMENTE im Designer
 */
class Ext_TC_Gui2_Designer_Data {
	
	/**
	 * get a List of Fix Elements for the current Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element 
	 */
	public function getFixTabElements(){		
		$aList = array();
		return $aList;
	}
	
	/**
	 * get a List of Dynamic Elements for the SR Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element 
	 */
	public function getDynamicTabElements(){
		
		$aList = array();
		
		/*$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'input';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Eingabefeld')
			)
		);
		$aList[] = $oElement;
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'textarea';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Textfeld')
			)
		);
		$aList[] = $oElement;
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'html';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('HTML Feld')
			)
		);
		$aList[] = $oElement;
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'checkbox';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Checkbox')
			)
		);
		$aList[] = $oElement;
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'date';
		$oElement->format		= 'Ext_TC_Gui2_Format_Date';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Datumsfeld')
			)
		);
		$aList[] = $oElement;
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'select';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Dropdown')
			)
		);
		$aList[] = $oElement;*/
		
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'upload';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Upload')
			)
		);
		$aList[] = $oElement;
		/*
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'image';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Bild')
			)
		);
		$aList[] = $oElement;
		*/
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'headline';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Überschrift')
			)
		);
		$aList[] = $oElement;
				
		$oElement = new Ext_TC_Gui2_Design_Tab_Element();
		$oElement->type			= 'content';
		$oElement->i18n = array(
			array(
				'language_iso'	=> Ext_TC_System::getInterfaceLanguage(),
				'name'			=> L10N::t('Mehrspaltiger Bereich')
			)
		);
		$aList[] = $oElement;
		
		return $aList;
	}
	
}
