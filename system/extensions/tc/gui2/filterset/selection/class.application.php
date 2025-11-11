<?php

class Ext_TC_Gui2_Filterset_Selection_Application extends Ext_Gui2_View_Selection_Abstract
{

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
                
		#$aApplication = Ext_TC_Gui2_Filterset::getApplications();
        
		$aApplication = Ext_TC_Factory::executeStatic('Ext_TC_Gui2_Filterset', 'getApplications');
		
        $oTemp = new Ext_TC_Gui2_Filterset();
        $aObjects = $oTemp->getObjectList();
        
        foreach($aObjects as $oObject){
            if(
                $oObject->isActive() && 
                !in_array($oObject->id, $aSelectedIds)
            ){
                $sApplication = $oObject->application;
                unset($aApplication[$sApplication]);
            }
        }

        return $aApplication;
	}
}