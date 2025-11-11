<?php

/*
 * Die Klasse ist für das De-aktivieren der GUI2 Icons zuständig in der Unterkunftskommunikationsliste
 */
class Ext_Thebing_Gui2_Icon_Accommodation_Communication_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		$iSelectedId = reset($aSelectedIds);

		$aRowSelectedData = array();
		// ausgewählten Datensatz ermitteln
		foreach ((array)$aRowData as $iKey => $aData) {
			if($aData['encoded_gui_id'] == $iSelectedId){
				$aRowSelectedData = $aData;
				break;
			}
		}

		$bShow = 0;
		
		if(
			count($aSelectedIds) > 0 &&
			(
				(
				$oElement->action == 'communication' &&
				(
					$oElement->additional == 'accommodation_communication_provider' ||
					$oElement->additional == 'accommodation_communication_customer_agency'
				)
				) || 
				$oElement->action == 'confirm_provider' ||
				$oElement->action == 'confirm_customer_agency'
			)
		){
			$bShow = 1;

			foreach ((array)$aRowData as $iKey => $aData) {


					if(
					(
						isset($aData['accommodation_canceled']) &&
						$aData['accommodation_canceled'] != 0
					)||(
						isset($aData['customer_agency_canceled']) &&
						$aData['customer_agency_canceled'] != 0
					)
					) {

						// Icons auch nur dann anzeigen wenn nicht schon abgesagt wurde in der History Liste
						$bShow = 0;
						break;
				} 
						
					}

			// Unterkunftskommunikation nur erlaube, wenn es auch eine Familie gibt/gab
			if(
				(
					$oElement->additional == 'accommodation_communication_provider' ||
					$oElement->action == 'confirm_provider'
				)&&
					(int)$aRowSelectedData['allocation_room_id'] <= 0
			){
				$bShow = 0;
			}


		} elseif($oElement->action == 'history') {

			$iMatchingCount = (int)$aRowSelectedData['all_matchings'];

			if(
				$iMatchingCount > 0 &&
				count($aSelectedIds) == 1 // Nur bei einer Markierung anzeigen
			){
				$bShow = 1;
			}else{
				$bShow = 0;
			}

		} elseif($oElement->action == 'request_availability') {

			if(
				empty($aRowSelectedData['active_accommodation_allocations']) &&
				count($aSelectedIds) == 1 // Nur bei einer Markierung anzeigen
			){
				$bShow = 1;
			}else{
				$bShow = 0;
			}

		} elseif($oElement->action == 'edit'){
			if(count($aSelectedIds) == 1){
				$bShow = 1;
			} else {
				$bShow = 0;
			}
		} elseif(
			$oElement->action == 'additional_document' ||
			$oElement->action == 'openDocumentPdf'
		){
			$oInquiryIconStatusActive = new Ext_Thebing_Gui2_Icon_Inbox('accommodation_inquiry_id');
			$bShow = $oInquiryIconStatusActive->getStatus($aSelectedIds, $aRowData, $oElement);
		}
	
		
		// Bestätigt anzeigen nur wenn nicht schon bestätigt worden ist
		
		
		// Wiederrufen nur anzeigen wenn bestätigt wurde
		if(
			count($aSelectedIds) > 0 &&
			(
				$oElement->action == 'revoce_customer_agency' ||
				$oElement->action == 'revoce_provider' ||
				$oElement->action == 'confirm_provider' ||
				$oElement->action == 'confirm_customer_agency'
			) 
		){	
			$bShow = 1;
			
			if(empty($aRowData)){
				$bShow = 0;
			}
			
			if($oElement->action == 'confirm_customer_agency'){
				foreach ((array)$aRowData as $iKey => $aData) {
					if(
						(
							isset($aData['accommodation_customer_agency_confirmed']) &&
							(int)$aData['accommodation_customer_agency_confirmed'] > 0
						) ||
						(int)$aRowSelectedData['allocation_room_id'] <= 0
					) {
						// Icons auch nur dann anzeigen wenn noch NICHT bestätigt wurde
						$bShow = 0;
						break;
					} 
				}
			}
			
			if($oElement->action == 'confirm_provider'){
				foreach ((array)$aRowData as $iKey => $aData) {
					if(
						(
							isset($aData['accommodation_accommodation_confirmed']) &&
							(int)$aData['accommodation_accommodation_confirmed'] > 0
						) ||
						(int)$aRowSelectedData['allocation_room_id'] <= 0
					) {
						// Icons auch nur dann anzeigen wenn noch NICHT bestätigt wurde
						$bShow = 0;
						break;
					} 
     
				}

			}
			
			if($oElement->action == 'revoce_customer_agency'){
				foreach ((array)$aRowData as $iKey => $aData) {
					if(
						(
							isset($aData['accommodation_customer_agency_confirmed']) &&
							(int)$aData['accommodation_customer_agency_confirmed'] == 0
						) ||
						(int)$aRowSelectedData['allocation_room_id'] <= 0
					) {
						// Icons auch nur dann anzeigen wenn bestätigt wurde
						$bShow = 0;
						break;
					} 
				}
			}
			
			if($oElement->action == 'revoce_provider'){
				foreach ((array)$aRowData as $iKey => $aData) {
					if(
						(
							isset($aData['accommodation_accommodation_confirmed']) &&
							(int)$aData['accommodation_accommodation_confirmed'] == 0
						) ||
						(int)$aRowSelectedData['allocation_room_id'] <= 0
					) {
						// Icons auch nur dann anzeigen wenn bestätigt wurde
						$bShow = 0;
						break;
					} 
				}
			}			
			
		}

		if(
			$oElement->task == 'export_csv' ||
			$oElement->task == 'export_excel'
		) {
			$bShow = 1;
		}

		return $bShow;
	}
}
