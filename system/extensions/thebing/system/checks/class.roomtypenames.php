<?
class Ext_Thebing_System_Checks_Roomtypenames extends GlobalChecks {

	public function executeCheck(){

		global $system_data;

		Ext_Thebing_Util::backupTable('customer_db_10');

		foreach((array)$system_data['allowed_languages'] as $sLang => $sName){

			$sSql = " UPDATE
							`customer_db_10`
						SET
							#field = `ext_4`,
							`changed` = `changed`
						WHERE
							#field IS NULL
						";
			$aSql = array('field' => 'name_'.$sLang);
			DB::executePreparedQuery($sSql, $aSql);
			$sSql = " UPDATE
							`customer_db_10`
						SET
							#fieldshort = `ext_1`,
							`changed` = `changed`
						WHERE
							#fieldshort IS NULL
						";
			$aSql = array('fieldshort' => 'short_'.$sLang);
			DB::executePreparedQuery($sSql, $aSql);

		}

		return true;
	}

}