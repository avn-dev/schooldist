<?php 

class Ext_Thebing_System_Checks_SchoolClientId extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		return true;
		
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		// office
		// ext_31

		// office
		// crs_partnerschool

		Ext_Thebing_Util::backupTable('customer_db_1', true);
		Ext_Thebing_Util::backupTable('kolumbus_inquiries', true);

		$sSql = "
				SELECT
					`ki`.`id` `ki_id`,
					`cdb1`.`id` `cdb1_id`,
					`ki`.`office` `ki_office`,
					`cdb1`.`office` `cdb1_office`,
					`ki`.`crs_partnerschool`,
					`cdb1`.`ext_31`,
					`ki_cdb2`.`idClient` `ki_school_client_id`,
					`cdb1_cdb2`.`idClient` `cdb1_school_client_id`
				FROM
					`customer_db_1` `cdb1` LEFT JOIN
					`kolumbus_inquiries` `ki` ON
						`ki`.`idUser` = `cdb1`.`id` LEFT JOIN
					`customer_db_2` `cdb1_cdb2` ON
						`cdb1`.`ext_31` = `cdb1_cdb2`.`id` LEFT JOIN
					`customer_db_2` `ki_cdb2` ON
						`ki`.`crs_partnerschool` = `ki_cdb2`.`id`
				WHERE
					`cdb1`.`active` = 1 AND
					(
						`cdb1`.`office` = 0 OR
						`cdb1`.`ext_31` = 0 OR
						(
							`ki`.`id` != NULL AND
							(
								`ki`.`office` = 0 OR
								`ki`.`crs_partnerschool` = 0
							)
						) OR
						`ki`.`office` != `cdb1`.`office` OR
						`ki`.`crs_partnerschool` != `cdb1`.`ext_31`
					)
				ORDER BY
					`cdb1`.`id`
			";
		$aItems = DB::getQueryRows($sSql);

		__pout(count($aItems));

		foreach((array)$aItems as $aItem) {

			if($aItem['ki_id'] > 0) {

				if($aItem['ki_office'] != $aItem['cdb1_office']) {
					if(
						empty($aItem['ki_office']) &&
						!empty($aItem['cdb1_office'])
					) {
						$sSql = "UPDATE `kolumbus_inquiries` SET `changed` = `changed`, `office` = ".(int)$aItem['cdb1_office']." WHERE `id` = ".(int)$aItem['ki_id'];
						DB::executeQuery($sSql);
					} elseif(
						empty($aItem['cdb1_office']) &&
						!empty($aItem['ki_office'])
					) {
						$sSql = "UPDATE `customer_db_1` SET `last_changed` = `last_changed`, `office` = ".(int)$aItem['ki_office']." WHERE `id` = ".(int)$aItem['cdb1_id'];
						DB::executeQuery($sSql);
					} else {
						//__pout($aItem);
					}
				}

				if($aItem['crs_partnerschool'] != $aItem['ext_31']) {
					if(
						empty($aItem['crs_partnerschool']) &&
						!empty($aItem['ext_31'])
					) {
						$sSql = "UPDATE `kolumbus_inquiries` SET `changed` = `changed`, `crs_partnerschool` = ".(int)$aItem['ext_31']." WHERE `id` = ".(int)$aItem['ki_id'];
						DB::executeQuery($sSql);
					} elseif(
						empty($aItem['ext_31']) &&
						!empty($aItem['crs_partnerschool'])
					) {
						$sSql = "UPDATE `customer_db_1` SET `last_changed` = `last_changed`, `ext_31` = ".(int)$aItem['crs_partnerschool']." WHERE `id` = ".(int)$aItem['cdb1_id'];
						DB::executeQuery($sSql);
					} else {
						//__pout($aItem);
					}
				}

			} else {

				if(
					$aItem['cdb1_office'] == 0 &&
					$aItem['ext_31'] > 0
				) {
					$aSchool = $this->_getClientFromSchool($aItem['ext_31']);
					if($aSchool['idClient'] > 0) {
						$sSql = "UPDATE `customer_db_1` SET `last_changed` = `last_changed`, `office` = ".(int)$aSchool['idClient']." WHERE `id` = ".(int)$aItem['cdb1_id'];
						DB::executeQuery($sSql);
					} else {
						//__pout($aItem);
					}
				} else {
					//__pout($aItem);
				}

			}

		}

		return true;
		
	}

	protected function _getClientFromSchool($iSchoolId) {

		$sSql = "SELECT * FROM customer_db_2 WHERE `id` = ".(int)$iSchoolId;
		$aSchool = DB::getQueryRow($sSql);

		return $aSchool;

	}
	
}
