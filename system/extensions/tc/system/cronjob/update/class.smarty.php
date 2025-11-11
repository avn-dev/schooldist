<?php

class Ext_TC_System_Cronjob_Update_Smarty extends Ext_TC_System_CronJob_Update {
	
	public function executeUpdate() {
		
		try {
		
			$oSmarty = new SmartyWrapper();

			$oSmarty->clearAllCache(SmartyWrapper::CLEAR_EXPIRED);
			
			/*
			 * Ablaufdatum mit SmartyWrapper::CLEAR_EXPIRED wird in der Methode nicht unterstützt.
			 * Daher fester Zeitraum von 48h
			 */
			$oSmarty->clearCompiledTemplate(null, null, (48*60*60));

			self::log('Ext_TC_System_Cronjob_Update_Smarty - Clear cache files');	
			
		} catch(Exception $e) {
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
		}
		
	}
	
}
