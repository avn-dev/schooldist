<?php

class Ext_TC_Exchangerate_Table_Overview_Gui2_Filter_Selection_Date extends Ext_Gui2_View_Selection_Filter_Abstract {
	
	/**
	 * baut sie Select-Options für das Datenselect zusammen. Zur Auswahl stehen alle Daten, 
	 * für die Wechselkurse vorhanden sind
	 * @param array $aParentGuiIds
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	public function getOptions($aParentGuiIds, &$oGui)
    {

		$aReturn = array();
		$iExchangerateTableId = (int) reset($aParentGuiIds);
		$oDateFormat = new Ext_TC_Gui2_Format_Date();
		
		// Wenn ein Eintrag ausgewählt wurde
		if(!empty($iExchangerateTableId)) {
		
			// Wechselkurs-Tabelle holen
			$oExchangerateTable = Ext_TC_Exchangerate_Table::getInstance($iExchangerateTableId);
			// Daten, für die Wechselkurse zur Verfügung stehen holen
			$aData = $oExchangerateTable->getAllRateDates();			
			
			// Struktur des Arrays ändern und Daten formatieren						
			foreach($aData as $sDate) {
				$aReturn[$sDate] = $oDateFormat->format($sDate);
			}

		}
		
		// Aktuelles Datum hinzufügen. Nötig, wenn für den aktuellen Tag noch keine Wechselkurse gezogen wurden
		$sDate = date('Y-m-d');		
		if(empty($aReturn[$sDate])) {
			$aReturn[$sDate] = $oDateFormat->format($sDate);
		}
		
		return $aReturn;
	}
	
}
