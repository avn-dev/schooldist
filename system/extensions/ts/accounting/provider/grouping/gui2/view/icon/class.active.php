<?php

/**
 * Class Ext_TS_Accounting_Provider_Grouping_Gui2_View_Icon_Grouping
 */
class Ext_TS_Accounting_Provider_Grouping_Gui2_View_Icon_Active extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * {@inheritdoc}
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Den Klassennamen laden, da drei Provider Objekte auf diese Klasse zugreifen, da
		// sie alle den selben Ablauf haben und auf die gleiche Eigenschaft geprüft werden kann.
		// Anmerkung: SelectIds hat nur von einem Typ die Ids, daher muss die Klasse nur einmal geholt werden.
		$sClassName = $this->_oGui->class_wdbasic;
		
		$bShowIcon = true;
		if(empty($aSelectedIds) && $oElement->task !== 'export_csv' && $oElement->task !== 'export_excel') {
			$bShowIcon = false;
		}

		if($oElement->task === 'openMultiplePdf') {
			// Alle Ids durch checken, wenn irgendeine Id bzw irgendein Objekt keine Datei hat, dann muss
			// das Icon deaktiviert werden, da man dann nicht das MassenPdf-Icon verwenden darf.
			foreach($aSelectedIds as $iId) {

				$oPaymentProvider = $sClassName::getInstance($iId);
				$sFilePath = $oPaymentProvider->file;

				if(empty($sFilePath)) {
					$bShowIcon = false;
					break;
				}

			}
		} elseif($oElement->action === 'interface_export') {
			foreach($aSelectedIds as $iId) {
				$bShowIcon = true;
			    /** @var Ext_TS_Accounting_Provider_Grouping_Accommodation $oPaymentProvider */
				$oPaymentProvider = $sClassName::getInstance($iId);
				$sProcessed = $oPaymentProvider->processed;

                /*
                 * Zusätzlich prüfen ob die Schule und der aktuelle Provider jeweils
                 * eine Iban und eine Schule ausgewählt haben [Wenn Sepa aktiv ist, ansonsten kann
                 * das ignoriert werden].
                 * Die Iban-Überprüfung hat auf die Icons keinen Einfluss. (Ein deaktiviertes Icon hilft dem User nicht dabei
                 * den Fehler zu erkennen. Eine Fehlermeldung schon:)
                 */

				if ($sProcessed !== null) {
					$bShowIcon = false;
					break;
				}
			}
		// Das Historie-Icon ist immer aktiv!
		} elseif(
			$oElement->task === 'openDialog' &&
			$oElement->action === 'interface_export_history'
		) {
			$bShowIcon = true;
		}

		if($bShowIcon === false) {
			return 0;
		}

		return 1;

	}

}