<?php


class Ext_Thebing_System_Checks_Customercleanup extends GlobalChecks { 

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries');

		$sSql = "
			SELECT
				`ki`.`id`,
				`ki`.`referer_select`,
				`ki`.`idAgency`,
				`cdb1`.`ext_44`,
				`cdb1`.`ext_45`
			FROM
				`kolumbus_inquiries` `ki` INNER JOIN
				`customer_db_1` `cdb1` ON
					`cdb1`.`id` = `ki`.`idUser` AND
					`cdb1`.`active` = 1
			WHERE
				`ki`.`active` = 1
		";
		$aInquiries = DB::getQueryRows($sSql);

		foreach((array)$aInquiries as $aData) {
			
			// ------------------------------------------------------
			$aSql = array();
			$aSql['referer']	= $aData['referer'];
			$aSql['agency']		= $aData['idAgency'];
			$aSql['id']			= $aData['id'];

			$sSql = "UPDATE
							`kolumbus_inquiries`
						SET
							`changed` = `changed`,
							`referer_select` = :referer,
							`idAgency` = :agency
						WHERE
							`id` = :id
					";
			
			if(
				$aData['referer'] != $aData['ext_44'] &&
				!empty($aData['ext_44'])
			){
				$aSql['referer'] = $aData['ext_44'];
			}
			
			if(
				$aSql['agency'] != $aData['ext_45'] &&
				!empty($aData['ext_45'])
			){
				$aSql['agency'] = $aData['ext_45'];
			}

			DB::executePreparedQuery($sSql, $aSql);
			
		}

		return true;

	}

	public function getTitle() {
		$sTitle = 'Status Merge';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Merge status of enquiries and bookings.';
		return $sDescription;
	}

}