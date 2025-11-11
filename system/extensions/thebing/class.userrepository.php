<?php

class Ext_Thebing_UserRepository extends WDBasic_Repository {

	/**
	 * Gibt alle MasterUsers des Systems zurück
	 *
	 * @see Ext_Thebing_Access::getMasterUserIds()
	 * @return Ext_Thebing_User[]
	 */
	public function getMasterUsers() {

		$sSql = "
			SELECT
				 su.*
			FROM
				`system_user` `su` INNER JOIN
				`kolumbus_clients_users` `kcu` ON
					`kcu`.`user_id` = `su`.`id` AND
					`kcu`.`master` = 1
			WHERE
				`su`.`active` = 1				
		";

		$aRows = DB::getQueryRows($sSql);

		$aEntities = [];
		foreach($aRows as $aRow) {
			$aEntities[] = $this->_getEntity($aRow);
		}
		
		return $aEntities;
		
	}

	/**
	 * Lädt alle Benutzer die die Sales Person Checkbox aktiviert haben
	 *
	 * @return Ext_Thebing_User[]
	 */
	public function getSalesPersons() {
		$sSql = "
			SELECT
				su.*
			FROM
				`system_user` `su` INNER JOIN
				`kolumbus_clients_users` `kcu` ON
				`kcu`.`user_id` = `su`.`id`
			WHERE
				`su`.`active` = 1 AND
				`su`.`ts_is_sales_person` = 1
		";

		$aRows = DB::getQueryRows($sSql);

		$aEntities = [];
		if(!empty($aRows)) {
			$aEntities[] = $this->_getEntities($aRows);
			$aEntities = reset($aEntities);
		}

		return $aEntities;
	}

	/**
	 * Gibt alle Namen der Rechtegruppe wieder, die der Benutzer hat
	 *
	 * @param Ext_Thebing_User $oUser
	 *
	 * @return array
	 */
	public function getUserRights(Ext_Thebing_User $oUser) {

		$sSql = "
			
			SELECT
				GROUP_CONCAT(`kag`.`name`) `rights`
			FROM
				`kolumbus_access_user_group` `kaug` INNER JOIN
				`kolumbus_access_group` `kag` ON
					`kag`.`id` = `kaug`.`group_id` AND 
					`kag`.`active` = 1
			WHERE
				`kaug`.user_id = :iUserId
		";

		$aRows = DB::getQueryRows($sSql, ['iUserId' => $oUser->getId()]);
		return $aRows;
	}

}