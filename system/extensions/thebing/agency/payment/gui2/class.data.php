<?php

class Ext_Thebing_Agency_Payment_Gui2_Data extends Ext_TS_Inquiry_Index_Gui2_Data {

	/**
	 * @inheritdoc
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {	

		$aData = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		if($sAction == 'payment') {
			// Parent-Gui neuladen, da dort die Beträge aktualisiert werden müssen
			$aData['parent_gui'] = $this->_oGui->getParentGuiData();
		}
		
		return $aData;
	}

	/**
	 * @inheritdoc
	 */
	public function addWDSearchIDFilter(\ElasticaAdapter\Facade\Elastica $oSearch, array $aSelectedIds, $sIdField) {
		global $_VARS;

		parent::addWDSearchIDFilter($oSearch, $aSelectedIds, $sIdField);

		$aParentGuiIds = (array)$_VARS['parent_gui_id'];
		$iAgencyPaymentId = reset($aParentGuiIds);
		$oAgencyPayment = Ext_Thebing_Agency_Payment::getInstance($iAgencyPaymentId);

		// Währung zwischen Agenturbezahlung und Buchung muss übereinstimmen
		$oQuery = $oSearch->getFieldQuery('currency_id_original', $oAgencyPayment->amount_currency);
		$oSearch->addMustQuery($oQuery);

	}
	
}
