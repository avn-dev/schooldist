<?php

namespace TsAccommodation\Gui2\Icon\Cleaning;

use TsAccommodation\Entity\Cleaning\Status;

class Visible extends \Ext_Gui2_View_Icon_Abstract {

    /**
     * @param array $aSelectedIds
     * @param array $aRowData
     * @param $oElement
     * @return bool
     */
    public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

        if(
            $oElement->action === 'markAsDirty'
        ) {

            $dirtySelect = collect($aSelectedIds)
                ->map(function($id) {
                    $decoded = $this->_oGui->decodeId($id);
                    return $decoded['status'];
                })
                ->toArray();

            return in_array(Status::STATUS_CLEAN, $dirtySelect);

        }

        return true;
    }
}
