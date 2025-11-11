<?php

class tc_backend {
	
	function executeHook($strHook, &$mixInput) {
		global $_VARS, $user_data;
		
		switch ($strHook) {
			case "navigation_top" :
				
				$oNavigation = Ext_TC_System_Navigation::getInstance();
				$aNavigation = $oNavigation->getTopNavigation();
				
				if(!empty($aNavigation)){
					$mixInput = $mixInput + $aNavigation;
				}

				break;
			case "navigation_left" :
				
				$sTask = $mixInput['name'];
				
				$oNavigation = Ext_TC_System_Navigation::getInstance();
				$aNavigation = $oNavigation->getLeftNavigation($sTask);
				
				if(!empty($aNavigation)){
					$mixInput['childs'] = $mixInput['childs'] + $aNavigation;
				}

				break;
			case 'system_update':

				\Core\Facade\Cache::forgetGroup(\TcExternalApps\Service\AppService::CACHE_GROUP);
				
				// Queries und Dateien aus dem Systemupdate direkt ins Thebing Update schreiben
				if(Ext_TC_Util::isDevCoreSystem()) {

					Ext_TI_Update::transferUpdate($mixInput, 'core');

				}

				break;
		
			case 'extension_update':

				// Queries und Dateien aus dem Systemupdate direkt ins Thebing Update schreiben
				if(Ext_TC_Util::isDevCoreSystem()) {

					Ext_TI_Update::transferUpdate($mixInput, 'core');

				}
				
				break;
			case 'login_check':
				unset($_SESSION);
				break;
			case 'login_ok':

				Ext_Gui2_Session::reset($mixInput->key);
				
				if($mixInput instanceof Access_Backend) {
					$mLogin = Ext_TC_Access::checkLoginData($mixInput);
					if($mLogin !== true){
						$mixInput->destroyAccess();
						Core\Handler\SessionHandler::getInstance()->getFlashBag()->add('error', $mLogin);
					}
				}
				
				break;
			case 'has_right':

				$bCheck = null;

				$mTempCheck = (array)$mixInput['right'];
				$mTempCheck = reset($mTempCheck);
				
				if(
					is_array($mixInput['right']) &&
					count($mixInput['right']) == 2 &&
					!is_array($mTempCheck)
				) {
					$bCheck = Ext_TC_User::hasRight($mixInput['right'][0], $mixInput['right'][1]);
				} else if(
					is_array($mixInput['right'])
				){
					$bCheck = false;
					foreach((array)$mixInput['right'] as $mRight){
						
						$bCheckTemp = false;
						
						if(
							is_array($mRight) &&
							count($mRight) == 2 &&
							!is_array($mRight[0])		
						) {
							$bCheckTemp = Ext_TC_User::hasRight($mRight[0], $mRight[1]);
						}

						if($bCheckTemp){
							$bCheck = true;
							break;
						}
					} 
				} elseif(
					$mixInput['right'] == 'update' ||
					$mixInput['right'] == 'modules_admin'
				) {
					$bCheck = Ext_TC_User::hasRight('core_updates', 'execute');
				} else {
					// Nichttun das passert generel immer
					//$bCheck = hasRight($mRight);
				}
				
				if($bCheck != null){
					$mixInput['has_right'] = $bCheck;
					$aRight['return'] = true;
				}

				break;
			case "toolbar_tabs" :
				
				$sLink = L10N::t("Cache bereinigen",'Thebing Core » Toolbar');
				$sLink = '<a href="/admin/extensions/tc/flush_cache.html" target="_blank" >'.$sLink.'</a>';
				$mixInput[7]['text'] = $sLink;
				$mixInput[7]['url'] = "#";
				$mixInput[7]['type'] = "html";
				
				$sLink = L10N::t("Dialoge freigeben",'Thebing Core » Toolbar');
				$sLink = '<a href="/admin/extensions/tc/unlock_dialog.html" target="_blank" >'.$sLink.'</a>';
				$mixInput[8]['text'] = $sLink;
				$mixInput[8]['url'] = "#";
				$mixInput[8]['type'] = "html";
				
			break;	
			case "user_data_backend" :

				Ext_TC_System::setInterfaceLanguage('de');
				
				break;	
			
			case 'welcome_both':

				// SQL Updates einlesen
				if(class_exists('Ext_LocalDev_Sql')) {
					$aDebug = Ext_LocalDev_Sql::update('v5');
					if(!empty($aDebug['query'])) {
						__pout($aDebug['query']);
					}
					$aDebug = Ext_LocalDev_Sql::update('core');
					if(!empty($aDebug['query'])) {
						__pout($aDebug['query']);
					}
				}

				// News
				$mixInput[0]['title'] = L10N::t('Ankündigungen', 'Thebing » Welcome');
				$mixInput[0]['function'] = ['Ext_TC_Welcome', 'getNewsContent'];
				$mixInput[0]['class'] = 'box-warning news';
				$mixInput[0]['show_always'] = 1;
				$mixInput[0]['no_padding'] = true;
				$mixInput[0]['handler'] = (new \Admin\Components\Dashboard\Handler(3, 12, true))
					->min(2, 4)
					->deletable(false);

				break;
				
			case 'wdmail_send':

				Ext_TC_Communication_WDMail::manipulateClass($mixInput);

				break;
						
			case 'ext_localdev_sql_query':

				// Query im Update speichern
				if(
					class_exists('Ext_TI_Update') &&
					Ext_TC_Util::getHost() === 'dev.core.fidelo.com' // Nicht Methode, da das sonst lokal ausgeführt werden würde
				) {
					$oUpdate = Ext_TI_Update::getInstance();
					$oUpdate->saveUpdateQuery($mixInput, 'core');
				}

				break;
			
			case "logout":

				if (!empty($key = $mixInput->key)) {
					Ext_Gui2_Session::reset($key);
				}
				
				break;

			case 'set_locale':

				Ext_TC_Util::getAndSetTimezone();

				break;

			// Wird von Core ausgeführt, nur für Agentur
			case 'tc_cronjobs_15minutes':

				$oImapUpdate = new Ext_TC_System_CronJob_Update_Imap();
				$oImapUpdate->initializeUpdate();

				break;

			// Wird von Core ausgeführt, nur für Agentur
			case 'tc_cronjobs_hourly':

				// Wechselkurse aktualisieren
				$oExchangerateUpdate = new Ext_TC_System_CronJob_Update_ExchangeRate();
				$oExchangerateUpdate->initializeUpdate();

				// Frontend-Elemente-Aktualisierung
				$oFrontendUpdate = new Ext_TC_System_CronJob_Update_Frontend();
				$oFrontendUpdate->initializeUpdate();

				// Smarty-Cache leeren
				// TODO: Fehlt in der Schulsoftware, viel Smarty wird da aber nicht benutzt
				$oSmartyUpdate = new Ext_TC_System_Cronjob_Update_Smarty();
				$oSmartyUpdate->initializeUpdate();

				// Depuration
				$oDepurationUpdate = new Ext_TC_System_Cronjob_Update_Depuration();
				$oDepurationUpdate->initializeUpdate();

				// Execute Hook
				$oHook = new Ext_TC_System_CronJob_Update_60MinutesHook();
				$oHook->initializeUpdate();

				break;

			// Wird von Core ausgeführt, nur für Agentur
			case 'tc_cronjobs_daily':

				break;

		}

	}

}

\System::wd()->addHook('set_locale', 'tc');
\System::wd()->addHook('navigation_top', 'tc');
\System::wd()->addHook('navigation_left', 'tc');
\System::wd()->addHook('system_update', 'tc');
\System::wd()->addHook('extension_update', 'tc');
\System::wd()->addHook('login_check', 'tc');

\System::wd()->addHook('login_ok', 'tc');
\System::wd()->addHook('logout', 'tc');

\System::wd()->addHook('toolbar_tabs', 'tc');
\System::wd()->addHook('has_right', 'tc');
\System::wd()->addHook('user_data_backend', 'tc');
\System::wd()->addHook('welcome_both', 'tc');
\System::wd()->addHook('wdmail_send', 'tc');
\System::wd()->addHook('ext_localdev_sql_query', 'tc');

\System::wd()->addHook('tc_cronjobs_15minutes', 'tc');
\System::wd()->addHook('tc_cronjobs_hourly', 'tc');
\System::wd()->addHook('tc_cronjobs_daily', 'tc');

$aAllocations = array(
	'Ext_Gui2_Config_Parser' => 'Ext_TC_Gui2_Config',
	'Util' => 'Ext_TC_Util'
);

Ext_TC_Factory::setAllocations($aAllocations);
