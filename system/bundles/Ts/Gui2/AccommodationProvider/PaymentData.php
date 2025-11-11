<?php

namespace Ts\Gui2\AccommodationProvider;

use Ts\Handler\AccommodationProvider\PaymentHandler;
use TsAccounting\Service\NumberRange\AccommodationPaymentGrouping;

class PaymentData extends \Ext_Thebing_Gui2_Data {
	
	static $sL10NDescription = 'Thebing » Accounting » Accommodation';
	
	/**
	 * @var \Ext_Thebing_School
	 */
	static protected $oSchool;

	/**
	 * @var array
	 */
	private $aErrorData = [];

	/**
	 * @param \Ext_Thebing_School $oSchool
	 */
	static public function setSchool(\Ext_Thebing_School $oSchool) {
		self::$oSchool = $oSchool;
	}

	public static function getCategoryFilterOptions() {
		$aFilterOptions = array(
			'yes'	=>	\L10N::t('vorhanden', self::$sL10NDescription),
			'no'	=>	\L10N::t('nicht vorhanden', self::$sL10NDescription)
		);
		
		return $aFilterOptions;
	}

	/**
	 * @param array $_VARS
	 */
	public function switchAjaxRequest($_VARS) {

		if($_VARS['action'] == 'reset') {

			set_time_limit(1800);
			ini_set("memory_limit", '512M');

			$oSchool = \Ext_Thebing_School::getSchoolFromSession();

			$oPaymentHandler = new PaymentHandler($oSchool);

			try {
				$oPaymentHandler->resetPendingPayments();
				$oPaymentHandler->generate();

				$aReport = $oPaymentHandler->getReport();

				$sMessage = $this->t('Die Einträge wurden erfolgreich aktualisiert!');

				$sMessage .= '<br><br><table class="table" style="width: 100%;"><colgroup><col style="width: 200px;"><col style="width: 50px;"></colgroup>';
				$sMessage .= '<tr><th>'.$this->t('Zurückgesetzte Einträge').'</th><td style="text-align: right;">'.(int)$aReport['reset']['current_payments'].'</td></tr>';
				$sMessage .= '<tr><th>'.$this->t('Zuweisungen insgesamt').'</th><td style="text-align: right;">'.(int)$aReport['generate']['allocations_found'].'</td></tr>';
				$sMessage .= '<tr><th onclick="if($(\'tbl_skipped\')) {$(\'tbl_skipped\').toggle();}" style="cursor: pointer;">'.$this->t('Übersprungene Zuweisungen').' <i class="fa fa-chevron-down"></i></th><td style="text-align: right;">'.(int)count((array)$aReport['generate']['skipped']).'</td></tr>';
				if(!empty($aReport['generate']['skipped'])) {
					$sMessage .= '<tr id="tbl_skipped" style="display: none;"><td colspan="2">';
					$sMessage .= $this->printMessageTable((array)$aReport['generate']['skipped']);
				}
				$sMessage .= '</td></tr>';
				$sMessage .= '<tr><th onclick="if($(\'tbl_errors\')) {$(\'tbl_errors\').toggle();}" style="cursor: pointer;">'.$this->t('Zuweisungen mit Fehler').' <i class="fa fa-chevron-down"></i></th><td style="text-align: right;">'.(int)count((array)$aReport['generate']['error']).'</td></tr>';
				if(!empty($aReport['generate']['error'])) {
					$sMessage .= '<tr id="tbl_errors" style="display: none;"><td colspan="2">';
					$sMessage .= $this->printMessageTable((array)$aReport['generate']['error']);
				}
				$sMessage .= '</td></tr>';
				$sMessage .= '<tr><th>'.$this->t('Neue Einträge').'</th><td style="text-align: right;">'.(int)$aReport['generate']['entry'].'</td></tr>';
				$sMessage .= '<tr><th>'.$this->t('Nicht berechnete Einträge').'</th><td style="text-align: right;">'.(int)$aReport['generate']['non_calculate'].'</td></tr>';
				$sMessage .= '</table>';

				$aTransfer = array(
					'action' => 'openDialog',
					'data' => array(
						'id' => 'reload_feedback',
						'title' => $this->t('Erfolgreiche Aktualisierung'),
						'html' => $sMessage,
						'width' => 1000,
						'height' => 800
					),
					'load_table' => true
				);

			} catch(\Ts\Handler\AccommodationProvider\LockException $e) {

				$aTransfer = array(
					'action' => 'showError',
					'error' => array(
						$this->t('Es ist ein Fehler aufgetreten'),
						$this->t('Das Aktualisieren ist durch einen anderen Vorgang blockiert. Bitte warten Sie einen Moment und versuchen es erneut!')
					)
				);

			}

			echo json_encode($aTransfer);
		
		} elseif(
			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'accommodation_payment'
		) {

			$this->savePayment($_VARS);

		} else {
			
			parent::switchAjaxRequest($_VARS);

		}

	}

	protected function printMessageTable(array $aMessageArray) {
		
		$sTable = '<table class="table" style="width: 100%;">
			<tr>
				<th style="width: 100px;">'.$this->t('Kundennummer').'</th>
				<th style="width: 140px;">'.$this->t('Kunde').'</th>
				<th style="width: 140px;">'.$this->t('Anbieter').'</th>
				<th style="width: 150px;">'.$this->t('Abrechnungskategorie').'</th>
				<th style="width: 150px;">'.$this->t('Zeitraum').'</th>
				<th style="width: auto;">'.$this->t('Meldung').'</th>
			</tr>
			';

		foreach($aMessageArray as $aMessages) {
			if(!empty($aMessages)) {
				foreach($aMessages as $aMessage) {
					$sTable .= '<tr>
						<td>'.$aMessage['customer_number'].'</td>
						<td>'.$aMessage['customer_name'].'</td>
						<td>'.$aMessage['provider'].'</td>
						<td>'.$aMessage['payment_category'].'</td>
						<td>'.$aMessage['from'].' - '.$aMessage['until'].'</td>
						<td>'.$this->t($aMessage['message']).'</td>
					</tr>
					';
				}
			}
		}

		$sTable .= '</table>';
		
		return $sTable;
	}
	
	/**
	 * @return array
	 */
	static public function getListWhere() {

		if(self::$oSchool instanceof \Ext_Thebing_School) {
			$oSchool = self::$oSchool;
		} else {
			$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		}

		$aWhere['ts_ij.school_id'] = (int)$oSchool->id;

		return $aWhere;
	}

	/**
	 * Ermittelt die Daten des Payments per Listenquery
	 * 
	 * @param array|int $mSelectedIds
	 * @param boolean $bFirst
	 * @return array
	 */
	protected function getPaymentData($mSelectedIds, $bFirst=false) {

		$sView = $this->_oGui->sView;
		$this->_oGui->sView = 'single';
		
		$mSelectedIds = (array)$mSelectedIds;
		
		$aSelectedItemData = $this->getTableQueryData(array(), array(), $mSelectedIds, true);
		$aSelectedItems = $aSelectedItemData['data'];
	
		if($bFirst === true) {
			$aSelectedItems = reset($aSelectedItems);
		}

		$this->_oGui->sView = $sView;
		
		return $aSelectedItems;
	}

	/**
	 * Ermittelt aus den übergebenen SelectedIds per groupby die einzelnen Payments, falls Gruppierung vorlag
	 * @param array $aSelectedIds
	 * @return array
	 */
	protected function expandSelectedIdsByGrouping(array $aSelectedIds) {

		$aReturnIds = array();
		foreach($aSelectedIds as $iSelectedId) {
			$oPayment = \Ts\Entity\AccommodationProvider\Payment::getInstance($iSelectedId);
			$aPayments = \Ts\Entity\AccommodationProvider\Payment::getRepository()->findBy(array('groupby' => $oPayment->groupby));
			foreach($aPayments as $oPayment) {
				$aReturnIds[] = $oPayment->id;
			}
		}

		return $aReturnIds;
	}

	/**
	 * 
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @return \Ext_Gui2_Dialog
	 */
	protected function getPaymentDialog(&$oDialog, $aSelectedIds = array()) {

		$aSelectedIds = $this->expandSelectedIdsByGrouping($aSelectedIds);

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		$oClient = $oSchool->getClient();
		$iCurrencySchoolId = $oSchool->getCurrency();
		$iCurrencyAccommodationId = $oSchool->getAccommodationCurrency();
		$aMethods = $oSchool->getPaymentMethodList(true);
		$iDefaultMethod = 0;
		$aTemplates = \Ext_Thebing_Pdf_Template_Search::s('document_accommodation_payment', $oSchool->getLanguage(), $oSchool->id, null, true);
		$aTemplates = \Ext_TC_Util::addEmptyItem($aTemplates);

		$oDialog = $this->_oGui->createDialog($this->t('Bezahlen'), $this->t('Bezahlen'));
		$oDialog->bBigLabels = true;
		$oDialog->width = 950;
		$oDialog->sDialogIDTag	= 'ACCOMMODATION_PAYMENT';

		$oDivRow = $oDialog->createRow( $this->t('Datum'), 'calendar',	array('db_column' => 'date', 'db_alias' => '', 'format' => new \Ext_Thebing_Gui2_Format_Date(), 'required' => 1));
		$oDialog->setElement($oDivRow);
		$oDivRow = $oDialog->createRow( 
										$this->t('Methode'), 
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
		$oDivRow = $oDialog->createRow( $this->t('Kommentar'), 'textarea',	array('db_column' => 'comment', 'db_alias' => ''));
		$oDialog->setElement($oDivRow);
		$oDialog->setElement($oDialog->createRow($this->t('Template'), 'select', array(
			'db_column' => 'template_id',
			'select_options' => $aTemplates
		)));

		$fAmountTotal = 0;

		// Damit die Items chronologisch sind, müssen die IDs sortiert werden
		sort($aSelectedIds, SORT_NUMERIC);

		$aSelectedItems = $this->getPaymentData($aSelectedIds);

		// Ausgewählte Unterkunftsanbieter gruppieren
		$aProviders = array();
		foreach((array)$aSelectedItems as $aSelectedItem) {
			$aProviders[$aSelectedItem['accommodation_provider_id']][] = $aSelectedItem;
		}

		foreach((array)$aProviders as $iAccommodationId => $aProviderPayments) {

			$oDummy = null;
			$aFormatDataFirst = reset($aProviderPayments);
			$oAccommodationFormat = new \Ext_Thebing_Gui2_Format_Accommodation_Provider();
			$sTitel = $oAccommodationFormat->format($iAccommodationId, $oDummy, $aFormatDataFirst);

			$oH3 = new \Ext_Gui2_Html_H4();
			$oH3->setElement($sTitel);
			$oDialog->setElement($oH3);

			foreach($aProviderPayments as $aProviderPayment) {

				$aFormatData = $aProviderPayment;
				$oWeekFormat = new PaymentPeriodFormat();
				$oNameFormat = new \Ext_Gui2_View_Format_Name('customer_firstname', 'customer_lastname');

				$sLabel = '';
				
				if($aProviderPayment['type'] === 'additionalservice') {
					$additional = json_decode($aProviderPayment['additional'], true);
					$additionalService = \Ext_Thebing_School_Additionalcost::getInstance($additional['additionalservice_id']);
					$sLabel .= $additionalService->getName();
				} else {
					$sLabel .= $oWeekFormat->format($aFormatData['from'], $oDummy, $aFormatData);
				}

				if(!empty($sLabel)) {
					$sLabel .= '<br />';
				}
				$sLabel .= $oNameFormat->format($oDummy, $oDummy, $aFormatData);

				$fAmount = $aProviderPayment['amount'];
				$fAmountTotal += $fAmount;

				// Betragszeile
				$aData = array();
				$aData['db_column_1']			= 'amount';
				$aData['db_column_2']			= 'amount_school';
				$aData['db_column_currency']	= 'payment_currency_id';
				$aData['db_alias']				= (int)$aProviderPayment['id'];
				$aData['school_id']				= $oSchool->id;
				$aData['format']				= new \Ext_Thebing_Gui2_Format_Amount();
				$aData['amount']				= $fAmount;
				$aData['currency_id']			= $iCurrencyAccommodationId;
				$aData['school_currency_id']	= $iCurrencySchoolId;

				$oAmountDiv	= \Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, $sLabel, false, true);
				$oDialog->setElement($oAmountDiv);

				// Zusatzzeile
				$aData = array();
				$aData['db_column_1']			= 'additional][amount]['.(int)$aProviderPayment['id'].'][';
				$aData['db_column_2']			= 'additional][amount_school]['.(int)$aProviderPayment['id'].'][';
				$aData['db_column_currency']	= 'additional][payment_currency_id';
				$aData['db_alias']				= '';
				$aData['db_alias_currency']		= '['.(int)$aProviderPayment['id'].'][]';
				$aData['school_id']				= $oSchool->id;
				$aData['format']				= new \Ext_Thebing_Gui2_Format_Amount();
				$aData['amount']				= 0;
				$aData['currency_id']			= $iCurrencyAccommodationId;
				$aData['school_currency_id']	= $iCurrencySchoolId;

				$oDivLabel = new \Ext_Gui2_Html_Div();
				$oDivLabel->class = 'input-group currency_amount_row_label_input';
				$oInput = new \Ext_Gui2_Html_Input();
				$oInput->class = "txt form-control";
				$oInput->placeholder = $this->_oGui->t('Zusatzposition');
				$oInput->style = "float:left;";
				$oInput->name = "save[additional][comment][".(int)$aProviderPayment['id']."][]";
				$oDivLabel->setElement($oInput);

				$oInputGroupBtn = new \Ext_Gui2_Html_Span();
				$oInputGroupBtn->class = 'input-group-btn';
				$oButton = new \Ext_Gui2_Html_Button();
				$oButton->class = 'btn btn-sm';
				$oButton->title = $this->_oGui->t('Weitere Zusatzpositionen');
				$oButton->setElement('<i class="fa fa-plus"></i>');
				$oButton->onclick="aGUI['".$this->_oGui->hash."'].copyGuiRow(this); aGUI['".$this->_oGui->hash."'].checkPaymentCurrencyCallback(); return false;";
				$oInputGroupBtn->setElement($oButton);
				$oDivLabel->setElement($oInputGroupBtn);

				$oAmountDiv	= \Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, $oDivLabel, false, true);
				$oDialog->setElement($oAmountDiv);

				$oDivRow = $oDialog->createRow($this->t('Kommentar'), 'textarea',	array('name' => 'save[single_payment_note]['.(int)$aProviderPayment['id'].']'));
				$oDialog->setElement($oDivRow);

				$oHr = new \Ext_Gui2_Html_Hr();
				$oDialog->setElement($oHr);
			}
		}

		$aData = array();
		$aData['db_column_1']			= 'sum_amount';
		$aData['db_column_2']			= 'sum_amount_school';
		$aData['db_column_currency']	= 'sum_payment_currency_id';
		$aData['school_id']				= $oSchool->id;
		$aData['format']				= new \Ext_Thebing_Gui2_Format_Amount();
		$aData['amount']				= $fAmountTotal;
		$aData['currency_id']			= $iCurrencyAccommodationId;
		$aData['school_currency_id']	= $iCurrencySchoolId;
		$aData['disable_all']			= 1;
		$aData['class_name_from']		= 'currency_sum_row_input_from';
		$aData['class_name_to']			= 'currency_sum_row_input_to';

		// SUMME
		$oAmountDiv	= \Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, '', false, true);
		$oDialog->setElement($oAmountDiv);
				
		return $oDialog;

	}
	
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {
		global $_VARS;

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		$sDescription	= $this->_oGui->gui_description;

		// get dialog object
		switch($sIconAction) {
			case 'accommodation_payment':

				$oDialog = $this->getPaymentDialog($oDialog, $aSelectedIds);

				break;
			default :
				$oDialog = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
				break;
		}

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		return $aData;
	}
	
	public function savePayment($_VARS) {
		global $user_data;

		$iTemplateId = (int)$_VARS['save']['template_id'];
		$sDialogIDTag = 'ACCOMMODATION_PAYMENT';
		$_VARS['id'] = (array)$_VARS['id'];
		$_VARS['id'] = array_unique($_VARS['id']);

		$sDialogIDTag = $sDialogIDTag.implode('_', $_VARS['id']);

		$oGroupingNumberRange = AccommodationPaymentGrouping::getObject();
		if ($oGroupingNumberRange && !$oGroupingNumberRange->acquireLock()) {
			$aTransfer = [
				'action' => 'saveDialogCallback',
				'error' => [\Ext_Thebing_Document::getNumberLockedError()],
				'data' => ['id' => $sDialogIDTag]
			];
			echo json_encode($aTransfer);
			die();
		}

		$aErrors = array();
		$aHints	= array();
		$aTempSaveData = array();

		// Gruppierungen pro Unterkunftsanbieter
		$aGroupings = array();
		$aGroupingPayments = array();
		$aGeneratedGroupings = array();

		/* @var $aPayedAccommodationProviderPayments \Ts\Entity\AccommodationProvider\Payment[] */
		$aPayedAccommodationProviderPayments = array();

		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date();
		$oDummy = null;
		$sDate = $oDateFormat->convert($_VARS['save']['date'], $oDummy);

		\DB::begin('accommodationprovider_save_payment');

		foreach((array)$_VARS['save']['amount'] as $iAccommodationPaymentId => $mAmount){

			$aSelectedItems = $this->getPaymentData(array($iAccommodationPaymentId));
			$aData = reset($aSelectedItems);

			/* @var $oAccommodationProviderPayment \Ts\Entity\AccommodationProvider\Payment */
			$oAccommodationProviderPayment = \Ts\Entity\AccommodationProvider\Payment::getInstance($aData['id']);

			$oAccommodation = \Ext_Thebing_Accommodation::getInstance($aData['accommodation_provider_id']);

			/**
			 * Prüfen, ob das Payment für diese Zuweisung das erste ist, oder einer vorhandenen Zahlung direkt folgt
			 * Es darf keine Lücken in den Zahlungen geben
			 *
			 * @var $oAllocation \Ext_Thebing_Accommodation_Allocation
			 */
			$oAllocation = $oAccommodationProviderPayment->getJoinedObject('allocation');
			
			$dLatestPayment = $oAllocation->getLatestSavedPaymentDate();
			$dAllocationFrom = new \Core\Helper\DateTime($oAllocation->from);
			
			$dCompareDate = max($dLatestPayment, $dAllocationFrom);
			
			$dPaymentFrom = new \Core\Helper\DateTime($aData['from']);

			if(
				$oAccommodationProviderPayment->payment_category_id !== null &&
				$dCompareDate != $dPaymentFrom
			) {
				$oWeekFormat = new PaymentPeriodFormat();
				$sPeriodLabel = $oWeekFormat->format($aData['from'], $oDummy, $aData);
				
				$oNameFormat = new \Ext_Gui2_View_Format_Name('customer_firstname', 'customer_lastname');
				$sNameLabel = $oNameFormat->format($oDummy, $oDummy, $aData);

				// Key zusammenbauen, damit man nachher die Daten wiederhat, wegen den unflexiblen GUI2-Fehlern
				$sErrorKey = md5($oAccommodation->getName().'_'.$sPeriodLabel.'_'.$sNameLabel);
				$aErrors[$sErrorKey] = 'UNPAYED_EARLIER_ENTRIES';
				$this->aErrorData[$sErrorKey] = [$oAccommodation->getName(), $sPeriodLabel, $sNameLabel];

				continue;
			}

			// Kommentar ====================================================
			$aComment = array();

			// Unterkunft
			$aComment[] = $oAccommodation->getName();

			// Unterkunft in das Grouping einfügen
			if(!isset($aGroupings[$oAccommodation->id])) {
				$aGroupings[$oAccommodation->id] = $oAccommodation;
			}

			// Schüler
			if(
				!empty($aData['lastname'])&&
				!empty($aData['firstname'])
			){
				$aComment[] = \Ext_Thebing_Gui2_Format_CustomerName::manually_format($aData['lastname'], $aData['firstname']);
			}

			// Zeitraum
			$aTemp = array();
			$aTemp['cost_type'] = $aData['cost_type'];
			
			$oWeekFormat = new PaymentPeriodFormat();
			$sDateComment = $oWeekFormat->format($aData['from'], $oDummy, $aData);
			
			$aComment[] = $sDateComment;

			$sComment = implode(' - ', $aComment);

			$sCommentFinal	= $sComment;

			if(!empty($_VARS['save']['comment'])){
				$sCommentFinal	.= ' - ' . $_VARS['save']['comment'];
			}

			$mAmountSchool		= $_VARS['save']['amount_school'][$aData['id']];
			$iCurrencyId		= $_VARS['save']['payment_currency_id'][$aData['id']];
			$sSinglePaymentNote = $_VARS['save']['single_payment_note'][$aData['id']];

			$oSchool = \Ext_Thebing_School::getInstance($aData['school_id']);
			$iSchoolCurrencyId = $oSchool->getCurrency();

			// Da deaktiviert -> keine daten übermittelt
			if($iCurrencyId <= 0){
				$iCurrencyId = $oSchool->getAccommodationCurrency();
			}

			// Wenn fest (monatlich), dann darf kein Kunde gespeichert werden
			$iCustomerId = $aData['customer_id'];
			if($aData['type'] === 'month') {
				$iCustomerId = 0;
			}

			$fAmount			= \Ext_Thebing_Format::convertFloat($mAmount);
			$fAmountSchool		= \Ext_Thebing_Format::convertFloat($mAmountSchool);

			$oPayment = new \Ext_Thebing_Accommodation_Payment();
			$oPayment->accommodation_id		= (int)$aData['accommodation_provider_id'];
			$oPayment->inquiry_accommodation_id	= (int)$aData['inquiry_accommodation_id'];
			$oPayment->allocation_id = (int)$aData['allocation_id'];

			$oPayment->timepoint = $aData['from'];
			$oPayment->until = $aData['until'];
			$oPayment->payment_type			= $aData['type'];
			$oPayment->select_type			= 'week';
			$oPayment->select_value			= $aData['from'];
//			$oPayment->current				= $aData['current'];
//			$oPayment->total				= $aData['total'];
//			$oPayment->nights				= $aData['nights'];
//			$oPayment->single_amount		= $aData['single_amount'];

			$oPayment->comment				= $sCommentFinal;
			$oPayment->comment_single		= $_VARS['save']['comment'];
			$oPayment->payment_note			= $sSinglePaymentNote;
			$oPayment->method_id			= $_VARS['save']['method_id'];
			$oPayment->amount				= (float)$fAmount;
			$oPayment->amount_school		= (float)$fAmountSchool;
			$oPayment->payment_currency_id	= (int)$iCurrencyId;
			$oPayment->school_currency_id	= $iSchoolCurrencyId;

			$oPayment->date					= (string)$sDate;
			$oPayment->customer_id			= $iCustomerId;

			$oPayment->room_id				= $aData['allocated_room'];
			$oPayment->meal_id				= $aData['meal_id'];

			$oPayment->cost_type			= $aData['cost_type'];
			$oPayment->inquiry_id			= $aData['inquiry_id'];
			$oPayment->costcategory_id		= $aData['cost_category_id'];

			// Key der Gui2-Kodierung nur zwischenspeichern
			$oPayment->iSelectedId			= $aData['id'];

			$oPayment->additional = $oAccommodationProviderPayment->additional;
			
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

				$aPayedAccommodationProviderPayments[] = $oAccommodationProviderPayment;
				$aTempSaveData[] = $oPayment;
				$aGroupingPayments[$oAccommodation->id][] = $oPayment;

				if(empty($aErrors)) {

					foreach((array)$_VARS['save']['additional']['amount'][$aData['id']] as $iAdditionalKey => $sAmount) {

						$fAdditionalAmount = \Ext_Thebing_Format::convertFloat($sAmount);

						if($fAdditionalAmount <= 0) {
							continue;
						}

						$sCommentFinal = $sComment;

						if(!empty($_VARS['save']['additional']['comment'][$aData['id']][$iAdditionalKey])){
							$sCommentFinal .= ' - ' . $_VARS['save']['additional']['comment'][$aData['id']][$iAdditionalKey];
						}


						$fAdditionalAmountSchool = $_VARS['save']['additional']['amount_school'][$aData['id']][$iAdditionalKey];

						$fAdditionalAmountSchool	= \Ext_Thebing_Format::convertFloat($fAdditionalAmountSchool);

						## Zusatzpositionen speichern ##
						$oAdditionalPayment = new \Ext_Thebing_Accommodation_Payment();
						$oAdditionalPayment->accommodation_id = $oPayment->accommodation_id;
						$oAdditionalPayment->inquiry_accommodation_id = $oPayment->inquiry_accommodation_id;
						$oAdditionalPayment->allocation_id = $oPayment->allocation_id;

						$oAdditionalPayment->timepoint = $oPayment->timepoint;
						$oAdditionalPayment->until = $oPayment->until;

						$oAdditionalPayment->method_id			= $oPayment->method_id;
						$oAdditionalPayment->payment_currency_id= $oPayment->payment_currency_id;
						$oAdditionalPayment->school_currency_id	= $oPayment->school_currency_id;
						$oAdditionalPayment->payment_type		= $oPayment->payment_type;
						$oAdditionalPayment->date				= $oPayment->date;
						$oAdditionalPayment->comment			= $sCommentFinal;
						$oAdditionalPayment->comment_single		= $_VARS['save']['comment'];
						$oAdditionalPayment->payment_note		= $sSinglePaymentNote;
						$oAdditionalPayment->amount				= $fAdditionalAmount;
						$oAdditionalPayment->amount_school		= $fAdditionalAmountSchool;
						$oAdditionalPayment->parent_id			= $oPayment->id;
						$oAdditionalPayment->room_id			= $oPayment->room_id;
						$oAdditionalPayment->meal_id			= $oPayment->meal_id;
						$oAdditionalPayment->select_type		= $oPayment->select_type;
						$oAdditionalPayment->select_value		= $oPayment->select_value;
//						$oAdditionalPayment->current			= $oPayment->current;
//						$oAdditionalPayment->total				= $oPayment->total;
//						$oAdditionalPayment->nights				= $oPayment->nights;
//						$oAdditionalPayment->single_amount		= $oPayment->single_amount;
						$oAdditionalPayment->cost_type			= $oPayment->cost_type;
						$oAdditionalPayment->inquiry_id			= $oPayment->inquiry_id;
						$oAdditionalPayment->customer_id		= $oPayment->customer_id;
						$oAdditionalPayment->costcategory_id 	= $oPayment->costcategory_id;
						$oAdditionalPayment->iSelectedId		= $aData['id'];
						$oAdditionalPayment->additional = $oPayment->additional;

						$mValidate		= $oAdditionalPayment->validate();
						if(!isset($_VARS['ignore_errors'])){
							$mValidateHint	= $oAdditionalPayment->checkIgnoringErrors();
						}else{
							$mValidateHint	= true;
						}

						if(
							$mValidate !== true || 
							$mValidateHint !== true
						) {
							if($mValidate !== true){
								$aErrors = array_merge($aErrors, (array)$mValidate);
							}
							if($mValidateHint !== true){
								$aHints = array_merge($aHints, (array)$mValidateHint);
							}
						} else {
							$oAdditionalPayment->save();
							$aTempSaveData[] = $oAdditionalPayment;
							$aGroupingPayments[$oAccommodation->id][] = $oAdditionalPayment;
						}
					}
				}
			}

		}

		// Gruppierung schreiben pro Unterkunft
		if(
			empty($aErrors) && 
			empty($aHints)
		) {

			foreach($aGroupings as $oAccommodation) {
				/** @var $oAccommodation \Ext_Thebing_Accommodation */

				/** @var $aAccommodationPayments \Ext_Thebing_Accommodation_Payment[] */
				$aAccommodationPayments = $aGroupingPayments[$oAccommodation->id];
				$fGroupingAmount = 0;
				$fGroupingAmountSchool = 0;
				
				// Schule wird nur für Sprache verwendet
				$oSchool = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();

				// Gesamtbeträge addieren
				foreach($aAccommodationPayments as $oPayment) {
					$fGroupingAmount = (float)bcadd((string)$fGroupingAmount, (string)$oPayment->amount);
					$fGroupingAmountSchool = (float)bcadd((string)$fGroupingAmountSchool, (string)$oPayment->amount_school);
				}

				$oLastPayment = end($aAccommodationPayments);

				$oGrouping = new \Ext_TS_Accounting_Provider_Grouping_Accommodation();
				$oGrouping->accommodation_id = $oAccommodation->id;
				$oGrouping->payment_method_id = $_VARS['save']['method_id'];
				$oGrouping->template_id = $iTemplateId;
				$oGrouping->date = $sDate;
				$oGrouping->amount = $fGroupingAmount;
				$oGrouping->amount_currency_id = $oLastPayment->payment_currency_id;
				$oGrouping->amount_school = $fGroupingAmountSchool;
				$oGrouping->amount_school_currency_id = $oLastPayment->school_currency_id;
				$oGrouping->save();

				$aGeneratedGroupings[] = $oGrouping;

				// Payments Gruppierung zuweisen
				// Wird für Platzhalter bereits hier benötigt
				foreach($aAccommodationPayments as $oPayment) {
					$oPayment->grouping_id = $oGrouping->id;
					$oPayment->save();
				}

				// PDF (pro Gruppierung) generieren, wenn Template vorhanden
				if($iTemplateId > 0) {
					$oTemplate = \Ext_Thebing_Pdf_Template::getInstance($iTemplateId);

					// Daten, die direkt in die Platzhalterklasse geschrieben werden unter dem Key »grouping_data«
					$aGroupingDataPlaceholder = array(
						'provider_payment_overview' => $this->getDataForPaymentOverviewPlaceholder($aAccommodationPayments, $fGroupingAmount, $oSchool->language)
					);

					$sFilePath = $oGrouping->createPdf($oTemplate, $aGroupingDataPlaceholder);
					$sFilePath = str_replace(\Util::getDocumentRoot().'storage', '', $sFilePath);

					$oGrouping->file = $sFilePath;
					$oGrouping->save();

					$aGeneratedPdfs[] = $sFilePath;
				}

			}
		}

		$aAction		= array('action' => 'accommodation_payment');

		$aErrorData		= (array)$this->_getErrorData($aErrors, $aAction, 'error', true);
		$aErrorDataHint = (array)$this->_getErrorData($aHints, $aAction, 'hint', true);

		$aErrorsAll		= array_merge($aErrorData,$aErrorDataHint);

		$aTransfer = array();
		if(empty($aErrorsAll)){

			$aTransfer['action'] = 'closeDialogAndReloadTable';

			// Alle Payment die bezahlt wurden aus der Tabelle löschen
			foreach($aPayedAccommodationProviderPayments as $oPayedAccommodationProviderPayment) {
				$oPayedAccommodationProviderPayment->delete();
			}
			
			// Dialog zum Öffnen der generierten Dokumente anzeigen
			$aGeneratedPdfs = array();
			foreach($aGeneratedGroupings as $oGrouping) {
				if(!empty($oGrouping->file)) {
					$oAccommodation = \Ext_Thebing_Accommodation::getInstance($oGrouping->accommodation_id);
					$sUrl = '/storage/download'.$oGrouping->file;
					$aGeneratedPdfs[] = '<a target="_blank" href="'.$sUrl.'">'.$oAccommodation->getName().'</a>';
				}
			}

			if(!empty($aGeneratedPdfs)) {
				$aTransfer['success_message'] = $this->t('Die Dokumente wurden erfolgreich angelegt. Bitte klicken Sie hier, um ein PDF mit allen Positionen anzuzeigen.');
				$aTransfer['success_message'] .= '<br /><br />'.join(', ', $aGeneratedPdfs);
			}

		} else {
			$aTransfer['action'] = 'saveDialogCallback';
		}

		if(!empty($aErrorsAll)) {
			\DB::rollback('accommodationprovider_save_payment');
		} else {
			\DB::commit('accommodationprovider_save_payment');
		}

		if ($oGroupingNumberRange && $oGroupingNumberRange->exist()) {
			$oGroupingNumberRange->removeLock();
		}

		$aTransfer['error']			= $aErrorsAll;

		$aTransfer['data']			= array();
		$aTransfer['data']['id']	= $sDialogIDTag;

		if(!empty($aErrorDataHint)){
			$aTransfer['data']['show_skip_errors_checkbox'] = 1;
		}

		echo json_encode($aTransfer);
		$this->_oGui->save();
		die();

	}

	/**
	 * Generiert ein Array mit formatierten Daten aus der GUI-Liste
	 * für den Platzhalter »provider_payment_overview«.
	 *
	 * @see \Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable
	 * @param \Ext_Thebing_Accommodation_Payment[] $aPayments
	 * @param float $fSum
	 * @return array
	 */
	public function getDataForPaymentOverviewPlaceholder($aPayments, $fSum, $sLanguage = '') {

		$aData = array();
		$oDummy = null;

		// Die Werte müssen neu formatiert werden für das PDF, also entsprechende Klassen holen
		$oFormatAmount = new \Ext_Thebing_Gui2_Format_Amount();

		foreach($aPayments as $oPayment) {

			// Daten aus enkodierter GUI holen
			$aDecodedData = $this->getPaymentData(array($oPayment->iSelectedId), true);

			$oCustomer = \Ext_TS_Inquiry_Contact_Traveller::getInstance($oPayment->customer_id);
			$oFormatTimeframe = new PaymentPeriodFormat();
			//$oFormatSingleAmount = new \Ext_Thebing_Gui2_Format_Accounting_Accommodation_Payment_Singleamount('currency_id', $sLanguage);

			// Kunde darf nur angezeigt werden, wenn es sich um keine feste (monatliche) Bezahlung handelt
			if($aDecodedData['type'] !== 'month') {
				$sCustomerName = $oCustomer->getName();
				$sCustomerNumber = $oCustomer->getCustomerNumber();
			} else {
				$sCustomerName = $sCustomerNumber = '';
			}

			$aResultData = array(
				'currency_id' => $oPayment->payment_currency_id,
				'cost_type' => $oPayment->cost_type
			);

			// Spalte: Betrag
			$sAmount = $oFormatAmount->format($oPayment->amount, $oDummy, $aResultData);

			$aData['rows'][] = array(
				'timeframe' => $oFormatTimeframe->format($aDecodedData['from'], $oDummy, $aDecodedData),
				'customer_number' => $sCustomerNumber,
				'customer_name' => $sCustomerName,
				'service' => $this->_getPaymentOverviewServiceValue($aDecodedData),
				// Diese Information hab ich nicht eindeutig zur Verfügung
				//'per_night_month' => '',//$oFormatSingleAmount->format($oPayment->single_amount, $oDummy, $aResultData),
				'amount' => $sAmount,
			);

		}

		// $aResultData: Da eine Gruppierung immer die selbe Währung hat, kann hier weiterhin $aResultData verwendet werden
		$aData['amount_sum'] = $oFormatAmount->format($fSum, $oDummy, $aResultData);

		return $aData;
	}

	/**
	 * Baut den Wert für die Spalte »Leistung« für den Platzhalter »provider_payment_overview«
	 * @param $aDecodedData
	 * @return string
	 */
	protected function _getPaymentOverviewServiceValue($aDecodedData) {
		
		$iCategoryId = $aDecodedData['default_category_id'];
		$aAccommodationCategoryIds = (array)explode(',', $aDecodedData['accommodation_category_ids']);
				
		if(
			in_array($aDecodedData['booked_category'], $aAccommodationCategoryIds)
		) {
			$iCategoryId = $aDecodedData['booked_category'];
		}

		$oAccommodationCategory = \Ext_Thebing_Accommodation_Category::getInstance($iCategoryId);
		$oMealCategory = \Ext_Thebing_Accommodation_Meal::getInstance($aDecodedData['meal_id']);
		$oRoomCategory = \Ext_Thebing_Accommodation_Roomtype::getInstance($aDecodedData['allocated_roomtype_id']);

		$sValue = $oAccommodationCategory->getShortName();
		$sValue .= ' / ';
		$sValue .= $oRoomCategory->getShortName();
		$sValue .= ' / ';
		$sValue .= $oMealCategory->getName('', true);

		return $sValue;

	}

	/**
	 * @inheritdoc
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if($sError === 'UNPAYED_EARLIER_ENTRIES') {

			$sMessage = 'Die Zahlung für die Unterkunft "{provider}" im Zeitraum "{period}" für den Schüler "{student}" kann nicht gespeichert werden, da es vorherige, ungezahlte Einträge gibt.';
			$sMessage = str_replace(['{provider}', '{period}', '{student}'], $this->aErrorData[$sField], $this->t($sMessage));
			return $sMessage;

		} else {
			return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

	}

	public static function getAccommodationCategories() {
		return \Ext_Thebing_Accommodation_Category::getSelectOptions(true, null, null, true);
	}

}
