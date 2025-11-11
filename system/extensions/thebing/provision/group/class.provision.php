<?php

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Provision_Group_Provision extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_commission_categories_values_old';

	public static function getProvisionObject($iSchoolId, $iSeasonId, $iCategoryId, $iItemId, $iAdditionalId, $sType, $aTableData){

		$iProvisionId = 0;
		foreach((array)$aTableData as $iKey => $aData){

			if(
				(int)$aData['school_id']		== (int)$iSchoolId &&
				(int)$aData['season_id']		== (int)$iSeasonId &&
				(int)$aData['category_id']		== (int)$iCategoryId &&
				(int)$aData['type_id']			== (int)$iItemId &&
				(int)$aData['additional_id']	== (int)$iAdditionalId &&
				$aData['type']					== $sType
			){
				$iProvisionId = (int)$aData['id'];
				break;
			}
		}


		return self::getInstance($iProvisionId);
	}

}