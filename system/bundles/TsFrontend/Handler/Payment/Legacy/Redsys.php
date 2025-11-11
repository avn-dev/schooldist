<?php

namespace TsFrontend\Handler\Payment\Legacy;

use Core\Entity\ParallelProcessing\Stack;
use TsFrontend\DTO\OnlinePayment;

/**
 * @deprecated
 *
 * https://github.com/drmonkeyninja/test-payment-cards#redsys
 * 
 * Card Number: 4548812049400004
 * Expiration: 12/20
 * CVV: 123
 */
class Redsys extends AbstractPayment {
	
	const ORDER_POST_FIX = '0001';
	
	public static function getLabel(): string {
		return 'Redsys';
	}
	
	/**
	 * Wird nach dem erfolgreichen Abschicken des Formulares aufgerufen. Hier wird das Redsys-Formular (HTML)
	 * an die Bestätigungsnachricht angehangen 
	 * 
	 * @param \Ext_TS_Inquiry_Abstract $oInquiry
	 * @param array $aItems
	 * @param string $sConfirmationMessage
	 * @throws \RuntimeException
	 */
	public function createPayment(\Ext_TS_Inquiry_Abstract $oInquiry, array $aItems, &$sConfirmationMessage) {
		
		$oSchool = $this->oCombination->requireSchool();
		$oCurrency = $this->oCombination->getFormCurrency();
		$sLanguage = $this->oCombination->requireLanguage();

		if(
			empty($oSchool->getMeta('redsys_client_key')) ||
			empty($oSchool->getMeta('redsys_merchant_code')) ||
			empty($oSchool->getMeta('redsys_merchant_terminal')) ||
			empty($oSchool->getMeta('redsys_merchant_name'))
		) {
			// Konfiguration der Schule muss vollständig sein
			throw new \RuntimeException('Missing school configuration for redsys!'.print_r(['school_id' => $oSchool->getId()] , true));
		}
			
		$sClientKey = $oSchool->getMeta('redsys_client_key');
		$sEnv = 'live';		
		// Die Sandbox wird über einen Prefix gesteuert
		if(strpos($sClientKey, 'test:') !== false) {
			$sClientKey = str_replace('test:', '', $sClientKey);
			$sEnv = 'test';
		}

		// Amount zusammenrechnen
		$fAmount = 0;
		foreach($aItems as $aItem) {
			$fAmount += (float)$aItem['amount_with_tax'];
		}		

		$oRedsys = new \Sermepa\Tpv\Tpv();
		$oRedsys->setAmount($fAmount);
		$oRedsys->setOrder($oInquiry->id.self::ORDER_POST_FIX);		
		$oRedsys->setMerchantcode($oSchool->getMeta('redsys_merchant_code')); // Bank
		$oRedsys->setCurrency($oCurrency->getIsoNum()); // Redsys arbeitet mit nummerischen Codes
		$oRedsys->setLanguage($this->getLanguageCode($sLanguage)); // Interface sprache bei Redsys (nummerisch)
		$oRedsys->setTransactiontype('0');
		$oRedsys->setTerminal($oSchool->getMeta('redsys_merchant_terminal'));
		$oRedsys->setMethod('C'); // Kredit Karte
		$oRedsys->setUrlOk($this->oCombination->getRequestingUrl('return=payment&success=true')); // Erfolgreiche Zahlung
		$oRedsys->setUrlKo($this->oCombination->getRequestingUrl('return=payment&success=false')); // Fehler bei Zahlung oder Abbruch
		$oRedsys->setVersion('HMAC_SHA256_V1');
		$oRedsys->setTradeName($oSchool->getMeta('redsys_merchant_name'));
		$oRedsys->setTitular($oInquiry->getTraveller()->getName());
				
		if(!empty($oSchool->getMeta('redsys_product_description'))) {
			$oRedsys->setProductDescription($oSchool->getMeta('redsys_product_description'));
		}
		
		// Submit Button
		$oRedsys->setAttributesSubmit('btn_submit', 'btn_submit', \Ext_TC_Placeholder_Abstract::translateFrontend('Mit Redsys bezahlen', $sLanguage), '', '');
		// Sandbox (test/live)
		$oRedsys->setEnvironment($sEnv); 

		$sSignature = $oRedsys->generateMerchantSignature($sClientKey);
		$oRedsys->setMerchantSignature($sSignature);

		$oSmarty = new \SmartyWrapper();
		
		$oSmarty->assign('oRedsys', $oRedsys);
		$oSmarty->assign('sJQueryName', \Ext_TS_Frontend_Combination_Inquiry_Abstract::THEBING_JQUERY_NAME);
		
		$sConfirmationMessage .= $oSmarty->fetch('@TsFrontend/payment_legacy/redsys.tpl');
	}

	/**
	 * Hier kommt der Return-Request von dem Zahlungsanbieter an
	 * 
	 * @return bool
	 */
	public function executePayment(): bool {
		$oRequest = $this->oCombination->getRequest();

		// Zahlung war erfolgreich
		if($oRequest->get('success') === 'true') {
			
			$oRedsys = new \Sermepa\Tpv\Tpv();
			$aMerchantParameters = $oRedsys->getMerchantParameters($oRequest->get('Ds_MerchantParameters'));
			
			$this->oLogger->addInfo('Redsys executePayment', ['merchant parameters' => $aMerchantParameters]);
			
			// versuchen wieder an die Buchung zu kommen
			$oInquiry = $this->getInquiryFromPayment($aMerchantParameters);
			
			$oOnlinePayment = $this->createOnlinePayment($oRedsys, $aMerchantParameters, $oRequest, $oInquiry);			

			if(is_null($oOnlinePayment)) {
				$this->oLogger->addError('Redsys executePayment - no valid online payment (abort)', ['request' => $oRequest->getAll(), 'merchant_parameters' => $aMerchantParameters]);
				return false;
			}
			
			// Zahlung als "nicht zugewiesen" abspeichern
			$oUnallocatedPayment = $this->createUnallocatedPayment($oOnlinePayment);
			
			// Wenn die Buchung aus dem Return-Request des Zahlungsanbieters entnommen werden kann 
			// kann die Zahlung automatisch zugewiesen werden (PP)
			if(!is_null($oInquiry)) {

				$aStackData = [
					'object' => \Ext_TS_Inquiry::class,
					'object_id' => $oInquiry->id,
					'unallocated_payment_id' => $oUnallocatedPayment->id,
				];

				$oStackRepository = Stack::getRepository();
				$oStackRepository->writeToStack('ts-frontend/form-payment', $aStackData, 5);
			}

			return true;
		}

		// Zahlung abgebrochen
		return false;
	}

	/**
	 * Sammelt alle wichtigen Informationen aus dem Return-Request des Zahlungsanbieters
	 * 
	 * @param \Sermepa\Tpv\Tpv $oRedsys
	 * @param array $aMerchantParameters
	 * @param \MVC_Request $oRequest
	 * @return \TsFrontend\DTO\OnlinePayment|null
	 */
	private function createOnlinePayment(\Sermepa\Tpv\Tpv $oRedsys, array $aMerchantParameters, \MVC_Request $oRequest, $oInquiry = null) {
		
		if(
			isset($aMerchantParameters["Ds_Response"]) &&
			!empty($aMerchantParameters["Ds_AuthorisationCode"])
		) {		

			$oSchool = $this->oCombination->requireSchool();

			$sDsResponse = $aMerchantParameters["Ds_Response"];
			$sDsResponse += 0;

			$sClientKey = str_replace('test:', '', $oSchool->getMeta('redsys_client_key'));
			
			// Zahlungsbestätigung überprüfen (siehe RedsysApi)
			if(
				$oRedsys->check($sClientKey, $oRequest->getAll()) && 
				$sDsResponse <= 99
			) {

				// Verhindern das Zahlung doppelt angelegt wird
				$bExistingPayment = $this->checkExistingPayment($aMerchantParameters["Ds_AuthorisationCode"]);

				if(!$bExistingPayment) {
					
					// Redsys arbeitet mit nummerischen Codes
					$oCurrency = \Ext_Thebing_Currency::getRepository()->findOneBy(['iso4217_num' => $aMerchantParameters['Ds_Currency']]);
					// Informationen der Kreditkarte
					$sComment = 'Card Number: '.$aMerchantParameters['Ds_Card_Number']."\n";
					$sComment .= 'Card Country: '.$aMerchantParameters['Ds_Card_Country']."\n";
					$sComment .= 'Card Brand: '.$aMerchantParameters['Ds_Card_Brand']."\n";
					//$sComment .= 'AuthorisationCode: '.$aMerchantParameters['Ds_AuthorisationCode'];

					$oDto = new OnlinePayment();
					$oDto->sTransactionCode = $aMerchantParameters['Ds_AuthorisationCode'];
					$oDto->sComment = $sComment;

					if($oInquiry instanceof \Ext_TS_Inquiry) {
						$oTraveller = $oInquiry->getTraveller();
						$oDto->sFirstname = $oTraveller->firstname;
						$oDto->sLastname = $oTraveller->lastname;
					}				

					$oDto->fAmount = bcdiv((float) $aMerchantParameters['Ds_Amount'], 100);
					$oDto->sCurrencyIso = ($oCurrency !== null) ? $oCurrency->iso4217 : null;
					$oDto->sPaymentDate = (new \DateTime())->format('Y-m-d');

					return $oDto;			
				}
			}			
		}
		
		$this->oLogger->addError('Redsys createOnlinePayment - payment is not valid', ['client key' => $sClientKey, 'request' => $oRequest->getAll(), 'ds_response' => $sDsResponse]);
		
		return null;		
	}
	
	private function checkExistingPayment(string $sTransactionCode) {
		
		$sSql = "SELECT `id` FROM #table WHERE `transaction_code` = :transaction_code LIMIT 1";
		
		$iFound = (int) \DB::getQueryOne($sSql, ['table' => 'ts_inquires_payments_unallocated', 'transaction_code' => $sTransactionCode]);
		if($iFound === 0) {
			$iFound = (int) \DB::getQueryOne($sSql, ['table' => 'kolumbus_inquiries_payments', 'transaction_code' => $sTransactionCode]);
		}
		
		if($iFound > 0) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Versucht die Buchung aus Return-Request des Zahlungsanbieters auszulesen
	 * 
	 * @param array $aMerchantParameters
	 * @return \Ext_TS_Inquiry|null
	 */
	private function getInquiryFromPayment(array $aMerchantParameters) {
		
		if(
			!empty($aMerchantParameters['Ds_Order']) &&
			is_numeric($aMerchantParameters['Ds_Order'])
		) {
			
			$iInquiryId = (int) substr($aMerchantParameters['Ds_Order'], 0, (strlen(self::ORDER_POST_FIX) * -1));

			$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);
			if($oInquiry->exist()) {
				return $oInquiry;
			}
		}

		$this->oLogger->addError('Inquiry in response is missing!', ['order' => $aMerchantParameters['Ds_Order'], 'post_fix' => self::ORDER_POST_FIX]);

		return null;		
	}

	/**
	 * Redsys arbeitet mit nummerischen Codes
	 * 
	 * @param string $sLanguageIso
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function getLanguageCode(string $sLanguageIso) {
		
		$aMapping = [
			'es' =>  '001',
			'en' =>  '002',
			'ca' =>  '003',
			'fr' =>  '004',
			'de' =>  '005',
			'nl' =>  '006',
			'it' =>  '007',
			'sv' =>  '008',
			'pt' =>  '009',
			//'de' =>  '010',
			'pl' =>  '011',
			//'de' =>  '012',
			'eu' =>  '013'
		];
		
		if(!isset($aMapping[$sLanguageIso])) {
			throw new \InvalidArgumentException('Language code is not supported');
		}
		
		return $aMapping[$sLanguageIso];
	}
	
	/**
	 * Diese Felder erscheinen bei der Schule als Eingabefelder. 
	 * Werte werden in Attributes abgespeichert
	 * 
	 * @return array
	 */
	public static function getSettings() {
		return \TsFrontend\ExternalApps\Redsys::getSettings();
	}

}