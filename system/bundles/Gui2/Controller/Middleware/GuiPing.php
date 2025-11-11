<?php

namespace Gui2\Controller\Middleware;

use Illuminate\Http\Request;

/**
 * @TODO Keine richtige Middleware, da das bisher so nicht funktioniert
 */
class GuiPing {

	public function handle(Request $oRequest/*, \Closure $oNext*/) {

		//$oResponse = $oNext($oRequest);

		$oUser = \System::getCurrentUser();

		// Alle Dialoge entsperren
		\Ext_Gui2_Data::unlockUserDialogs($oUser->id);

		// Übermittelte GUIs holen
		$aGuiInstances = $oRequest->input('guis');

		// Wenn GUIs übermittelt wurden
		if(is_array($aGuiInstances)) {

			foreach($aGuiInstances as $sInstanceHash => $aGuis) {

				foreach($aGuis as $sHash => $aDialogs) {

					$aDialogs = array_values((array)$aDialogs);

					// Ping ausführen (Dialoge entsperren und sperren
					\Ext_Gui2_Data::lockDialogs($sHash, $sInstanceHash, $oUser->id, $aDialogs);

					// Gui2 am Leben halten, wenn der Ping noch existiert
					\Ext_Gui2_GarbageCollector::touchSession($sHash, $sInstanceHash);

				}
			}

			// Aufräumen
			\Ext_Gui2_GarbageCollector::clean();

		}

		//return $oResponse;

	}

}
