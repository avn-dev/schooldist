<?php

class Ext_Thebing_Gui2_Format_Accounting_Overview_Print extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oFormat = new Ext_Thebing_Gui2_Format_Date();
		return $oFormat->format($mValue, $oColumn, $aResultData);

	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {
		
		$sHtml = '';
		
		if($aResultData['version_id'] > 0){
            $oDocument = Ext_Thebing_Inquiry_Document::getInstance($aResultData['id']);
			$aVersions = $oDocument->getAllVersions(false, 'DESC');
			
            $aAllPrints = array();
            
			foreach($aVersions as $oVersion){
                $aPrints = $oVersion->getJoinedObjectChilds('print');
                $aAllPrints = array_merge($aAllPrints, $aPrints);
            }
            
            if(!empty($aAllPrints)){

                $oFormatTime = new Ext_Thebing_Gui2_Format_Timestamp();
                $oFormatUser = new Ext_Gui2_View_Format_UserName(true);


                $oTable = new Ext_Gui2_Html_Table();
                $oTable->class = 'tooltip_table';

                $oTr = new Ext_Gui2_Html_Table_tr();

                $oTh  = new Ext_Gui2_Html_Table_Tr_Th();
                $oTh->setElement(L10N::t('Benutzer'));
                $oTh->style = 'width: 150px';
                $oTr->setElement($oTh);

                $oTh  = new Ext_Gui2_Html_Table_Tr_Th();
                $oTh->setElement(L10N::t('gedruckt'));
                #$oTh->class = 'tooltip_family';
                $oTr->setElement($oTh);

                $oTable->setElement($oTr);

                foreach((array)$aAllPrints as $oPrint){

                    $oTr = new Ext_Gui2_Html_Table_tr();

                    $oTd  = new Ext_Gui2_Html_Table_Tr_Td();
                    $oTd->setElement($oFormatUser->format($oPrint->user_id, $oColumn, $aResultData));
                    $oTr->setElement($oTd);

                    $oTd  = new Ext_Gui2_Html_Table_Tr_Td();
                    $oTd->setElement($oFormatTime->format($oPrint->printed, $oColumn, $aResultData));
                    $oTr->setElement($oTd);

                    $oTable->setElement($oTr);
                }

                $sHtml = $oTable->generateHTML();
            }
        }          
		                                   
		$aReturn = array();                
		$aReturn['content'] = $sHtml;      
		$aReturn['name'] = '1';            
		$aReturn['tooltip'] = true;        
                                           
		return $aReturn;                   
		                                   
	}                  
}