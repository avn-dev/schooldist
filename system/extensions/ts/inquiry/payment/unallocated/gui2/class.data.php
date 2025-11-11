<?php

class Ext_TS_Inquiry_Payment_Unallocated_Gui2_Data extends Ext_Gui2_Data {

	/**
	 * @return array
	 */
	public static function getOrderby() {
		return ['created' => 'DESC'];
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Transaktion').': {transaction_code}');
		$oDialog->width = 750;
		$oDialog->height = 600;
		$oDialog->bCheckLock = false;

		if(Ext_Thebing_System::isAllSchools()) {
			$aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true, [$oSchool->id]);
		}

		$oDialog->setElement($oDialog->createRow($oGui->t('Transaktionscode'), 'input', array(
			'db_column' => 'transaction_code',
			'disabled' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_column' => 'comment'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Vorname'), 'input', array(
			'db_column' => 'firstname',
			'readonly' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Nachname'), 'input', array(
			'db_column' => 'lastname',
			'readonly' => true
		)));

//		$oDialog->setElement($oDialog->createRow($oGui->t('Betrag'), 'input', array(
//			'db_column' => 'amount',
//			'disabled' => true,
//			'format' => new Ext_Thebing_Gui2_Format_Amount()
//		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezahlmethode'), 'select', array(
			'db_column' => 'payment_method_id',
			'select_options' => Util::addEmptyItem($aPaymentMethods),
			'required' => true,
			'skip_value_handling' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Mit Buchung verknüpfen'), 'autocomplete', array(
			'db_column' => 'autocomplete_inquiry_id',
			'autocomplete' => new Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry(\Ext_TS_Inquiry::TYPE_BOOKING_STRING),
			'required' => true,
			'skip_value_handling' => true
		)));

		return $oDialog;

	}

	public static function getSettingsDialog(Ext_Gui2 $gui2)
	{
		$dialog = $gui2->createDialog($gui2->t('Einstellungen'), $gui2->t('Einstellungen'));

		$settingsGui = new \Ext_TC_Config_Gui2(md5('unallocated_payments_config'), 'Ext_TC_Config_Gui2_Data');
		$settingsGui->gui_description = $gui2->gui_description;
		$settingsGui->gui_title = $gui2->t('Einstellungen');
		$settingsGui->include_jscolor = true;
		$settingsGui->setOption('right', 'thebing_accounting_assign_client_payments_config');

		$config = [];
		$config['openbanking.automatic.inquiries_not_older_than'] = array(
			'description' => $settingsGui->t('Automatische Zuweisung zu Buchungen nicht älter als X Monate'),
			'type' => 'input',
			'format' => new \Ext_Gui2_View_Format_Null(),
			'required' => false
		);

		$settingsGui->setConfigurations($config);
		$settingsGui->setWDBasic(\Ext_TS_Config::class);

		$dialog->setElement($settingsGui);

		return $dialog;
	}

	/**
	 * @inheritdoc
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		/** @var Ext_TS_Inquiry_Payment_Unallocated $oPayment */
		$oPayment = $this->_getWDBasicObject($aSelectedIds);

		// Bezahlmethode vorauswählen
		$aData =  parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);
		foreach($aData as &$aField) {
			if($aField['db_column'] === 'payment_method_id') {
				$aField['value'] = $oPayment->payment_method_id;
			}
		}

		return $aData;

	}

	/**
	 * @inheritdoc
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		if ($sAction === 'edit') {

			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, false);

			$iSelectedId = reset($aSelectedIds);

			$oPayment = Ext_TS_Inquiry_Payment_Unallocated::getInstance($iSelectedId);
			if(!$oPayment->exist()) {
				throw new RuntimeException('Payment does not exist!');
			}

			$oInquiry = null;
			if(!empty($aData['autocomplete_inquiry_id'])) {
				$oInquiry = Ext_TS_Inquiry::getInstance((int)$aData['autocomplete_inquiry_id']);
			}

			if(
				!$oInquiry ||
				!$oInquiry->exist()
			) {
				$aTransfer['error'][] = [
					'type' => 'error',
					'message' => $this->t('Bitte wählen Sie eine gültige Buchung aus.'),
					//'input' => [
					//	'dbcolumn' => 'autocomplete_inquiry_id'
					//]
				];
			} else {

				DB::begin(__METHOD__);

				try {
					$oPayment->createInquiryPayment($oInquiry, (int)$aData['payment_method_id']);
					DB::commit(__METHOD__);
					$aTransfer['action'] = 'closeDialogAndReloadTable';
				} catch (\LogicException $e) {
					DB::rollback(__METHOD__);
					$aTransfer['error'][] = [
						'type' => 'error',
						'message' => $this->getErrorMessage($e->getMessage(), '')
					];
				}

			}

			if(!empty($aTransfer['error'])) {
				array_unshift($aTransfer['error'], $this->t('Fehler beim Speichern'));
			}
		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

		return $aTransfer;

	}

	/**
	 * @inheritdoc
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) {

		if($sError === 'INQUIRY_HAS_NO_INVOICE') {
			$sErrorMessage = $this->t('Die ausgewählte Buchung hat keine Rechnung.');
		} else {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sErrorMessage;

	}

}
