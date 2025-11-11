<?php

/**
 * @deprecated
 */
abstract class Ext_TC_System_Checks_System_Tabs_AbstractMoved extends GlobalChecks {

	public function getTitle() {
		return 'Update interface tabs';
	}

	public function getDescription() {
		return 'Change link to new page.';
	}

	/**
	 * from => to
	 * @return array
	 */
	abstract protected function getMovedTabs(): array;

	public function executeCheck() {

		\Util::backupTable('system_user');

		$users = \DB::getQueryRows("SELECT * FROM `system_user` WHERE `active` = 1");
		$moved = $this->getMovedTabs();

		foreach($users as $user) {

			$additional = json_decode($user['additional'], true);

			$isChanged = false;
			if(!empty($additional['admin_tabs'])) {
				foreach($additional['admin_tabs'] as &$tab) {
					if (isset($moved[$tab['value']])) {
						$tab['value'] = $moved[$tab['value']];
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
