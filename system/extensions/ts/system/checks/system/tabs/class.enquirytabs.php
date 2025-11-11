<?php

class Ext_TS_System_Checks_System_Tabs_EnquiryTabs extends GlobalChecks {
	
	public function getTitle() {
		return 'Update enquiry default tab';
	}
	
	public function getDescription() {
		return 'Change link to new page.';
	}
	
	public function executeCheck() {
		
		\Util::backupTable('system_user');
		
		$users = \DB::getQueryRows("SELECT * FROM `system_user` WHERE `additional` LIKE '%enquiries%'");
		
		foreach($users as $user) {
			
			$additional = json_decode($user['additional'], true);
			
			if(!empty($additional['admin_tabs'])) {
				foreach($additional['admin_tabs'] as &$tab) {
					if(
						$tab['key'] == 'enquiries' &&
						$tab['value'] == '/admin/extensions/thebing/students/request.html'
					) {
						$tab['value'] = '/ts/enquiry/page';
					}
				}
			}
			
			$additionalJson = json_encode($additional);
			
			\DB::executePreparedQuery("UPDATE `system_user` SET `changed` = `changed`, `additional` = :additional WHERE `id` = :id", ['id'=>$user['id'], 'additional' => $additionalJson]);

		}

		return true;
	}

}
		