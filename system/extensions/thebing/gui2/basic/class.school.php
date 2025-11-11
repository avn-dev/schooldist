<?php

/*
 * Brauchen wir diese Klasse wirklich noch ??? (setTableData)
 * Die klasse leitet die Datenklasse der GUI2 ab für das Thebing Projekt
 * Auserdem basiert diese darauf das es in school_id und ein client_id feld gibt!
 * Sorgt dafür das man dies nicht extra jedesmal ableiten muss!
 */

class Ext_Thebing_Gui2_Basic_School extends Ext_Thebing_Gui2_Data {

	protected $sSchoolField = 'school_id';
	protected $sClientField = 'client_id';

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		
		global $user_data;

		$sSchoolField = $this->sSchoolField;
		$sClientField = $this->sClientField;

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId	= (int)$oSchool->id;
		$iClientId	= (int)$user_data['client'];

		$this->_getWDBasicObject($aSelectedIds);

		if(
			is_object($this->oWDBasic) &&
			$this->oWDBasic instanceof WDBasic
		)
		{
			if(!empty($sSchoolField)){
				$this->oWDBasic->$sSchoolField	= $iSchoolId;
			}
			if(!empty($sClientField)){
				$this->oWDBasic->$sClientField	= $iClientId;
			}
		}

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction);
		
		return $aTransfer;
	}

	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {
		global $user_data;

		parent::_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		$sAlias = '';

		if($this->_oGui->query_id_alias != ""){
			$sAlias = '`'.$this->_oGui->query_id_alias.'`.';
		}

		$sWhereStart = ' WHERE ';

		if(!empty($aSqlParts['where'])){
			$sWhereStart = ' AND ';
		}

		if($this->sSchoolField != ""){
			$aSqlParts['where'] .= $sWhereStart.' '.$sAlias.'#school_field = :school_id';
			$sWhereStart = ' AND ';
		} 
		
		if($this->sClientField != ""){
			$aSqlParts['where'] .= $sWhereStart.' '.$sAlias.'#client_field = :client_id ';
			$sWhereStart = ' AND ';
		}


		$aSql['school_id'] = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		$aSql['client_id'] = (int)$user_data['client'];
		$aSql['school_field'] = $this->sSchoolField;
		$aSql['client_field'] = $this->sClientField;
	}
	
}