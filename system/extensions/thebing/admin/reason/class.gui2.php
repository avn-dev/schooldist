<?php

class Ext_Thebing_Admin_Reason_Gui2 extends Ext_Thebing_Gui2_Data
{
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oDialog = $oGui->createDialog($oGui->t('Grund "{name}" bearbeiten'), $oGui->t('Neuen Grund anlegen'));


		$oRow = $oDialog->createRow($oGui->t('Name'), 'input', array('db_column' => 'name', 'required' => 1));
		$oDialog->setElement($oRow);

		#############################################################################################################

		$oDialog->width = 950;
		
		return $oDialog;
	}
	
	static public function getWhere() {

		return ['client_id' => Ext_Thebing_Client::getClientId()];
	}

}