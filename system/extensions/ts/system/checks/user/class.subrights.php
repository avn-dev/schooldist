<?php

class Ext_TS_System_Checks_User_Subrights extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Introduction of sub-rights';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '';
		return $sDescription;
	}

	public function executeCheck() {

		DB::begin(__METHOD__);

		Util::backupTable('kolumbus_access_group_access');
		Util::backupTable('kolumbus_access_user_access');

		// Alte Rechte durch Core-Rechte ersetzen
		$aMapping = [
			'thebing_admin_email_accounts' => 'core_admin_emailaccounts',
			'thebing_admin_email_templates_cronjob' => 'core_admin_templates_automatic',
			'thebing_admin_fonts' => 'core_admin_templates_fonts',
			'thebing_wdmvc_token' => 'core_frontend_token',
			'thebing_admin_parallelprocessing_error_stack'=>'core_admin_parallelprocessing_error_stack',
			'thebing_admin_frontend_combinations' => 'core_frontend_combinations',
			'thebing_communication' => 'core_communication',
			'thebing_admin_frontend_templates' => 'core_frontend_templates',
			'thebing_marketing_advertency' => 'core_marketing_referrers',
			'thebing_admin_numberranges' => 'core_numberranges',
			'thebing_admin_flexibility' => 'core_user_flexibility',
			'thebing_support_icon' => 'core_zendesk'
		];

		foreach($aMapping as $sOld=>$sNew) {
			DB::executePreparedQuery("UPDATE `kolumbus_access_group_access` SET `access` = :new WHERE `access` = :old", ['old'=>$sOld, 'new'=>$sNew]);
			DB::executePreparedQuery("UPDATE `kolumbus_access_user_access` SET `access` = :new WHERE `access` = :old", ['old'=>$sOld, 'new'=>$sNew]);
		}

		$aSections = Ext_Thebing_Access_Client::getRightList();

		$aSectionSubRights = [];
		
		foreach($aSections as $sSection=>$aRights) {
			
			foreach($aRights as $sRight=>$iRight) {

				// Unterrecht
				if($sRight !== 'dummy') {
					$aSectionSubRights[$sSection][] = $sRight;				
				}
				
			}

		}

		foreach($aSectionSubRights as $sSection=>$aSubRights) {
			
			// Alle Gruppen und alle Benutzer mit Section als Recht, bekommen die Unterrechte zugewiesen
			$aGroupsWithSection = DB::getQueryRows("SELECT * FROM `kolumbus_access_group_access` WHERE `access` = :access", ['access'=>$sSection]);
			
			if(!empty($aGroupsWithSection)) {
				foreach($aGroupsWithSection as $aGroupWithSection) {
					foreach($aSubRights as $sSubright) {
						$aGroupWithSection['access'] = $sSection.'-'.$sSubright;
						DB::insertData('kolumbus_access_group_access', $aGroupWithSection, true, true);
					}
				}
			}
			
			unset($aGroupsWithSection);
			
			$aUsersWithSection = DB::getQueryRows("SELECT * FROM `kolumbus_access_user_access` WHERE `user_id` > 0 AND `access` = :access", ['access'=>$sSection]);

			if(!empty($aUsersWithSection)) {
				foreach($aUsersWithSection as $aUserWithSection) {
					foreach($aSubRights as $sSubright) {
						$aUserWithSection['access'] = $sSection.'-'.$sSubright;
						DB::insertData('kolumbus_access_user_access', $aUserWithSection, true, true);
					}
				}
			}
			
			unset($aUsersWithSection);
			
		}
		
		DB::commit(__METHOD__);
		
		return true;
	}

}
