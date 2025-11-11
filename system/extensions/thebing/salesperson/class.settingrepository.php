<?php

/**
 * Class Ext_Thebing_Salesperson_SettingRepository
 */
class Ext_Thebing_Salesperson_SettingRepository extends WDBasic_Repository {

	/**
	 * Findet alle Einstellungen bis auf die, die angegeben wurde.
	 *
	 * @param Ext_Thebing_Salesperson_Setting $oSetting
	 * @param array $aSchoolIds
	 * @return Ext_Thebing_Salesperson_Setting[]
	 */
	public function getAllSettingsExceptTheGiven(Ext_Thebing_Salesperson_Setting $oSetting, array $aSchoolIds) {

		$sSql = "
			SELECT
				ts_susps.*
				
			FROM
				`ts_system_user_sales_persons_schools` `ts_suspss` INNER JOIN
				`ts_system_user_sales_persons_settings` `ts_susps` ON
					`ts_susps`.`id` = `ts_suspss`.`setting_id` AND
					`ts_susps`.`id` != :iSettingId
			WHERE
				`ts_suspss`.`school_id` IN (:schoolIds)
		";

		$aRows = DB::getQueryRows($sSql, [
			'iSettingId' => $oSetting->getId(),
			'schoolIds' => $aSchoolIds,
		]);

		$aEntities = [];
		if(!empty($aRows)) {
			$aEntities[] = $this->_getEntities($aRows);
			$aEntities = reset($aEntities);
		}

		return $aEntities;

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Agency $oAgency
	 * @return Ext_Thebing_Salesperson_Setting
	 */
	public function getSettingBySchoolAndAgency(Ext_Thebing_School $oSchool, Ext_Thebing_Agency $oAgency) {
		
		$sSql = "
			SELECT 
				*
			FROM
				`ts_system_user_sales_persons_schools` `ts_susps` JOIN
				`ts_system_user_sales_persons_agencies` `ts_suspa` ON
					`ts_susps`.`setting_id` = `ts_suspa`.`setting_id`
			WHERE
				`ts_susps`.`school_id` = :school_id AND
				`ts_suspa`.`agency_id` = :agency_id
			LIMIT 1
				";
		
		$aSql = [
			'school_id' => (int)$oSchool->id,
			'agency_id' => (int)$oAgency->id
		];
		
		$iSettingId = DB::getQueryOne($sSql, $aSql);
		
		if(!empty($iSettingId)) {

			$oSetting = Ext_Thebing_Salesperson_Setting::getInstance($iSettingId);

			return $oSetting;
		}
		
	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Agency $oAgency
	 * @return Ext_Thebing_Salesperson_Setting
	 */
	public function getSettingBySchoolAndNationality(Ext_Thebing_School $oSchool, $sNationality) {
		
		$sSql = "
			SELECT 
				*
			FROM
				`ts_system_user_sales_persons_schools` `ts_susps` JOIN
				`ts_system_user_sales_persons_nationalities` `ts_suspn` ON
					`ts_susps`.`setting_id` = `ts_suspn`.`setting_id`
			WHERE
				`ts_susps`.`school_id` = :school_id AND
				`ts_suspn`.`country_iso` = :country_iso
			LIMIT 1
				";
		
		$aSql = [
			'school_id' => (int)$oSchool->id,
			'country_iso' => $sNationality
		];
		
		$iSettingId = DB::getQueryOne($sSql, $aSql);
		
		if(!empty($iSettingId)) {

			$oSetting = Ext_Thebing_Salesperson_Setting::getInstance($iSettingId);

			return $oSetting;
		}
		
	}
	
}