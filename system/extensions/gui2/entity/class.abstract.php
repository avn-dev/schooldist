<?php
abstract class Ext_Gui2_Entity_Abstract {
    
	static public function getInstance($sId){
        return new static($sId);
    }
    
	public function getClassName(){
        return get_class($this);
    }
    
    public function getTableName(){
        return '';
    }
    
    public function getDbConnection(){
        return DB::getDefaultConnection();
    }

    public function getJoinedObject(){
        return $this;
    }
    
    public function manipulateSqlParts(&$aSqlParts, $sView=null) {
        return $aSqlParts;
    }

    abstract public function validate();
    
    abstract public function save();
	
    abstract public function exist();

    abstract public function getListQueryData($oGui = null);
	
	abstract public function disableUpdateOfCurrentTimestamp();
	
}