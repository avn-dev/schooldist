<?php

/* 
 *
 * Anlegen von Modulen
 *
 * @author Christian Felix
 * @created 23.01.11
 * 
 */

class Ext_Thebing_Tuition_Module extends Ext_Thebing_Basic {
    
	protected $_sTable				= 'kolumbus_tuition_modules';
	protected $_sTableAlias			= 'ktm';
	
	protected $languages_container	= array();
	
	protected $_aLanguages			= array();

	public function getList($bPrepareForSelect=false,$iSchoolId=false){

		if(!$iSchoolId){
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}else{
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		}

		$iSchoolId		= $oSchool->id;
		$sNameField		= 'name_'.$oSchool->getInterfaceLanguage();

		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`active` = 1 AND
				`school_id`	= :school_id
		";

		$aSql = array(
			'table'		=> $this->_sTable,
			'school_id' => $iSchoolId,
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		if($bPrepareForSelect){
			$aData = array();
			foreach($aResult as $aRowData){
				$aData[$aRowData['id']] = $aRowData[$sNameField];
			}
		}else{
			$aData = $aResult;
		}

		return $aData;
	}
	
}
