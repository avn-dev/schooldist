<?php

/**
 * @TODO GUI wird für Tab Provisionen und Tab Terms of Payment verwendet
 *
 * @property Ext_Thebing_Agency_Provision_Group|Ext_Thebing_Agency_Payment_Group_Assignment $oWDBasic
 */
class Ext_Thebing_Agency_Provision_Gui2_Data extends Ext_Thebing_Gui2_Data {

	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {
		
		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		if(empty($aTransfer['error'])) {

			$oAgency = $this->oWDBasic->getAgency();
			if($oAgency instanceof Ext_Thebing_Agency) {
				// Beliebter Fall in Thebing: Updated verändern, obwohl sich überhaupt nichts an der Entität verändert hat
				$oAgency->updateChangedData();
			}

		}

		return $aTransfer;

	}

}
