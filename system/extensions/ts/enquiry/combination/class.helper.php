<?php


class Ext_TS_Enquiry_Combination_Helper {

	/**
	 * CombinationInsurances Preis holen und in die gleiche Form bringen wie bei Ext_TS_Inquiry_Journey_Insurance
	 * 
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param Ext_TS_Enquiry_Combination_Insurance[] $aServiceInsurances 
	 * @param string $sDisplayLanguage
	 */
	public static function getInsurancesWithPriceData(Ext_TS_Inquiry_Abstract $oInquiry, $aServiceInsurances, $sDisplayLanguage=null) {

		$aResult = array();
		$oDate = new WDDate();
		$iCurrency = $oInquiry->getCurrency();
		$oSchool = $oInquiry->getSchool();

		foreach($aServiceInsurances as $oCombinationInsurance) {

			if($oCombinationInsurance->active != 1){
				continue;
			}

			$aData	= $oCombinationInsurance->getData();

			$oDate->set($aData['from'], WDDate::DB_DATE);
			$aData['from'] = $oDate->get(WDDate::TIMESTAMP);

			$oDate->set($aData['until'], WDDate::DB_DATE);
			$aData['until'] = $oDate->get(WDDate::TIMESTAMP);

			$oInsurance = $oCombinationInsurance->getInsurance();
			$aData['insurance_id'] = $oInsurance->id;
			$aData['insurance'] = $oInsurance->getName($sDisplayLanguage);
			$aData['payment'] = $oInsurance->payment;
			$aData['currency_id'] = $iCurrency;

			// Schul-ID weitergeben damit diese in Ext_Thebing_Insurances_Gui2_Customer abgefragt werden kann
			$aData['school_id'] = $oSchool->id;

			$aResult[] = $aData;

		}

		$oTemp = new Ext_Thebing_Insurances_Gui2_Customer($aResult); 
		$aResult = $oTemp->format($aResult, $sDisplayLanguage);

		return $aResult;

	}

	public static function filterTransfers($aCombinationTransfers, $sFilter = '', $bIgnoreBookingStatus = false)
	{
		$aBack					= array();
		$sFilterOriginal		= $sFilter;
		
		$aCombinationTransfers = (array)$aCombinationTransfers;

		#if(!$bIgnoreBookingStatus)
		#{
			//Transfer nach TransferTyp(An/Abreise) filtern

			/* @var $oCombinationTransfer Ext_TS_Enquiry_Combination_Transfer */
			foreach($aCombinationTransfers as $oCombinationTransfer)
			{
				if(empty($sFilter) && !$bIgnoreBookingStatus)
				{
					//Wenn kein Filter angegeben und nach TransferTyp(An/Abreise) gefiltert werden soll, 
					//dann Filter anhand des TransferTyps's(An/Abreise) definieren
					$oCombination	= $oCombinationTransfer->getCombination();
					$sTransferMode	= $oCombination->transfer_mode;
					
					$sFilter		= $sTransferMode;
				}

				if(
					empty($sFilterOriginal) &&
					$oCombinationTransfer->transfer_type == 0
				)
				{
					//Wenn individuell, dann Filter rausnehmen, weil individuell nicht TransferTyp(An/Abreise) gebunden ist
					$sFilter = '';
				}

				if($sFilter == 'no')
				{
					//Wenn TransferTyp(An/Abreise) gefiltert werden soll & kein Transfer erwünscht, dann nichts zurückgeben
					continue;
				}

				if(
					(
						$sFilter == 'arrival' &&
						$oCombinationTransfer->transfer_type != 1
					) ||
					(
						$sFilter == 'departure' &&
						$oCombinationTransfer->transfer_type != 2
					) ||
					(
						$sFilter == 'additional' &&
						$oCombinationTransfer->transfer_type != 0	
					)
				)
				{
					//Wenn in der Kombination 'Anreise' gewählt und jetziger Transfer Abreise oder
					//Wenn in der Kombination 'Abreise' gewählt und jetziger Transfer Anreise => dann jetzigen Transfer ignorieren
					continue; 
				}

				$aBack[] = $oCombinationTransfer;

			}
		#}
		
		if(
			$sFilterOriginal == 'arrival' ||
			$sFilterOriginal == 'departure'
		)
		{
			$aBack = reset($aBack);
		}

		return $aBack;
	}

}
