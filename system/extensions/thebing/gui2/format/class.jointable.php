<?php

/**
 * NICHT VERWENDEN!!!
 */
class Ext_Thebing_Gui2_Format_JoinTable extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// TODO: Bitte bei MK melden!
		$oWDBasic = call_user_func(array($this->oGui->class_wdbasic, 'getInstance'), (int)$aResultData['id']);

		$sColumn = $oColumn->db_column;
		$mValue = $oWDBasic->$sColumn;

		$aJoinTables = $oWDBasic->getJoinTables();
		$sJoinFormat = '';

		foreach((array)$aJoinTables as $sKey => $aJoinData){
			if($sKey == $sColumn){
				$sJoinFormat = $aJoinData['format'];
				break;
			}
		}

		$aBack = array();

		foreach((array)$mValue as $iJoinId){

			if(!empty($sJoinFormat)){
				$oFormat = new $sJoinFormat();
				$mFormat = $oFormat->format($iJoinId, $oColumn, $aResultData);
		
				$aBack[] = $mFormat;
			} else {
				$aBack[] = $iJoinId;
			}


		}

		$sBack = implode('<br/>', $aBack);
	
		return $sBack;

	}

}
