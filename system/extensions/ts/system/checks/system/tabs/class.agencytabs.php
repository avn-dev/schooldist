<?php

class Ext_TS_System_Checks_System_Tabs_AgencyTabs extends GlobalChecks {
	
	public function getTitle() {
		return 'Update agency tab';
	}
	
	public function getDescription() {
		return 'Change link to new page.';
	}
	
	public function executeCheck() {
		
		\Util::backupTable('system_user');
		
		$users = \DB::getQueryRows("SELECT * FROM `system_user` WHERE `active` = 1");
		
		foreach($users as $user) {
			
			$additional = json_decode($user['additional'], true);
			
			$isChanged = false;
			if(!empty($additional['admin_tabs'])) {
				foreach($additional['admin_tabs'] as &$tab) {
					if($tab['value'] == '/admin/extensions/thebing/marketing/agencies.html') {
						$tab['value'] = '/ts/companies/gui2/page/agencies';
						$isChanged = true;
					}
				}
			}
			
			if($isChanged) {
				$additionalJson = json_encode($additional);
				\DB::executePreparedQuery("UPDATE `system_user` SET `changed` = `changed`, `additional` = :additional WHERE `id` = :id", ['id'=>$user['id'], 'additional' => $additionalJson]);
			}

		}

		return true;
	}

}
		