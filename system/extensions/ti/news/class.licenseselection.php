<?php

class Ext_TI_News_LicenseSelection extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {

		$aReturn = array();

		$sSql = "
			SELECT 
				`id`,
				`description`
			FROM 
				tc_licences 
			WHERE
				`active` = 1 AND
				`release` IN (:release)
			ORDER BY
				`description`
				";
		$aSql = array(
			'release'=>(array)$oWDBasic->release
		);
		$aLicense = DB::getQueryPairs($sSql, $aSql);
		$oDb = DB::getDefaultConnection();

		return $aLicense;

	}
	
}
?>
