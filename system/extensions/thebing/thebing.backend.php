<?php

use Core\Entity\ParallelProcessing\Stack;

define('LANGUAGE', 'en');

set_time_limit(300);
ini_set('memory_limit', '256M');
mb_internal_encoding('UTF-8');
bcscale(5);

L10N::setDefaultLanguage('en');

/**
 * v4
 */
class thebing_backend {

	function executeHook($strHook, & $mixInput) {
		global $oRequest, $strLanguage, $objWebDynamicsDAO, $system_data, $oL10N, $user_data, $session_data;
		
		switch ($strHook) {

			case "navigation_top" :

				$oNavigation = Ext_TS_System_Navigation::getInstance();
				$aNavigation = $oNavigation->getTopNavigation();

				// Framework Medien
				#unset($mixInput[30]);

				// Framework Einstellungen
				#unset($mixInput[40]);
				
				// Framework Userverwaltung
				unset($mixInput[80]);

				// Framework Auswertungen
				unset($mixInput[90]);
				
				// Framework Papierkorb
				unset($mixInput[100]);
				
				// Framework Support
				#unset($mixInput[110]);
				
				if(!empty($aNavigation)){
					$mixInput = $mixInput + $aNavigation;
				}

				break;
			case "navigation_left" :

				$sTask = $mixInput['name'];

				$oNavigation = Ext_TS_System_Navigation::getInstance();
				$aNavigation = $oNavigation->getLeftNavigation($sTask);

				if(!empty($aNavigation)){
					$mixInput['childs'] = $mixInput['childs'] + $aNavigation;
				}

				// Neue bundle-Elemente kommen vor thebing.backend.php
				\System::wd()->executeHook('ts_navigation_left', $mixInput);

				break;			

			case 'welcome_both':

				// SQL Updates einlesen
				//if(class_exists('Ext_LocalDev_Sql')) {
				// Da die Klasse auf jedem Live-System existiert, ist class_exists() ziemlich sinnlos
				if (Ext_Thebing_Util::isDevSystem() || Ext_Thebing_Util::isLocalSystem()) {
					$aDebug = Ext_LocalDev_Sql::update('v5');
					if(!empty($aDebug['query'])) {
						__pout($aDebug['query']);
					}
					$aDebug = Ext_LocalDev_Sql::update('core');
					if(!empty($aDebug['query'])) {
						__pout($aDebug['query']);
					}
					$aDebug = Ext_LocalDev_Sql::update('school');
					if(!empty($aDebug['query'])) {
						__pout($aDebug['query']);
					}
				}
				
				ini_set('max_execution_time', 300);
				include('thebing.backend.welcome_both.php');
				break;

			case 'welcome_left':
	
//				$mixInput[13]['title'] = L10N::t('Neueste Wünsche', 'Thebing » Welcome');
//				$mixInput[13]['function'] = array('Ext_TC_Welcome', 'getWishes');
//				$mixInput[13]['right'] = 'thebing_welcome_wishlist';

				break;
				
			case 'welcome_right':
				include('thebing.backend.welcome_right.php');
				break;

			case "system_frame":

				unset($_SESSION['thebing_navigation_top']);
				unset($_SESSION['thebing_navigation_left']);
				
				break;
			
			case 'login_check':
				unset($_SESSION);
				break;
				
			case 'login_ok':
				/* @var \Access $mixInput */
				$userData = $mixInput->getUserData();

				Ext_Gui2_Session::reset($userData['key']);

				// thebing.backend.login.php
				if($mixInput instanceof Access_Backend) {
					$mLogin = Ext_Thebing_Access::checkLogin($mixInput);
					if($mLogin !== true) {
						$mixInput->destroyAccess();
						__pout($mLogin);
					}
				}

				// Schule setzen beim Login
				$oSchoolIdHandler = new Ts\Handler\SchoolId();
				$oSchoolIdHandler->checkSchool();

				break;
				
			case 'has_right':

				// Nicht schulabhängig, hier abfragen, damit die Session nicht geholt wird
				if($mixInput['right'] === 'control') {
					break;
				}
				
				$bCheck = false;
				 
				$iSchoolId = Core\Handler\SessionHandler::getInstance()->get('sid');
				
				if(!is_array($mixInput['right'])) {
					$mixInput['right'] = array($mixInput['right'], '');
				}
				
				$mTempCheck = $mixInput['right'];
				$mTempCheck = reset($mTempCheck);

				if(
					count($mixInput['right']) == 2 &&
					!is_array($mTempCheck)
				) {
					// Es ist ein Array von Einzelrechten
					if(strpos($mixInput['right'][1], 'thebing_') === 0) {
						foreach($mixInput['right'] as &$sRight) {
							$sRight = [$sRight, ''];
						}
					} else {
						$mixInput['right'] = [$mixInput['right']];
					}
				}

				// Framework-Rechte über eigene Schulrechte abfragen
				$aFrameworkMapping = [
					'gui2_flexibility' => 'thebing_gui_flex',
					'licence_invoices' => 'core_licence_invoices',
					'update' => 'thebing_admin_update',
					'modules_admin' => 'thebing_admin_update',
					'backup' => 'thebing_admin_update',
					'whoisonline' => 'thebing_whoisonline',
					'parallelprocessing' => 'thebing_welcome_parallelprocessing',
					'languages' => 'thebing_admin_frontend_translations',
					
					'edit_add_category' => 'core_cms_folder-new',
					'edit_view_pages' => 'core_cms_page-view',
					'edit' => 'core_cms_page-edit',
					'page_pref' => 'core_cms_page-properties',
					'new_page' => 'core_cms_page-new',
					'edit_delete_pages' => 'core_cms_page-delete',
					'publish' => 'core_cms_page-publish',
					'templates' => 'core_cms_template-edit',
					
					'admin_sites' => 'core_cms_sites-edit',
					'page_admin' => 'core_cms_page-admin',
					'block_admin' => 'core_cms_blocks-edit',
					'pagetemplates' => 'core_cms_template-edit',
					'link_checker' => 'core_cms_page-check_links',
					'filter_admin' => 'core_cms_page-filter',

					'media_admin' => 'core_media-edit',
					'storage_admin' => 'core_storage-edit',
					
					'settings' => 'core_settings-edit'
					
				];				
				
				foreach((array)$mixInput['right'] as $sRight) {
					
					if(is_array($sRight)) {
						$sRight = implode('-', $sRight);
						$sRight = rtrim($sRight, '-');
					}

					// All School Recht
					if(
						$sRight == 'all_school' && 
						$iSchoolId == 0
					){
						$bCheck = true;
						break;
					} else if($sRight == 'all_school') {
						$bCheck = false;
						break;
					// Debugmode select deaktivieren damit man nicht ausversehen den immer an macht
                    // das ganze system wir dann langsamer
                    // daher bei fehler bitte immer per url aktivieren UND DANACH! wieder ausmachen!
					} else if($sRight == 'debug_mode') {
                        $bCheck =  false;
                        break;
                    }
					
                    if(
						substr($sRight, 0, 7) == 'thebing' ||
						substr($sRight, 0, 5) == 'core_' ||
						substr($sRight, 0, 7) == 'school_' ||
						substr($sRight, 0, 3) == 'ts_'
					) {
						$mixInput['return'] = true;
						$bCheckRight = Ext_Thebing_Access::hasRight($sRight); 
						
						if($bCheckRight){
							$bCheck = true;
							break;
						} else {
							$bCheck = false;
						}

					} elseif(
						array_key_exists($sRight, $aFrameworkMapping)
					) {
						$bCheckRight = Ext_Thebing_Access::hasRight($aFrameworkMapping[$sRight]);
						if($bCheckRight) {
							$bCheck = true;
							break;
						}
					}
					
				}

				$mixInput['has_right'] = $bCheck;

				break;

			// TODO Wird das überhaupt noch benutzt? system/legacy/admin/js/calendar/calendar.ajax.php
			case 'manipulate_calendar_data':

				$iTestTime = mktime(12,13,14, 12, 31, 1990);
				$sTestTime = Ext_Thebing_Format::LocalDate($iTestTime, (int)$_VARS['school_id']);
				$sFormat = str_replace(array('31','12','1990','90'), array('%d', '%m', '%Y', '%y'), $sTestTime);

				if(
					$sFormat == "" || 
					(
						is_numeric($sFormat) && 
						$sFormat <= 0
					)
				){
					$sFormat = "%d.%m.%Y";
				}

				$mixInput['sFormat'] = $sFormat;

				if(strpos($mixInput['aParams']['dateField'], 'holidays') === 0) {
					$mixInput['aActiveDays'] = array(0, 1, 6);
					
					$oInquiry = new Ext_TS_Inquiry((int)$_VARS['inquiry_id']);
					$aPeriod = $oInquiry->getCompletePeriod();

					$mixInput['sFirstDay'] = strftime($mixInput['sFormat'], $aPeriod['from']);
					$mixInput['sLastDay'] = strftime($mixInput['sFormat'], $aPeriod['until']);
				}
				
				break;	
			case 'ajax_gui_flexiblelist_setid':

				if(is_array($mixInput)) {
					$oClient = Ext_Thebing_Client::getInstance();

					if($oClient->flex_user_based) {
						$mixInput['id'] = $user_data['id'];
						$mixInput['item'] = 'user';
					} else {
						$mixInput['id'] = $user_data['client'];
						$mixInput['item'] = 'client';
					}
				} else {
					$mixInput = $user_data['client'];
				}

				break;	

			case 'wdmail_send':

				Ext_TC_Communication_WDMail::manipulateClass($mixInput);

				break;

			case 'fckeditor_config_js':

				echo "

					FCKConfig.ToolbarSets[\"Basic\"] = [
						['FontFormat','FontSize','-','RemoveFormat','-','PasteWord'],
						['Bold','Italic','Underline','StrikeThrough','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','TextColor','-','Source']
					] ;

				";

				break;

			case "languages_update":
			case "languages_insert":

				break;

			case "languages_delete":

				break;
				
			case 'system_update':

				// Datenbankprozeduren (neu) setzen
				Ext_Thebing_Db_StoredFunctions::updateStoredFunctions();
				
				// Queries und Dateien aus dem Systemupdate direkt ins Thebing Update schreiben
				if(Ext_Thebing_Util::isDevSchoolSystem()) {

					Ext_TI_Update::transferUpdate($mixInput, 'test');

				}

				break;

			case 'system_update_database':
				
				if(
					Ext_Thebing_Util::isDevSystem() ||
					Ext_Thebing_Util::isTestSystem()
				) {
					// Nichts machen
					$oUpdate = null;
				} elseif(Ext_Thebing_Util::isLive2System()) {
					$oUpdate = new Ext_Thebing_Update('test');
				} else {
					$oUpdate = new Ext_Thebing_Update('live');
				}

				if($oUpdate) {

					#$oUpdate->writeDBTableNew('tc_flex_sections');

					// Muss per Query aktualisiert werden, damit es hier eine Versionierung gibt!
					#$oUpdate->writeDBTableNew('customer_db_config');

					// Kommen über das normale Update
					#$oUpdate->writeDBTableNew('language_data');
					#$oUpdate->writeDBTableNew('language_files');

					// Wird nicht mehr benötigt, Rechte sind ja individuell
					#$oUpdate->writeDBTableNew('system_rights');
					
					// Wird nicht mehr benötigt, weil die Einstellungen nicht mehr in der DB abgelegt werden
					#$oUpdate->writeDBTableNew('system_imgbuilder');

					$oUpdate->writeDBTableNew('kolumbus_statistic_cols_definitions');

					/*
					 * Diese Tabelle wird weiterhin mit insertMany() befüllt, da diese Tabelle bei PreparedStatements total ausrastet?
					 * Die language_data hat ~20000 Einträge, diese hier > 7000. Die language_data läuft in unter einer halben Minute durch,
					 * bei dieser hier ist nach fünf Minuten (abgebrochen) immer noch nichts fertig gewesen #6004
					 */
					$oUpdate->writeDBTableNew('kolumbus_statistic_cols_definitions_access', false, false);
					$oUpdate->writeDBTableNew('kolumbus_statistic_cols_definition_options');
					$oUpdate->writeDBTableNew('kolumbus_statistic_cols_groups');

					$oUpdate->writeDBTableNew('kolumbus_examination_sections_entity_type');

					$oUpdate->updateFrontendTranslations();

					$oUpdate->updateBasicStatistics();

					$oUpdate->sendEmailLog();

					Ext_Thebing_Util::updateLanguageFields();

				}

				break;

			case 'framework_logos':

				Ext_Thebing_Util::getFrameworkLogosHook($mixInput);

				break;
			case 'set_locale':

				Ext_Thebing_Util::getAndSetTimezone();

				break;
				
			case "logout":

				if (isset($mixInput['key'])) {
					Ext_Gui2_Session::reset($mixInput['key']);
				}
				
				break;

			case 'system_main_frame':

				break;

			case 'ext_localdev_sql_query':

				// Query im Update speichern
				if(
					class_exists('Ext_TI_Update') &&
					Ext_TC_Util::getHost() === 'dev.school.fidelo.com' // Nicht Methode, da das sonst lokal ausgeführt werden würde
				) {
					$oUpdate = Ext_TI_Update::getInstance();
					$oUpdate->saveUpdateQuery($mixInput, 'test');
				}

				break;

			case 'l10n_getdata':
				
				Ext_TC_L10N::getIndividualTranslations($mixInput);
				
				break;
				
			case 'system_color':
				
				$sSystemColor = Ext_Thebing_Util::getSystemColor();
				
				if(!empty($sSystemColor)) {
					$mixInput = $sSystemColor;
				}

				break;
			
			case 'check_valid_license':

				break;

			case 'admin_page_template':

				if($mixInput['resource'] === 'system') {
					$mixInput['template'] = 'system/bundles/Tc/Resources/views/system.tpl';
				}

				break;

			// Wird von jedem System ausgeführt
			case 'tc_cronjobs_hourly_execute':

				$oStackRepository = Stack::getRepository();
				$oStackRepository->writeToStack('admin/dashboard', [], 100);

				break;

			case "user_data_backend" :

				// TODO $user_data['client'] entfernen
				$mixInput['client'] = Ext_Thebing_Client::getClientId();

				break;

			default :
				break;
		}
	}
}

Ext_Thebing_Util::setFactoryAllocations();

\System::wd()->addHook('user_data_backend', 'thebing');

\System::wd()->addHook('navigation_top', 'thebing');
\System::wd()->addHook('navigation_left', 'thebing');
\System::wd()->addHook('toolbar_tabs', 'thebing');

\System::wd()->addHook('welcome_both', 'thebing');
\System::wd()->addHook('welcome_left', 'thebing');
\System::wd()->addHook('welcome_right', 'thebing');

\System::wd()->addHook('login_ok', 'thebing');
\System::wd()->addHook('logout', 'thebing');
\System::wd()->addHook('has_right', 'thebing');

\System::wd()->addHook('system_frame', 'thebing');

\System::wd()->addHook('ajax_gui_flexiblelist_setid', 'thebing');

\System::wd()->addHook('manipulate_calendar_data', 'thebing');

\System::wd()->addHook('wdmail_send', 'thebing');

\System::wd()->addHook('fckeditor_config_js', 'thebing');

\System::wd()->addHook('languages_update', 'thebing');
\System::wd()->addHook('languages_insert', 'thebing');
\System::wd()->addHook('languages_delete', 'thebing');

\System::wd()->addHook('system_update', 'thebing');
\System::wd()->addHook('system_update_database', 'thebing');
\System::wd()->addHook('system_main_frame', 'thebing');
\System::wd()->addHook('ext_localdev_sql_query', 'thebing');

\System::wd()->addHook('framework_logos', 'thebing');

\System::wd()->addHook('set_locale', 'thebing');

\System::wd()->addHook('l10n_getdata', 'thebing');

\System::wd()->addHook('system_color', 'thebing');

\System::wd()->addHook('check_valid_license', 'thebing');

\System::wd()->addHook('ts_inquiry_placeholder_replace', 'thebing');

\System::wd()->addHook('tc_cronjobs_hourly_execute', 'thebing');

\System::wd()->addHook('admin_page_template', 'thebing');

