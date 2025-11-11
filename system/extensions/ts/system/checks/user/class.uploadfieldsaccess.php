<?php

class Ext_TS_System_Checks_User_UploadFieldsAccess extends GlobalChecks
{
	 const ITEM_TYPE = 'customer_uploads';

	public function getTitle()
	{
		return 'Upload fields';
	}

	public function getDescription()
	{
		return 'Sets default access rights on upload fields';
	}

	public function executeCheck()
	{
		$check = DB::getQueryRows("SELECT * FROM `tc_access_matrix_items` WHERE `item_type` = '".self::ITEM_TYPE."'");

		// Check wurde schon ausgeführt
		if(!empty($check)) {
			return true;
		}

		DB::begin(__METHOD__);
		
		// User groups
		$userGroups = Ext_Thebing_Admin_Usergroup::getList();
		// Users
		$users = Ext_Thebing_Client::getInstance()->getUsers(true);

		$uploadFieldIds = \Ext_Thebing_School_Customerupload::query()->pluck('id');

		foreach($uploadFieldIds as $uploadFieldId) {
			
			$item = ['item_id' => $uploadFieldId, 'item_type' => self::ITEM_TYPE];
			
			$itemId = DB::insertData('tc_access_matrix_items', $item);

			foreach(array_keys($userGroups) as $userGroupId) {
				$item = ['item_id' => $itemId, 'group_id' => $userGroupId];
				DB::insertData('tc_access_matrix_groups', $item);
			}

			foreach(array_keys($users) as $userId) {
				$aItem = ['item_id' => $itemId, 'user_id' => $userId, 'right' => 1];
				DB::insertData('tc_access_matrix_rights', $aItem);
			}
		}

		DB::commit(__METHOD__);
		
		return true;
	}

}
