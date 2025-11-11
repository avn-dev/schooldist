<?

class Ext_Thebing_Gui2_Format_Matching_FamilyChange extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$sHtml = '';
		if(!empty($mValue)){
			$oImg = new Ext_Gui2_Html_I();
			$oImg->class = 'fa '.Ext_Thebing_Util::getIcon('info');
			$sHtml = $oImg->generateHTML();
		}

		
		return $sHtml;

	}
	
	public function align(&$oColumn = null){
		return 'center';
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {
		
		$sHtml = '';
		$oFormat = new Ext_Thebing_Gui2_Format_Date();

		if(!empty($aResultData['family_change'])){
			
			$oTable = new Ext_Gui2_Html_Table();
			$oTable->class = 'tooltip_table';
			
			$oTr = new Ext_Gui2_Html_Table_tr();
			
			$oTh  = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement(L10N::t('Familie'));
			$oTh->class = 'tooltip_family';
			$oTr->setElement($oTh);
			
			$oTh  = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement(L10N::t('Raum'));
			$oTh->class = 'tooltip_room';
			$oTr->setElement($oTh);
			
			$oTh  = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement(L10N::t('Von'));
			$oTh->style = 'width: 80px;';
			$oTh->class = 'tooltip_date';
			$oTr->setElement($oTh);
			
			$oTh  = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement(L10N::t('Bis'));
			$oTh->style = 'width: 80px;';
			$oTh->class = 'tooltip_date';
			$oTr->setElement($oTh);
			
			$oTh  = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement(L10N::t('Gelöscht'));
			$oTh->style = 'width: 80px;';
			$oTh->class = 'tooltip_date';
			$oTr->setElement($oTh);
			
			
			$oTable->setElement($oTr);
			


			$aAllocationData = explode('{||}', $aResultData['family_change']);

			if(!empty($aAllocationData)){


				foreach((array)$aAllocationData as $sValue){
					$aData = explode('{|}', $sValue);

					if(!empty($aData)){
						
						if(
							empty($aData[1]) ||
							empty($aData[2]) ||
							empty($aData[3]) ||
							empty($aData[4]) ||
							empty($aData[5])
						){ 					
							continue;
						}  
						
						$sFrom = $oFormat->format($aData[1]);
						$sUntil = $oFormat->format($aData[2]);
						$sDeleted = $oFormat->format($aData[5]);
    
						                   
						$oTr = new Ext_Gui2_Html_Table_tr();
						// Familie         
						$oTd  = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement((string)$aData[4]);
						$oTr->setElement($oTd);
						                   
						// Raum            
						$oTd  = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement((string)$aData[3]);
						$oTr->setElement($oTd);
						                   
						// Von             
						$oTd  = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement((string)$sFrom);
						$oTr->setElement($oTd);
						                   
						// Bis             
						$oTd  = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement((string)$sUntil);
						$oTr->setElement($oTd);
						                   
						// Gelöscht        
						$oTd  = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement((string)$sDeleted);
						$oTr->setElement($oTd);
						                   
						$oTable->setElement($oTr);
                                           
					}                      
				}                          
			}		                       
                                           
			$sHtml = $oTable->generateHTML();
		}                                  
		                                   
                                           
		                                   
		                                   
		$aReturn = array();                
		$aReturn['content'] = $sHtml;      
		$aReturn['name'] = '1';            
		$aReturn['tooltip'] = true;        
                                           
		return $aReturn;                   
		                                   
	}                                      
                                           
}