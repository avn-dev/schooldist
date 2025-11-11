<?
class Ext_Thebing_System_Checks_PaymentImport extends GlobalChecks {

	/* für externe server deaktiviert
	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'admin' || $user_data['name'] == 'wielath'){
			return true;
		}

		return false;
	}
	 */
	 
	
	
	public function executeCheck(){
		global $user_data, $_VARS;

		//vorgeben nicht im backend zu sein damit die Schul zugriffsprüfung nicht greift!!!
		$user_data['cms'] = 0;

		@ini_set("memory_limit", '2048M');

		/*
		 *
		 * wiederherstellung
		DROP TABLE `kolumbus_inquiries_payments`;
		DROP TABLE `kolumbus_inquiries_payments_agencypayments`;
		DROP TABLE `kolumbus_inquiries_payments_documents`;
		DROP TABLE `kolumbus_inquiries_payments_items`;
		DROP TABLE `kolumbus_inquiries_payments_overpayment`;



		RENAME TABLE `__2010_08_27_kolumbus_inquiries_payments` TO `kolumbus_inquiries_payments`;
		RENAME TABLE `__2010_08_27_kolumbus_inquiries_payments_agencypayments` TO `kolumbus_inquiries_payments_agencypayments`;
		RENAME TABLE `__2010_08_27_kolumbus_inquiries_payments_documents` TO `kolumbus_inquiries_payments_documents`;
		RENAME TABLE `__2010_08_27_kolumbus_inquiries_payments_items` TO `kolumbus_inquiries_payments_items`;
		RENAME TABLE `__2010_08_27_kolumbus_inquiries_payments_overpayment` TO `kolumbus_inquiries_payments_overpayment`;
		 *
		 */

		// Backup der Tabellen
		try{
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments_documents');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments_items');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments_overpayment');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments_agencypayments');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}

		// Alte Payments laden

		$sWhereAddon = '';


		// Live 1 Kunden von Live 0 ausschließen
		if(Ext_Thebing_Util::isLive1System()){
			$sWhereAddon = " AND (
								`idClient` >= 47 ||
								`idClient` IN (9, 11, 16, 17, 19, 20, 22, 24, 27, 28, 30, 33, 36, 37, 40, 41, 42, 43, 44, 45, 46, 47, 1, 21)
							)";
		}

		// Live 0 keine Live 1 Mandanten
		if(Ext_Thebing_Util::isLive2System()){
			$sWhereAddon = "AND (
								`idClient` < 47 AND
								`idClient` NOT IN (9, 11, 16, 17, 19, 20, 22, 24, 27, 28, 30, 33, 36, 37, 40, 41, 42, 43, 44, 45, 46, 47, 1, 21)
							)";
		}

		$sSql = "SELECT
						*, 
						UNIX_TIMESTAMP(`received`) AS `date`,
						UNIX_TIMESTAMP(`created`) AS `created`
					FROM
						`kolumbus_payments`
					WHERE
						`active` = 1 AND
						`idInquiry` > 0 AND
						(
							`amount` > 0 OR
							`amount_school` > 0
						)
				".$sWhereAddon;
		$aSql = array();

		$aOldPayments = DB::getPreparedQueryData($sSql,$aSql);

		foreach((array)$aOldPayments as $aOldPayment){

			$oInquiry			= Ext_TS_Inquiry::getInstance($aOldPayment['idInquiry']);
			$iInquiryId			= (int)$oInquiry->id;
			$iCurrencyId		= (int)$oInquiry->getCurrency();
			$iSchoolId			= (int)$oInquiry->crs_partnerschool;
			$oSchool			= new Ext_Thebing_School(null, $iSchoolId, true);
			$iCurrencySchoolId	= (int)$oSchool->getCurrency();

			$iAgencyPaymentId	= (int)$aOldPayment['agency_payment_id'];

			$fAmount			= (float)$aOldPayment['amount'];
			$fAmountSchool		= (float)$aOldPayment['amount_school'];

			if($fAmountSchool == 0){
				$fAmountSchool = $fAmount;
			} else if($fAmount == 0){
				$fAmount = $fAmountSchool;
			}

			$sSender			= 'customer';
			$sReceiver			= 'customer';

			if($oInquiry->agency_id > 0){
				$sSender			= 'agency';
				$sReceiver			= 'agency';
			}

			$aDocuments = $oInquiry->getDocuments('invoice_without_proforma', true, true);
			$aItems = array();
			foreach((array)$aDocuments as $oDocument){
				$oLastVersions = $oDocument->getLastVersion();
				if(is_object($oLastVersions)){
					$aTemp = $oLastVersions->getItemObjects(true);
					$aItems = array_merge($aItems, $aTemp);
				}
			}

			$iTypeId = 1;
			if($aOldPayment['refund'] == 1){
				$iTypeId = 3;
			} else if($aOldPayment['local_payment'] == 1){
				$iTypeId = 2;
			}


			$aSelectedIds						= array($iInquiryId);
			$aData								= array();
			$aData['payment']['created']		= $aOldPayment['created'];
			$aData['payment']['date']			= Ext_Thebing_Format::LocalDate($aOldPayment['date'], $iSchoolId);
			$aData['payment']['method_id']		= (int)$aOldPayment['method'];
			$aData['payment']['type_id']		= (int)$iTypeId;
			$aData['payment']['sender']			= $sSender;
			$aData['payment']['receiver']		= $sReceiver;
			$aData['payment']['comment']		= $aOldPayment['comment'];
			$aData['payment']['user_id']		= $aOldPayment['user_id'];
			$aData['payment']['amount_inquiry'] = (float)$fAmount;
			$aData['payment']['amount_school']	= (float)$fAmountSchool;

			// Faktor ausrechnen
			$fFaktor							=  (float)($fAmount / $fAmountSchool);
			// Restsummen
			$fTempAmount						= $fAmount;
			$fTempAmountSchool					= $fAmountSchool;

			foreach((array)$aItems as $oItem){

				// Wenn kein geld mehr da ist -> Abbruch
				if($fTempAmount == 0){
					break;
				}

				$fAmount	= (float)$oItem->amount;
				$fAmount	+= $oItem->getTaxAmount();

				$fPayAmount = 0;

				// Wenn der Gesammtbetrag großer oder gleich groß wie das Item ist
				if($fTempAmount >= $fAmount){
					// erhöhe $fPayAmount um den Betrag $fAmount
					$fPayAmount = $fAmount;
					// Reduziere den gesammt Betrag um $fAmount
					$fTempAmount -= $fAmount;
				}elseif( $fTempAmount < $fAmount && $fTempAmount > 0){
					$fPayAmount = $fTempAmount;
					$fTempAmount = 0;
				}else{
					$fPayAmount = 0;
				}

				// Schulbetrag ausrechnen selbes verhältniss
				$fPayAmountSchool	= $fFaktor * $fPayAmount;
				$fTempAmountSchool	= $fTempAmountSchool - $fPayAmountSchool;

				$aData['items'][$oItem->id]['currency_inquiry']	= (int)$iCurrencyId;
				$aData['items'][$oItem->id]['currency_school']	= (int)$iCurrencySchoolId;
				$aData['items'][$oItem->id]['amount_inquiry']	= (float)$fPayAmount;
				$aData['items'][$oItem->id]['amount_school']	= (float)$fPayAmountSchool;

			}
			## Ende Items

			// Restlichen Beträge als Overpay eintragen
			$aData['overpay']['amount_inquiry'] = (float)$fTempAmount;
			$aData['overpay']['amount_school']	= (float)$fTempAmountSchool;

			$aData = Ext_Thebing_Inquiry_Payment::saveNewPayment(null, $aSelectedIds, $aData, false, false);
			$iPaymentId = (int)$aData['data']['save_id'];

			if($iAgencyPaymentId > 0 && $iPaymentId > 0){
				$aKeys = array('payment_id' => (int)$iPaymentId, 'agency_payment_id' => $iAgencyPaymentId);
				$aJoinData = array($iAgencyPaymentId);
				DB::updateJoinData('kolumbus_inquiries_payments_agencypayments', $aKeys, $aJoinData, 'agency_payment_id');
			}
			
		}

		return true;
	}

}
