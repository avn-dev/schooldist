<?php

/**
 * @property int $id
 * @property string $changed
 * @property string $created
 * @property int $creator_id
 * @property int $active
 * @property string $name
 * @property int $idSchool
 * @property int $idClient
 */

class Ext_Thebing_Agency_Group extends Ext_Thebing_Basic {
	
	protected $_sTable = 'kolumbus_agency_groups';
	
	public function getAgencys(){

		$sSql = "SELECT
						*
					FROM
						`ts_companies`
					WHERE
					 	`type` & ".\TsCompany\Entity\AbstractCompany::TYPE_AGENCY." AND
						`active` = 1
				";
		$aSql = array();
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		foreach((array)$aResult as $iKey => $aData){
			$aGroups = explode(',', $aData['ext_30']);
			if(!in_array($this->id, $aGroups)){
				unset($aResult[$iKey]);
			}
		}

		return $aResult;
	}


}
