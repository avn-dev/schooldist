<?php

namespace TsFrontend\Handler\Payment\Legacy;

use TsFrontend\DTO\OnlinePayment;

/**
 * @TODO Mit RegForm V2 entfernen
 *
 * @deprecated
 */
abstract class AbstractPayment {

	const CLASSES = [
		'paypal' => PayPal::class,
		'redsys' => Redsys::class,
		'stripe' => Stripe::class,
	];

	/**
	 * @var \Ext_TS_Frontend_Combination_Inquiry_Abstract
	 */
	protected $oCombination;

	/**
	 * @var \Monolog\Logger
	 */
	protected $oLogger;

	/**
	 * @param \Ext_TS_Frontend_Combination_Inquiry_Abstract $oCombination
	 */
	public function __construct(\Ext_TS_Frontend_Combination_Inquiry_Abstract $oCombination) {
		$this->oCombination = $oCombination;
		$this->oLogger = \Log::getLogger('frontend');
	}

	/**
	 * @return string
	 */
	abstract public static function getLabel();

	/**
	 * Nach dem erfolgreichen Abschicken des Formulars: Zahlung generieren
	 *
	 * @param \Ext_TS_Inquiry_Abstract $oInquiry
	 * @param array $aItems
	 * @param string $sConfirmationMessage
	 */
	abstract public function createPayment(\Ext_TS_Inquiry_Abstract $oInquiry, array $aItems, &$sConfirmationMessage);

	/**
	 * Nach Bezahlung bzw. Rückleitung von Zahlungsanbieter auf das Formular: Payment speichern
	 *
	 * @return bool
	 */
	abstract public function executePayment();

	/**
	 * Unzugewiesene Zahlung generieren
	 *
	 * Dient als Zwischenschritt, falls bei der Verarbeitung/Zuweisung ein Fehler auftritt
	 *
	 * @param OnlinePayment $oPayment
	 * @return \Ext_TS_Inquiry_Payment_Unallocated
	 * @throws \Exception
	 */
	protected function createUnallocatedPayment(OnlinePayment $oPayment) {

		$oForm = $this->oCombination->requireForm();
		$oSchool = $this->oCombination->requireSchool();
		$oCurrency = \Ext_Thebing_Currency::getByIso($oPayment->sCurrencyIso);

		$oUnallocatedPayment = new \Ext_TS_Inquiry_Payment_Unallocated();

		$oUnallocatedPayment->transaction_code = $oPayment->sTransactionCode;
		$oUnallocatedPayment->comment = $oPayment->sComment;
		$oUnallocatedPayment->firstname = $oPayment->sFirstname;
		$oUnallocatedPayment->lastname = $oPayment->sLastname;
		$oUnallocatedPayment->amount = $oPayment->fAmount;
		$oUnallocatedPayment->amount_currency = $oCurrency->id;
		$oUnallocatedPayment->payment_date = $oPayment->sPaymentDate;

		if(!empty($oForm->getSchoolSetting($oSchool, 'payment_method'))) {
			$oUnallocatedPayment->payment_method_id = $oForm->getSchoolSetting($oSchool, 'payment_method');
		}

		$oUnallocatedPayment->validate(true);
		$oUnallocatedPayment->save();

		return $oUnallocatedPayment;

	}
	
	/**
	 * @deprecated
	 *
	 * @return array
	 */
	public static function getOptions() {
		$aReturn = [];
		foreach(self::CLASSES as $sKey => $sClass) {
			if(\TcExternalApps\Service\AppService::hasApp($sKey)) {
				$aReturn[$sKey] = $sClass::getLabel();
			}
		}
		return $aReturn;
	}

}
