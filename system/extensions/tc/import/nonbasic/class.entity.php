<?php
class Ext_TC_Import_NonBasic_Entity {
    
    protected $_iId = 0;
    
    protected $_sTable = '';


    public function setId($iId) {
        $this->_iId = $iId;
    }
    
    public function setTable($sTable, $sPrimary){
        $this->_sTable          = $sTable;
        $this->_sPrimaryField   = $sPrimary;
    }
    
    public function getChildData(){
        return array(
            'type' => 'joinedobject_child',
            'data' => array(
                'key' => $this->_sPrimaryField,
                'table' => $this->_sTable
             )
        );
    }
    
    public function getTableName(){
        return $this->_sTable;
    }
}