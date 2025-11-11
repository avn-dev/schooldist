<?php

class Ext_TC_Frontend_Combination_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

    /**
     * @param array $aSelectedIds
     * @param array $aRowData
     * @param $oElement
     * @return bool|int
     */
    public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

        if(
        	count($aRowData) > 1 && (
				$oElement->action === 'edit' ||
				$oElement->action === 'combination_usages'
			)
		) {
			return false;
		}

        if(
            $oElement->action === 'edit' ||
            $oElement->action === 'refreshCombinationData'
        ) {

            if($aRowData[0]['status'] === Ext_TC_Frontend_Combination::STATUS_PENDING) {
				return false;
            }

        }

        return true;

    }

}