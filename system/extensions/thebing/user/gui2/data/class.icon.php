<?php

class Ext_Thebing_User_Gui2_Data_Icon extends Ext_TC_User_Gui2_Icon {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->action == 'access') {

			if(count($aSelectedIds) === 1) {

				$iEmployeeId = reset($aSelectedIds);

				$oEmployee = Ext_Thebing_User::getInstance($iEmployeeId);

				if($oEmployee->hasSystemType('user')) {
					return true;
				}

			} else {
				return false;
			}

		} elseif($oElement->action == 'salesperson') {

			if(
				count($aSelectedIds) === 1 &&
				(int)$aRowData[0]['ts_is_sales_person'] === 1
			) {

				return true;
			}

		} else {
			return parent::getStatus($aSelectedIds, $aRowData, $oElement);
		}

	}

}