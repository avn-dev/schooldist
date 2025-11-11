<?php

class Ext_TS_System_Checks_User_InboxAccess extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Converting access to booking inbox to a new structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '';
		return $sDescription;
	}

	public function executeCheck() {

		$aCheck = DB::getQueryRows("SELECT * FROM `tc_access_matrix_items` WHERE `item_type` = 'inbox'");

		// Check wurde schon ausgeführt
		if(!empty($aCheck)) {
			return true;
		}
		
		DB::begin(__METHOD__);
		
		// User rols
		$aUserRoles = Ext_Thebing_Admin_Usergroup::getList();
		
		$oClient = Ext_Thebing_Client::getInstance();
		$aInboxes = $oClient->getInboxList();

		foreach($aInboxes as $aInbox) {
			
			$aItem = [
				'item_id' => $aInbox['id'],
				'item_type' => 'inbox'
			];
			
			$iItemId = DB::insertData('tc_access_matrix_items', $aItem);
			
			$sAccessRight = 'thebing_invoice_inbox_'.$aInbox['id'];
			
			foreach($aUserRoles as $iUserRoleId=>$sUserRole) {

				$oAccessGroup = new Ext_Thebing_Access_Group($iUserRoleId);
				$bAccess = $oAccessGroup->checkAccess($sAccessRight);
				
				if($bAccess) {
					$aItem = [
						'item_id' => $iItemId,
						'group_id' => $iUserRoleId
					];

					DB::insertData('tc_access_matrix_groups', $aItem);
				}
				
			}

			// Users
			$aUsers = $oClient->getUsers(true);

			foreach($aUsers as $iUserId=>$sUser) {

				$oAccessUser = new Ext_Thebing_Access_User($iUserId);
				$bAccess = $oAccessUser->checkUserAccess($sAccessRight, null, true);
				
				if($bAccess !== null) {
					$aItem = [
						'item_id' => $iItemId,
						'user_id' => $iUserId,
						'right' => (int)$bAccess
					];

					DB::insertData('tc_access_matrix_rights', $aItem);
				}
				
			}
			
		}

		DB::commit(__METHOD__);
		
		return true;
	}

}
