<?php
class Ext_TC_Import_NonBasic extends Ext_TC_Import_Abstract {

    protected $_sEntityPrimary = 'parent_id';


    public function __construct($sTable, $sPrimary) {
        $this->_sEntityTable = $sTable;
        $this->_sEntityPrimary = $sPrimary;
        parent::__construct($sTable);
    }
    
    public function createEntity($iEntity = 0){
        $oEntity = new Ext_TC_Import_NonBasic_Entity();
        $oEntity->setTable($this->_sEntityTable, $this->_sEntityPrimary);
        $oEntity->setId($iEntity);
        return $oEntity;
    }
    
}