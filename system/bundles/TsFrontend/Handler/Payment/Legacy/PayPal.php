<?php

namespace TsFrontend\Handler\Payment\Legacy;

use Core\Entity\ParallelProcessing\Stack;
use PayPal\Api;
use TsFrontend\DTO\OnlinePayment;

/**
 * @deprecated
 *
 * PayPal API V1
 *
 * Alte Implementierung für V2, bei der auf die PayPal-Seite umgeleitet wird.
 * PayPal\Api ist außerdem deprecated!
 *
 * PHP SDK Dokumentation:
 * https://paypal.github.io/PayPal-PHP-SDK/
 *
 * API Dokumentation:
 * https://developer.paypal.com/docs/api/overview/
 */
class PayPal extends AbstractPayment {

	/**
	 * @inheritdoc
	 */
	public static function getLabel() {
		return 'PayPal';
	}

	/**
	 * API-Kontext für alle PayPal-Requests
	 *
	 * @return \PayPal\Rest\ApiContext
	 */
	private function createApiContext() {

		$oSchool = $this->oCombination->requireSchool();
		$oApiContext = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($oSchool->getMeta('paypal_client_id'), $oSchool->getMeta('paypal_client_secret')));

		$sMode = 'live';
		if($oSchool->getMeta('paypal_client_sandbox')) {
			$sMode = 'sandbox';
		}

		$sDir = \Util::getDocumentRoot().'storage/cache';
		\Util::checkDir($sDir);

		$oLogger = new class implements \PayPal\Log\PayPalLogFactory {
			public function getLogger($className) {
				return \Ext_TC_Log::getLogger('frontend', 'paypal');
			}
		};

		$oApiContext->setConfig([
			'mode' => $sMode,
			'log.LogEnabled' => true,
			//'log.FileName' => '../PayPal.log',
			'log.LogLevel' => 'INFO', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
			'log.AdapterFactory' => $oLogger,
			'cache.enabled' => true,
			'cache.FileName' => $sDir.'/paypal_auth.cache',
			'http.CURLOPT_CONNECTTIMEOUT' => 30
		]);

		return $oApiContext;

	}

	/**
	 * WebProfile / Experience Profile / Payment Experience (diverse Namen) ggf. anlegen
	 *
	 * Damit in der REST-API alte Features wie keine Versandadresse oder direkte Bezahlung (anstatt
	 * Authorisierung und weiter) benutzt werden können, muss so ein WebProfile angelegt werden. Diese
	 * ID wird dann im Payment angegeben. Der Name ist unique, aber die ID kann man nicht selbst vergeben.
	 * Zudem kann man diese angelegten Profile auch nirgends im Developer Dashboard oder sonstwo sehen, was
	 * das ganze nur zu einem Verwaltungsaufwand macht.
	 * https://github.com/paypal/PayPal-REST-API-issues/issues/32
	 *
	 * Wenn in der Schule die Client ID verändert wird, wird die paypal_webprofile_id gelöscht,
	 * damit das System im nachfolgenden Code nicht davon ausgeht, dass dann doch das WebProfile existiert.
	 * Existiert dieses aber doch auf dem Account, wird das WebProfile im catch-Block gesucht und gesetzt.
	 *
	 * @return mixed|string
	 * @throws \PayPal\Exception\PayPalConnectionException
	 */
	private function getExperienceProfileId() {

		$oSchool = $this->oCombination->requireSchool();

		if(empty($oSchool->getMeta('paypal_webprofile_id'))) {

			$sWebProfileName = 'Fidelo School Frontend Forms v1';
			$oApiContext = $this->createApiContext();

			$this->oLogger->addInfo('No PayPal WebProfile set, create or search...');

			try {

				$oFlowConfig = new Api\FlowConfig();

				// Standardmäßig würde PayPal alle Werte an die URL hängen
				$oFlowConfig->setReturnUriHttpMethod('POST');

				// Ganz wichtig: »Jetzt bezahlen« anzeigen statt »Weiter«, ansonsten wäre lediglich die Zahlung autorisiert und PayPal würde zurückleiten:
				// »Sie können Ihre Bestellung überprüfen, bevor Sie Ihren Einkauf abschließen«
				$oFlowConfig->setUserAction('commit');

				// Kein Versand benötigt (sonst würde PayPal eine Adressauswahl über dem Button anzeigen)
				$oInputFields = new Api\InputFields();
				$oInputFields->setNoShipping(1);

				$oWebProfile = new Api\WebProfile();
				$oWebProfile
					// Name muss einmalig pro Merchant sein
					->setName($sWebProfileName)
					->setFlowConfig($oFlowConfig)
					->setInputFields($oInputFields);

				$oWebProfileCreated = $oWebProfile->create($oApiContext);
				$oSchool->setMeta('paypal_webprofile_id', $oWebProfileCreated->getId());
				$oSchool->save();

				$this->oLogger->addInfo('Created PayPal WebProfile', [$oWebProfileCreated->toJSON()]);

			} catch(\PayPal\Exception\PayPalConnectionException $e) {

				if(strpos($e->getData(), 'A profile with this name already exists') === false) {
					throw $e;
				}

				$this->oLogger->addInfo('Search for PayPal WebProfile');

				// Config leer, aber WebProfile existiert mit diesem Namen bereits: Suchen und ID setzen
				$aWebProfiles = Api\WebProfile::get_list($oApiContext);
				foreach($aWebProfiles as $oWebProfile) {
					if($oWebProfile->getName() === $sWebProfileName) {
						$oSchool->setMeta('paypal_webprofile_id', $oWebProfile->getId());
						$oSchool->save();

						$this->oLogger->addInfo('Searched and setted PayPal WebProfile', [$oWebProfile->toJSON()]);
					}
				}

			}

		}

		return $oSchool->getMeta('paypal_webprofile_id');

	}

	/**
	 * Nach dem erfolgreichen Abschicken des Formulars: PayPal-Zahlung generieren
	 *
	 * @inheritdoc
	 */
	public function createPayment(\Ext_TS_Inquiry_Abstract $oInquiry, array $aItems, &$sConfirmationMessage) {

//		$oSchool = $this->oCombination->requireSchool();
		$oCurrency = $this->oCombination->getFormCurrency();
		$aCustomData = ['inquiry_id' => $oInquiry->id];

		$fTotal = $fTaxTotal = $fTotalWithTax = 0;
		$oItemList = new Api\ItemList();
		foreach($aItems as $aItem) {

			$oItem = new Api\Item();
			$fPrice = (float)$aItem['amount'];
			$fTax = $aItem['amount_tax'];
//			$iTaxCategory = (int)$aItem['tax_category'];
//
//			// Exklusive Steuer addieren und separat ausweisen
//			if(
//				$iTaxCategory > 0 &&
//				$oSchool->getTaxStatus() == \Ext_Thebing_School::TAX_EXCLUSIVE
//			) {
//				$iTaxRate = \Ext_TS_Vat::getTaxRate($iTaxCategory, $oSchool->id);
//				$aTax = \Ext_TS_Vat::calculateExclusiveTaxes($fPrice, $iTaxRate);
//				$fTax = $aTax['amount'];
//				$fTaxTotal += $fTax;
//			}

			$oItem
				->setName($aItem['description'])
				->setCurrency($oCurrency->getIso())
				->setQuantity(1)
				->setPrice($fPrice)
				->setTax($fTax);
			$oItemList->addItem($oItem);
			$fTotal += $fPrice;
			$fTaxTotal += $aItem['amount_tax'];
			$fTotalWithTax += $fPrice + $fTax;

		}

		$oAmountDetails = new Api\Details();
		$oAmountDetails->setSubtotal($fTotal);
		$oAmountDetails->setTax($fTaxTotal);

		$oAmount = new Api\Amount();
		$oAmount
			->setCurrency($oCurrency->getIso())
			->setTotal($fTotalWithTax)
			->setDetails($oAmountDetails);

		$oTransaction = new Api\Transaction();
		$oTransaction
			->setAmount($oAmount)
			//->setDescription('')
			->setItemList($oItemList)
			->setCustom(json_encode($aCustomData));

		$oPayerInfo = new Api\PayerInfo();
		$oPayerInfo->setEmail($oInquiry->getTraveller()->getFirstEmailAddress()->email);

		$oPayer = new Api\Payer();
		$oPayer->setPaymentMethod('paypal');
		$oPayer->setPayerInfo($oPayerInfo);

		$oRedirectUrls = new Api\RedirectUrls();
		$oRedirectUrls
			->setReturnUrl($this->oCombination->getRequestingUrl('return=payment&success=true'))
			->setCancelUrl($this->oCombination->getRequestingUrl('return=payment&success=false'));

		$oPayment = new Api\Payment();
		$oPayment
			->setIntent('sale')
		    ->setPayer($oPayer)
			->setExperienceProfileId($this->getExperienceProfileId())
		    ->setRedirectUrls($oRedirectUrls)
		    ->setTransactions([$oTransaction]);

		$oPayment->create($this->createApiContext());

		$sConfirmationMessage = str_replace('{paypal_approval_link}', $oPayment->getApprovalLink(), $sConfirmationMessage);

	}

	/**
	 * Nach Bezahlung bzw. Rückleitung von PayPal auf das Formular: Payment speichern
	 *
	 * @return bool
	 */
	public function executePayment() {

		$oRequest = $this->oCombination->getRequest();

		if($oRequest->get('success') === 'true') {

			$oApiContext = $this->createApiContext();
			$oPayment = Api\Payment::get($oRequest->get('paymentId'), $oApiContext);

			// Eigentlich sollte das durch commit nicht notwendig sein, aber ansonsten ist related_resources leer
			// Zudem sorgt das aber auch dafür, dass bei F5 der Code nicht noch einmal ausgeführt werden sollte
			$oPaymentExecution = new Api\PaymentExecution();
			$oPaymentExecution->setPayerId($oRequest->get('PayerID'));
			$oPaymentExecution = $oPayment->execute($oPaymentExecution, $oApiContext);
			//$oPayment = Api\Payment::get($oRequest->get('paymentId'), $this->createApiContext());

			if($oPaymentExecution->getState() === 'failed') {
				return false;
			}

			$oOnlinePayment = $this->createOnlinePayment($oPaymentExecution);

			if($oOnlinePayment === null) {
				return false;
			}

			$oInquiry = $this->getInquiryFromPayment($oPaymentExecution);
			$oUnallocatedPayment = $this->createUnallocatedPayment($oOnlinePayment);

			// Zahlung automatisch zuweisen
			$aStackData = [
				'object' => \Ext_TS_Inquiry::class,
				'object_id' => $oInquiry->id,
				'unallocated_payment_id' => $oUnallocatedPayment->id,
				'type' => 'paypal', // Nur für Error-Stack
				'paypal_payment' => $oPaymentExecution->toArray() // Nur für Error-Stack
			];

			$oStackRepository = Stack::getRepository();
			$oStackRepository->writeToStack('ts-frontend/form-payment', $aStackData, 5);

			return true;

		}

		// Payment abgebrochen
		return false;

	}

	/**
	 * @param Api\Payment $oPayment
	 * @return OnlinePayment|null
	 */
	private function createOnlinePayment(Api\Payment $oPayment) {

		$oTransaction = $this->getTransactionFromPayment($oPayment);

		if(empty($oTransaction->getRelatedResources())) {
			throw new \RuntimeException('No related resources in transaction!');
		}

		$oPayerInfo = $oPayment->getPayer()->getPayerInfo();
		$oAmount = $oTransaction->getAmount();
		$oCurrency = \Ext_Thebing_Currency::getByIso($oAmount->getCurrency());
		$oRelatedResource = reset($oTransaction->getRelatedResources()); /** @var \PayPal\Api\RelatedResources $oRelatedResource */
		$oSale = $oRelatedResource->getSale();

		if(
			$oCurrency === null ||
			!$oCurrency->exist()
		) {
			throw new \RuntimeException('Unknown PayPal currency: '.$oAmount->getCurrency());
		}

		// Früher konnte man PayPal-Zahlungen nicht mehrfach ausführen, mittlerweile geht das
		// Daher muss überprüft werden, ob eine Zahlung mit diesem Transaktionscode bereits existiert
		$oInquiryPayment = \Ext_Thebing_Inquiry_Payment::getRepository()->findOneBy(['transaction_code' => $oSale->id]);
		if($oInquiryPayment !== null) {
			return null;
		}

		$sComment = \L10N::t('E-Mail', \Ext_Thebing_Document::$sL10NDescription).': '.$oPayerInfo->getEmail()."\n";
		$sComment .= \L10N::t('Transaktionscode', \Ext_Thebing_Document::$sL10NDescription).': '.$oSale->getId();

		$oDto = new OnlinePayment();
		$oDto->sTransactionCode = $oSale->getId();
		$oDto->sComment = $sComment;
		$oDto->sFirstname = $oPayerInfo->getFirstName();
		$oDto->sLastname = $oPayerInfo->getLastName();
		$oDto->fAmount = (float)$oAmount->getTotal();
		$oDto->sCurrencyIso = $oAmount->getCurrency();
		$oDto->sPaymentDate = (new \DateTime($oPayment->getCreateTime()))->format('Y-m-d');

		return $oDto;

	}

	/**
	 * Transkation aus PayPal-Payment extrahieren
	 *
	 * @param Api\Payment $oPayment
	 * @return \PayPal\Api\Transaction
	 */
	private function getTransactionFromPayment(Api\Payment $oPayment) {

		if($iCount = count($oPayment->getTransactions()) !== 1) {
			throw new \RuntimeException('Wrong count of PayPay payment transactions! '.$iCount);
		}

		return reset($oPayment->getTransactions());

	}

	/**
	 * Inquiry aus Paypal-Payment extrahieren (im custom-Feld der Transaktion)
	 *
	 * @param Api\Payment $oPayment
	 * @return \Ext_TS_Inquiry|null
	 */
	private function getInquiryFromPayment(Api\Payment $oPayment) {

		$oTransaction = $this->getTransactionFromPayment($oPayment);

		// inquiry_id wird als JSON im Feld custom mitgegeben
		$aCustom = json_decode($oTransaction->getCustom(), true);
		if(!empty($aCustom['inquiry_id'])) {
			$oInquiry = \Ext_TS_Inquiry::getInstance($aCustom['inquiry_id']);
			if($oInquiry->exist()) {
				return $oInquiry;
			}
		}

		return null;

	}

	/**
	 * @inheritdoc
	 */
	public static function getSettings() {
		return \TsFrontend\ExternalApps\PayPal::getSettings();
	}


}
