<?php

namespace TsAccounting\Gui2\Format\Release;

class DateUser extends \Ext_Thebing_Gui2_Format_Contract_DateUser {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oEntity = $this->getEntityObject((int)$aResultData['id']);

		$mReturn = null;
		if($oEntity->isReleased()) {
			$sReleaseDate = $oEntity->getReleaseTime(\WDDate::DB_DATE);
			$sReleaseUser = $oEntity->getReleaseUser();

			$aResultData[$oColumn->db_column] = $sReleaseDate;
			$aResultData[$oColumn->db_column.'_by'] = $sReleaseUser;

			$mReturn = parent::format($mValue, $oColumn, $aResultData);
		}

		return $mReturn;
	}

	protected function getEntityObject(int $iId) {
		$oEntity = call_user_func_array([$this->oGui->class_wdbasic, 'getInstance'], [$iId]);
		return $oEntity;
	}
}
