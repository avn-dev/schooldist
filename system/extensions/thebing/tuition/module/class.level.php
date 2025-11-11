<?php

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Tuition_Module_Level extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_tuition_modules_levels';
	protected $_sTableAlias = 'ktml';

	public static function getLevelsByModule($iModuleId, $bPrepareForSelect=true, $sInterfaceLanguage=false){

		$sSelect = '*';
		if($bPrepareForSelect){
			if(!$sInterfaceLanguage){
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
				$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
			}

			$sNameField = 'name_'.$sInterfaceLanguage;
			$sSelect	= '`id`,`'.$sNameField.'`';
		}

		$sSql = "
			SELECT
				".$sSelect."
			FROM
				`kolumbus_tuition_modules_levels`
			WHERE
				`active` = 1 AND
				`module_id` = :module_id
		";

		$aSql = array(
			'module_id' => (int)$iModuleId
		);

		if($bPrepareForSelect){
			#$aResult = (array)DB::getQueryPairs($sSql, $aSql);
			$aResult = array();
			$aData = DB::getPreparedQueryData($sSql, $aSql);
			foreach($aData as $aRowData){
				$aResult[$aRowData['id']] = $aRowData[$sNameField];
			}
		}else{
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
		}

		return $aResult;
	}

}