<?php

namespace TcComplaints\Gui2\Format;

class ValidUntil extends \Ext_Gui2_View_Format_Abstract {

    public function format($mValue, &$oColumn = null, &$aResultData = null) {

        $sFormatedDate = '';

        if($aResultData['valid_until'] !== '0') {

            $oDate = \Factory::getObject('Ext_TC_Gui2_Format_Date');
            $sFormatedDate = $oDate->format($aResultData['valid_until']);

        }

        return $sFormatedDate;

    }

}