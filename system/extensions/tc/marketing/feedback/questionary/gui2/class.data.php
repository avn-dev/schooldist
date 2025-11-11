<?php

class Ext_TC_Marketing_Feedback_Questionary_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * Dialog um Themen anzulegen
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog($oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Fragebogen "{name}" editieren'), $oGui->t('Fragebogen anlegen'));	

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_mqn',
			'db_column' => 'name',
			'required' => true
		)));
		
		$sObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');
		$aObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		
		$oDialog->setElement($oDialog->createRow($sObjectLabel, 'select', array(
			'db_alias' => 'tc_fqn',
			'db_column' => 'objects',
			'multiple' => 5, 
			'jquery_multiple' => 1,
			'select_options' => $aObjects,
			'searchable' => 1,
			'style' => 'height: 105px;',
			'required' => true
		)));
		
		$sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Questionary', 'getSubObjectLabel');
		
		$oSelection = Ext_TC_Factory::getObject('Ext_TC_Marketing_Feedback_Questionary_Gui2_Selection_SubObjects');
		
		$oDialog->setElement($oDialog->createRow($sSubObjectLabel, 'select', array(
			'db_alias' => 'tc_fqn',
			'db_column' => 'subobjects',
			'multiple' => 5, 
			'jquery_multiple' => 1,
			'selection' => $oSelection,
			'searchable' => 1,
			'style' => 'height: 105px;',
			'required' => true,
			'dependency' => array(
				array(
					'db_alias' => 'tc_fqn',
					'db_column' => 'objects'
				),
			)
		)));
		
		return $oDialog;
	}
	
}	
	
?>