<?php

/**
 * Icon-Management für die Kommunikation
 */
class Ext_TC_Communication_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if ($oElement->action === 'messageAllocations') {

			$aAllocationConfigs = Factory::executeStatic(\Ext_TC_Communication_Gui2_Data::class, 'getAllocationHandler');

			$aSelectedIds = (array)$aSelectedIds;
			$oMessage = \Factory::getInstance(\Ext_TC_Communication_Message::class, reset($aSelectedIds));

			// Schauen, ob eine Zuweisung für diese Nachricht möglich ist
			foreach ($aAllocationConfigs as $aAllocationConfig) {
				$oHandler = app()->make($aAllocationConfig['class']);
				if ($oHandler->isValid($oMessage)) {
					// Sobald eine Aktion verfügbar ist, kann das Icon benutzt werden
					return true;
				}
			}

			return false;
		}

		/*
		 * Es war bisher so, dass man keine E-Mails löschen konnte außer 
		 * eingehende E-Mails ohne zusätzliche Verknüpfungen.
		 * Ist zu einschränkend, daher kann man jetzt alles löschen aber es gibt ein Recht für das Icon
		 */
		return true;
	}
	
}
