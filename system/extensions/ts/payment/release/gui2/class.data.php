<?php

use Carbon\Carbon;

class Ext_TS_Payment_Release_Gui2_Data extends Ext_Thebing_Gui2_Data {
	use \TsAccounting\Traits\Gui2\TestExport;

	protected function confirmRelease($vars) {

		$paymentIds = (array)$vars['id'];

		DB::begin('create_payment_booking_stack');

		$errors = [];

		foreach($paymentIds as $paymentId) {

			$this->_getWDBasicObject($paymentId);

			try {

				$success = $this->oWDBasic->releasePayment();

			} catch (Ext_TS_Accounting_Bookingstack_Generator_Exception $exc) {

				// TODO das hier funktioniert noch nicht da es hier keinen Dialog für die Freigabe gibt. Dadurch sind Warnings hinfällig
				//if ($exc->isWarning()) {
				//	$errors[] = ['message' => $exc->getMessage(), 'type' => 'hint', 'code' => $exc->getKey()];
				//} else {
					$errors[] = ['message' => $exc->getMessage(), 'type' => 'error'];
					__pout($exc->getOptionalData());
				//}

				$success = false;
			}

			if($success !== true && !$success instanceof WDBasic) {

				if($this->oWDBasic->hasError()) {

					$errors[] = $this->_getErrorMessage($this->oWDBasic->getError(), '');

				} elseif($this->oWDBasic->hasHint()) {

					$errors[] = array(
						'message' => $this->_getErrorMessage($this->oWDBasic->getHint(), ''),
						'type' => 'hint',
					);

				}

			}

		}

		$transfer = [];

		if(!empty($errors)) {
			$transfer['action'] = 'showError';
			$transfer['error'] = $errors;
			DB::rollback('create_payment_booking_stack');
		} else {
			$transfer['action'] = 'loadTable';
			DB::commit('create_payment_booking_stack');
		}

		return $transfer;
	}

	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		$sErrorMessage = $sError;

		if ($sError === 'PAYMENT_RELEASED') {
			$sErrorMessage = $this->t('Zahlung wurde bereits freigegeben!');
		} else if($sError === 'DOCUMENT_NOT_RELEASED') {
			$sErrorMessage = $this->t('Die Rechnungen der Zahlung sind noch nicht freigegeben!');
		}

		return $sErrorMessage;
	}

	public static function getPaymentDateFilterDefaultDate() {

		$start = (new Carbon)->subWeek()->startOfWeek();
		$end = (new Carbon)->endOfWeek();

		return [
			'from' => sprintf('-P%dD', Carbon::now()->diff($start)->days),
			'until' => sprintf('P%dD', Carbon::now()->diff($end)->days)
		];
	}

	public static function getInboxes(){
		
		$oClient = Ext_Thebing_System::getClient();
		return $oClient->getInboxList(true, true);
	}

	public static function getEditorOptions(){
		
		$oClient = Ext_Thebing_Client::getInstance();
		return $oClient->getUsers(true, true);
	}

	public static function getAgencies(){
		
		$oClient = Ext_Thebing_Client::getInstance();

		return $oClient->getAgencies(true);
	}

	public static function getMethods(){

		if(Ext_Thebing_System::isAllSchools()) {

			return Ext_Thebing_Admin_Payment::getPaymentMethods(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();

			return $oSchool->getPaymentMethodList(true);
		}
	}

	public static function getWhere(){

		if(!Ext_Thebing_System::isAllSchools()) {
			$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

			return ['ip.school_id' => (int)$iSessionSchoolId];
		}
	}

}
