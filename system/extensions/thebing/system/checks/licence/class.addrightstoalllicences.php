<?php

/**
 * Check, der angegebene Rechte in allen Lizenzen setzt
 *
 * DIESER CHECK DARF NUR AUF LIVE0 AUSGEFÜHRT WERDEN!
 * Entsprechende abgeleitete Checks sollten nicht im Repository landen…
 */
abstract class Ext_Thebing_System_Checks_Licence_AddRightsToAllLicences extends GlobalChecks {

	protected $_aRights = array();

	public $bThebingPlus = true;

	public function executeCheck() {

		// Nur auf live0 ausführen
		if(!Ext_Thebing_Util::isLive2System()) {
			throw new RuntimeException('Check lässt sich nur auf Live 0 ausführen und gehört nur dorthin!');
		}

		// IDs zu den Rechten holen
		$aRightsIds = array();
		foreach($this->_aRights as $sRight) {

			$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_access`
				WHERE
					`access` = :right
			";

			$iId = DB::getQueryOne($sSql, array('right' => $sRight));

			if(empty($iId)) {
				throw new RuntimeException('Right "'.$sRight.'" not found!');
			}

			$aRightsIds[$sRight] = $iId;
		}

		// Alle Lizenzen holen
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_access_licence`
			WHERE
				`active` = 1
		";

		if($this->bThebingPlus) {
			$sSql .= "
				AND `thebing_plus` = 1
			";
		}

		$aLicences = DB::getQueryCol($sSql);

		Util::backupTable('kolumbus_access_licence_access');

		DB::begin('Ext_Thebing_System_Checks_Licence_AddRightsToAllLicences');

		// Alle Lizenzen durchgehen und einfach per REPLACE INTO einfügen
		foreach($aLicences as $iLicenceId) {
			foreach($aRightsIds as $sRight => $iRightId) {

				$sSql = "
					REPLACE INTO
						`kolumbus_access_licence_access`
					SET
						`licence_id` = :licence_id,
						`access_id` = :access_id
				";

				DB::executePreparedQuery($sSql, array(
					'licence_id' => $iLicenceId,
					'access_id' => $iRightId
				));

				$this->logInfo('Added right "'.$sRight.'" to licence '.$iLicenceId);
			}
		}

		DB::commit('Ext_Thebing_System_Checks_Licence_AddRightsToAllLicences');

		return true;
	}

	public function getTitle() {
		$sTitle = 'Import new rights';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

}