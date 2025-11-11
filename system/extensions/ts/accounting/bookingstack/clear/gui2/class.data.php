<?php


class Ext_TS_Accounting_BookingStack_Clear_Gui2_Data extends Ext_Thebing_Gui2_Data
{

    public function addWDSearchIDFilter(\ElasticaAdapter\Facade\Elastica $oSearch, array $aSelectedIds, $sIdField) {
        $aGroupDocs     = array();
        $_SESSION['clear_booking_stack_entries'] = array();
        
		// Value des Firmen-Filters aus der Parent-GUI
		$iCompany = (int) $this->_getCompanyFilterValue();

        $aStackList     = Ext_TS_Accounting_BookingStack::getCollection();
				
		$aParentDocuments = array();
			
        // Alle Dokumente aus dem Stack nach Inquiry Gruppieren
        foreach($aStackList as $aStack) {
			
			$iDocument = $aStack['document_id'];
			
			//#5155 - Nur Einträge der gewünschter Firma anzeigen
			if(
				$iCompany > 0 &&
				$aStack['company_id'] != $iCompany
			) {
				continue;
			}
						
            if($iDocument > 0){
                $oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocument);
				
                if(
					(
						$oDocument->entity === Ext_TS_Inquiry::class &&
						$oDocument->entity_id > 0
					) ||
					$oDocument->type == 'manual_creditnote'
				) {

					$oParentDoc = $oDocument->getParentDocument();
	
					if(isset($aParentDocuments[$iDocument])) {
						$oParentDoc = null;
					}
					
					// Creditnote muss gesondert behandelt werden, da Parent die brutto/netto-Rechnung ist
					if(
						$oParentDoc &&
						$oDocument->type === 'creditnote'
					) {
						$oTmpParentDoc = $oParentDoc->getParentDocument();

						if($oTmpParentDoc) {
							$oParentCreditnote = $oTmpParentDoc->getCreditNote();
							if($oParentCreditnote) {
								$oParentDoc = $oParentCreditnote;
							} else {
								// Muss auf null gesetzt werden, damit $iDiff == 0 bleibt und $oDocument in $aGroupDocs landet
								$oParentDoc = null;
							}
						}
					}

                    $aData = (array)$aGroupDocs[$oDocument->entity_id][$oDocument->getId()];
                    $aCurrentStacks = (array)$aData['stacks'];
                    $aCurrentStacks[] = $aStack['id'];
					
					$iDiff = 0;
					if($oParentDoc) {						
						if($oDocument->type == 'manual_creditnote') {							
							$oManualCreditNote = $oDocument->getManualCreditnote();
							$fDocumentAmount = $oManualCreditNote->amount;
							$oParentManualCreditnote = $oParentDoc->getManualCreditnote();							
							$fParentAmount = $oParentManualCreditnote->amount;
						} else {
							$fParentAmount = $oParentDoc->getAmount();
							$fDocumentAmount = $oDocument->getAmount();
						}
						
						// #5155 - Die Beträge der Rechnungen müssen sich gegenseitig aufheben
						$iDiff = $fParentAmount + $fDocumentAmount;
					}

					// Anmerkung: Hier landen auch die entsprechenden Parent Documents automatisch drin, da $iDiff == 0
					if($iDiff == 0) {
						$aGroupDocs[$oDocument->entity_id][$oDocument->getId()] = array('parent' => $oParentDoc->id, 'stacks' => $aCurrentStacks);
						$aParentDocuments[$oParentDoc->id] = $oDocument->getId();
					}
                }
            }
        }
        
        $aClearDocuments = array();

		ksort($aGroupDocs);
		
        // Schauen weche Dokumente eltern haben die auch noch nicht freigegeben wurden
        // und sich somit aufheben
        foreach($aGroupDocs as $iInquiry => $aDocuments){
            foreach($aDocuments as $iDocument => $aTemp){
    
                $iParentDocument = $aTemp['parent'];

                // wenn das eltern element ebenfalls noch im stack ist ( noch nicht freigegeben wurde )
                if(
                    $iParentDocument > 0 &&
					array_key_exists($iParentDocument, $aDocuments)
                ) {
					
                    $oDocument          = Ext_Thebing_Inquiry_Document::getInstance($iDocument);
                    $aHistory           = (array)$oDocument->booking_stack_histories;
                    $oParentDocument    = Ext_Thebing_Inquiry_Document::getInstance($iParentDocument);
                    $aParentHistory     = (array)$oParentDocument->booking_stack_histories;

                    // und wenn es entweder eine gutschrift oder eine storno ist
                    // dann heben sich die dokumente auf! SOFERN keine Historien Daten da sind, da sonst teile schon freigegeben wurden!
                    // da ursprung und aktuelle rechnung noch nicht freigegeben sind und man weder teil gutschreiben noch teilstornieren kann     
					if(
                        empty($aHistory) &&
                        empty($aParentHistory)
                        && 
                        (
                            $oDocument->is_credit == 1 ||
                            $oDocument->type == 'storno' ||
							$oDocument->type == 'manual_creditnote'
                        )
                    ){
                        $aClearDocuments[] = $oDocument->id;
                        $aClearDocuments[] = $iParentDocument;
						// Stack Einträge des Parent-Dokument auch bereinigen
						foreach($aDocuments[$iParentDocument]['stacks'] as $iParentStack) {
							$_SESSION['clear_booking_stack_entries'][$iParentDocument][] = $iParentStack;
							// #5339 - Stack-Einträge des Parent-Dokumentes müssen auch bereinigt werden, wenn nur ein Child-Dokument angetickt wurde
							$_SESSION['clear_booking_stack_entries'][$oDocument->id][] = $iParentStack;
						}
                        foreach($aTemp['stacks'] as $iStack){
                            $_SESSION['clear_booking_stack_entries'][$oDocument->id][] = $iStack;							
							// #5339 - Stack-Einträge der Child-Dokumente müssen auch bereinigt werden, wenn nur das Parent-Dokument angetickt wurde
							$_SESSION['clear_booking_stack_entries'][$iParentDocument][] = $iStack;
                        }
						
                    }
                }
            }
        }
        
        $aSelectedIds = $aClearDocuments;

        if(empty($aSelectedIds)){
            $aSelectedIds[] = 0;
        }

        parent::addWDSearchIDFilter($oSearch, $aSelectedIds, $sIdField);
    }
    
    public static function getListWhere($oGui){
        return array();
    }
    
    public static function getOrderBy(){
        return array('customer_number' => 'DESC');
    }	
		
	public function switchAjaxRequest($_VARS) {

		if($_VARS['task'] == 'updateIcons') {
			$_SESSION['selected_booking_stack_entries'] = (array) $_VARS['id'];				
		}
			
		parent::switchAjaxRequest($_VARS);		
	}	
	
	/**
	 * liefert den Value des Firmen-Filters aus der Parent-GUI
	 * @return int
	 */ 
	protected function _getCompanyFilterValue() {
		$oParentGui = $this->_oGui->getParentClass();
		
		$iCompany = 0;
		
		if($oParentGui) {
			$aFilters = $oParentGui->getAllFilterElements();
			// Firmen-Filter raussuchen
			foreach($aFilters as $oFilter) {
				if($oFilter->id == 'company_id') {
					$iCompany = (int) $oFilter->value;
					break;
				}
			}
		}
		
		return $iCompany;
	}
}