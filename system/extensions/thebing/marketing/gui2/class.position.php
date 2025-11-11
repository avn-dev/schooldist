<?php


class Ext_Thebing_Marketing_Gui2_Position extends Ext_Thebing_Gui2_Data
{

	public function switchAjaxRequest($_VARS)
    {

		$sTask = $_VARS['task'];

		if($sTask == 'updatePlaceholder'){

			$sSql = "SELECT
							*
						FROM
							`kolumbus_positions_order`
						WHERE
							`id` = :id
			";

			$aSql = array();
			$aSql['id'] = (int)$_VARS['data_id'];

			$aResult = DB::getPreparedQueryData($sSql, $aSql);


			$sTitle = $aResult[0]['title'];


			$sSql = "UPDATE
							`kolumbus_positions_order`
						SET
							`title` = :title
						WHERE
							`id` = :id
					";

			$aSql['title'] = $sTitle . ' {$' . $_VARS['placeholder'] . '}';

			DB::executePreparedQuery($sSql, $aSql);

			$aTransfer = array();
			$aTransfer['action'] = 'loadTable';
			echo json_encode($aTransfer);
			$this->_oGui->save();
			die();
				
		}

		parent::switchAjaxRequest($_VARS);

	}

	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit = false)
    {

		$aResult = parent::getTableQueryData($aFilter, $aOrderBy, $aSelectedIds, $bSkipLimit);

		if(
			empty($aSelectedIds)
		){
			$aTableData = (array)$this->_oGui->_aTableData;

			if(
				isset($aTableData['where']) &&
				isset($aTableData['where']['school_id']) &&
				(int)$aTableData['where']['school_id'] > 0
			){
				$iSchoolId = (int)$aTableData['where']['school_id'];

				$sWDBasic = $this->_oGui->class_wdbasic;

				$aPositions = [];
				$aRows = (array)$aResult['data'];

				foreach($aRows as $aRowData){
					$aPositions[$aRowData['position_key']] = $aRowData['title'];
				}

				$aAllPositions = call_user_func(array($sWDBasic, 'getAllPositions'));

				$aDiff = array_diff_key($aAllPositions, $aPositions);

				$iCounter = 1;

				$oDate = new WDDate();

				foreach($aDiff as $sPositionKey => $sPositionName){

					$oPosition = new Ext_Thebing_School_Positions();
					$oPosition->position_key = $sPositionKey;
					$oPosition->title = $sPositionName;
					$oPosition->position = $iCounter;
					$oPosition->school_id = $iSchoolId;
					$oPosition->save();

					$aRow = $oPosition->getArray();
					$oDate->set($aRow['created'], WDDate::DB_TIMESTAMP);
					$aRow['created'] = $oDate->get(WDDate::TIMESTAMP);
					$oDate->set($aRow['changed'], WDDate::DB_TIMESTAMP);
					$aRow['changed'] = $oDate->get(WDDate::TIMESTAMP);

					$aRows[] = $aRow;

					$iCounter++;
				}

				$aResult['data']	= $aRows;
				$aResult['count']	= count($aRows);
				$aResult['end']		= count($aRows);
			}
		}

		return $aResult;
	
	}

	public static function getWhere()
	{
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->id;

		return ['school_id' => $iSchoolId];
	}
	
}
