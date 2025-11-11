<?php

namespace TsAccommodation\Gui2\Style\Cleaning;

class Arrival extends \Ext_Gui2_View_Style_Abstract {

    private $forPdf;

    public function __construct(bool $forPdf = false) {
        $this->forPdf = $forPdf;
    }

    /**
     * @param mixed $mValue
     * @param $oColumn
     * @param array $aRowData
     * @return string $sStyle
     */
    public function getStyle($mValue, &$oColumn, &$aRowData) {

        $sStyle = '';

        if(
            !empty($mValue) &&
            $mValue === $aRowData['date']
        ) {
            if($this->forPdf) {
                $sStyle .= 'bgcolor="'.\Ext_Thebing_Util::getColor('green').'"';
            } else {
                $sStyle .= 'background-color: '.\Ext_Thebing_Util::getColor('green').';';
            }

        }

        return $sStyle;

    }

}
