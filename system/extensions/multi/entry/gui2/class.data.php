<?php

class Ext_Multi_Entry_Gui2_Data extends Ext_Gui2_Data {

	public function requestPublish() {
		
		Ext_Multi::writeMultiDataTable($this->_oGui->sView);
		
		$aTransfer = array(
			'action' => 'showSuccess',
			'message' => 'Die Einträge wurden erfolgreich veröffentlicht.',
			'success_title' => 'Veröffentlichung'
		);
		
		return $aTransfer;
	}
	
	protected function deleteRow($iRowId) {

		$oEntry = Ext_Multi_Entry::getInstance($iRowId);

		$oEntry->multi_id = 0;

		$oEntry->save();

		return true;
	}

	protected function _saveNewSort($aIds){

		$iPos = 1;

		foreach((array)$aIds as $iId) {

			$aUpdate = array(
				'position' => $iPos
			);
			DB::updateData('multi_entry', $aUpdate, '`id` = '.(int)$iId);

			$iPos++;
		}

//		if(!empty($iId)) {
//			$oEntry = Ext_Multi_Entry::getInstance($iId);
//
//			Ext_Multi::writeMultiDataTable($oEntry->multi_id);
//		}

	}

}