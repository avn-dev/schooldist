<?php

/**
 * Platzhalter für Bezahlungsübersichten beim Bezahlen von Unterkunftsanbietern (PDF bei den Gruppieren der bezahlten Unterkünfte)
 */
class Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder extends Ext_TS_Accounting_Provider_Grouping_Placeholder_ContractBridge
{
	protected $_sSection = 'accommodations';
	protected $_sPlaceholderAreaTranslation = 'Unterkunftsanbieter';

	/**
	 * @inheritdoc
	 */
	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		switch($sPlaceholder) {
			case 'customernumber':
			case 'firstname':
			case 'surname':
			case 'name':
				$sValue = $this->_getValueForContact($sPlaceholder);
				break;
			case 'first_start_date':
			case 'last_end_date':
				$sValue = $this->_getValueForServiceTimeframe($sPlaceholder);
				break;
			case 'payment_number':
				$sValue = $this->_oGrouping->getNumber();
				break;
			case 'provider_number':
				$sValue = $this->_oGrouping->getAccommodationProvider()?->getNumber();
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
		}

		return $sValue;
	}

	/**
	 * Customer-Platzhalter ersetzen
	 * @param string $sPlaceholder
	 * @return string
	 */
	protected function _getValueForContact($sPlaceholder) {
		$aData = array();
		$aPayments = (array)$this->_oGrouping->getJoinedObjectChilds('payments', true);

		foreach($aPayments as $oPayment) {
			$oContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($oPayment->customer_id);

			// Monatliche Zahlungen und Zahlungen ohne Kontakte überspringen
			if(
				$oPayment->select_type == 'month' ||
				$oContact->id < 0
			) {
				continue;
			}

			switch($sPlaceholder) {
				case 'customernumber':
					$aData[] = $oContact->getCustomerNumber();
					break;
				case 'firstname':
					$aData[] = $oContact->firstname;
					break;
				case 'surname':
					$aData[] = $oContact->lastname;
					break;
				case 'name':
					$oFormat = new Ext_TC_Gui2_Format_Name();
					$oDummy = null;
					$aResultData = array(
						'lastname' => $oContact->lastname,
						'firstname' => $oContact->firstname
					);
					$aData[] = $oFormat->format(null, $oDummy, $aResultData);
					break;
			}

		}

		$aData = array_unique($aData);
		foreach($aData as $iKey => $sData) {
			if(empty($sData)) {
				unset($aData[$iKey]);
			}
		}

		return join(', ', $aData);
	}

	/**
	 * Platzhalter ersetzen für Leistungsbeginn- und Ende
	 * @param string $sPlaceholder
	 * @throws RuntimeException
	 * @return string
	 */
	protected function _getValueForServiceTimeframe($sPlaceholder) {
		$sValue = '';
		$aPayments = (array)$this->_oGrouping->getJoinedObjectChilds('payments', true);

		$oFrom = new DateTime('2999-12-31 00:00:00');
		$oUntil = new DateTime('0000-01-01 00:00:00');

		/* @var $oPayment Ext_Thebing_Accommodation_Payment */
		foreach($aPayments as $oPayment) {

			$oPaymentFrom = $oPayment->getFromDate();

			if($oPaymentFrom instanceof WDDate) {
				$dPaymentFrom = new DateTime($oPaymentFrom->get(WDDate::DB_DATE));
				$oFrom = min($oFrom, $dPaymentFrom);
			}
			
			$oPaymentUntil = $oPayment->getUntilDate();

			if($oPaymentUntil instanceof WDDate) {
				$dPaymentUntil = new DateTime($oPaymentUntil->get(WDDate::DB_DATE));
				$oUntil = max($oUntil, $dPaymentUntil);
			}

		}

		if($sPlaceholder === 'first_start_date') {
			$iTime = $oFrom->getTimestamp();
		} elseif($sPlaceholder === 'last_end_date') {
			$iTime = $oUntil->getTimestamp();
		} else {
			throw new RuntimeException();
		}

		if(!empty($oPayment)) {
			// Hier einfach die letzte Payment nehmen,
			//	denn pro Anbieter gibt es ein separates PDF
			if($oPayment->select_type !== 'month') {
				$sValue = Ext_Thebing_Format::LocalDate($iTime);
			} else {
				$sValue = strftime('%B', $iTime);
			}
		}

		return $sValue;
	}
}