<?php

/*
 * Klasse für die Stornobedingungen, ob storniert werden darf oder nicht
 */
class Ext_Thebing_Storno_Condition  { 

	/*
	 * Prüfen aller Bedingungen die erfüllt sein müssen damit storniert werden darf
	 */
	public static function check(&$oInquiry, $sCheck = 'all'){
		
		$aStornoCheck = array();
		$iErrorCounter = 0;

		// Transferanbieter prüfen ===============================================
		if(
			$sCheck == 'all' ||
			$sCheck == 'transfer'
		){
			$oTransferArrival		= $oInquiry->getTransfers('arrival');
			$oTransferDeparture		= $oInquiry->getTransfers('departure');
			$aTransfers = array();
			if(is_object($oTransferArrival)){
				$aTransfers[] = $oTransferArrival;
			}
			if(is_object($oTransferDeparture)){
				$aTransfers[] = $oTransferDeparture;
			}

			foreach((array)$aTransfers as $oTransfer){
				if(
					$oTransfer->provider_id > 0 &&
					$oTransfer->provider_type != ''
				){
					// Alle aktiven Zahlungen
					$aPayments = $oTransfer->getJoinedObjectChilds('accounting_payments_active');
					if(!empty($aPayments)){
						$aStornoCheck[$iErrorCounter]['description'] = $oTransfer->getName();
						$aStornoCheck[$iErrorCounter]['type'] = 'transfer';
						$iErrorCounter++;
					}
				}
			}
		}
		
		
		// Matching prüfen =========================================================
		if(
			$sCheck == 'all' ||
			$sCheck == 'accommodation'
		){
			$aInquiryAccommodations = $oInquiry->getAccommodations(true);
		
			foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){

				$aPayments = $oInquiryAccommodation->checkPaymentStatus();

				// Alle Bezahlungen anzeigen
				foreach((array)$aPayments as $oPayment){
					$aStornoCheck[$iErrorCounter]['description'] = $oPayment->comment;
					$aStornoCheck[$iErrorCounter]['type'] = 'accommodation';
					$iErrorCounter++;
				}

			}
		}

		
		// Unterricht prüfen =======================================================
		if(
			$sCheck == 'all' ||
			$sCheck == 'course'
		){
			$aInquiryCourses = $oInquiry->getCourses(true);
		
			foreach((array)$aInquiryCourses as $oInquiryCourse){

				$aPayments = $oInquiryCourse->checkPaymentStatus();

				// Alle Bezahlungen anzeigen
				foreach((array)$aPayments as $oPayment){
					$aStornoCheck[$iErrorCounter]['description'] = $oPayment->comment;
					$aStornoCheck[$iErrorCounter]['type'] = 'course';
					$iErrorCounter++;
				}
			}
		}

		// Anwesenheiten prüfen =======================================================
		if (
			$sCheck == 'all' ||
			$sCheck == 'attendance'
		) {
			$attendances = Ext_Thebing_Tuition_Attendance::getActiveAttendances([
				['inquiry_id', '=', $oInquiry->id]
			]);
			$programServiceIds = $attendances->map(fn ($attendance) => $attendance->program_service_id)->unique();
			$programServices = \TsTuition\Entity\Course\Program\Service::query()->whereIn('id', $programServiceIds)->where('type', \TsTuition\Entity\Course\Program\Service::TYPE_COURSE)->get();
			foreach ($programServices as $programService) {
				$aStornoCheck[$iErrorCounter]['description'] = $programService->getService()->getName();
				$aStornoCheck[$iErrorCounter]['type'] = 'attendance';
				$iErrorCounter++;
			}
		}

		return $aStornoCheck;
	}
	
	/*
	 * Liefert den Fehler Dialog
	 */
	public static function getDialog($iSelectedID, $aData, &$oGui){

		$oDialog = $oGui->createDialog($oGui->t('Stornieren'), $oGui->t('Stornieren'));

		// Set ID
		$oDialog->id = 'STORNOERROR_' . (int)$iSelectedID;
		$oDialog->sDialogIDTag = 'STORNOERROR_';
		$oDialog->title = L10N::t('Stornieren', $oGui->gui_description);
		$oDialog->width = 700;
		$oDialog->height = 300;
		$oDialog->save_button = false;
		
		$aErrors = array();

		$sWarning = 'Der Schüler kann nicht storniert werden. Bitte löschen Sie zuvor alle getätigten Zahlungen oder eingetragene Anwesenheiten.';

		foreach((array)$aData as $aValue){
			switch($aValue['type']){
				case 'course':
					$aErrors['course'][] = $aValue;
					break;
				case 'accommodation':
					$aErrors['accommodation'][] = $aValue;
					break;
				case 'transfer':
					$aErrors['transfer'][] = $aValue;
					break;
				case 'attendance':
					$aErrors['attendance'][] = $aValue;
					break;
			}
		}

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->setElement(L10N::t($sWarning, $oGui->gui_description));

		foreach((array)$aErrors as $sType => $aValue){
			
			$sHeadline = '';
			switch($sType){
				case 'course':
					$sHeadline = L10N::t('Kurs', $oGui->gui_description);
					break;
				case 'accommodation':
					$sHeadline = L10N::t('Unterkunft', $oGui->gui_description);
					break;
				case 'transfer':
					$sHeadline = L10N::t('Transfer', $oGui->gui_description);
					break;
				case 'attendance':
					$sHeadline = L10N::t('Anwesenheit', $oGui->gui_description);
					break;
			}
			
			$oH3 = new Ext_Gui2_Html_H3();
			$oH3->setElement($sHeadline);
			$oDiv->setElement($oH3);
			
			$oUl = new Ext_Gui2_Html_Ul();
			foreach($aValue as $aErrorData){
				$oLi = new Ext_Gui2_Html_Li();
				$oLi->setElement($aErrorData['description']);
				$oUl->setElement($oLi);
			}

			
			$oDiv->setElement($oUl);
		}
		
		
		
		$oDialog->setElement($oDiv);

		return $oDialog;
	}
}
