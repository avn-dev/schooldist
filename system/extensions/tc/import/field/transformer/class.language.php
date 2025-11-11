<?php
class Ext_TC_Import_Field_Transformer_Language extends Ext_TC_Import_Field_Transformer{
    
    public function __construct($sLang = 'en') {
        global $aLangs;
        $aLangs = Ext_TC_Language::getSelectOptions($sLang);
        $this->setManipulator(
        function($mValue){
            global $aLangs;
            foreach($aLangs as $sIso => $sLang){
                if(strtolower($sLang) == strtolower($mValue)){
                    return $sIso;
                }
            }
            return '';
        });
    }
    
}