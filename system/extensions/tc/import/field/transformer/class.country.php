<?php
class Ext_TC_Import_Field_Transformer_Country extends Ext_TC_Import_Field_Transformer{
    
    public function __construct($sLang = 'en') {
        $this->setManipulator(
                function($mValue){
                    $aCountries = Ext_TC_Country::getSelectOptions($sLang);
                    foreach($aCountries as $sCountryIso => $sCountry){
                        if(strtolower($sCountry) == strtolower($mValue)){
                            return $sCountryIso;
                        }
                    }
                    return '';
                });
        $this->setValidator(
                function($mValue){
                    if(strlen($mValue) != 2){ 
                        return false; 
                    } else {
                        return true;
                    }
                });
    }
    
}