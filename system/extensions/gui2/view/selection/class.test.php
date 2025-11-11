<?php

class Ext_Gui2_View_Selection_Test extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$sSql = "
			SELECT
				`id`,
				`name`
			FROM
				`test_mark_selection`
			WHERE
				1
			ORDER BY
				`name`
		";
		$aSql = array();
		$aOptions = DB::getQueryPairs($sSql, $aSql);

		return $aOptions;

	}

}