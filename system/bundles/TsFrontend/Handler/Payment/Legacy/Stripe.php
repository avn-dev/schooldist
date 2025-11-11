<?php

namespace TsFrontend\Handler\Payment\Legacy;

use Core\Entity\ParallelProcessing\Stack;
use TsFrontend\DTO\OnlinePayment;

/**
 * @deprecated
 *
 * @link https://stripe.com/docs/payments/checkout/server
 *
 * Sandbox:
 *
 * Wird aktiviert beim Voranstellen von test: bei User-ID
 *
 * Card: 378282246310005 
 *
 */
class Stripe extends AbstractPayment {

	/**
	 * @inheritdoc
	 */
	public static function getLabel() {
		return 'Stripe';
	}
	
	/**
	 * @inheritdoc
	 */
	public function createPayment(\Ext_TS_Inquiry_Abstract $oInquiry, array $aItems, &$sConfirmationMessage) {

		$oSchool = $this->oCombination->requireSchool();
		$oCurrency = $this->oCombination->getFormCurrency();
		$sLanguage = $this->oCombination->requireLanguage();
		
		if(
			empty($oSchool->getMeta('stripe_api_key')) ||
			empty($oSchool->getMeta('stripe_api_key_public')) ||
			empty($oSchool->getMeta('stripe_item_title')) ||
			empty($oSchool->getMeta('stripe_item_description'))
		) {
			// Konfiguration der Schule muss vollständig sein
			throw new \RuntimeException('Missing school configuration for redsys!'.print_r(['school_id' => $oSchool->getId()] , true));
		}
		
		// Amount zusammenrechnen
		$fAmount = 0;
		foreach($aItems as $aItem) {
			$fAmount += (float)$aItem['amount_with_tax'];
		}
		
		\Stripe\Stripe::setApiKey($oSchool->getMeta('stripe_api_key'));
		
		$oTraveller = $oInquiry->getTraveller();
		
		$oSession = \Stripe\Checkout\Session::create([
			"success_url" => $this->oCombination->getRequestingUrl('return=payment&success=true&session_id={CHECKOUT_SESSION_ID}'),
			"cancel_url" => $this->oCombination->getRequestingUrl('return=payment&success=false&session_id={CHECKOUT_SESSION_ID}'),
			"payment_method_types" => ["card"],
			"customer_email" => $oTraveller->getFirstEmailAddress()->email,
			"line_items" => [[
				"name" => $oSchool->getMeta('stripe_item_title'),
				"description" => $oSchool->getMeta('stripe_item_description'),
				"amount" => $fAmount * 100,
				"currency" =>$oCurrency->iso4217,
				"quantity" => 1
			]],
			'locale' => $sLanguage,
			'payment_intent_data' => [
				'capture_method' => 'manual',
				'metadata' => [
					'inquiry_id' => $oInquiry->getId()
				]
			],
		]);
		
		$this->oLogger->addInfo('Stripe createPayment', ['inquiry_id' => $oInquiry->getId(), 'session_id' => $oSession->id, 'amount' => $fAmount, 'currency' => $oCurrency->iso4217]);
		
		$oSmarty = new \SmartyWrapper();
		$oSmarty->assign('sApiKeyPublic', $oSchool->getMeta('stripe_api_key_public'));
		$oSmarty->assign('sButtonLabel', \Ext_TC_Placeholder_Abstract::translateFrontend('Mit Stripe bezahlen', $sLanguage));
		$oSmarty->assign('sCheckoutSessionId', $oSession->id);
		
		$sConfirmationMessage .= $oSmarty->fetch('@TsFrontend/payment_legacy/stripe.tpl');
	}

	/**
	 * @inheritdoc
	 */
	public function executePayment() {

		$oSchool = $this->oCombination->requireSchool();

		$oRequest = $this->oCombination->getRequest();
		\Stripe\Stripe::setApiKey($oSchool->getMeta('stripe_api_key'));
		
		$oSession = \Stripe\Checkout\Session::retrieve($oRequest->get('session_id'));
		
		if($oSession) {
			
			$this->oLogger->addInfo('Stripe executePayment', ['session_id' => $oSession->id]);
					
			$oPaymentIntent = \Stripe\PaymentIntent::retrieve($oSession->payment_intent);
			if($oPaymentIntent) {

				try {
					// Zahlung bestätigen
					$oPaymentIntent = $oPaymentIntent->capture();
				
				} catch(\Exception $e) {
					// Wenn man die Seite reloaded wird eine Exception geschmissen da die Zahlung bereits bestätigt wurde
					$this->oLogger->addError('Stripe executePayment: failed', [$e->getMessage()]);
					return false;
				}
				
				if($oPaymentIntent->status === 'succeeded') {

					$oInquiry = $this->getInquiryFromPayment($oPaymentIntent);
					
					$oOnlinePayment = $this->createOnlinePayment($oPaymentIntent, $oInquiry);

					if($oOnlinePayment === null) {
						return false;
					}

					$oUnallocatedPayment = $this->createUnallocatedPayment($oOnlinePayment);
					
					// Zahlung automatisch zuweisen
					if($oInquiry !== null) {
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
			} else {
				$this->oLogger->addError('Stripe executePayment: missing payment intent', ['session_id' => $oSession->id]);
			}
		} else {
			$this->oLogger->addError('Stripe executePayment: missing session', ['request' => $oRequest->getAll()]);
		}

		return false;
	}
	
	/**
	 * @param Api\Payment $oPayment
	 * @return OnlinePayment|null
	 */
	private function createOnlinePayment(\Stripe\PaymentIntent $oPaymentIntent, $oInquiry = null) {

		$sCurrency = strtoupper($oPaymentIntent->currency);
		
		$oCustomer = \Stripe\Customer::retrieve($oPaymentIntent->customer);
		$oCurrency = \Ext_Thebing_Currency::getByIso($sCurrency);
		
		if(
			$oCurrency === null ||
			!$oCurrency->exist()
		) {
			throw new \RuntimeException('Unknown PayPal currency: '.$sCurrency);
		}

		$sComment = \L10N::t('E-Mail', \Ext_Thebing_Document::$sL10NDescription).': '.$oCustomer->email."\n";
		$sComment .= \L10N::t('Transaktionscode', \Ext_Thebing_Document::$sL10NDescription).': '.$oPaymentIntent->id;

		$sFirstname = $sLastname = '';
		
		if($oInquiry) {
			$oTraveller = $oInquiry->getTraveller();
			$sFirstname = $oTraveller->firstname;
			$sLastname = $oTraveller->lastname;
		}
		
		$oDto = new OnlinePayment();
		$oDto->sTransactionCode = $oPaymentIntent->id;
		$oDto->sComment = $sComment;
		$oDto->sFirstname = $sFirstname;
		$oDto->sLastname = $sLastname;
		$oDto->fAmount = (float)($oPaymentIntent->amount/100);
		$oDto->sCurrencyIso = $sCurrency;
		$oDto->sPaymentDate = (new \DateTime())->setTimestamp($oPaymentIntent->created)->format('Y-m-d');

		return $oDto;

	}
	
	/**
	 * Inquiry aus Paypal-Payment extrahieren (im custom-Feld der Transaktion)
	 *
	 * @param Api\Payment $oPayment
	 * @return \Ext_TS_Inquiry|null
	 */
	private function getInquiryFromPayment(\Stripe\PaymentIntent $oPaymentIntent) {

		$oMetaData = $oPaymentIntent->metadata;
		$iInquiryId = (int) $oMetaData->inquiry_id;
		
		$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);
		if($oInquiry->exist()) {
			return $oInquiry;
		}

		$this->oLogger->addError('Inquiry in response is missing!', $aResponse);
		
		return null;

	}
	
	/**
	 * Diese Felder erscheinen bei der Schule als Eingabefelder. 
	 * Werte werden in Attributes abgespeichert
	 * 
	 * @return array
	 */
	public static function getSettings() {
		return \TsFrontend\ExternalApps\Stripe::getSettings();
	}
}
