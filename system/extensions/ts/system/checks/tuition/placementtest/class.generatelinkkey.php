<?php

/**
 * https://redmine.thebing.com/redmine/issues/8580
 */
class Ext_TS_System_Checks_Tuition_Placementtest_GenerateLinkKey extends GlobalChecks {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Update Placementtest database';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return self::getTitle();
    }

    /**
     * Alle Datensätze die keinen Key haben bekommen einen
     *
     * @return boolean
     */
    public function executeCheck() {

        if(!Util::backupTable('ts_placementtests_results')) {
            return false;
        }

        $sSql = "
            SELECT
                `id`
            FROM
                `ts_placementtests_results`
            WHERE
                `key` = '' OR
                `key` IS NULL
        ";

	    $aResults = DB::getQueryRows($sSql);

	    if(!empty($aResults)) {

		    $oUpdateStatement = DB::getPreparedStatement("
		        UPDATE
		        	`ts_placementtests_results`
		        SET
		            `key` = ?
		        WHERE
		        	`id` = ?
		    ");

		    foreach($aResults as $aResult) {

			    $oPlacement = new Ext_Thebing_Placementtests_Results();

				$aSql = [
					$oPlacement->getUniqueKey(),
					$aResult['id']
				];

			    DB::executePreparedStatement($oUpdateStatement, $aSql);

		    }

	    }

	    return true;

    }

}