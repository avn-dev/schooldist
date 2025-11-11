<?php
/**
 * Löscht User (system_user)
 * 	• die "gelöscht" sind (active=0)
 *  • die keinen aktiven Clienten mehr haben
 *	• die keinerlei Zuweisung zu irgendeinem Clienten haben
 * Löscht außerdem die Zuweisungen vom User zum Clienten (kolumbus_clients_users).
 *
 * Redmine Ticket #2051
 *
 * @since 14.08.2012
 * @author DG <dg@plan-i.de>
 */
class Ext_Thebing_System_Checks_DeleteOldUsers extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Delete old user data';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Delete old, not longer needed user data.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {

		Ext_TC_Util::backupTable('system_users');
		Ext_TC_Util::backupTable('kolumbus_clients_users');

		$sSql = "
			SELECT
				`su`.`id` `su_id`,
				`kcu`.`id` `kcu_id`,
				`su`.`username` as `su_username`
			FROM
				`system_user` `su` LEFT JOIN
				`kolumbus_clients_users` `kcu` ON
				`kcu`.`user_id` = `su`.`id` LEFT JOIN
				`kolumbus_clients` `kc` ON
				`kc`.`id` = `kcu`.`idClient`
			WHERE
				`kc`.`active` = 0 OR						-- Wenn Client deaktiviert
				`su`.`active` = 0 OR (						-- Wenn User gelöscht
					`su`.`active` = 1 AND					-- Wenn User nicht gelöscht, aber ohne Zuweisung
					`kcu`.`id` IS NULL
				)
			GROUP BY
				`su`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$aUserIds = array();
		$aClientUsersIds = array();
		foreach($aResult as $aData) {
			if(is_numeric($aData['su_id'])) {
				$aUserIds[] = (int)$aData['su_id'];
			}

			if(is_numeric($aData['kcu_id'])) {
				$aClientUsersIds[] = (int)$aData['kcu_id'];
			}
		}

		$this->_deleteRows('system_user', $aUserIds);
		$this->_deleteRows('kolumbus_clients_users', $aClientUsersIds);

		return true;

	}

	/**
	 * Löscht Spalten in einer Tabelle
	 *
	 * @param $sTable
	 * @param $aArray
	 */
	protected function _deleteRows($sTable, $aArray)
	{
		$aSql = array(
			'table' => $sTable,
			'array' => $aArray
		);

		$sSql = "
			DELETE FROM
				#table
			WHERE
				`id` IN (:array)
		";

		DB::executePreparedQuery($sSql, $aSql);
	}

}