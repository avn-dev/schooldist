<?php

class Ext_TS_System_Checks_User_ReportingAccess extends GlobalChecks
{
	public function getTitle()
	{
		return 'Reporting access rights';
	}

	public function getDescription()
	{
		return 'Set default access rights (ACL) on reporting V2 reports.';
	}

	public function executeCheck()
	{
		$type = 'ts_reporting_reports';
		$entities = \TsReporting\Entity\Report::query()->pluck('id');

		$check = DB::getQueryRows("SELECT * FROM `tc_access_matrix_items` WHERE `item_type` = '$type'");

		if (!empty($check)) {
			return true;
		}

		DB::begin(__METHOD__);

		$userGroups = Ext_Thebing_Admin_Usergroup::getList();
//		$users = Ext_Thebing_Client::getInstance()->getUsers(true);

		foreach ($entities as $entityId) {

			$item = ['item_id' => $entityId, 'item_type' => $type];
			$itemId = DB::insertData('tc_access_matrix_items', $item);

			foreach (array_keys($userGroups) as $userGroupId) {
				$item = ['item_id' => $itemId, 'group_id' => $userGroupId];
				DB::insertData('tc_access_matrix_groups', $item);
			}

//			foreach(array_keys($users) as $userId) {
//				$aItem = ['item_id' => $itemId, 'user_id' => $userId, 'right' => 1];
//				DB::insertData('tc_access_matrix_rights', $aItem);
//			}
		}

		DB::commit(__METHOD__);

		return true;
	}

}
