<?php

class Ext_Thebing_Gui2_Style_InboxRow extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sRedFont = Ext_Thebing_Util::getColor('red_font');
		$sChanged = Ext_Thebing_Util::getColor('changed');
		$sStorno = Ext_Thebing_Util::getColor('storno');
		$sStyle = '';

        $aInvoiceStatus = (array)$aRowData['invoice_status'];

        $bHasProInvData = true;
        if(
			(
				isset($aRowData['has_proforma_or_invoice_data']) &&
				$aRowData['has_proforma_or_invoice_data'] == 0
			) ||
			(
				(isset($aRowData['has_proforma']) && !$aRowData['has_proforma']) &&
				(isset($aRowData['has_invoice']) && !$aRowData['has_invoice'])
			)
		) {
			$bHasProInvData = false;
		}

		$bConfirmed = true;
		if(
			isset($aRowData['confirmed_original']) &&
			$aRowData['confirmed_original'] === false) {
			$bConfirmed = false;
		}

		## BG Farbe ##
		// Storniert
		if(in_array('cancelled', $aInvoiceStatus)) {
			$sStyle .= 'background-color: '.$sStorno.';';
		} else if(
			!empty($aRowData['document_version_item_change']) &&
			!($bConfirmed == false) // Nur Kunden mit Rechnung dürfen markiert werden (Anmerkung: Confirmed wird bereits bei Proforma gesetzt)
		) {
			$sStyle .= 'background-color: ' . $sChanged . ';';
		}

		## Schriftfarbe ##
		// #FFCCAA dieses rot ist als schriftfarbe hässlich :)
		// Dieses rot kann zu bleibenden schäden beim Betrachter führen.
		// wenn nicht bestätigt
		if($bConfirmed == false) {
			$sStyle .= 'color: #888; ';
		} elseif(!$bHasProInvData) {
			$sStyle .= 'color: '.$sRedFont.'; ';
		}
		return $sStyle;

	}

}
