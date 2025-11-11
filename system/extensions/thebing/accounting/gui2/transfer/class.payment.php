<?php


class Ext_Thebing_Accounting_Gui2_Transfer_Payment extends Ext_Thebing_Payment_Provider_Gui2_Abstract {

	public function switchAjaxRequest($_VARS) {
		global $user_data;

		$aTransfer = array();
		switch($_VARS['task']){
			case 'saveDialog':
				if($_VARS['action'] == 'transfer_payment'){

					$aErrors = array();
					$aHints	= array();
					$oDummy = null;

					// Gruppierungen pro Transferanbieter
					$aGroupings = array();
					$aGroupingPayments = array();
					$aGeneratedGroupings = array();

					$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
					$oSchoolSession = Ext_Thebing_School::getSchoolFromSession();
					$aTempSaveData = array();

					foreach((array)$_VARS['save']['amount'] as $iTransferId => $mAmount){

						$_VARS['id'][] = $iTransferId;

						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
						$aProviders = Ext_TS_Inquiry_Journey_Transfer::getProvider((array)$oTransfer->id);
						$oProvider = reset($aProviders);
						$oInquiry = $oTransfer->getInquiry();
						$oSchool = $oInquiry->getSchool();
						$aTmp = array('school_id' => $oSchool->id);
						$sDate = $oFormatDate->convert($_VARS['save']['date'], $oDummy, $aTmp);

						if(
							!$oProvider instanceof Ext_Thebing_Pickup_Company &&
							!$oProvider instanceof Ext_Thebing_Accommodation
						) {
							throw new UnexpectedValueException('Provider has instance of wrong class!');
						}

						// Transfer je Transferanbieter in das Grouping einfügen
						$sProviderKey = get_class($oProvider).'_'.$oProvider->id;
						if(!isset($aGroupings[$sProviderKey])) {
							$aGroupings[$sProviderKey] = $oProvider;
						}

						$iCurrency = (int)$_VARS['save']['payment_currency_id'][$iTransferId];
						$iCurrencySchool = $oSchool->getCurrency();

						$oPayment = new Ext_Thebing_Transfer_Payment(0);
						$oPayment->user_id				= (int)$user_data['id'];
						$oPayment->inquiry_transfer_id	= (int)$iTransferId;
						$oPayment->amount				= Ext_Thebing_Format::convertFloat($mAmount, $oSchool->id);
						$oPayment->amount_school		= Ext_Thebing_Format::convertFloat($_VARS['save']['amount_school'][$iTransferId], $oSchool->id);
						$oPayment->payment_currency_id	= (int)$iCurrency;
						$oPayment->school_currency_id	= (int)$iCurrencySchool;
						$oPayment->date					= $sDate;
						$oPayment->method_id			= (int)$_VARS['save']['method_id'];
						$oPayment->comment				= $_VARS['save']['comment'];
						$oPayment->payment_note			= $_VARS['save']['single_payment_note'][$iTransferId];

						$mValidate		= $oPayment->validate();
						if(!isset($_VARS['ignore_errors'])){
							$mValidateHint	= $oPayment->checkIgnoringErrors();
						}else{
							$mValidateHint	= true;
						}

						if($mValidate !== true || $mValidateHint !== true){
							if($mValidate !== true){
								$aErrors = array_merge($aErrors, (array)$mValidate);
							}
							if($mValidateHint !== true){
								$aHints = array_merge($aHints, (array)$mValidateHint);
							}
						} else {
							$oPayment->save();
							$aGroupingPayments[$sProviderKey][] = $oPayment;

							// den "verändert" timestamp des Transfers muss wieder resettet werden
							$oTransfer->updated = 0;
							$oTransfer->save();

							Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);

							$aTempSaveData[] = $oPayment;
							$bSuccess = true;

							if(is_array($bSuccess)){
								$aErrors = $bSuccess;
							}
						}

						// Bei Fehler wieder Löschen
						if(!empty($aErrors) || !empty($aHints)){

							foreach((array)$aTempSaveData as $oPayment){
								$oPayment->delete();
							}

							break;
						}

					}

					if(empty($aErrors) && empty($aHints)) {
						foreach($aGroupings as $sProviderKey => $oProvider) {
							/** @var $oProvider Ext_Thebing_Pickup_Company */

							/** @var $aTransferPayments Ext_Thebing_Transfer_Payment[] */
							$aTransferPayments = $aGroupingPayments[$sProviderKey];
							$fGroupingAmount = 0;
							$fGroupingAmountSchool = 0;
							$iTemplateId = (int)$_VARS['save']['template_id'];

							// Gesamtbeträge addieren
							foreach($aTransferPayments as $oPayment) {
								$fGroupingAmount = (float)bcadd((string)$fGroupingAmount, (string)$oPayment->amount);
								$fGroupingAmountSchool = (float)bcadd((string)$fGroupingAmountSchool, (string)$oPayment->amount_school);
							}

							$oLastPayment = end($aTransferPayments);

							$oGrouping = new Ext_TS_Accounting_Provider_Grouping_Transfer();
							$oGrouping->school_id = $oSchoolSession->id;
							$oGrouping->provider_id = $oProvider->id;
							$oGrouping->payment_method_id = $_VARS['save']['method_id'];
							$oGrouping->template_id = $iTemplateId;
							$oGrouping->date = $oLastPayment->date;
							$oGrouping->amount = $fGroupingAmount;
							$oGrouping->amount_currency_id = $oLastPayment->payment_currency_id;
							$oGrouping->amount_school = $fGroupingAmountSchool;
							$oGrouping->amount_school_currency_id = $oLastPayment->school_currency_id;

							if($oProvider instanceof Ext_Thebing_Accommodation) {
								$oGrouping->provider_type = 'accommodation';
							} else {
								$oGrouping->provider_type = 'provider';
							}

							$oGrouping->save();
							$aGeneratedGroupings[] = $oGrouping;

							// Payments Gruppierung zuweisen
							// Wird für Platzhalter bereits hier benötigt
							foreach($aTransferPayments as $oPayment) {
								$oPayment->grouping_id = $oGrouping->id;
								$oPayment->save();
							}

							// PDF (pro Gruppierung) generieren, wenn Template vorhanden
							if($iTemplateId > 0) {
								$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplateId);

								// Daten, die direkt in die Platzhalterklasse geschrieben werden unter dem Key »grouping_data«
								$aGroupingDataPlaceholder = array(
									'provider_payment_overview' => $this->getDataForPaymentOverviewPlaceholder($aTransferPayments, $fGroupingAmount, $oSchool->language)
								);

								$sFilePath = $oGrouping->createPdf($oTemplate, $aGroupingDataPlaceholder);
								$sFilePath = str_replace(Util::getDocumentRoot().'storage', '', $sFilePath);
								$oGrouping->file = $sFilePath;
								$aGeneratedPdfs[] = $sFilePath;
								$oGrouping->save();
							}

						}
					}

					$aAction		= array('action' => 'transfer_payment');

					$aErrorData		= (array)$this->_getErrorData($aErrors, $aAction, 'error', true);
					$aErrorDataHint = (array)$this->_getErrorData($aHints, $aAction, 'hint', true);

					$aErrorsAll		= array_merge($aErrorData,$aErrorDataHint);

					$sDialogIDTag = 'TRANSFER_PAYMENT';

					$_VARS['id'] = array_unique($_VARS['id']);

					$sDialogIDTag = $sDialogIDTag.implode('_', $_VARS['id']);

					$aTransfer = array();
					if(empty($aErrorsAll)){
						$aTransfer['action']		= 'closeDialogAndReloadTable';

						// Dialog zum Öffnen der generierten Dokumente anzeigen
						$aGeneratedPdfs = array();
						foreach($aGeneratedGroupings as $oGrouping) {
							if(!empty($oGrouping->file)) {
								$oProvider = $oGrouping->getProvider();
								$sUrl = '/storage/download'.$oGrouping->file;
								$aGeneratedPdfs[] = '<a target="_blank" href="'.$sUrl.'">'.$oProvider->getName().'</a>';
							}
						}

						if(!empty($aGeneratedPdfs)) {
							$aTransfer['success_message'] = $this->t('Die Dokumente wurden erfolgreich angelegt. Bitte klicken Sie hier, um ein PDF mit allen Positionen anzuzeigen.');
							$aTransfer['success_message'] .= '<br /><br />'.join(', ', $aGeneratedPdfs);
						}


					}else{
						$aTransfer['action']		= 'saveDialogCallback';
					}

					$aTransfer['error']				= $aErrorsAll;

					$aTransfer['data']				= array();
					$aTransfer['data']['id']		= $sDialogIDTag;

					if(!empty($aErrorDataHint)){
						$aTransfer['data']['show_skip_errors_checkbox'] = 1;
					}

					echo json_encode($aTransfer);
					$this->_oGui->save();
					\Core\Facade\SequentialProcessing::execute();
					die();
				}
				break;
			case 'confirm':
				if($_VARS['action'] == 'transfer_payment_remove'){
					foreach((array)$_VARS['id'] as $iInquiryTransferId){
						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiryTransferId);
						$oTransfer->deletePaymentData();
					}
					$aTransfer = array();
					$aTransfer['error'] = array();
					$aTransfer['action'] = 'loadTable';
					echo json_encode($aTransfer);
					$this->_oGui->save();
					die();
				}
				break;
			default:
				// sonst parent ( hier wird ein echo gestartet )
				parent::switchAjaxRequest($_VARS);
		}

	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {
		global $_VARS;

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		$sDescription	= $this->_oGui->gui_description;

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$oClient = $oSchool->getClient();
		
		// get dialog object
		switch($sIconAction) {
			case 'transfer_payment':
				$oFirstTransfer		= Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
				$oFirstInquiry		= $oFirstTransfer->getJourney()->getInquiry();
				$oFirstSchool		= $oFirstInquiry->getSchool();
				
				$iFirstSchoolId		= $oFirstSchool->id;
				$oFirstSchool		= Ext_Thebing_School::getInstance($iFirstSchoolId);
				// Währung der Schule
				$iCurrencySchoolId	= $oFirstSchool->getCurrency();
				// Währung für transfer
				$iTransferCurrencyId	= $oFirstSchool->getTransferCurrency();
				$aMethods			= $oFirstSchool->getPaymentMethodList(true);
				$iDefaultMethod		= 0;

				$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_transfer_payment', $oSchool->getLanguage(), $oSchool->id, null, true);
				$aTemplates = Ext_TC_Util::addEmptyItem($aTemplates);

				$oDialog = $this->_oGui->createDialog($this->t('Bezahlen'), $this->t('Bezahlen'));
				$oDialog->bBigLabels = true;
				$oDialog->width = 950;
				$oDialog->sDialogIDTag	= 'TRANSFER_PAYMENT';


				$oDivRow = $oDialog->createRow( L10N::t('Datum', $sDescription), 'calendar',	array('db_column' => 'date', 'db_alias' => '', 'format' => new Ext_Thebing_Gui2_Format_Date(), 'required' => 1));
				$oDialog->setElement($oDivRow);
				$oDivRow = $oDialog->createRow( 
												L10N::t('Methode', $sDescription), 
												'select',	
												array(
													'db_column' => 'method_id', 
													'db_alias' => '', 
													'select_options' => $aMethods, 
													'required' => 1,
													'class' => 'payment_method_select',
													'default_value' => $iDefaultMethod
												)
						);
				$oDialog->setElement($oDivRow);
				$oDivRow = $oDialog->createRow( L10N::t('Kommentar', $sDescription), 'textarea',	array('db_column' => 'comment', 'db_alias' => ''));
				$oDialog->setElement($oDivRow);
				$oDialog->setElement($oDialog->createRow($this->t('Template'), 'select', array(
					'db_column' => 'template_id',
					'select_options' => $aTemplates
				)));

				$fAmountTotal = 0;

				// Transferanbieter gruppieren
				$aProviders = array(); // Provider-Cache
				$aProviderGrouping = array();
				foreach((array)$aSelectedIds as $iInquiryTransferId) {
					$oJourneyTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiryTransferId);
					$oProvider = reset($oJourneyTransfer->getProvider((array)$iInquiryTransferId));
					$aProviderGrouping[$oProvider->id][] = $oJourneyTransfer;
					$aProviders[$oProvider->id] = $oProvider;
				}

				foreach($aProviderGrouping as $iProviderId => $aData) {

					$oProvider = $aProviders[$iProviderId];

					$oH3 = new Ext_Gui2_Html_H4();
					$oH3->setElement($oProvider->getName());
					$oDialog->setElement($oH3);

					foreach($aData as $oJourneyTransfer) {
						/** @var $oJourneyTransfer Ext_TS_Inquiry_Journey_Transfer  */
//						$oInquiry = Ext_TS_Inquiry::getInstance($oJourneyTransfer->inquiry_id);
//						$oCustomer = $oInquiry->getCustomer();
//
//						$aDocs = $oInquiry->getDocuments('invoice', true, true);
//						$oDocs = reset($aDocs);
//
//						$sDocumentNumber = $oCustomer->name;
//						if($oDocs){
//							$sDocumentNumber .= ', '.$oDocs->document_number;
//						}

						$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($oJourneyTransfer, $oJourneyTransfer->provider_id);
						$fAmount = $oPackage->amount_cost;
						$fAmountTotal += $fAmount;

						$sLabel = $oJourneyTransfer->getName();

						$aData = array();
						$aData['db_column_1']	= 'amount';
						$aData['db_column_2']	= 'amount_school';
						$aData['db_column_currency']	= 'payment_currency_id';
						$aData['db_alias']		= (int)$oJourneyTransfer->id;
						$aData['school_id']		= $oFirstSchool->id;
						$aData['format']		= new Ext_Thebing_Gui2_Format_Amount();
						$aData['amount']		= $fAmount;
						$aData['currency_id']	= $iTransferCurrencyId;
						$oAmountDiv	= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, $sLabel, false, true);
						$oDialog->setElement($oAmountDiv);

						$oDivRow = $oDialog->createRow(L10N::t('Kommentar', $sDescription), 'textarea',	array('name' => 'save[single_payment_note]['.$oJourneyTransfer->id.']'));
						$oDialog->setElement($oDivRow);
					}
				}

				$oDialog->setElement(new Ext_Gui2_Html_Hr());

				// Summe
				$aData = array();
				$aData['db_column_1']			= 'sum_amount';
				$aData['db_column_2']			= 'sum_amount_school';
				$aData['db_column_currency']	= 'sum_payment_currency_id';
				$aData['school_id']				= $oSchool->id;
				$aData['format']				= new Ext_Thebing_Gui2_Format_Amount();
				$aData['amount']				= $fAmountTotal;
				$aData['currency_id']			= $iTransferCurrencyId;
				$aData['school_currency_id']	= $iCurrencySchoolId;
				$aData['disable_all']			= 1;
				$aData['class_name_from']		= 'currency_sum_row_input_from';
				$aData['class_name_to']			= 'currency_sum_row_input_to';
				$oAmountDiv	= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, '', false, true);
				$oDialog->setElement($oAmountDiv);

				break;
			default :
				$oDialog = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
				break;
		}

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		return $aData;
	}

	/**
	 * Generiert ein Array mit formatierten Daten aus der GUI-Liste
	 * für den Platzhalter »provider_payment_overview«.
	 *
	 * @see Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable
	 * @param Ext_Thebing_Transfer_Payment[] $aPayments
	 * @param float $fSum
	 * @return array
	 */
	public function getDataForPaymentOverviewPlaceholder($aPayments, $fSum) {

		$aData = array();
		$oDummy = null;

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
		$oFormatAmount = new Ext_Thebing_Gui2_Format_Amount();
		$oFormatService = new Ext_Thebing_Gui2_Format_Transfer_Type();

		foreach($aPayments as $oPayment) {

			$oJourneyTransfer   = $oPayment->getJourneyTransfer();
			$oInquiry           = $oJourneyTransfer->getInquiry();
			$oBooker            = $oInquiry->getFirstTraveller();
            
            $sCustomerNumber    = '';
            $sCustomerName      = '';
            
            if($oBooker){
                $sCustomerNumber    = $oBooker->getCustomerNumber();
                $sCustomerName      = $oBooker->getName();  
            }

			$aResultData = array(
				'currency_id' => $oPayment->payment_currency_id
			);

			$sAmount = $oFormatAmount->format($oPayment->amount, $oDummy, $aResultData);

			$aData['rows'][] = array(
				'timeframe' => $oFormatDate->format($oJourneyTransfer->transfer_date, $oDummy, $oDummy),
				'customer_number' => $sCustomerNumber,
				'customer_name' => $sCustomerName,
				'service' => $oFormatService->format($oJourneyTransfer->transfer_type),
				'amount' => $sAmount,
			);

		}

		// $aResultData: Da eine Gruppierung immer die selbe Währung hat, kann hier weiterhin $aResultData verwendet werden
		$aData['amount_sum'] = $oFormatAmount->format($fSum, $oDummy, $aResultData);

		return $aData;

	}
	static public function getFromDate(){
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->subMonth()->startOfMonth());
	}

	static public function getUntilDate(){
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->endOfMonth());
	}

	static public function getProviders(){
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aProviders = $oSchool->getTransferProvider(true);
		return $aProviders;
	}

	static public function getInbox(){
		
		$oClient = Ext_Thebing_System::getClient();
		$aInboxes = $oClient->getInboxList(true, true);
		return $aInboxes;
	}

}