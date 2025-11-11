<?php
class Ext_TC_Import_Field_Transformer_YesNo extends Ext_TC_Import_Field_Transformer{
    
    public function __construct() {
        $this->setManipulator(function($mValue){
            $mValue = strtolower($mValue);
            return $mValue;
        });
        $this->setTransform('yes', 1);
        $this->setTransform('ja', 1);
        $this->setTransform('wahr', 1);
        $this->setFallback(0);
    }
    
}