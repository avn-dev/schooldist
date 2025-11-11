<?php
			class Ext_Thebing_Access_Client {

				static protected $iVersion = 1;
				static protected $aAccess = array();
				static protected $aAccessSort = array();
				static protected $mLicence = '';

				static public function checkVersion() {

				
			try {
				$sConfigKey = 'license_auth_key';
				if(!\System::d($sConfigKey)) {

					$oUpdate = new Update();
					$oUpdate->getFile('/system/includes/class.update.php', null, null);

					$oReflection = new \ReflectionMethod($oUpdate, 'requestUpdateServer');
					if($oReflection->isPublic()) {
						$sLicenseAuthKey = \Util::generateRandomString(40);
						$oUpdate->requestUpdateServer("/license.php?auth_key=".$sLicenseAuthKey);
						\System::s($sConfigKey, $sLicenseAuthKey);
					}

				}
			} catch(\Exception $ex) {
				__pout($ex, 1);
			} catch(\Error $e) {
				__pout($e, 1);
			}
		
					$sConfigKey = 'fix_2018_subrights';
					if(!\System::d($sConfigKey)) {
					
						// Dieser Check darf erst mit dem nächsten Login nach dem Update ausgeführt werden
						$oCheck = new Ext_TS_System_Checks_User_Subrights;
						$oCheck->executeCheck();

						\System::s($sConfigKey, 1);
					}
				
				$sConfigKey = 'fix_stack_data_fieldsize';
				if(!\System::d($sConfigKey)) {

					DB::executeQuery("ALTER TABLE `core_parallel_processing_stack` CHANGE `data` `data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
					DB::executeQuery("ALTER TABLE `core_parallel_processing_stack_error` CHANGE `data` `data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");

					\System::s($sConfigKey, 1);
				}
			
				$sConfigKey = 'fix_update_server';
				if(!\System::d($sConfigKey)) {
                    try {
					    DB::executeQuery("UPDATE `system_config` SET `c_value` = 'update.fidelo.com' WHERE `system_config`.`c_key` = 'update_server' AND `system_config`.`c_value` = 'update.thebing.com'");
                        \System::s($sConfigKey, 1);
                    } catch (\Throwable $e) {}
				}
			
					return self::$iVersion;
				}

				static protected $oClient = NULL;

				/**
				 * Holt eine Instance
				 * und läd die daten in den cache
				 * @return Object
				 */
				static public function getInstance($mLicence = ''){

					if (self::$oClient === NULL)  {
						self::$oClient = new Ext_Thebing_Access_Client($mLicence);
					}

					return self::$oClient;

				}


				public function __construct($mLicence = ''){

					if($mLicence == '') {
						$mLicence = \System::d('license');
					} 

					if($mLicence == ''){
						Ext_Thebing_Error::log("Error no Licence!"); 
						return false;  
					}

					self::$mLicence = $mLicence;

					$this->loadData();

				}
				
				public function checkAccess($sSectionKey, $sAccessKey = "") {

					$aTest = self::$aAccess;
					if(empty($aTest)){
						$this->loadData();
					}

					if(!isset(self::$aAccess[self::$mLicence])){
						Ext_Thebing_Error::log("No licence access data!");
						return false;
					}
					
					if(
						isset(self::$aAccess[self::$mLicence][$sSectionKey]) &&
						isset(self::$aAccess[self::$mLicence][$sSectionKey][$sAccessKey]) &&
						self::$aAccess[self::$mLicence][$sSectionKey][$sAccessKey] == 1
					){
						return true;
					} else if(
						isset(self::$aAccess[self::$mLicence][$sSectionKey]) && 
						empty($sAccessKey)
					){
						return true;
					}

					return false;
				}
				
				static public function check($sSectionKey, $sAccessKey = ""){

					$oClient = self::getInstance(self::$mLicence);

					return $oClient->checkAccess($sSectionKey, $sAccessKey);

				}
			
				public function getAccessSortRightList($bAll = false){
					if($bAll){
						return self::$aAccessSort;
					}
					return self::$aAccessSort[self::$mLicence];
				}

				public function getAccessRightList($bAll = false){
					if($bAll){
						return self::$aAccess;
					}
					return self::$aAccess[self::$mLicence];
				}

				static public function getSortRightList($mLicence = '',$bAll = false){
					$oClient = self::getInstance($mLicence);
					return $oClient->getAccessSortRightList($bAll); 
				}

				static public function getRightList($mLicence = '',$bAll = false){
					$oClient = self::getInstance($mLicence);
					return $oClient->getAccessRightList($bAll); 
				}

		
				/**
				 * prelive, 1.984
				 */
				protected function loadData(){

			
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][0] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'New',
						'access' => 'core_gui2_designer-new',
						'position' => 0
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][1] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'Edit',
						'access' => 'core_gui2_designer-edit',
						'position' => 1
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][2] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'Delete',
						'access' => 'core_gui2_designer-delete',
						'position' => 2
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][3] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'Show',
						'access' => 'core_gui2_designer-show',
						'position' => 3
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_templates']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][4] = [
						'section' => 'Frontend » Templates',
						'name' => 'New',
						'access' => 'core_frontend_templates-new',
						'position' => 4
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_templates']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][5] = [
						'section' => 'Frontend » Templates',
						'name' => 'Edit',
						'access' => 'core_frontend_templates-edit',
						'position' => 5
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_templates']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][6] = [
						'section' => 'Frontend » Templates',
						'name' => 'Delete',
						'access' => 'core_frontend_templates-delete',
						'position' => 6
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_countrygroups']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][7] = [
						'section' => 'Country groups',
						'name' => 'Show',
						'access' => 'core_admin_countrygroups-show',
						'position' => 7
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_countrygroups']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][8] = [
						'section' => 'Country groups',
						'name' => 'Edit',
						'access' => 'core_admin_countrygroups-edit',
						'position' => 8
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_countrygroups']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][9] = [
						'section' => 'Country groups',
						'name' => 'New',
						'access' => 'core_admin_countrygroups-new',
						'position' => 9
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_countrygroups']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][10] = [
						'section' => 'Country groups',
						'name' => 'Delete',
						'access' => 'core_admin_countrygroups-delete',
						'position' => 10
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_countrygroups']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][11] = [
						'section' => 'Country groups',
						'name' => 'Deactivate',
						'access' => 'core_admin_countrygroups-deactivate',
						'position' => 11
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][12] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'New',
						'access' => 'core_admin_exchangerate-new',
						'position' => 12
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][13] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'Edit',
						'access' => 'core_admin_exchangerate-edit',
						'position' => 13
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][14] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'Delete',
						'access' => 'core_admin_exchangerate-delete',
						'position' => 14
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][15] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'Show',
						'access' => 'core_admin_exchangerate-show',
						'position' => 15
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_vat']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][16] = [
						'section' => 'Tax » Tax rates',
						'name' => 'Edit',
						'access' => 'core_admin_vat-edit',
						'position' => 16
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_vat']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][17] = [
						'section' => 'Tax » Tax rates',
						'name' => 'Delete',
						'access' => 'core_admin_vat-delete',
						'position' => 17
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_vat']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][18] = [
						'section' => 'Tax » Tax rates',
						'name' => 'New',
						'access' => 'core_admin_vat-new',
						'position' => 18
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_vat']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][19] = [
						'section' => 'Tax » Tax rates',
						'name' => 'Show',
						'access' => 'core_admin_vat-show',
						'position' => 19
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_fonts']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][20] = [
						'section' => 'Templates » Fonts',
						'name' => 'Edit',
						'access' => 'core_admin_templates_fonts-edit',
						'position' => 20
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_fonts']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][21] = [
						'section' => 'Templates » Fonts',
						'name' => 'Delete',
						'access' => 'core_admin_templates_fonts-delete',
						'position' => 21
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_fonts']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][22] = [
						'section' => 'Templates » Fonts',
						'name' => 'New',
						'access' => 'core_admin_templates_fonts-new',
						'position' => 22
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_fonts']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][23] = [
						'section' => 'Templates » Fonts',
						'name' => 'Show',
						'access' => 'core_admin_templates_fonts-show',
						'position' => 23
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_templates']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][24] = [
						'section' => 'Frontend » Templates',
						'name' => 'Show',
						'access' => 'core_frontend_templates-show',
						'position' => 24
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][25] = [
						'section' => 'Templates » E-Mail » Templates',
						'name' => 'Edit',
						'access' => 'core_admin_templates_email-edit',
						'position' => 25
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][26] = [
						'section' => 'Templates » E-Mail » Templates',
						'name' => 'Delete',
						'access' => 'core_admin_templates_email-delete',
						'position' => 26
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][27] = [
						'section' => 'Templates » E-Mail » Templates',
						'name' => 'New',
						'access' => 'core_admin_templates_email-new',
						'position' => 27
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][28] = [
						'section' => 'Templates » E-Mail » Templates',
						'name' => 'Show',
						'access' => 'core_admin_templates_email-show',
						'position' => 28
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_sms']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][29] = [
						'section' => 'Templates » SMS templates',
						'name' => 'Edit',
						'access' => 'core_admin_templates_sms-edit',
						'position' => 29
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_sms']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][30] = [
						'section' => 'Templates » SMS templates',
						'name' => 'Delete',
						'access' => 'core_admin_templates_sms-delete',
						'position' => 30
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_sms']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][31] = [
						'section' => 'Templates » SMS templates',
						'name' => 'New',
						'access' => 'core_admin_templates_sms-new',
						'position' => 31
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_sms']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][32] = [
						'section' => 'Templates » SMS templates',
						'name' => 'Show',
						'access' => 'core_admin_templates_sms-show',
						'position' => 32
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][33] = [
						'section' => 'Frontend » Combinations',
						'name' => 'Edit',
						'access' => 'core_frontend_combinations-edit',
						'position' => 33
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][34] = [
						'section' => 'Frontend » Combinations',
						'name' => 'Delete',
						'access' => 'core_frontend_combinations-delete',
						'position' => 34
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][35] = [
						'section' => 'Frontend » Combinations',
						'name' => 'New',
						'access' => 'core_frontend_combinations-new',
						'position' => 35
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][36] = [
						'section' => 'Frontend » Combinations',
						'name' => 'Show',
						'access' => 'core_frontend_combinations-show',
						'position' => 36
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][37] = [
						'section' => 'Number range',
						'name' => 'Edit',
						'access' => 'core_numberranges-edit',
						'position' => 37
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][38] = [
						'section' => 'Number range',
						'name' => 'Delete',
						'access' => 'core_numberranges-delete',
						'position' => 38
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][39] = [
						'section' => 'Number range',
						'name' => 'New',
						'access' => 'core_numberranges-new',
						'position' => 39
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][40] = [
						'section' => 'Number range',
						'name' => 'Show',
						'access' => 'core_numberranges-show',
						'position' => 40
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['allocate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][41] = [
						'section' => 'Number range',
						'name' => 'Assignment',
						'access' => 'core_numberranges-allocate',
						'position' => 41
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email_layouts']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][42] = [
						'section' => 'Templates » E-Mail » Layouts',
						'name' => 'Edit',
						'access' => 'core_admin_templates_email_layouts-edit',
						'position' => 42
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email_layouts']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][43] = [
						'section' => 'Templates » E-Mail » Layouts',
						'name' => 'Delete',
						'access' => 'core_admin_templates_email_layouts-delete',
						'position' => 43
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email_layouts']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][44] = [
						'section' => 'Templates » E-Mail » Layouts',
						'name' => 'New',
						'access' => 'core_admin_templates_email_layouts-new',
						'position' => 44
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email_layouts']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][45] = [
						'section' => 'Templates » E-Mail » Layouts',
						'name' => 'Show',
						'access' => 'core_admin_templates_email_layouts-show',
						'position' => 45
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_numberranges']['settings'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][46] = [
						'section' => 'Number range',
						'name' => 'Settings',
						'access' => 'core_numberranges-settings',
						'position' => 46
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_emailaccounts']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][47] = [
						'section' => 'E-Mail accounts',
						'name' => 'Edit',
						'access' => 'core_admin_emailaccounts-edit',
						'position' => 47
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_emailaccounts']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][48] = [
						'section' => 'E-Mail accounts',
						'name' => 'Delete',
						'access' => 'core_admin_emailaccounts-delete',
						'position' => 48
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_emailaccounts']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][49] = [
						'section' => 'E-Mail accounts',
						'name' => 'New',
						'access' => 'core_admin_emailaccounts-new',
						'position' => 49
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_emailaccounts']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][50] = [
						'section' => 'E-Mail accounts',
						'name' => 'Show',
						'access' => 'core_admin_emailaccounts-show',
						'position' => 50
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['sync'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][51] = [
						'section' => 'Communication',
						'name' => 'Synchronize E-mail accounts',
						'access' => 'core_communication-sync',
						'position' => 51
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_communication_category']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][52] = [
						'section' => 'Communication » Categories',
						'name' => 'Edit',
						'access' => 'core_admin_communication_category-edit',
						'position' => 52
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_communication_category']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][53] = [
						'section' => 'Communication » Categories',
						'name' => 'Delete',
						'access' => 'core_admin_communication_category-delete',
						'position' => 53
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_communication_category']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][54] = [
						'section' => 'Communication » Categories',
						'name' => 'New',
						'access' => 'core_admin_communication_category-new',
						'position' => 54
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_communication_category']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][55] = [
						'section' => 'Communication » Categories',
						'name' => 'Show',
						'access' => 'core_admin_communication_category-show',
						'position' => 55
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][56] = [
						'section' => 'Communication',
						'name' => 'Delete',
						'access' => 'core_communication-delete',
						'position' => 56
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_vat']['admin'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][57] = [
						'section' => 'Tax » Tax rates',
						'name' => 'Rates',
						'access' => 'core_admin_vat-admin',
						'position' => 57
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['list'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][58] = [
						'section' => 'Communication',
						'name' => 'Display list',
						'access' => 'core_communication-list',
						'position' => 58
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication_signatures']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][59] = [
						'section' => 'Communication » E-Mail signatures',
						'name' => 'Edit',
						'access' => 'core_communication_signatures-edit',
						'position' => 59
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication_signatures']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][60] = [
						'section' => 'Communication » E-Mail signatures',
						'name' => 'Show',
						'access' => 'core_communication_signatures-show',
						'position' => 60
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_emailaccounts']['synergee_settings'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][61] = [
						'section' => 'E-Mail accounts',
						'name' => 'Synergee settings',
						'access' => 'core_admin_emailaccounts-synergee_settings',
						'position' => 61
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_referrers']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][62] = [
						'section' => 'Referrer',
						'name' => 'Edit',
						'access' => 'core_marketing_referrers-edit',
						'position' => 62
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_referrers']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][63] = [
						'section' => 'Referrer',
						'name' => 'Delete',
						'access' => 'core_marketing_referrers-delete',
						'position' => 63
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_referrers']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][64] = [
						'section' => 'Referrer',
						'name' => 'New',
						'access' => 'core_marketing_referrers-new',
						'position' => 64
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_referrers']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][65] = [
						'section' => 'Referrer',
						'name' => 'Show',
						'access' => 'core_marketing_referrers-show',
						'position' => 65
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['overwrite'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][66] = [
						'section' => 'Frontend » Combinations',
						'name' => 'Checkbox » Overwrite parameter',
						'access' => 'core_frontend_combinations-overwrite',
						'position' => 66
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][67] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'Deactivate',
						'access' => 'core_gui2_designer-deactivate',
						'position' => 67
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['exchangerate_update'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][68] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'Update rates',
						'access' => 'core_admin_exchangerate-exchangerate_update',
						'position' => 68
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_exchangerate']['exchangerate_view'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][69] = [
						'section' => 'Prices & addons »  Exchange rates',
						'name' => 'View rates',
						'access' => 'core_admin_exchangerate-exchangerate_view',
						'position' => 69
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_automatic']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][70] = [
						'section' => 'Templates » E-Mail » Automated E-mails',
						'name' => 'New',
						'access' => 'core_admin_templates_automatic-new',
						'position' => 70
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_automatic']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][71] = [
						'section' => 'Templates » E-Mail » Automated E-mails',
						'name' => 'Edit',
						'access' => 'core_admin_templates_automatic-edit',
						'position' => 71
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_automatic']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][72] = [
						'section' => 'Templates » E-Mail » Automated E-mails',
						'name' => 'Delete',
						'access' => 'core_admin_templates_automatic-delete',
						'position' => 72
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_automatic']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][73] = [
						'section' => 'Templates » E-Mail » Automated E-mails',
						'name' => 'Show',
						'access' => 'core_admin_templates_automatic-show',
						'position' => 73
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_user_flexibility']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][74] = [
						'section' => 'General » Custom fields',
						'name' => 'New',
						'access' => 'core_user_flexibility-new',
						'position' => 74
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_user_flexibility']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][75] = [
						'section' => 'General » Custom fields',
						'name' => 'Edit',
						'access' => 'core_user_flexibility-edit',
						'position' => 75
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_user_flexibility']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][76] = [
						'section' => 'General » Custom fields',
						'name' => 'Delete',
						'access' => 'core_user_flexibility-delete',
						'position' => 76
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_user_flexibility']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][77] = [
						'section' => 'General » Custom fields',
						'name' => 'Show',
						'access' => 'core_user_flexibility-show',
						'position' => 77
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_topics']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][78] = [
						'section' => 'Feedback » Topics',
						'name' => 'New',
						'access' => 'core_marketing_topics-new',
						'position' => 78
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_topics']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][79] = [
						'section' => 'Feedback » Topics',
						'name' => 'Edit',
						'access' => 'core_marketing_topics-edit',
						'position' => 79
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_topics']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][80] = [
						'section' => 'Feedback » Topics',
						'name' => 'Delete',
						'access' => 'core_marketing_topics-delete',
						'position' => 80
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_topics']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][81] = [
						'section' => 'Feedback » Topics',
						'name' => 'Show',
						'access' => 'core_marketing_topics-show',
						'position' => 81
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questions']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][82] = [
						'section' => 'Feedback » Questions',
						'name' => 'New',
						'access' => 'core_marketing_questions-new',
						'position' => 82
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questions']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][83] = [
						'section' => 'Feedback » Questions',
						'name' => 'Edit',
						'access' => 'core_marketing_questions-edit',
						'position' => 83
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questions']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][84] = [
						'section' => 'Feedback » Questions',
						'name' => 'Delete',
						'access' => 'core_marketing_questions-delete',
						'position' => 84
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questions']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][85] = [
						'section' => 'Feedback » Questions',
						'name' => 'Show',
						'access' => 'core_marketing_questions-show',
						'position' => 85
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_ratings']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][86] = [
						'section' => 'Feedback » Ratings',
						'name' => 'New',
						'access' => 'core_marketing_ratings-new',
						'position' => 86
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_ratings']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][87] = [
						'section' => 'Feedback » Ratings',
						'name' => 'Edit',
						'access' => 'core_marketing_ratings-edit',
						'position' => 87
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_ratings']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][88] = [
						'section' => 'Feedback » Ratings',
						'name' => 'Delete',
						'access' => 'core_marketing_ratings-delete',
						'position' => 88
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_ratings']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][89] = [
						'section' => 'Feedback » Ratings',
						'name' => 'Show',
						'access' => 'core_marketing_ratings-show',
						'position' => 89
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questionnaires']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][90] = [
						'section' => 'Feedback » Questionnaires',
						'name' => 'New',
						'access' => 'core_marketing_questionnaires-new',
						'position' => 90
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questionnaires']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][91] = [
						'section' => 'Feedback » Questionnaires',
						'name' => 'Edit',
						'access' => 'core_marketing_questionnaires-edit',
						'position' => 91
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questionnaires']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][92] = [
						'section' => 'Feedback » Questionnaires',
						'name' => 'Delete',
						'access' => 'core_marketing_questionnaires-delete',
						'position' => 92
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questionnaires']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][93] = [
						'section' => 'Feedback » Questionnaires',
						'name' => 'Show',
						'access' => 'core_marketing_questionnaires-show',
						'position' => 93
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_zendesk']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][94] = [
						'section' => 'General » Support',
						'name' => 'Show',
						'access' => 'core_zendesk-show',
						'position' => 94
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['feedback_list']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][95] = [
						'section' => 'Feedback » Results',
						'name' => 'View',
						'access' => 'feedback_list-edit',
						'position' => 95
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['feedback_list']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][96] = [
						'section' => 'Feedback » Results',
						'name' => 'Show',
						'access' => 'feedback_list-show',
						'position' => 96
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_combinations']['refresh'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][97] = [
						'section' => 'Frontend » Combinations',
						'name' => 'Refresh',
						'access' => 'core_frontend_combinations-refresh',
						'position' => 97
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_token']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][98] = [
						'section' => 'Frontend » API Token',
						'name' => 'New',
						'access' => 'core_frontend_token-new',
						'position' => 98
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_token']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][99] = [
						'section' => 'Frontend » API Token',
						'name' => 'Edit',
						'access' => 'core_frontend_token-edit',
						'position' => 99
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_token']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][100] = [
						'section' => 'Frontend » API Token',
						'name' => 'Delete',
						'access' => 'core_frontend_token-delete',
						'position' => 100
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_token']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][101] = [
						'section' => 'Frontend » API Token',
						'name' => 'Show',
						'access' => 'core_frontend_token-show',
						'position' => 101
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['sms'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][102] = [
						'section' => 'Communication',
						'name' => 'Text messages',
						'access' => 'core_communication-sms',
						'position' => 102
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_questionnaires']['duplicate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][103] = [
						'section' => 'Feedback » Questionnaires',
						'name' => 'Duplicate',
						'access' => 'core_marketing_questionnaires-duplicate',
						'position' => 103
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_frontend_preview']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][104] = [
						'section' => 'Frontend » Preview',
						'name' => 'Show',
						'access' => 'core_frontend_preview-show',
						'position' => 104
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_parallelprocessing_error_stack']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][105] = [
						'section' => 'Parallel Processing » Error stack',
						'name' => 'Delete',
						'access' => 'core_admin_parallelprocessing_error_stack-delete',
						'position' => 105
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_marketing_referrers']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][106] = [
						'section' => 'Referrer',
						'name' => 'Deactivate',
						'access' => 'core_marketing_referrers-deactivate',
						'position' => 106
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_external_apps']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][107] = [
						'section' => 'External Apps',
						'name' => 'List',
						'access' => 'core_external_apps',
						'position' => 107
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_licence_invoices']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][108] = [
						'section' => 'Licence » Invoices',
						'name' => 'Show',
						'access' => 'core_licence_invoices',
						'position' => 108
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_chat']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][109] = [
						'section' => 'Icon » Chat',
						'name' => 'Dummy',
						'access' => 'thebing_chat',
						'position' => 109
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_whoisonline']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][110] = [
						'section' => 'Reports » Logged in users',
						'name' => 'Dummy',
						'access' => 'thebing_whoisonline',
						'position' => 110
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_birthdays']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][111] = [
						'section' => 'Reports » Birthdays',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_birthdays',
						'position' => 111
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_system_information']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][112] = [
						'section' => 'Report » System Information',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_system_information',
						'position' => 112
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_parallelprocessing']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][113] = [
						'section' => 'Report » Parallel Processing',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_parallelprocessing',
						'position' => 113
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_students_course_related']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][114] = [
						'section' => 'Report » Students at school (course related)',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_students_course_related',
						'position' => 114
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_pending_housing_placements']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][115] = [
						'section' => 'Report » Pending housing placements',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_pending_housing_placements',
						'position' => 115
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_bookings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][116] = [
						'section' => 'XX - Report » Bookings',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_bookings',
						'position' => 116
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_agencies']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][117] = [
						'section' => 'XX - Report » Top 10 agencies this year',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_agencies',
						'position' => 117
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_students']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][118] = [
						'section' => 'XX - Reports » Students',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_students',
						'position' => 118
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_welcome_pending_confirmations']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][119] = [
						'section' => 'XX - Reports » Pending Confirmations',
						'name' => 'Dummy',
						'access' => 'thebing_welcome_pending_confirmations',
						'position' => 119
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][120] = [
						'section' => 'Enquiries » List',
						'name' => 'Show',
						'access' => 'thebing_students_contact_request-show',
						'position' => 120
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_enquiry']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][121] = [
						'section' => 'Enquiries » Student Record » Comments',
						'name' => 'Dummy',
						'access' => 'thebing_students_contact_enquiry',
						'position' => 121
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_group']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][122] = [
						'section' => 'Enquiries »  Student Record »  Group tab',
						'name' => 'Dummy',
						'access' => 'thebing_students_contact_group',
						'position' => 122
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_convert']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][123] = [
						'section' => 'Enquiries » Convert into booking',
						'name' => 'Dummy',
						'access' => 'thebing_students_contact_convert',
						'position' => 123
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_gui']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][124] = [
						'section' => 'Enquiries » Extended module',
						'name' => 'Dummy',
						'access' => 'thebing_students_contact_gui',
						'position' => 124
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_search']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][125] = [
						'section' => '__Enquiries » Combinations',
						'name' => 'Dummy',
						'access' => 'thebing_students_contact_search',
						'position' => 125
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][126] = [
						'section' => 'Icon: Students',
						'name' => 'Dummy',
						'access' => 'thebing_students_icon',
						'position' => 126
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][127] = [
						'section' => 'Student lists » Simple view',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view',
						'position' => 127
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][128] = [
						'section' => 'Student lists » Simple view » Edit',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view_edit',
						'position' => 128
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view_delete_updated']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][129] = [
						'section' => 'Student lists » Simple view » Annul pink colour',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view_delete_updated',
						'position' => 129
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][130] = [
						'section' => 'Student lists » Simple view » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view_documents',
						'position' => 130
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][131] = [
						'section' => 'Student lists » Simple view » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view_display_pdf',
						'position' => 131
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][132] = [
						'section' => 'Student lists » Arrival list',
						'name' => 'Dummy',
						'access' => 'thebing_students_welcome_list',
						'position' => 132
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][133] = [
						'section' => 'Student lists » Arrival list » Edit',
						'name' => 'Dummy',
						'access' => 'thebing_students_welcome_list_edit',
						'position' => 133
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][134] = [
						'section' => 'Student lists » Simple view » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_students_simple_view_communication',
						'position' => 134
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][135] = [
						'section' => 'Student lists » Arrival list » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_students_welcome_list_communication',
						'position' => 135
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][136] = [
						'section' => 'Student lists » Arrival list » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_students_welcome_list_documents',
						'position' => 136
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][137] = [
						'section' => 'Student lists » Arrival list » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_students_welcome_list_display_pdf',
						'position' => 137
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][138] = [
						'section' => 'Student lists » Departure list',
						'name' => 'Dummy',
						'access' => 'thebing_students_departure_list',
						'position' => 138
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][139] = [
						'section' => 'Student lists » Departure list » Edit',
						'name' => 'Dummy',
						'access' => 'thebing_students_departure_list_edit',
						'position' => 139
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][140] = [
						'section' => 'Student lists » Departure list » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_students_departure_list_communication',
						'position' => 140
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][141] = [
						'section' => 'Student lists » Departure list » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_students_departure_list_documents',
						'position' => 141
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][142] = [
						'section' => 'Student lists » Departure list » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_students_departure_list_display_pdf',
						'position' => 142
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_feedback_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][143] = [
						'section' => 'Student lists » Feedbacks',
						'name' => 'Dummy',
						'access' => 'thebing_students_feedback_list',
						'position' => 143
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_feedback_list_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][144] = [
						'section' => 'Student lists » Feedbacks » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_students_feedback_list_communication',
						'position' => 144
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][145] = [
						'section' => 'Student lists » Visa list',
						'name' => 'Dummy',
						'access' => 'thebing_students_visa_list',
						'position' => 145
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][146] = [
						'section' => 'Student lists » Visa list » Edit',
						'name' => 'Dummy',
						'access' => 'thebing_students_visa_list_edit',
						'position' => 146
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][147] = [
						'section' => 'Student lists » Visa list » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_students_visa_list_communication',
						'position' => 147
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][148] = [
						'section' => 'Student lists » Visa list » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_students_visa_list_documents',
						'position' => 148
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][149] = [
						'section' => 'Student lists » Visa list » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_students_visa_list_display_pdf',
						'position' => 149
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_student_cards']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][150] = [
						'section' => 'Student ID cards',
						'name' => 'Dummy',
						'access' => 'thebing_students_student_cards',
						'position' => 150
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_student_cards_camera']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][151] = [
						'section' => 'Student ID cards » Camera',
						'name' => 'Dummy',
						'access' => 'thebing_student_cards_camera',
						'position' => 151
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][152] = [
						'section' => 'Icon: Bookings',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_icon',
						'position' => 152
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_generate_student']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][153] = [
						'section' => 'Actions » New student record',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_generate_student',
						'position' => 153
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_edit_student']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][154] = [
						'section' => 'Actions » Edit student record',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_edit_student',
						'position' => 154
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_delete_inquiry']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][155] = [
						'section' => 'Actions » Delete student record',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_delete_inquiry',
						'position' => 155
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_groups']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][156] = [
						'section' => 'Actions » Groups » Group dialogue',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_groups',
						'position' => 156
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_enter_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][157] = [
						'section' => 'Actions » Payments » Enter payments',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_enter_payments',
						'position' => 157
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_enter_payments_history']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][158] = [
						'section' => 'Actions » Payments » Payment history',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_enter_payments_history',
						'position' => 158
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_payments_delete']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][159] = [
						'section' => 'Actions » Payment » Delete entries',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_payments_delete',
						'position' => 159
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][160] = [
						'section' => 'Actions » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_communication',
						'position' => 160
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][161] = [
						'section' => 'Actions » Generate documents',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_documents',
						'position' => 161
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][162] = [
						'section' => 'Actions » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_display_pdf',
						'position' => 162
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_proforma_new']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][163] = [
						'section' => 'Proforma » New document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_proforma_new',
						'position' => 163
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_proforma_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][164] = [
						'section' => 'Proforma » Edit document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_proforma_edit',
						'position' => 164
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_proforma_refresh']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][165] = [
						'section' => 'Proforma » Refresh document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_proforma_refresh',
						'position' => 165
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_proforma_delete']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][166] = [
						'section' => 'Proforma » Delete document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_proforma_delete',
						'position' => 166
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_proforma_transform']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][167] = [
						'section' => 'Proforma » Convert Into invoice',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_proforma_transform',
						'position' => 167
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_proforma_document_cancel']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][168] = [
						'section' => 'Proforma » Cancel student',
						'name' => 'Dummy',
						'access' => 'thebing_proforma_document_cancel',
						'position' => 168
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_new']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][169] = [
						'section' => 'Invoice » New document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_new',
						'position' => 169
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][170] = [
						'section' => 'Invoice » Edit document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_edit',
						'position' => 170
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_refresh']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][171] = [
						'section' => 'Invoice » Refresh document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_refresh',
						'position' => 171
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_cancel']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][172] = [
						'section' => 'Invoice » Cancel student',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_cancel',
						'position' => 172
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_credit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][173] = [
						'section' => 'Invoice » Credit document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_credit',
						'position' => 173
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_dialog_document_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][174] = [
						'section' => 'Invoice » Generate additional documents with invoice',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_dialog_document_tab',
						'position' => 174
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_difference_partial']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][175] = [
						'section' => 'Difference » New partial invoice to student',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_difference_partial',
						'position' => 175
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_difference_customer']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][176] = [
						'section' => 'Difference » New Invoice to student',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_difference_customer',
						'position' => 176
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_difference_agency']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][177] = [
						'section' => 'Difference » New Invoice to agent',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_difference_agency',
						'position' => 177
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_creditnote_agency_new']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][178] = [
						'section' => 'Credit note to agent » New document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_creditnote_agency_new',
						'position' => 178
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_creditnote_agency_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][179] = [
						'section' => 'Credit note to agent » Edit document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_creditnote_agency_edit',
						'position' => 179
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_creditnote_agency_refresh']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][180] = [
						'section' => 'Credit note to agent » Refresh document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_creditnote_agency_refresh',
						'position' => 180
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_numberranges']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][181] = [
						'section' => 'Documents » Change number range',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_numberranges',
						'position' => 181
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_payment_modalities']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][182] = [
						'section' => 'Documents » Edit payment modalities',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_payment_modalities',
						'position' => 182
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_refresh_always']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][183] = [
						'section' => 'Documents » Refresh all',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_refresh_always',
						'position' => 183
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_agency_data']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][184] = [
						'section' => 'Student Record » Agency data',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_agency_data',
						'position' => 184
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_sales_person']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][185] = [
						'section' => 'Student Record » Sales person',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_sales_person',
						'position' => 185
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_difference_customer_credit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][186] = [
						'section' => 'XX - Difference » New  Invoice to student with internal credit',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_difference_customer_credit',
						'position' => 186
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_personal_details_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][187] = [
						'section' => 'XX - Student Record » Personal details',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_personal_details_tab',
						'position' => 187
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_course_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][188] = [
						'section' => 'XX - Student Record » Course',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_course_tab',
						'position' => 188
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_accommodation_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][189] = [
						'section' => 'XX - Student Record » Accommodation',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_accommodation_tab',
						'position' => 189
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_matching_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][190] = [
						'section' => 'XX - Student Record » Matching',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_matching_tab',
						'position' => 190
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_pickup_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][191] = [
						'section' => 'XX - Student Record » Pickup',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_pickup_tab',
						'position' => 191
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_upload_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][192] = [
						'section' => 'XX - Student Record » Upload',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_upload_tab',
						'position' => 192
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_visa_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][193] = [
						'section' => 'Student Record » Visa',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_visa_tab',
						'position' => 193
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_sponsoring_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][194] = [
						'section' => 'Student Record » Sponsoring',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_sponsoring_tab',
						'position' => 194
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_insurance_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][195] = [
						'section' => 'XX - Student record » Insurance',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_insurance_tab',
						'position' => 195
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_classses_attendance_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][196] = [
						'section' => 'XX - Student Record » Classes & Attendance',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_classses_attendance_tab',
						'position' => 196
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_holiday_tab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][197] = [
						'section' => 'XX - Student Record » Holidays',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_holiday_tab',
						'position' => 197
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][198] = [
						'section' => 'Icon: Classes',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_icon',
						'position' => 198
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][199] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Class » New',
						'access' => 'thebing_tuition_planificaton-new',
						'position' => 199
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_classes']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][200] = [
						'section' => 'Class scheduling » Classes',
						'name' => 'New',
						'access' => 'thebing_tuition_classes-new',
						'position' => 200
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][201] = [
						'section' => 'Class scheduling » Tailor made overviews',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_overview',
						'position' => 201
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_course_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][202] = [
						'section' => 'Class scheduling » Booked courses',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_course_list',
						'position' => 202
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_certificates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][203] = [
						'section' => 'Class scheduling » Certificates',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_certificates',
						'position' => 203
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_certificates_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][204] = [
						'section' => 'Class scheduling » Certificates » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_certificates_documents',
						'position' => 204
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_certificates_display_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][205] = [
						'section' => 'Class scheduling » Certificates » Display documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_certificates_display_documents',
						'position' => 205
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_progress_report']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][206] = [
						'section' => 'Class scheduling » Progress Report',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_progress_report',
						'position' => 206
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_progress_report_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][207] = [
						'section' => 'Class scheduling » Progress report » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_progress_report_communication',
						'position' => 207
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_placement_test']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][208] = [
						'section' => 'Class scheduling » Placement tests',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_placement_test',
						'position' => 208
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_placement_test_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][209] = [
						'section' => 'Class scheduling » Placement test » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_placement_test_communication',
						'position' => 209
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_placement_test_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][210] = [
						'section' => 'Class scheduling » Placement test » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_placement_test_documents',
						'position' => 210
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_placement_test_display_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][211] = [
						'section' => 'Class scheduling » Placement test » Display documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_placement_test_display_documents',
						'position' => 211
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_teacher_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][212] = [
						'section' => 'Class scheduling » Teacher overview',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_teacher_overview',
						'position' => 212
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_edit_attendance']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][213] = [
						'section' => 'Attendance » Register',
						'name' => 'Delete',
						'access' => 'thebing_tuition_edit_attendance-delete',
						'position' => 213
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][214] = [
						'section' => 'Attendance » Overview',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_list',
						'position' => 214
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][215] = [
						'section' => 'Attendance » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_communication',
						'position' => 215
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_report']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][216] = [
						'section' => 'Attendance » Report',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_report',
						'position' => 216
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][217] = [
						'section' => 'Attendance » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_documents',
						'position' => 217
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_display_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][218] = [
						'section' => 'Attendance » Display documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_display_documents',
						'position' => 218
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_attendance_score_progress_report']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][219] = [
						'section' => 'Attendance » Score Progress Report',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_attendance_score_progress_report',
						'position' => 219
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_examination']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][220] = [
						'section' => 'Transcripts » Report cards',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_examination',
						'position' => 220
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_examination_sections']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][221] = [
						'section' => 'Transcripts » Transcript areas',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_examination_sections',
						'position' => 221
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_examination_templates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][222] = [
						'section' => 'Transcripts » Transcripts',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_examination_templates',
						'position' => 222
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][223] = [
						'section' => 'Teacher management',
						'name' => 'New',
						'access' => 'thebing_tuition_resource_teachers-new',
						'position' => 223
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers_crm']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][224] = [
						'section' => 'Teacher management » Teacher » CRM',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_teachers_crm',
						'position' => 224
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers_access']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][225] = [
						'section' => 'Teacher management » Teacher » Access rights',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_teachers_access',
						'position' => 225
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers_login']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][226] = [
						'section' => 'Teacher management » Teacher » Login',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_teachers_login',
						'position' => 226
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers_contracts']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][227] = [
						'section' => 'Teacher management » Teacher » Cost category',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_teachers_contracts',
						'position' => 227
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers_absence']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][228] = [
						'section' => 'Teacher management » Teacher absence',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_teachers_absence',
						'position' => 228
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_teacher_contracts']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][229] = [
						'section' => 'Teacher management » Teacher contracts',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_teacher_contracts',
						'position' => 229
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_teachercategories']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][230] = [
						'section' => 'Teacher management » Cost categories',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_teachercategories',
						'position' => 230
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_teacher_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][231] = [
						'section' => 'Teacher management » Actions » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_teacher_deactivate',
						'position' => 231
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][232] = [
						'section' => 'Resources',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources',
						'position' => 232
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][233] = [
						'section' => 'Resources » Courses',
						'name' => 'New',
						'access' => 'thebing_tuition_resources_courses-new',
						'position' => 233
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_acc']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][234] = [
						'section' => 'Resources » Courses » Accommodation (Price list)',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_courses_acc',
						'position' => 234
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][235] = [
						'section' => 'Resources » Courses » Actions » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_courses_deactivate',
						'position' => 235
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][236] = [
						'section' => 'Resources » Courses » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_courses_documents',
						'position' => 236
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_course_categories']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][237] = [
						'section' => 'Resources » Course Categories',
						'name' => 'New',
						'access' => 'thebing_tuition_resource_course_categories-new',
						'position' => 237
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_proficiency']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][238] = [
						'section' => 'Rescources » Levels',
						'name' => 'New',
						'access' => 'thebing_tuition_resource_proficiency-new',
						'position' => 238
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_levelgroup']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][239] = [
						'section' => 'Resources » Levelgroups',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_levelgroup',
						'position' => 239
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_classrooms']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][240] = [
						'section' => 'Resources » Classrooms',
						'name' => 'New',
						'access' => 'thebing_tuition_resource_classrooms-new',
						'position' => 240
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_classrooms_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][241] = [
						'section' => 'Resources » Classrooms » Actions » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_classrooms_deactivate',
						'position' => 241
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_buildings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][242] = [
						'section' => 'Resources » School buildings / floors',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_buildings',
						'position' => 242
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_edit_placement_test']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][243] = [
						'section' => 'Resources » Placement test questions',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_edit_placement_test',
						'position' => 243
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_tuition_templates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][244] = [
						'section' => 'Resources » Default times',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_tuition_templates',
						'position' => 244
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_colors']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][245] = [
						'section' => 'Resources » Color codes for classes',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_colors',
						'position' => 245
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][246] = [
						'section' => 'Resources » Tailor made overviews',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resource_overview',
						'position' => 246
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_modules']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][247] = [
						'section' => 'XX - Resources » Modules',
						'name' => 'Dummy',
						'access' => 'thebing_tuition_resources_modules',
						'position' => 247
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][248] = [
						'section' => 'Icon: Accommodation',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_icon',
						'position' => 248
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][249] = [
						'section' => 'Communication',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_overview',
						'position' => 249
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_details']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][250] = [
						'section' => 'Communication » Details',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_details',
						'position' => 250
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_history']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][251] = [
						'section' => 'Communication » History',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_history',
						'position' => 251
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_student']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][252] = [
						'section' => 'Communication » Communication to student/agent',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_student',
						'position' => 252
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_provider']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][253] = [
						'section' => 'Communication » Communication to acc provider',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_provider',
						'position' => 253
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_filter_agency']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][254] = [
						'section' => 'Communication » Agency Filter',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_filter_agency',
						'position' => 254
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][255] = [
						'section' => 'Communication » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_documents',
						'position' => 255
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][256] = [
						'section' => 'Communication » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_communicate_display_pdf',
						'position' => 256
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_family_matching']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][257] = [
						'section' => 'Room allocation » Host family Matching',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_family_matching',
						'position' => 257
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_other_matching']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][258] = [
						'section' => 'Room allocation » Res. acc. Matching',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_other_matching',
						'position' => 258
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_other_roomcleaning']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][259] = [
						'section' => 'Room allocation » Simple cleaning schedule export',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_other_roomcleaning',
						'position' => 259
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_availability']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][260] = [
						'section' => 'Room allocation » Availability',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_availability',
						'position' => 260
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_mealplan']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][261] = [
						'section' => 'Room allocation » Meal plan',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_mealplan',
						'position' => 261
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_resources']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][262] = [
						'section' => 'Resources',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_resources',
						'position' => 262
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][263] = [
						'section' => 'Resources » Accommodation provider',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations',
						'position' => 263
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_new_provider']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][264] = [
						'section' => 'Resources » Accommodation providers » New record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_new_provider',
						'position' => 264
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_edit_provider']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][265] = [
						'section' => 'Resources » Accommodation providers » Edit record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_edit_provider',
						'position' => 265
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_delete_provider']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][266] = [
						'section' => 'Resources » Accommodation providers » Delete record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_delete_provider',
						'position' => 266
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][267] = [
						'section' => 'Resources » Accommodation providers » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_deactivate',
						'position' => 267
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_display_feedback']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][268] = [
						'section' => 'Resources » Accommodation providers » Feedback',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_display_feedback',
						'position' => 268
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][269] = [
						'section' => 'Resources » Accommodation provider » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_communication',
						'position' => 269
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_release_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][270] = [
						'section' => 'Resources » Accommodation provider » Uploads',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_release_documents',
						'position' => 270
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_release_documents_sl']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][271] = [
						'section' => 'Resources » Accommodation provider » Uploads App',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_release_documents_sl',
						'position' => 271
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_release_pictures']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][272] = [
						'section' => 'Resources » Accommodation provider » Pictures',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_release_pictures',
						'position' => 272
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_release_pictures_sl']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][273] = [
						'section' => 'Resources » Accommodation provider » Pictures App',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_release_pictures_sl',
						'position' => 273
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_accommodations_requirements']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][274] = [
						'section' => 'Resources » Accommodation provider » Requirements',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_accommodations_requirements',
						'position' => 274
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_contracts']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][275] = [
						'section' => 'Resources » Provider contracts',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_contracts',
						'position' => 275
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_categories']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][276] = [
						'section' => 'Resources » Accommodation categories',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_categories',
						'position' => 276
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_categories_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][277] = [
						'section' => 'Resources » Accommodation categories » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_categories_deactivate',
						'position' => 277
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_categories_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][278] = [
						'section' => 'Resources » Accommodation categories » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_categories_documents',
						'position' => 278
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_roomtypes']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][279] = [
						'section' => 'Resources » Rooms',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_roomtypes',
						'position' => 279
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_roomtypes_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][280] = [
						'section' => 'Resources » Rooms » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_roomtypes_deactivate',
						'position' => 280
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_meals']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][281] = [
						'section' => 'Resources » Boards',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_meals',
						'position' => 281
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_meals_deactivate']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][282] = [
						'section' => 'Resources » Board » Deactivate record',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_meals_deactivate',
						'position' => 282
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_accommodationcategories']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][283] = [
						'section' => 'Resources » Cost categories',
						'name' => 'New',
						'access' => 'thebing_marketing_accommodationcategories-new',
						'position' => 283
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_billing_terms']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][284] = [
						'section' => 'Resources » Billing terms',
						'name' => 'New',
						'access' => 'thebing_accommodation_billing_terms-new',
						'position' => 284
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_requirements']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][285] = [
						'section' => 'Resources » Requirements',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_requirements',
						'position' => 285
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_other_cleaning']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][286] = [
						'section' => 'Room allocation » Extended cleaning schedule',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_other_cleaning',
						'position' => 286
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][287] = [
						'section' => 'Icon: Pickup',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_icon',
						'position' => 287
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][288] = [
						'section' => 'Communication',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation',
						'position' => 288
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][289] = [
						'section' => 'Communication » Edit',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_edit',
						'position' => 289
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_button_request']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][290] = [
						'section' => 'Communication » Button » Request transfer',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_button_request',
						'position' => 290
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_button_confirm_transfer']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][291] = [
						'section' => 'Communication » Button » Confirm transfer',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_button_confirm_transfer',
						'position' => 291
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_button_confirm_accommodation']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][292] = [
						'section' => 'Communication » Button » Confirm to accommodation',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_button_confirm_accommodation',
						'position' => 292
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_button_confirm_student']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][293] = [
						'section' => 'Communication » Button » Confirm to student/agent',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_button_confirm_student',
						'position' => 293
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_request_transfer']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][294] = [
						'section' => 'Communication » Request transfer',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_request_transfer',
						'position' => 294
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_confirm_transfer']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][295] = [
						'section' => 'Communication » Confirm transfer',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_confirm_transfer',
						'position' => 295
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_confirm_student']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][296] = [
						'section' => 'Communication » Confirm to student/agent',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_confirm_student',
						'position' => 296
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_confirm_accommodation']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][297] = [
						'section' => 'Communication » Confirm to accommodation',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_confirm_accommodation',
						'position' => 297
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][298] = [
						'section' => 'Communication » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_documents',
						'position' => 298
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_confirmation_display_pdf']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][299] = [
						'section' => 'Communication » Display PDF\'s',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_confirmation_display_pdf',
						'position' => 299
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_resources']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][300] = [
						'section' => 'Resources',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_resources',
						'position' => 300
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_resources_airports']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][301] = [
						'section' => 'Resources » Pickup locations',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_resources_airports',
						'position' => 301
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_resources_companies']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][302] = [
						'section' => 'Resources » Pickup provider',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_resources_companies',
						'position' => 302
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_resources_packages_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][303] = [
						'section' => 'Resources » Transfer packages » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_resources_packages_documents',
						'position' => 303
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][304] = [
						'section' => 'Icon: Accounting',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_icon',
						'position' => 304
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_payment_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][305] = [
						'section' => 'Invoice overview » Document overview',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_payment_overview',
						'position' => 305
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_extended_invoice_export']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][306] = [
						'section' => 'Invoice overview » Extended export',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_extended_invoice_export',
						'position' => 306
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][307] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'New',
						'access' => 'thebing_accounting_release_documents_list-new',
						'position' => 307
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_button']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][308] = [
						'section' => 'Invoice overview » Release Button',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_release_documents_button',
						'position' => 308
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_booking_stack']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][309] = [
						'section' => 'Invoice overview » Booking stack',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_booking_stack',
						'position' => 309
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_proforma']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][310] = [
						'section' => 'Invoice overview » Convert into invoice',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_proforma',
						'position' => 310
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_client_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][311] = [
						'section' => 'Incoming payments » Client payments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_client_payments',
						'position' => 311
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_agency_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][312] = [
						'section' => 'Incoming payments » Agent payments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_agency_payments',
						'position' => 312
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_incoming_inquiry_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][313] = [
						'section' => 'Incoming payments » Incoming payment list',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_incoming_inquiry_payments',
						'position' => 313
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_incoming_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][314] = [
						'section' => 'Incoming payments » Payment details',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_incoming_payments',
						'position' => 314
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_incoming_accounts_receivable']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][315] = [
						'section' => 'Incoming payments » Accounts receivable',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_incoming_accounts_receivable',
						'position' => 315
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_assign_client_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][316] = [
						'section' => 'Incoming payments » Assign client payments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_assign_client_payments',
						'position' => 316
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_account_provision']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][317] = [
						'section' => 'Outgoing payments » Pay commission',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_account_provision',
						'position' => 317
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_provision_enter_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][318] = [
						'section' => 'Outgoing payments » Pay commission » Enter payments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_provision_enter_payments',
						'position' => 318
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_provision_payment_history']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][319] = [
						'section' => 'Outgoing payments » Pay commission » Payment history',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_provision_payment_history',
						'position' => 319
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_provision_delete']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][320] = [
						'section' => 'Outgoing payments » Pay commission » Delete entries',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_provision_delete',
						'position' => 320
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_manual_creditnote']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][321] = [
						'section' => 'Outgoing payments » Manual credit notes',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_manual_creditnote',
						'position' => 321
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_incorrect_accounting']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][322] = [
						'section' => 'Outgoing payments » Overpayments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_incorrect_accounting',
						'position' => 322
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_overpayments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][323] = [
						'section' => 'Outgoing payments » Overpayments » Payment Button',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_overpayments',
						'position' => 323
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_teacher_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][324] = [
						'section' => 'Pay your providers » Teacher payments',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_teacher_payments',
						'position' => 324
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_teacher_payments_groupings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][325] = [
						'section' => 'Pay your providers » Paid teachers',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_teacher_payments_groupings',
						'position' => 325
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_accommodation_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][326] = [
						'section' => 'Pay your providers » Pay accommodation providers',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_accommodation_payments',
						'position' => 326
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_accommodation_payments_groupings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][327] = [
						'section' => 'Pay your providers » Paid accommodation providers',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_accommodation_payments_groupings',
						'position' => 327
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_accommodation_payments_delete']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][328] = [
						'section' => 'Pay your providers » Paid accommodation providers » Delete payment',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_accommodation_payments_delete',
						'position' => 328
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_accommodation_payments_process']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][329] = [
						'section' => 'Pay your providers » Paid accommodation providers » Further processing',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_accommodation_payments_process',
						'position' => 329
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_accommodation_payments_history']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][330] = [
						'section' => 'Pay your providers » Paid accommodation providers » History',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_accommodation_payments_history',
						'position' => 330
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_pickup_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][331] = [
						'section' => 'Pay your providers » Pay transfers',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_pickup_payments',
						'position' => 331
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_pickup_payments_groupings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][332] = [
						'section' => 'Pay your providers » Paid transfers',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_pickup_payments_groupings',
						'position' => 332
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_resources']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][333] = [
						'section' => 'Resources',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_resources',
						'position' => 333
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_vat_allocation']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][334] = [
						'section' => 'Resources » Tax allocation',
						'name' => 'Dummy',
						'access' => 'thebing_vat_allocation',
						'position' => 334
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_companies']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][335] = [
						'section' => 'Resources » Companies',
						'name' => 'Dummy',
						'access' => 'thebing_companies',
						'position' => 335
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_company_template_receipttext']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][336] = [
						'section' => 'Resources » Template for booking texts',
						'name' => 'Dummy',
						'access' => 'thebing_company_template_receipttext',
						'position' => 336
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_payment_methods']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][337] = [
						'section' => 'Resources » Payment methods',
						'name' => 'Dummy',
						'access' => 'thebing_admin_payment_methods',
						'position' => 337
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_payment_groups']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][338] = [
						'section' => 'Resources » Payment term categories',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_payment_groups',
						'position' => 338
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_did_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][339] = [
						'section' => 'XX - Abrechnungsliste (DID)',
						'name' => 'Dummy',
						'access' => 'thebing_did_icon',
						'position' => 339
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_cheque']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][340] = [
						'section' => 'Outgoing payments » Checks',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_cheque',
						'position' => 340
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_credit_card']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][341] = [
						'section' => 'XX - Resources » Payment methods » Credit cards',
						'name' => 'Dummy',
						'access' => 'thebing_accounting_credit_card',
						'position' => 341
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_resources_check_options']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][342] = [
						'section' => 'Resources » Assign payment types » Checks',
						'name' => 'Dummy',
						'access' => 'thebing_resources_check_options',
						'position' => 342
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][343] = [
						'section' => 'Icon: Activities',
						'name' => 'Dummy',
						'access' => 'thebing_activities_icon',
						'position' => 343
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_scheduling']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][344] = [
						'section' => 'Activities » Scheduling',
						'name' => 'Dummy',
						'access' => 'thebing_activities_scheduling',
						'position' => 344
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_overview']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][345] = [
						'section' => 'Activities » Overview',
						'name' => 'Dummy',
						'access' => 'thebing_activities_overview',
						'position' => 345
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_resources_activities']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][346] = [
						'section' => 'Activities » Resources » Activities',
						'name' => 'Dummy',
						'access' => 'thebing_activities_resources_activities',
						'position' => 346
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_resources_activities_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][347] = [
						'section' => 'Activities » Resources » Activities » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_activities_resources_activities_documents',
						'position' => 347
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_activities_providers']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Activities'][348] = [
						'section' => 'Activities » Resources » Providers',
						'name' => 'Dummy',
						'access' => 'thebing_activities_providers',
						'position' => 348
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][349] = [
						'section' => 'Icon: Marketing',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_icon',
						'position' => 349
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][350] = [
						'section' => 'Agents',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies',
						'position' => 350
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][351] = [
						'section' => 'Agents » Edit Agency record',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_edit',
						'position' => 351
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_members_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][352] = [
						'section' => 'Agents » Members edit',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_members_edit',
						'position' => 352
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_members_username']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][353] = [
						'section' => 'Agents » Employees » Username',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_members_username',
						'position' => 353
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_notes_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][354] = [
						'section' => 'Agents » Notes edit',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_notes_edit',
						'position' => 354
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_uploads_edit']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][355] = [
						'section' => 'Agents » Uploads edit',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_uploads_edit',
						'position' => 355
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_creditnotes_edit']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][356] = [
						'section' => 'Agents » Credit notes',
						'name' => 'Edit',
						'access' => 'thebing_marketing_agencies_creditnotes_edit-edit',
						'position' => 356
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agency_crm']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][357] = [
						'section' => 'Agents » CRM',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agency_crm',
						'position' => 357
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agency_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][358] = [
						'section' => 'Agents » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agency_documents',
						'position' => 358
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agency_extended_export']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][359] = [
						'section' => 'Agents » Extended Agency Export',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agency_extended_export',
						'position' => 359
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agency_communication']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][360] = [
						'section' => 'Agents » Communication',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agency_communication',
						'position' => 360
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_agency_categories']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][361] = [
						'section' => 'Agents » Agent categories',
						'name' => 'Dummy',
						'access' => 'thebing_admin_agency_categories',
						'position' => 361
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agenciegroups']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][362] = [
						'section' => 'Agents » Agent groups',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agenciegroups',
						'position' => 362
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_provisions']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][363] = [
						'section' => 'Agents » Commission categories',
						'name' => 'Show',
						'access' => 'thebing_marketing_provisions-show',
						'position' => 363
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agency_list']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][364] = [
						'section' => 'Agents » Agent list',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agency_list',
						'position' => 364
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_sponsoring']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][365] = [
						'section' => 'Sponsoring',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_sponsoring',
						'position' => 365
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_complaints']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][366] = [
						'section' => 'Complaints',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_complaints',
						'position' => 366
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_special']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][367] = [
						'section' => 'Special',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_special',
						'position' => 367
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_prices']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][368] = [
						'section' => 'Prices & Costs » Prices - general',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_prices',
						'position' => 368
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_costs']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][369] = [
						'section' => 'Prices & Costs » Costs - general',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_costs',
						'position' => 369
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_status']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][370] = [
						'section' => 'Resources Marketing » Student status',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_status',
						'position' => 370
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_subject']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][371] = [
						'section' => 'Resources Marketing » CRM - Subject',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_subject',
						'position' => 371
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_activity']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][372] = [
						'section' => 'Resources Marketing » Type of contact',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_activity',
						'position' => 372
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_resources_complaints']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][373] = [
						'section' => 'Resources Marketing » Complaints',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_resources_complaints',
						'position' => 373
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_resources']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][374] = [
						'section' => 'Resources',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_resources',
						'position' => 374
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_reasons']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][375] = [
						'section' => 'Resources » Reasons for manual CN',
						'name' => 'Dummy',
						'access' => 'thebing_admin_reasons',
						'position' => 375
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_saisons']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][376] = [
						'section' => 'Resources » Periods',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_saisons',
						'position' => 376
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_public_holidays']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][377] = [
						'section' => 'Resources » Public holidays',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_public_holidays',
						'position' => 377
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_school_holidays']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][378] = [
						'section' => 'Resources » School holidays',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_school_holidays',
						'position' => 378
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_additional_costs']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][379] = [
						'section' => 'Resources » Additional fees',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_additional_costs',
						'position' => 379
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_cancelation_fee']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][380] = [
						'section' => 'Resources » Cancellation fees',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_cancelation_fee',
						'position' => 380
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_lektionen']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][381] = [
						'section' => 'Resources » Lessons (prices)',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_lektionen',
						'position' => 381
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_weeks']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][382] = [
						'section' => 'Resources » Weeks (prices)',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_weeks',
						'position' => 382
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_edit_agency_number']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][383] = [
						'section' => 'XX - Agents » Edit Agency Number',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_edit_agency_number',
						'position' => 383
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_tab_login']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][384] = [
						'section' => 'XX - Agents » Agents » Login',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_agencies_tab_login',
						'position' => 384
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][385] = [
						'section' => 'Icon: Reporting',
						'name' => 'Dummy',
						'access' => 'thebing_management_icon',
						'position' => 385
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][386] = [
						'section' => 'Overview',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports',
						'position' => 386
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_filter']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][387] = [
						'section' => 'Overview » Filter',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_filter',
						'position' => 387
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_standard']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][388] = [
						'section' => 'Basic reports 1',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_standard',
						'position' => 388
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_resources']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][389] = [
						'section' => 'Resources',
						'name' => 'Edit',
						'access' => 'thebing_management_resources-edit',
						'position' => 389
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_pages']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][390] = [
						'section' => 'Resources » Report pages',
						'name' => 'Dummy',
						'access' => 'thebing_management_pages',
						'position' => 390
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_statistics']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][391] = [
						'section' => 'Resources » Tailor made reports',
						'name' => 'Dummy',
						'access' => 'thebing_management_statistics',
						'position' => 391
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_accommodations']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][392] = [
						'section' => 'Reports » Accommodations',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_accommodations',
						'position' => 392
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_agencies']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][393] = [
						'section' => 'Reports » Agencies',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_agencies',
						'position' => 393
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_cancellations']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][394] = [
						'section' => 'Reports » Cancellations',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_cancellations',
						'position' => 394
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_costs']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][395] = [
						'section' => 'Reports » Costs',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_costs',
						'position' => 395
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_margins']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][396] = [
						'section' => 'Reports » Margins',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_margins',
						'position' => 396
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_requests']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][397] = [
						'section' => 'Reports » Requests',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_requests',
						'position' => 397
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_sales']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][398] = [
						'section' => 'Reports » Sales',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_sales',
						'position' => 398
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_schools']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][399] = [
						'section' => 'Reports » Schools',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_schools',
						'position' => 399
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_services']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][400] = [
						'section' => 'Reports » Services',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_services',
						'position' => 400
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_students']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][401] = [
						'section' => 'Reports » Students',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_students',
						'position' => 401
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_statistic_teachers']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][402] = [
						'section' => 'Reports » Teachers',
						'name' => 'Dummy',
						'access' => 'thebing_statistic_teachers',
						'position' => 402
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_feedback_provider_sum']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][403] = [
						'section' => 'Static report » Feedback Provider Sum',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_feedback_provider_sum',
						'position' => 403
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_mothertongueperinbox']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][404] = [
						'section' => 'Static report » Mother tongue per inbox',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_mothertongueperinbox',
						'position' => 404
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_prepaid_report']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][405] = [
						'section' => 'Static report » Prepaid Report',
						'name' => 'Dummy',
						'access' => 'thebing_management_prepaid_report',
						'position' => 405
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_due_payments']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][406] = [
						'section' => 'Static report » Due payments',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_due_payments',
						'position' => 406
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_agency_studentweeks_yearandcountry']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][407] = [
						'section' => 'Static report » Student weeks per agent and country',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_agency_studentweeks_yearandcountry',
						'position' => 407
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_agency_paymentrevenue']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][408] = [
						'section' => 'Static report » Agent Revenue Report',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_agency_paymentrevenue',
						'position' => 408
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_persessionrevenue']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][409] = [
						'section' => 'Static report » Per session revenue (Invoice related)',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_persessionrevenue',
						'position' => 409
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_prepaidpersession']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][410] = [
						'section' => 'Static report » Per session revenue (Payment related)',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_prepaidpersession',
						'position' => 410
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_booking_export_per_course']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][411] = [
						'section' => 'Static report » LSF Booking export per course',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_booking_export_per_course',
						'position' => 411
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_lsftaxdeclaration']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][412] = [
						'section' => 'Static report » LSF Tax Declaration',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_lsftaxdeclaration',
						'position' => 412
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_insurance_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Insurance'][413] = [
						'section' => 'Icon:  Insurance',
						'name' => 'Dummy',
						'access' => 'thebing_insurance_icon',
						'position' => 413
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_insurance_resources_insurances_documents']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Insurance'][414] = [
						'section' => 'Resources » Insurances » Documents',
						'name' => 'Dummy',
						'access' => 'thebing_insurance_resources_insurances_documents',
						'position' => 414
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_icon']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][415] = [
						'section' => 'Icon: Admin',
						'name' => 'Dummy',
						'access' => 'thebing_admin_icon',
						'position' => 415
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][416] = [
						'section' => 'Employees » List',
						'name' => 'Dummy',
						'access' => 'thebing_admin_users',
						'position' => 416
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users_salesperson']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][417] = [
						'section' => 'Employees » Sales Person',
						'name' => 'Dummy',
						'access' => 'thebing_admin_users_salesperson',
						'position' => 417
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_usergroups']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][418] = [
						'section' => 'Employees » User groups',
						'name' => 'Dummy',
						'access' => 'thebing_admin_usergroups',
						'position' => 418
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users_functions']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][419] = [
						'section' => 'Employees » Categories',
						'name' => 'New',
						'access' => 'thebing_admin_users_functions-new',
						'position' => 419
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_contacts']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][420] = [
						'section' => 'Contacts',
						'name' => 'Dummy',
						'access' => 'thebing_admin_contacts',
						'position' => 420
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_file_upload']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][421] = [
						'section' => 'Templates » File upload',
						'name' => 'Dummy',
						'access' => 'thebing_admin_file_upload',
						'position' => 421
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_template_types']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][422] = [
						'section' => 'Templates » PDF Layouts / Templates',
						'name' => 'Dummy',
						'access' => 'thebing_admin_template_types',
						'position' => 422
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_add_templates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][423] = [
						'section' => 'Templates » PDF templates',
						'name' => 'Dummy',
						'access' => 'thebing_admin_add_templates',
						'position' => 423
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_email_layouts']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][424] = [
						'section' => 'Templates » E-Mail layout',
						'name' => 'Dummy',
						'access' => 'thebing_admin_email_layouts',
						'position' => 424
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_email_templates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][425] = [
						'section' => 'Templates » E-Mail templates',
						'name' => 'Dummy',
						'access' => 'thebing_admin_email_templates',
						'position' => 425
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_email_templates_automatic_cronjob']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][426] = [
						'section' => 'Templates » Automated mailing » Cronjobs',
						'name' => 'Dummy',
						'access' => 'thebing_admin_email_templates_automatic_cronjob',
						'position' => 426
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_contract_templates']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][427] = [
						'section' => 'Templates » Contract types',
						'name' => 'Dummy',
						'access' => 'thebing_admin_contract_templates',
						'position' => 427
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_schools']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][428] = [
						'section' => 'Administration » Schools',
						'name' => 'Dummy',
						'access' => 'thebing_admin_schools',
						'position' => 428
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_add_schools']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][429] = [
						'section' => 'Administration » Schools » New entry',
						'name' => 'Dummy',
						'access' => 'thebing_admin_add_schools',
						'position' => 429
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_inbox']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][430] = [
						'section' => 'Administration » Inbox lists',
						'name' => 'Dummy',
						'access' => 'thebing_admin_inbox',
						'position' => 430
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_numberranges_visa']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][431] = [
						'section' => 'Administration » Number range » Visa',
						'name' => 'Dummy',
						'access' => 'thebing_admin_numberranges_visa',
						'position' => 431
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_update']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][432] = [
						'section' => 'Administration » System update',
						'name' => 'Dummy',
						'access' => 'thebing_admin_update',
						'position' => 432
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_clientdata']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][433] = [
						'section' => 'Administration » General settings',
						'name' => 'Dummy',
						'access' => 'thebing_admin_clientdata',
						'position' => 433
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_gui_flex']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][434] = [
						'section' => 'Administration » User defined flexibility',
						'name' => 'Dummy',
						'access' => 'thebing_gui_flex',
						'position' => 434
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_gui_document_areas']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][435] = [
						'section' => 'Administration » Display documents from other areas',
						'name' => 'Dummy',
						'access' => 'thebing_gui_document_areas',
						'position' => 435
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_gui_placeholdertab']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][436] = [
						'section' => 'Administration » Document » Placeholdertab',
						'name' => 'Dummy',
						'access' => 'thebing_gui_placeholdertab',
						'position' => 436
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_gui_addressee_export']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][437] = [
						'section' => 'Administration » Documents » Select addressee',
						'name' => 'Dummy',
						'access' => 'thebing_gui_addressee_export',
						'position' => 437
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_gui_email_history']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][438] = [
						'section' => 'Administration » History of E-Mail communication',
						'name' => 'Dummy',
						'access' => 'thebing_gui_email_history',
						'position' => 438
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_absence_categories']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][439] = [
						'section' => 'Other » Categories for absence',
						'name' => 'Dummy',
						'access' => 'thebing_admin_absence_categories',
						'position' => 439
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_visumstatus']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][440] = [
						'section' => 'Other » Visa types',
						'name' => 'Dummy',
						'access' => 'thebing_admin_visumstatus',
						'position' => 440
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_position_order']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][441] = [
						'section' => 'Other » Item order on invoice',
						'name' => 'Dummy',
						'access' => 'thebing_marketing_position_order',
						'position' => 441
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_release_documents_sl']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][442] = [
						'section' => 'Other » Release documents for App',
						'name' => 'Dummy',
						'access' => 'thebing_release_documents_sl',
						'position' => 442
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_core_config']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][443] = [
						'section' => 'User interface »  Filter sets',
						'name' => 'Dummy',
						'access' => 'thebing_core_config',
						'position' => 443
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_customerupload']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][444] = [
						'section' => 'User interface » Custom Upload fields',
						'name' => 'Dummy',
						'access' => 'thebing_admin_customerupload',
						'position' => 444
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_registration_form']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][445] = [
						'section' => 'Frontend » Online forms',
						'name' => 'Dummy',
						'access' => 'thebing_admin_registration_form',
						'position' => 445
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_frontend_translations']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][446] = [
						'section' => 'Frontend » Frontend translations',
						'name' => 'Dummy',
						'access' => 'thebing_admin_frontend_translations',
						'position' => 446
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_api_gel']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][447] = [
						'section' => 'Frontend » API » GEL',
						'name' => 'Dummy',
						'access' => 'thebing_api_gel',
						'position' => 447
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_api_latest_bookings']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][448] = [
						'section' => 'Frontend » API » Latest Bookings',
						'name' => 'Dummy',
						'access' => 'thebing_api_latest_bookings',
						'position' => 448
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_student_handbook']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][449] = [
						'section' => 'Frontend » Student Handbook',
						'name' => 'Dummy',
						'access' => 'thebing_admin_student_handbook',
						'position' => 449
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['edit_language_it']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][450] = [
						'section' => 'XX - Internal » Translations IT',
						'name' => 'Dummy',
						'access' => 'edit_language_it',
						'position' => 450
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['edit_language_zh']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][451] = [
						'section' => 'XX - Internal » Translations ZH',
						'name' => 'Dummy',
						'access' => 'edit_language_zh',
						'position' => 451
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['edit_language_fr']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][452] = [
						'section' => 'XX - Internal » Translations FR',
						'name' => 'Dummy',
						'access' => 'edit_language_fr',
						'position' => 452
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_nationalities']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][453] = [
						'section' => 'XX - Internal » Nationalities',
						'name' => 'Dummy',
						'access' => 'thebing_admin_nationalities',
						'position' => 453
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_mothercorrespondencetonge']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][454] = [
						'section' => 'XX - Internal » Mother tongue',
						'name' => 'Dummy',
						'access' => 'thebing_admin_mothercorrespondencetonge',
						'position' => 454
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_payment_debtorreport']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][455] = [
						'section' => 'Static Report » Debtor Report (???)',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_payment_debtorreport',
						'position' => 455
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_pickup_resources_packages']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Transfer'][456] = [
						'section' => 'Resources » Transfer packages',
						'name' => 'Dummy',
						'access' => 'thebing_pickup_resources_packages',
						'position' => 456
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_payment_deferredincome']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][457] = [
						'section' => 'Static Report » Deferred Income (???)',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_payment_deferredincome',
						'position' => 457
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_bookings_confirm']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][458] = [
						'section' => 'Actions » Confirm',
						'name' => 'Dummy',
						'access' => 'thebing_bookings_confirm',
						'position' => 458
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][459] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Class » Edit',
						'access' => 'thebing_tuition_planificaton-edit',
						'position' => 459
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][460] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Class » Show',
						'access' => 'thebing_tuition_planificaton-show',
						'position' => 460
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['students_remove'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][461] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Students » Remove',
						'access' => 'thebing_tuition_planificaton-students_remove',
						'position' => 461
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['students_assign'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][462] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Students » Assign',
						'access' => 'thebing_tuition_planificaton-students_assign',
						'position' => 462
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_classes']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][463] = [
						'section' => 'Class scheduling » Classes',
						'name' => 'Edit',
						'access' => 'thebing_tuition_classes-edit',
						'position' => 463
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_classes']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][464] = [
						'section' => 'Class scheduling » Classes',
						'name' => 'Delete',
						'access' => 'thebing_tuition_classes-delete',
						'position' => 464
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_classes']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][465] = [
						'section' => 'Class scheduling » Classes',
						'name' => 'Show',
						'access' => 'thebing_tuition_classes-show',
						'position' => 465
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_classes']['filemanager'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][466] = [
						'section' => 'Class scheduling » Classes',
						'name' => 'Filemanager',
						'access' => 'thebing_tuition_classes-filemanager',
						'position' => 466
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][467] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Class » Delete',
						'access' => 'thebing_tuition_planificaton-delete',
						'position' => 467
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_edit_attendance']['save'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][468] = [
						'section' => 'Attendance » Register',
						'name' => 'Save',
						'access' => 'thebing_tuition_edit_attendance-save',
						'position' => 468
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][469] = [
						'section' => 'Teacher management',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resource_teachers-edit',
						'position' => 469
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][470] = [
						'section' => 'Teacher management',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resource_teachers-delete',
						'position' => 470
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][471] = [
						'section' => 'Teacher management',
						'name' => 'Show',
						'access' => 'thebing_tuition_resource_teachers-show',
						'position' => 471
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][472] = [
						'section' => 'Teacher management',
						'name' => 'Deactivate',
						'access' => 'thebing_tuition_resource_teachers-deactivate',
						'position' => 472
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][473] = [
						'section' => 'Resources » Courses',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resources_courses-edit',
						'position' => 473
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][474] = [
						'section' => 'Resources » Courses',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resources_courses-delete',
						'position' => 474
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][475] = [
						'section' => 'Resources » Courses',
						'name' => 'Show',
						'access' => 'thebing_tuition_resources_courses-show',
						'position' => 475
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_course_categories']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][476] = [
						'section' => 'Resources » Course Categories',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resource_course_categories-edit',
						'position' => 476
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_course_categories']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][477] = [
						'section' => 'Resources » Course Categories',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resource_course_categories-delete',
						'position' => 477
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_course_categories']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][478] = [
						'section' => 'Resources » Course Categories',
						'name' => 'Show',
						'access' => 'thebing_tuition_resource_course_categories-show',
						'position' => 478
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_classrooms']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][479] = [
						'section' => 'Resources » Classrooms',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resource_classrooms-edit',
						'position' => 479
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_classrooms']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][480] = [
						'section' => 'Resources » Classrooms',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resource_classrooms-delete',
						'position' => 480
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_classrooms']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][481] = [
						'section' => 'Resources » Classrooms',
						'name' => 'Show',
						'access' => 'thebing_tuition_resource_classrooms-show',
						'position' => 481
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_proficiency']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][482] = [
						'section' => 'Rescources » Levels',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resource_proficiency-edit',
						'position' => 482
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_proficiency']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][483] = [
						'section' => 'Rescources » Levels',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resource_proficiency-delete',
						'position' => 483
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_proficiency']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][484] = [
						'section' => 'Rescources » Levels',
						'name' => 'Show',
						'access' => 'thebing_tuition_resource_proficiency-show',
						'position' => 484
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][485] = [
						'section' => 'Resources » Courses',
						'name' => 'Deactivate',
						'access' => 'thebing_tuition_resources_courses-deactivate',
						'position' => 485
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][486] = [
						'section' => 'Teacher management',
						'name' => 'Communication',
						'access' => 'thebing_tuition_resource_teachers-communication',
						'position' => 486
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users_functions']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][487] = [
						'section' => 'Employees » Categories',
						'name' => 'Edit',
						'access' => 'thebing_admin_users_functions-edit',
						'position' => 487
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users_functions']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][488] = [
						'section' => 'Employees » Categories',
						'name' => 'Show',
						'access' => 'thebing_admin_users_functions-show',
						'position' => 488
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users_functions']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][489] = [
						'section' => 'Employees » Categories',
						'name' => 'Delete',
						'access' => 'thebing_admin_users_functions-delete',
						'position' => 489
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_accommodationcategories']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][490] = [
						'section' => 'Resources » Cost categories',
						'name' => 'Edit',
						'access' => 'thebing_marketing_accommodationcategories-edit',
						'position' => 490
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_accommodationcategories']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][491] = [
						'section' => 'Resources » Cost categories',
						'name' => 'Delete',
						'access' => 'thebing_marketing_accommodationcategories-delete',
						'position' => 491
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_accommodationcategories']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][492] = [
						'section' => 'Resources » Cost categories',
						'name' => 'Show',
						'access' => 'thebing_marketing_accommodationcategories-show',
						'position' => 492
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_billing_terms']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][493] = [
						'section' => 'Resources » Billing terms',
						'name' => 'Edit',
						'access' => 'thebing_accommodation_billing_terms-edit',
						'position' => 493
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_billing_terms']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][494] = [
						'section' => 'Resources » Billing terms',
						'name' => 'Delete',
						'access' => 'thebing_accommodation_billing_terms-delete',
						'position' => 494
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_billing_terms']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][495] = [
						'section' => 'Resources » Billing terms',
						'name' => 'Show',
						'access' => 'thebing_accommodation_billing_terms-show',
						'position' => 495
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][496] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'Edit',
						'access' => 'thebing_accounting_release_documents_list-edit',
						'position' => 496
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][497] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'Show',
						'access' => 'thebing_accounting_release_documents_list-show',
						'position' => 497
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][498] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'Delete',
						'access' => 'thebing_accounting_release_documents_list-delete',
						'position' => 498
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['release'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][499] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'Release',
						'access' => 'thebing_accounting_release_documents_list-release',
						'position' => 499
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['it_xml_export'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][500] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'IT XML export',
						'access' => 'thebing_accounting_release_documents_list-it_xml_export',
						'position' => 500
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_release_documents_list']['it_xml_export_final'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][501] = [
						'section' => 'Invoice overview » Release documents',
						'name' => 'IT XML export final',
						'access' => 'thebing_accounting_release_documents_list-it_xml_export_final',
						'position' => 501
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_resources']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][502] = [
						'section' => 'Resources',
						'name' => 'Show',
						'access' => 'thebing_management_resources-show',
						'position' => 502
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list']['checkin'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][503] = [
						'section' => 'Student lists » Arrival list',
						'name' => 'Check-in',
						'access' => 'thebing_students_welcome_list-checkin',
						'position' => 503
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list']['checkout'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][504] = [
						'section' => 'Student lists » Departure list',
						'name' => 'Check-out',
						'access' => 'thebing_students_departure_list-checkout',
						'position' => 504
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view']['copy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][505] = [
						'section' => 'Student lists » Simple view',
						'name' => 'Copy',
						'access' => 'thebing_students_simple_view-copy',
						'position' => 505
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_bookings_invoices']['open_overview'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][506] = [
						'section' => 'Invoices',
						'name' => 'Open overview',
						'access' => 'ts_bookings_invoices-open_overview',
						'position' => 506
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_partialinvoices']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][507] = [
						'section' => 'Partial invoices',
						'name' => 'New',
						'access' => 'ts_accounting_partialinvoices-new',
						'position' => 507
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_partialinvoices']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][508] = [
						'section' => 'Partial invoices',
						'name' => 'Edit',
						'access' => 'ts_accounting_partialinvoices-edit',
						'position' => 508
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_partialinvoices']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][509] = [
						'section' => 'Partial invoices',
						'name' => 'Delete',
						'access' => 'ts_accounting_partialinvoices-delete',
						'position' => 509
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_partialinvoices']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][510] = [
						'section' => 'Partial invoices',
						'name' => 'Show',
						'access' => 'ts_accounting_partialinvoices-show',
						'position' => 510
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_logs']['list'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][511] = [
						'section' => 'Logs',
						'name' => 'List',
						'access' => 'core_logs-list',
						'position' => 511
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_edit_student']['logs'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][512] = [
						'section' => 'Actions » Edit student record',
						'name' => 'Logs',
						'access' => 'thebing_invoice_edit_student-logs',
						'position' => 512
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_parking_matching']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][513] = [
						'section' => 'Accommodation » Parking',
						'name' => 'Dummy',
						'access' => 'thebing_accommodation_parking_matching',
						'position' => 513
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_pos']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][514] = [
						'section' => 'Points of Sale',
						'name' => 'New',
						'access' => 'ts_admin_pos-new',
						'position' => 514
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_pos']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][515] = [
						'section' => 'Points of Sale',
						'name' => 'Edit',
						'access' => 'ts_admin_pos-edit',
						'position' => 515
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_pos']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][516] = [
						'section' => 'Points of Sale',
						'name' => 'Delete',
						'access' => 'ts_admin_pos-delete',
						'position' => 516
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_pos']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][517] = [
						'section' => 'Points of Sale',
						'name' => 'Show',
						'access' => 'ts_admin_pos-show',
						'position' => 517
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_debtors_report']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][518] = [
						'section' => 'Debtors report',
						'name' => 'New',
						'access' => 'ts_accounting_debtors_report-new',
						'position' => 518
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_debtors_report']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][519] = [
						'section' => 'Debtors report',
						'name' => 'Edit',
						'access' => 'ts_accounting_debtors_report-edit',
						'position' => 519
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_debtors_report']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][520] = [
						'section' => 'Debtors report',
						'name' => 'Delete',
						'access' => 'ts_accounting_debtors_report-delete',
						'position' => 520
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_accounting_debtors_report']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][521] = [
						'section' => 'Debtors report',
						'name' => 'Show',
						'access' => 'ts_accounting_debtors_report-show',
						'position' => 521
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_teacher_availability']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][522] = [
						'section' => 'Teacher management » Availability',
						'name' => 'New',
						'access' => 'ts_tuition_teacher_availability-new',
						'position' => 522
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_teacher_availability']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][523] = [
						'section' => 'Teacher management » Availability',
						'name' => 'Edit',
						'access' => 'ts_tuition_teacher_availability-edit',
						'position' => 523
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_teacher_availability']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][524] = [
						'section' => 'Teacher management » Availability',
						'name' => 'Delete',
						'access' => 'ts_tuition_teacher_availability-delete',
						'position' => 524
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_teacher_availability']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][525] = [
						'section' => 'Teacher management » Availability',
						'name' => 'Show',
						'access' => 'ts_tuition_teacher_availability-show',
						'position' => 525
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_incoming_inquiry_payments']['release'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][526] = [
						'section' => 'Incoming payments » Incoming payment list',
						'name' => 'Release',
						'access' => 'thebing_accounting_incoming_inquiry_payments-release',
						'position' => 526
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_structure']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][527] = [
						'section' => 'Frontend » Course structure',
						'name' => 'Edit',
						'access' => 'ts_admin_frontend_course_structure-edit',
						'position' => 527
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_bookings']['notes'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][528] = [
						'section' => 'Bookings',
						'name' => 'Notes',
						'access' => 'ts_bookings-notes',
						'position' => 528
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_bookings']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][529] = [
						'section' => 'Bookings',
						'name' => 'Communication',
						'access' => 'ts_bookings-communication',
						'position' => 529
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['app'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][530] = [
						'section' => 'Communication',
						'name' => 'App message',
						'access' => 'core_communication-app',
						'position' => 530
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_app']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][531] = [
						'section' => 'Templates » App templates',
						'name' => 'New',
						'access' => 'core_admin_templates_app-new',
						'position' => 531
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_app']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][532] = [
						'section' => 'Templates » App templates',
						'name' => 'Edit',
						'access' => 'core_admin_templates_app-edit',
						'position' => 532
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_app']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][533] = [
						'section' => 'Templates » App templates',
						'name' => 'Delete',
						'access' => 'core_admin_templates_app-delete',
						'position' => 533
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_app']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][534] = [
						'section' => 'Templates » App templates',
						'name' => 'Show',
						'access' => 'core_admin_templates_app-show',
						'position' => 534
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][535] = [
						'section' => 'Student lists » Simple view',
						'name' => 'Communication',
						'access' => 'thebing_students_simple_view-communication',
						'position' => 535
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_welcome_list']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][536] = [
						'section' => 'Student lists » Arrival list',
						'name' => 'Communication',
						'access' => 'thebing_students_welcome_list-communication',
						'position' => 536
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_departure_list']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][537] = [
						'section' => 'Student lists » Departure list',
						'name' => 'Communication',
						'access' => 'thebing_students_departure_list-communication',
						'position' => 537
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_feedback_list']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][538] = [
						'section' => 'Student lists » Feedbacks',
						'name' => 'Communication',
						'access' => 'thebing_students_feedback_list-communication',
						'position' => 538
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_visa_list']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][539] = [
						'section' => 'Student lists » Visa list',
						'name' => 'Communication',
						'access' => 'thebing_students_visa_list-communication',
						'position' => 539
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication_notes']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][540] = [
						'section' => 'Notes',
						'name' => 'New',
						'access' => 'core_communication_notes-new',
						'position' => 540
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication_notes']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][541] = [
						'section' => 'Notes',
						'name' => 'Edit',
						'access' => 'core_communication_notes-edit',
						'position' => 541
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication_notes']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][542] = [
						'section' => 'Notes',
						'name' => 'Delete',
						'access' => 'core_communication_notes-delete',
						'position' => 542
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_customise_dialogue']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][543] = [
						'section' => 'Customise dialogues',
						'name' => 'Edit',
						'access' => 'core_customise_dialogue-edit',
						'position' => 543
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][544] = [
						'section' => 'Marketing » Companies',
						'name' => 'New',
						'access' => 'ts_marketing_companies-new',
						'position' => 544
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][545] = [
						'section' => 'Marketing » Companies',
						'name' => 'Edit',
						'access' => 'ts_marketing_companies-edit',
						'position' => 545
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][546] = [
						'section' => 'Marketing » Companies',
						'name' => 'Delete',
						'access' => 'ts_marketing_companies-delete',
						'position' => 546
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][547] = [
						'section' => 'Marketing » Companies',
						'name' => 'Show',
						'access' => 'ts_marketing_companies-show',
						'position' => 547
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_industries']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][548] = [
						'section' => 'Marketing » Industries',
						'name' => 'New',
						'access' => 'ts_marketing_industries-new',
						'position' => 548
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_industries']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][549] = [
						'section' => 'Marketing » Industries',
						'name' => 'Edit',
						'access' => 'ts_marketing_industries-edit',
						'position' => 549
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_industries']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][550] = [
						'section' => 'Marketing » Industries',
						'name' => 'Delete',
						'access' => 'ts_marketing_industries-delete',
						'position' => 550
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_industries']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][551] = [
						'section' => 'Marketing » Industries',
						'name' => 'Show',
						'access' => 'ts_marketing_industries-show',
						'position' => 551
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_employees']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][552] = [
						'section' => 'Marketing » Companies » Employees',
						'name' => 'New',
						'access' => 'ts_marketing_companies_employees-new',
						'position' => 552
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_employees']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][553] = [
						'section' => 'Marketing » Companies » Employees',
						'name' => 'Edit',
						'access' => 'ts_marketing_companies_employees-edit',
						'position' => 553
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_employees']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][554] = [
						'section' => 'Marketing » Companies » Employees',
						'name' => 'Delete',
						'access' => 'ts_marketing_companies_employees-delete',
						'position' => 554
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_employees']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][555] = [
						'section' => 'Marketing » Companies » Employees',
						'name' => 'Show',
						'access' => 'ts_marketing_companies_employees-show',
						'position' => 555
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_notes']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][556] = [
						'section' => 'Marketing » Companies » Notes',
						'name' => 'Dummy',
						'access' => 'ts_marketing_companies_notes',
						'position' => 556
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_uploads']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][557] = [
						'section' => 'Marketing » Companies » Uploads',
						'name' => 'Dummy',
						'access' => 'ts_marketing_companies_uploads',
						'position' => 557
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_job_opportunities']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][558] = [
						'section' => 'Marketing » Companies » Job Opportunities',
						'name' => 'New',
						'access' => 'ts_marketing_companies_job_opportunities-new',
						'position' => 558
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_job_opportunities']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][559] = [
						'section' => 'Marketing » Companies » Job Opportunities',
						'name' => 'Edit',
						'access' => 'ts_marketing_companies_job_opportunities-edit',
						'position' => 559
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_job_opportunities']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][560] = [
						'section' => 'Marketing » Companies » Job Opportunities',
						'name' => 'Delete',
						'access' => 'ts_marketing_companies_job_opportunities-delete',
						'position' => 560
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_companies_job_opportunities']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][561] = [
						'section' => 'Marketing » Companies » Job Opportunities',
						'name' => 'Show',
						'access' => 'ts_marketing_companies_job_opportunities-show',
						'position' => 561
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_programs']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][562] = [
						'section' => 'Resources » Courses » Programs',
						'name' => 'New',
						'access' => 'thebing_tuition_resources_courses_programs-new',
						'position' => 562
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_programs']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][563] = [
						'section' => 'Resources » Courses » Programs',
						'name' => 'Edit',
						'access' => 'thebing_tuition_resources_courses_programs-edit',
						'position' => 563
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_programs']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][564] = [
						'section' => 'Resources » Courses » Programs',
						'name' => 'Delete',
						'access' => 'thebing_tuition_resources_courses_programs-delete',
						'position' => 564
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses_programs']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][565] = [
						'section' => 'Resources » Courses » Programs',
						'name' => 'Show',
						'access' => 'thebing_tuition_resources_courses_programs-show',
						'position' => 565
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_gui2_designer']['copy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][566] = [
						'section' => 'User interface » Dialog designer & Filter sets',
						'name' => 'Copy',
						'access' => 'core_gui2_designer-copy',
						'position' => 566
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][567] = [
						'section' => 'Page',
						'name' => 'New',
						'access' => 'core_cms_page-new',
						'position' => 567
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][568] = [
						'section' => 'Page',
						'name' => 'Edit',
						'access' => 'core_cms_page-edit',
						'position' => 568
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][569] = [
						'section' => 'Page',
						'name' => 'Delete',
						'access' => 'core_cms_page-delete',
						'position' => 569
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][570] = [
						'section' => 'Page',
						'name' => 'Show',
						'access' => 'core_cms_page-show',
						'position' => 570
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['publish'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][571] = [
						'section' => 'Page',
						'name' => 'Publish',
						'access' => 'core_cms_page-publish',
						'position' => 571
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_template']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][572] = [
						'section' => 'Template',
						'name' => 'Edit',
						'access' => 'core_cms_template-edit',
						'position' => 572
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_folder']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][573] = [
						'section' => 'Folder',
						'name' => 'New',
						'access' => 'core_cms_folder-new',
						'position' => 573
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['properties'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][574] = [
						'section' => 'Page',
						'name' => 'Properties',
						'access' => 'core_cms_page-properties',
						'position' => 574
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_sites']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][575] = [
						'section' => 'Websites',
						'name' => 'Edit',
						'access' => 'core_cms_sites-edit',
						'position' => 575
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['admin'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][576] = [
						'section' => 'Page',
						'name' => 'Administrator',
						'access' => 'core_cms_page-admin',
						'position' => 576
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_blocks']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][577] = [
						'section' => 'Blocks',
						'name' => 'Edit',
						'access' => 'core_cms_blocks-edit',
						'position' => 577
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['check_links'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][578] = [
						'section' => 'Page',
						'name' => 'Check links',
						'access' => 'core_cms_page-check_links',
						'position' => 578
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_cms_page']['filter'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['CMS'][579] = [
						'section' => 'Page',
						'name' => 'Filter functions',
						'access' => 'core_cms_page-filter',
						'position' => 579
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_media']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Files'][580] = [
						'section' => 'Media management',
						'name' => 'Edit',
						'access' => 'core_media-edit',
						'position' => 580
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_storage']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Files'][581] = [
						'section' => 'File management',
						'name' => 'Edit',
						'access' => 'core_storage-edit',
						'position' => 581
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_settings']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Settings'][582] = [
						'section' => 'Settings',
						'name' => 'Edit',
						'access' => 'core_settings-edit',
						'position' => 582
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_contacts']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][583] = [
						'section' => 'Administration » Contacts',
						'name' => 'New',
						'access' => 'core_admin_contacts-new',
						'position' => 583
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_contacts']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][584] = [
						'section' => 'Administration » Contacts',
						'name' => 'Edit',
						'access' => 'core_admin_contacts-edit',
						'position' => 584
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_contacts']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][585] = [
						'section' => 'Administration » Contacts',
						'name' => 'Delete',
						'access' => 'core_admin_contacts-delete',
						'position' => 585
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_contacts']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][586] = [
						'section' => 'Administration » Contacts',
						'name' => 'Show',
						'access' => 'core_admin_contacts-show',
						'position' => 586
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_contacts']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][587] = [
						'section' => 'Administration » Contacts',
						'name' => 'Communication',
						'access' => 'core_admin_contacts-communication',
						'position' => 587
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_planificaton']['block_daily_comments'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][588] = [
						'section' => 'Class scheduling » Scheduling',
						'name' => 'Block » Daily comments',
						'access' => 'thebing_tuition_planificaton-block_daily_comments',
						'position' => 588
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resources_courses']['filemanager'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][589] = [
						'section' => 'Resources » Courses',
						'name' => 'Filemanager',
						'access' => 'thebing_tuition_resources_courses-filemanager',
						'position' => 589
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_superordinate']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][590] = [
						'section' => 'Frontend » Superordinate courses',
						'name' => 'New',
						'access' => 'ts_admin_frontend_course_superordinate-new',
						'position' => 590
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_superordinate']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][591] = [
						'section' => 'Frontend » Superordinate courses',
						'name' => 'Edit',
						'access' => 'ts_admin_frontend_course_superordinate-edit',
						'position' => 591
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_superordinate']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][592] = [
						'section' => 'Frontend » Superordinate courses',
						'name' => 'Delete',
						'access' => 'ts_admin_frontend_course_superordinate-delete',
						'position' => 592
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_superordinate']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][593] = [
						'section' => 'Frontend » Superordinate courses',
						'name' => 'Show',
						'access' => 'ts_admin_frontend_course_superordinate-show',
						'position' => 593
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][594] = [
						'section' => 'Co-Op » Students',
						'name' => 'Show',
						'access' => 'ts_coop_students-show',
						'position' => 594
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][595] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'New',
						'access' => 'ts_coop_students_allocations-new',
						'position' => 595
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][596] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Delete',
						'access' => 'ts_coop_students_allocations-delete',
						'position' => 596
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['request'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][597] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Request',
						'access' => 'ts_coop_students_allocations-request',
						'position' => 597
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['confirm'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][598] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Confirm',
						'access' => 'ts_coop_students_allocations-confirm',
						'position' => 598
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['allocate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][599] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Allocate',
						'access' => 'ts_coop_students_allocations-allocate',
						'position' => 599
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['documents'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][600] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Documents',
						'access' => 'ts_coop_students_allocations-documents',
						'position' => 600
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_coop_students_allocations']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Co-Op'][601] = [
						'section' => 'Co-Op » Students » Allocations',
						'name' => 'Communication',
						'access' => 'ts_coop_students_allocations-communication',
						'position' => 601
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_admin_frontend_course_superordinate']['filemanager'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][602] = [
						'section' => 'Frontend » Superordinate courses',
						'name' => 'Filemanager',
						'access' => 'ts_admin_frontend_course_superordinate-filemanager',
						'position' => 602
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['allocate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][603] = [
						'section' => 'Communication',
						'name' => 'Allocate',
						'access' => 'core_communication-allocate',
						'position' => 603
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_admin_users']['access'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][604] = [
						'section' => 'Employees » List',
						'name' => 'Access rights',
						'access' => 'thebing_admin_users-access',
						'position' => 604
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_students_sponsoring_list']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][605] = [
						'section' => 'Student lists » Sponsoring list',
						'name' => 'Edit',
						'access' => 'ts_students_sponsoring_list-edit',
						'position' => 605
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_students_sponsoring_list']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][606] = [
						'section' => 'Student lists » Sponsoring list',
						'name' => 'Communication',
						'access' => 'ts_students_sponsoring_list-communication',
						'position' => 606
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_proficiency']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][607] = [
						'section' => 'Rescources » Levels',
						'name' => 'Deactivate',
						'access' => 'thebing_tuition_resource_proficiency-deactivate',
						'position' => 607
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][608] = [
						'section' => 'Event management',
						'name' => 'New',
						'access' => 'core_event_manager-new',
						'position' => 608
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][609] = [
						'section' => 'Event management',
						'name' => 'Edit',
						'access' => 'core_event_manager-edit',
						'position' => 609
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][610] = [
						'section' => 'Event management',
						'name' => 'Delete',
						'access' => 'core_event_manager-delete',
						'position' => 610
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][611] = [
						'section' => 'Event management',
						'name' => 'Show',
						'access' => 'core_event_manager-show',
						'position' => 611
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][612] = [
						'section' => 'Event management',
						'name' => 'Deactivate',
						'access' => 'core_event_manager-deactivate',
						'position' => 612
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_communication']['send'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Communication'][613] = [
						'section' => 'Communication',
						'name' => 'Send',
						'access' => 'core_communication-send',
						'position' => 613
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_cleaning_schedule_settings']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][614] = [
						'section' => 'Cleaning schedule » Settings',
						'name' => 'Edit',
						'access' => 'thebing_cleaning_schedule_settings-edit',
						'position' => 614
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_cleaning_schedule_settings']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][615] = [
						'section' => 'Cleaning schedule » Settings',
						'name' => 'Show',
						'access' => 'thebing_cleaning_schedule_settings-show',
						'position' => 615
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_wizard_setup']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Start'][616] = [
						'section' => 'Wizard » Setup',
						'name' => 'Show',
						'access' => 'ts_wizard_setup-show',
						'position' => 616
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_app']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][617] = [
						'section' => 'Templates » App templates',
						'name' => 'Deactivate',
						'access' => 'core_admin_templates_app-deactivate',
						'position' => 617
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_sms']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][618] = [
						'section' => 'Templates » SMS templates',
						'name' => 'Deactivate',
						'access' => 'core_admin_templates_sms-deactivate',
						'position' => 618
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_templates_email']['deactivate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][619] = [
						'section' => 'Templates » E-Mail » Templates',
						'name' => 'Deactivate',
						'access' => 'core_admin_templates_email-deactivate',
						'position' => 619
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_delete']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][620] = [
						'section' => 'Invoice » Delete document',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_delete',
						'position' => 620
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_invoice_partial_invoicing']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][621] = [
						'section' => 'Student Record » Partial invoicing',
						'name' => 'Dummy',
						'access' => 'ts_invoice_partial_invoicing',
						'position' => 621
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_definitions']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][622] = [
						'section' => 'Resources » New reporting » Definitions',
						'name' => 'New',
						'access' => 'ts_reporting_definitions-new',
						'position' => 622
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_definitions']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][623] = [
						'section' => 'Resources » New reporting » Definitions',
						'name' => 'Edit',
						'access' => 'ts_reporting_definitions-edit',
						'position' => 623
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_definitions']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][624] = [
						'section' => 'Resources » New reporting » Definitions',
						'name' => 'Delete',
						'access' => 'ts_reporting_definitions-delete',
						'position' => 624
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_definitions']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][625] = [
						'section' => 'Resources » New reporting » Definitions',
						'name' => 'Show',
						'access' => 'ts_reporting_definitions-show',
						'position' => 625
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_overview']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][626] = [
						'section' => 'Resources » New reporting » Overview',
						'name' => 'New',
						'access' => 'ts_reporting_overview-new',
						'position' => 626
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_overview']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][627] = [
						'section' => 'Resources » New reporting » Overview',
						'name' => 'Edit',
						'access' => 'ts_reporting_overview-edit',
						'position' => 627
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_overview']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][628] = [
						'section' => 'Resources » New reporting » Overview',
						'name' => 'Delete',
						'access' => 'ts_reporting_overview-delete',
						'position' => 628
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_reporting_overview']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][629] = [
						'section' => 'Resources » New reporting » Overview',
						'name' => 'Show',
						'access' => 'ts_reporting_overview-show',
						'position' => 629
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accommodation_communicate_overview']['request_availability'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accommodation'][630] = [
						'section' => 'Communication',
						'name' => 'Request availability',
						'access' => 'thebing_accommodation_communicate_overview-request_availability',
						'position' => 630
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_resource_teachers']['filemanager'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][631] = [
						'section' => 'Teacher management',
						'name' => 'Filemanager',
						'access' => 'thebing_tuition_resource_teachers-filemanager',
						'position' => 631
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_payments']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][632] = [
						'section' => 'Event management » Events » Payments',
						'name' => 'New',
						'access' => 'ts_event_manager_payments-new',
						'position' => 632
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_payments']['failed'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][633] = [
						'section' => 'Event management » Events » Payments',
						'name' => 'Failed',
						'access' => 'ts_event_manager_payments-failed',
						'position' => 633
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_payments']['allocation_failed'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][634] = [
						'section' => 'Event management » Events » Payments',
						'name' => 'Allocation Failed',
						'access' => 'ts_event_manager_payments-allocation_failed',
						'position' => 634
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['check_in'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][635] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Check-In',
						'access' => 'ts_event_manager_inquiries-check_in',
						'position' => 635
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['check_out'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][636] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Check-Out',
						'access' => 'ts_event_manager_inquiries-check_out',
						'position' => 636
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['birthday'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][637] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Customer Birthday',
						'access' => 'ts_event_manager_inquiries-birthday',
						'position' => 637
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['course_booked'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][638] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Course Booked',
						'access' => 'ts_event_manager_inquiries-course_booked',
						'position' => 638
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['transfer_booked'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][639] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'New Transfer',
						'access' => 'ts_event_manager_inquiries-transfer_booked',
						'position' => 639
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['scheduled'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][640] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Scheduled',
						'access' => 'ts_event_manager_inquiries-scheduled',
						'position' => 640
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_frontend']['placementtest'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][641] = [
						'section' => 'Event management » Events » Frontend',
						'name' => 'Placementtest',
						'access' => 'ts_event_manager_frontend-placementtest',
						'position' => 641
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_frontend']['form_saved'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][642] = [
						'section' => 'Event management » Events » Frontend',
						'name' => 'Form Submit',
						'access' => 'ts_event_manager_frontend-form_saved',
						'position' => 642
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_activities']['booked'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][643] = [
						'section' => 'Event management » Events » Activities',
						'name' => 'Booked',
						'access' => 'ts_event_manager_activities-booked',
						'position' => 643
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_activities']['cancelled'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][644] = [
						'section' => 'Event management » Events » Activities',
						'name' => 'Cancelled',
						'access' => 'ts_event_manager_activities-cancelled',
						'position' => 644
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_activities']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][645] = [
						'section' => 'Event management » Events » Activities',
						'name' => 'Delete',
						'access' => 'ts_event_manager_activities-delete',
						'position' => 645
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_activities']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][646] = [
						'section' => 'Event management » Events » Activities',
						'name' => 'Show',
						'access' => 'ts_event_manager_activities-show',
						'position' => 646
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager_system']['fidelo_news'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][647] = [
						'section' => 'Event management » Events » System',
						'name' => 'Fidelo News',
						'access' => 'core_event_manager_system-fidelo_news',
						'position' => 647
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager_system']['update'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][648] = [
						'section' => 'Event management » Events » System',
						'name' => 'Update',
						'access' => 'core_event_manager_system-update',
						'position' => 648
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager_system']['event_failed'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][649] = [
						'section' => 'Event management » Events » System',
						'name' => 'Event Failed',
						'access' => 'core_event_manager_system-event_failed',
						'position' => 649
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_frontend']['feedback_form'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][650] = [
						'section' => 'Event management » Events » Frontend',
						'name' => 'Feedback Form',
						'access' => 'ts_event_manager_frontend-feedback_form',
						'position' => 650
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_creditnotes_edit']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][651] = [
						'section' => 'Agents » Credit notes',
						'name' => 'Delete',
						'access' => 'thebing_marketing_agencies_creditnotes_edit-delete',
						'position' => 651
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_creditnotes_edit']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][652] = [
						'section' => 'Agents » Credit notes',
						'name' => 'New',
						'access' => 'thebing_marketing_agencies_creditnotes_edit-new',
						'position' => 652
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_creditnotes_edit']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][653] = [
						'section' => 'Agents » Credit notes',
						'name' => 'Show',
						'access' => 'thebing_marketing_agencies_creditnotes_edit-show',
						'position' => 653
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_agencies_creditnotes_edit']['cancel'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][654] = [
						'section' => 'Agents » Credit notes',
						'name' => 'Cancel',
						'access' => 'thebing_marketing_agencies_creditnotes_edit-cancel',
						'position' => 654
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['confirm'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][655] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Confirm',
						'access' => 'ts_event_manager_inquiries-confirm',
						'position' => 655
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['created'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][656] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Created',
						'access' => 'ts_event_manager_inquiries-created',
						'position' => 656
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_country_groups']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][657] = [
						'section' => 'Marketing » Country groups',
						'name' => 'New',
						'access' => 'ts_marketing_country_groups-new',
						'position' => 657
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_country_groups']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][658] = [
						'section' => 'Marketing » Country groups',
						'name' => 'Edit',
						'access' => 'ts_marketing_country_groups-edit',
						'position' => 658
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_country_groups']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][659] = [
						'section' => 'Marketing » Country groups',
						'name' => 'Delete',
						'access' => 'ts_marketing_country_groups-delete',
						'position' => 659
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_marketing_country_groups']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][660] = [
						'section' => 'Marketing » Country groups',
						'name' => 'Show',
						'access' => 'ts_marketing_country_groups-show',
						'position' => 660
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_client_payments']['reallocate'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][661] = [
						'section' => 'Incoming payments » Client payments',
						'name' => 'Reallocate',
						'access' => 'thebing_accounting_client_payments-reallocate',
						'position' => 661
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_simple_view']['change_inbox'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Students'][662] = [
						'section' => 'Student lists » Simple view',
						'name' => 'Change Inbox',
						'access' => 'thebing_students_simple_view-change_inbox',
						'position' => 662
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_accommodation']['requirements'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][663] = [
						'section' => 'Event management » Events » Accommodations',
						'name' => 'Requirements',
						'access' => 'ts_event_manager_accommodation-requirements',
						'position' => 663
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_accommodation']['missing_requirements'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][664] = [
						'section' => 'Event management » Events » Accommodations',
						'name' => 'Missing Requirements',
						'access' => 'ts_event_manager_accommodation-missing_requirements',
						'position' => 664
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_absence_reasons']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][665] = [
						'section' => 'Resources » Absence Reasons',
						'name' => 'New',
						'access' => 'ts_tuition_absence_reasons-new',
						'position' => 665
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_absence_reasons']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][666] = [
						'section' => 'Resources » Absence Reasons',
						'name' => 'Edit',
						'access' => 'ts_tuition_absence_reasons-edit',
						'position' => 666
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_absence_reasons']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][667] = [
						'section' => 'Resources » Absence Reasons',
						'name' => 'Delete',
						'access' => 'ts_tuition_absence_reasons-delete',
						'position' => 667
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_tuition_absence_reasons']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][668] = [
						'section' => 'Resources » Absence Reasons',
						'name' => 'Show',
						'access' => 'ts_tuition_absence_reasons-show',
						'position' => 668
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_event_manager']['testing'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][669] = [
						'section' => 'Event management',
						'name' => 'Testing',
						'access' => 'core_event_manager-testing',
						'position' => 669
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_provisions']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][670] = [
						'section' => 'Agents » Commission categories',
						'name' => 'New',
						'access' => 'thebing_marketing_provisions-new',
						'position' => 670
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_provisions']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][671] = [
						'section' => 'Agents » Commission categories',
						'name' => 'Delete',
						'access' => 'thebing_marketing_provisions-delete',
						'position' => 671
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_marketing_provisions']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Marketing'][672] = [
						'section' => 'Agents » Commission categories',
						'name' => 'Edit',
						'access' => 'thebing_marketing_provisions-edit',
						'position' => 672
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][673] = [
						'section' => 'Enquiries » List',
						'name' => 'New',
						'access' => 'thebing_students_contact_request-new',
						'position' => 673
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][674] = [
						'section' => 'Enquiries » List',
						'name' => 'Delete',
						'access' => 'thebing_students_contact_request-delete',
						'position' => 674
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][675] = [
						'section' => 'Enquiries » List',
						'name' => 'Edit',
						'access' => 'thebing_students_contact_request-edit',
						'position' => 675
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_mail_spool']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][676] = [
						'section' => 'Administration » Mail Spool',
						'name' => 'Delete',
						'access' => 'core_admin_mail_spool-delete',
						'position' => 676
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_mail_spool']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][677] = [
						'section' => 'Administration » Mail Spool',
						'name' => 'Show',
						'access' => 'core_admin_mail_spool-show',
						'position' => 677
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_mail_spool']['send'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][678] = [
						'section' => 'Administration » Mail Spool',
						'name' => 'Send',
						'access' => 'core_admin_mail_spool-send',
						'position' => 678
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_tuition_course_list']['telc_export'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Classes'][679] = [
						'section' => 'Class scheduling » Booked courses',
						'name' => 'TELC Export',
						'access' => 'thebing_tuition_course_list-telc_export',
						'position' => 679
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['communication'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][680] = [
						'section' => 'Enquiries » List',
						'name' => 'Communication',
						'access' => 'thebing_students_contact_request-communication',
						'position' => 680
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_students_contact_request']['notices'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Enquiries'][681] = [
						'section' => 'Enquiries » List',
						'name' => 'Notices',
						'access' => 'thebing_students_contact_request-notices',
						'position' => 681
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['updated'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][682] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Updated',
						'access' => 'ts_event_manager_inquiries-updated',
						'position' => 682
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_inquiries']['saved'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][683] = [
						'section' => 'Event management » Events » Inquiries',
						'name' => 'Saved',
						'access' => 'ts_event_manager_inquiries-saved',
						'position' => 683
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_event_manager_frontend']['pdf_failed'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Events'][684] = [
						'section' => 'Event management » Events » Frontend',
						'name' => 'PDF Creation Failed',
						'access' => 'ts_event_manager_frontend-pdf_failed',
						'position' => 684
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ts_bookings_invoices']['vat_selection'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][685] = [
						'section' => 'Invoices',
						'name' => 'VAT selection',
						'access' => 'ts_bookings_invoices-vat_selection',
						'position' => 685
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_assign_client_payments_config']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][686] = [
						'section' => 'Incoming payments » Assign client payments » Config',
						'name' => 'Edit',
						'access' => 'thebing_accounting_assign_client_payments_config-edit',
						'position' => 686
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_accounting_assign_client_payments_config']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Accounting'][687] = [
						'section' => 'Incoming payments » Assign client payments » Config',
						'name' => 'Show',
						'access' => 'thebing_accounting_assign_client_payments_config-show',
						'position' => 687
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_nationalities']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][688] = [
						'section' => 'Nationalities',
						'name' => 'New',
						'access' => 'core_admin_nationalities-new',
						'position' => 688
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_nationalities']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][689] = [
						'section' => 'Nationalities',
						'name' => 'Edit',
						'access' => 'core_admin_nationalities-edit',
						'position' => 689
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_nationalities']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][690] = [
						'section' => 'Nationalities',
						'name' => 'Delete',
						'access' => 'core_admin_nationalities-delete',
						'position' => 690
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['core_admin_nationalities']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][691] = [
						'section' => 'Nationalities',
						'name' => 'Show',
						'access' => 'core_admin_nationalities-show',
						'position' => 691
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ac_admin_languages']['new'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][692] = [
						'section' => 'Intern',
						'name' => 'New',
						'access' => 'ac_admin_languages-new',
						'position' => 692
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ac_admin_languages']['edit'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][693] = [
						'section' => 'Intern',
						'name' => 'Edit',
						'access' => 'ac_admin_languages-edit',
						'position' => 693
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ac_admin_languages']['delete'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][694] = [
						'section' => 'Intern',
						'name' => 'Delete',
						'access' => 'ac_admin_languages-delete',
						'position' => 694
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['ac_admin_languages']['show'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Admin'][695] = [
						'section' => 'Intern',
						'name' => 'Show',
						'access' => 'ac_admin_languages-show',
						'position' => 695
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_management_reports_course_schedule']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Reporting'][696] = [
						'section' => 'Static report » GA Course schedule',
						'name' => 'Dummy',
						'access' => 'thebing_management_reports_course_schedule',
						'position' => 696
					];
					self::$aAccess['UA5J-UGS3-74L5-G8SC']['thebing_invoice_document_accepted']['dummy'] = 1;
					self::$aAccessSort['UA5J-UGS3-74L5-G8SC']['Bookings'][697] = [
						'section' => 'Invoice » Accept invoice',
						'name' => 'Dummy',
						'access' => 'thebing_invoice_document_accepted',
						'position' => 697
					];

				}
			
		}	
		