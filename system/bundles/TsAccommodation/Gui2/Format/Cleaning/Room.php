<?php

namespace TsAccommodation\Gui2\Format\Cleaning;

class Room extends \Ext_Gui2_View_Format_Abstract {

    public function format($value, &$column = null, &$resultData = null){

        if($resultData['date'] === $resultData['departure']) {
            return sprintf('<strong>%s</strong>', $value);
        }

        return $value;
    }

}
