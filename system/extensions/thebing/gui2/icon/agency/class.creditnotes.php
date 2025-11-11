<?php
/*
 * Die Klasse ist für das De-aktivieren der GUI2 Icons zuständig in der Inbox
 */
class Ext_Thebing_Gui2_Icon_Agency_Creditnotes extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) { 

		$bPaid = false;
		$bStorno = false;
		$bIsStorno = false;

		$bIsReleased = false;
		$iCounterReleased = 0;

		foreach((array)$aSelectedIds as $iCN){
			$oCn = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iCN);

			$fPaid = $oCn->getAllocatedAccountingAmount();

			if($fPaid > 0){
				$bPaid = true;
			}

			if($oCn->storno_id > 0){
				$bStorno = true;
			}

			if($oCn->isStorno()) {
				$bIsStorno = true;
			}

			$oDocument = $oCn->getDocument();
			$bIsReleased = $oDocument->isReleased();
			
			$iCounterReleased += (int)$bIsReleased;
		}

		if($oElement->task == 'deleteRow'){
			if(count($aSelectedIds) > 0 && !$bPaid && !$bStorno && !$bIsStorno && $iCounterReleased <= 0){
				return 1;
			}
			return 0;
		}

        switch ($oElement->action) {
//			case 'manual_creditnote_pdf':
//			case 'open_manual_creditnote_pdf':
//				if(count($aSelectedIds) == 1){
//					return 1;
//				}
//				return 0;
			case 'storno':
				if(count($aSelectedIds) > 0 && !$bPaid && !$bStorno && !$bIsStorno){
					return 1;
				}
				return 0;
				break;
			case 'edit':
				if(count($aSelectedIds) == 1 && !$bPaid && !$bStorno && !$bIsStorno && !$bIsReleased){
					return 1;
				}
				return 0;
				break;
			default:
				return parent::getStatus($aSelectedIds, $aRowData, $oElement);
				break;
		}
		
	}
}
