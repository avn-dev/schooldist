<?php
class Ext_TC_Import_Field_Transformer_Gender extends Ext_TC_Import_Field_Transformer{
    
    public function __construct() {
        $this->setTransform('M.', 1);
        $this->setTransform('Mr.', 1);
        $this->setTransform('M', 1);
        $this->setTransform('Mr', 1);
        $this->setTransform('Herr', 1);
        $this->setTransform('Male', 1);
        $this->setTransform('Ms.', 2);
        $this->setTransform('Ms', 2);
        $this->setTransform('Frau', 2);
        $this->setTransform('Female', 2);
        $this->setFallback(0);
    }
    
}