<?php


class Ext_TS_Document_Release_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {
	
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		$aDocumentIds = array_column($aRowData, 'id');
		$aIsReleased = array_column($aRowData, 'is_released'); // Release-Status der ausgewählten Dokumente

		$iStatus = 0;	
					
		if($oElement->task == 'openDialog' && $oElement->action == 'release') {
			// Wenn ein Dokument noch nicht freigegeben wurde
			$iStatus = (in_array('released', $aIsReleased)) ? 0 : 1;
		} elseif(
			$oElement->task == 'requestAsUrl' && 
			$oElement->action == 'testExport'
		) {
			
			$iStatus = 1;

		} else if(
			$oElement->task == 'openDialog' && 
			(
				$oElement->action == 'xml_export_it' ||	
				$oElement->action == 'xml_export_it_final' 
			)
		) {	

			$oRepository = \TcAccounting\Service\eInvoice\Entity\File::getRepository();
			$aEInvoiceFiles = $oRepository->findBy(['document_id' => $aDocumentIds, 'type' => 'xml_it']);

			$iStatus = (empty($aEInvoiceFiles)) ? 1 : 0;
								
			if(
				$oElement->action == 'xml_export_it_final'&&
				!in_array('released', $aIsReleased)
			) {		
				// Nur freigegebene Dokumente können final exportiert werden
				$iStatus = 0;
			}				
			
			
		} else if($oElement->task == 'openDialog' && $oElement->action == 'einvoice_history') {	
		
			// Wenn es ein freigegebenes Dokument gibt prüfen ob es Export-Dateien gibt
			if(in_array('released', $aIsReleased)) {
				$oRepository = \TcAccounting\Service\eInvoice\Entity\File::getRepository();
				$aEInvoiceFiles = $oRepository->findBy(['document_id' => $aDocumentIds]);
			
				$iStatus = (!empty($aEInvoiceFiles)) ? 1 : 0;
			}
			
		} else {
			$iStatus = parent::getStatus($aSelectedIds, $aRowData, $oElement);
		}
		
		return $iStatus;
	}
}