<?php

use \Carbon\Carbon;

class Ext_TC_Gui2_Format_Age extends Ext_Gui2_View_Format_Age
{
    public function __construct(
        protected ?string $dateField = null,
        protected ?string $separator = ', '
    ) {}

    public function format($mValue, &$oColumn = null, &$aResultData = null)
    {
        if (is_array($mValue)) {
            $mValue = array_map(fn ($birthday) => $this->getAge($birthday, $aResultData), $mValue);
            return implode($this->separator, $mValue);
        }

        return $this->getAge($mValue, $aResultData);
	}

    private function getAge($birthday, $aResultData)
    {
        if(empty($birthday)) {
            return '';
        }

        $date = new Carbon;
        if(!empty($this->dateField)) {

            if(empty($aResultData[$this->dateField])) {
                return '';
            }

            $date = new Carbon($aResultData[$this->dateField]);

        }

        $birthday = new Carbon($birthday);

        $age = floor($birthday->floatDiffInYears($date));

        return $age;
    }

    public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData = null)
    {
        if (is_array($mValue)) {
            $mValue = implode(', ', $mValue);
        }

        parent::setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
    }

}