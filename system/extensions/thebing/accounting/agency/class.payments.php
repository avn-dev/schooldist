<?php

class Ext_Thebing_Accounting_Agency_Payments extends Ext_Thebing_Gui2_Data {

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$iCurrency	= (int)$aSaveData['amount_currency'];
		$iMethod	= (int)$aSaveData['method_id'];
		//$oMethod	= Ext_Thebing_Admin_Payment::getInstance($iMethod);

        $aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

        if($aTransfer['save_id'] > 0 && $bSave){
            $oSchool			= Ext_Thebing_Client::getFirstSchool();
            $iSchoolCurrency	= $oSchool->getCurrency();

            $oAgencyPayment = new Ext_Thebing_Agency_Payment((int)$aTransfer['save_id']);
            $oAgencyPayment->amount_currency = $iCurrency;
            $oAgencyPayment->amount_school_currency = (int)$iSchoolCurrency;
            $oAgencyPayment->save();
		}
		
		return $aTransfer;
	}

	// Übersetzungen
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aPaymentData = Ext_Thebing_Util::getPaymentTranslations();

		$aData = (array)$aData + (array)$aPaymentData;

		return $aData;

	}

	/**
	 * 
	 * @global type $user_data
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param type $aSelectedIds
	 * @param type $sAdditional
	 * @return boolean
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		global $user_data;
		
		$oDialogData->aElements = array();
        $oDialogData->bBigLabels = true;

		$oWDBasic	= $this->_getWDBasicObject($aSelectedIds);
		$iSchool	= $oWDBasic->school_id;
		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aAgencies = $oClient->getAgencies(true);
		$aSchools = $oClient->getSchools(true);
		$aSchools = Ext_Thebing_Util::addEmptyItem($aSchools);
		
		$sGuiDescription = 'Thebing » Accounting » Agency Payments';		
		
		$bIsAllSchools = Ext_Thebing_System::isAllSchools();
		
		if($bIsAllSchools) {
			$oDiv		= $oDialogData->createRow( L10N::t('Schule', $sGuiDescription), 'select',	array(
				'db_column' => 'school_id', 
				'db_alias' => '', 
				'select_options' => $aSchools, 
				'required'=>true
			));
			$oDialogData	->setElement($oDiv);
		}

		$oDiv		= $oDialogData->createRow( L10N::t('Agentur', $sGuiDescription), 'select',	array('db_column' => 'agency_id', 'db_alias' => '', 'select_options' => $aAgencies, 'required'=>true));
		$oDialogData	->setElement($oDiv);
		$oDiv		= $oDialogData->createRow( L10N::t('Datum', $sGuiDescription), 'calendar',	array('db_column' => 'date', 'db_alias' => '', 'value'=>  Ext_Thebing_Format::LocalDate(time()), 'format' => new Ext_Thebing_Gui2_Format_Date()));
		$oDialogData	->setElement($oDiv);

		$aData = array();
		$aData['db_column_1']	= 'amount';
		$aData['db_column_2']	='amount_school';
		$aData['db_alias']		= '';
		$aData['school_id']	= $iSchool;
		$aData['db_column_currency']		= 'amount_currency';
		$aData['show_right_div'] = true;
		$aData['format']		= new Ext_Thebing_Gui2_Format_Amount();
		$oDiv		= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialogData, $aData);
		$oDialogData	->setElement($oDiv);

		$aData = array();
		$aData['db_column']	='fee';
		$aData['db_alias']		= '';
		$aData['format']		= new Ext_Thebing_Gui2_Format_Amount();
		$aData['class']         = 'currency_amount_row_input_from';
		//$oDiv		= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, L10N::t('Gebühr', $sGuiDescription));
		$oDiv                   = $oDialogData->createRow(L10N::t('Gebühr in Zahlungswährung', $sGuiDescription), 'input', $aData);
		$oDialogData	->setElement($oDiv);

		if(Ext_Thebing_System::isAllSchools()) {
			$aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true, [$oSchool->id]);
		}
		
		$oDiv		= $oDialogData->createRow( L10N::t('Methode', $sGuiDescription), 'select',	array('db_column' => 'method_id', 'db_alias' => '', 'select_options' => $aPaymentMethods));
		$oDialogData	->setElement($oDiv);
		$oDiv		= $oDialogData->createRow( L10N::t('Kommentar', $sGuiDescription), 'textarea',	array('db_column' => 'comment', 'db_alias' => ''));
		$oDialogData	->setElement($oDiv);

		$oDialogData->width = 950;
		
		$aDialogHtml = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		return $aDialogHtml;
	}
	
}
