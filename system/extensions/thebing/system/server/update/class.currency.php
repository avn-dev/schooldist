<?php

class Ext_Thebing_System_Server_Update_Currency extends Ext_Thebing_System_Server_Update {
	
	/**
	 * gibt den Klassennamen zurück
	 */
	public function getClassName() {
		return get_class($this);
	}

	protected function getUpdateData(){
		$aPostData = array();

		Ext_Thebing_Currency::updateCurrencyValues();

		$aPostData['kolumbus_currency']			= Ext_Thebing_Util::getTableInsertData('kolumbus_currency');
		$aPostData['kolumbus_currency_factor']	= Ext_Thebing_Util::getTableInsertData('kolumbus_currency_factor', strtotime('- 1 Week'));

		return $aPostData;
	}

	public function executeUpdate(){
		global $_VARS;
		
		try {
			
			/*
			 * @todo Daten erstmal holen, da diese nicht mehr per Cronjob gesendet werden
			 */
			
			#Ext_Thebing_Util::insertTableData('kolumbus_currency', $_VARS['kolumbus_currency'], false);
			#Ext_Thebing_Util::insertTableData('kolumbus_currency_factor', $_VARS['kolumbus_currency_factor'], false);
			
			//Ext_Thebing_Util::reportError('Externeserver Updatescript - Erfolgreich', print_r($_VARS, 1));
		} catch (Exception $e) {
			Ext_Thebing_Util::reportError('Externeserver Updatescript - Fehler', $e);
		}

	}

}