<?php

namespace TsFrontend\Handler\Payment;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Core\Facade\Cache;
use Tc\Service\Language\Frontend;
use TsFrontend\Exceptions\PaymentChallenge;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

/**
 * Redsys-Implementierung über Insite Connection und REST-API (kein PCI DSS notwendig)
 *
 * Wiki:
 * @link https://redmine.fidelo.com/projects/ts-frontend/wiki/Redsys
 *
 * Generelle Integration:
 * @link https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-tipos-de-integracion/desarrolladores-insite/
 * @link https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-operativa/autorizacion/#rest-info
 *
 * Parameter + Error Codes:
 * @link https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/parametros-de-entrada-y-salida/
 *
 * Testdaten (unten):
 * @link https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/tarjetas-y-entornos-de-prueba/
 *
 * 3DS-API:
 * @link https://docs.3dsecure.io/3dsv2/specification_210.html
 *
 * Daten für Test Environment:
 * Key: sq7HjrUOBfKmC576ILgskD5srU870gJ7
 * Merchant: 999008881
 * Terminal: 1
 *
 * Kreditkarte für Test (3DS Auth):
 * 4548810000000003 12/49 123
 *
 * Frictionless:
 * 4548814479727229 12/49 123
 */
class Redsys implements PaymentProvider\RegistrationForm, PaymentProvider\PaymentForm, PaymentProvider\WebhookCapture
{
	use PaymentProviderTrait;

	const CARD_TYPES = [
		1 => 'VISA',
		2 => 'Mastercard',
		6 => 'Diners',
		7 => 'PRIVADA',
		8 => 'AMEX',
		9 => 'JCB',
		22 => 'UPI',
	];

	const RESPONSE_TEXTS = [
		101 => 'Card expired',
		102 => 'Card blocked temporarily or under susciption of fraud',
		106 => 'PIN attempts exceeded',
		125 => 'Card not effective',
		129 => 'CVV2/CVC2 Error',
		172 => 'Denied, do not repeat.',
		173 => 'Denied, do not repeat without updating card details.',
		174 => 'Denied, not to be repeated within 72 hours.',
		180 => 'Card out of service',
		184 => 'Authentication error',
		190 => 'Refusal with no specific reason',
		191 => 'Expiry date incorrect',
		195 => 'Requires SCA authentication',
		202 => 'Card blocked temporarily or under suspicion of fraud'
	];

	private array $methods = [];

	public function __construct(private readonly Request $request)
	{

	}

	public function getScriptUrl(): string
	{
		if ($this->school->getMeta('redsys_testing')) {
			return 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
		}

		return 'https://sis.redsys.es/sis/NC/redsysV3.js';
	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array
	{
		$amount = round($items->sum('amount_with_tax'), 2);
		$currency = $inquiry->getCurrency(true);

		// Identifikation für Payment: Muss laut API Docs 12-stellig alphanummerisch sein und mit 4 Ziffern beginnen
		$orderId = str_pad($inquiry->id, 4, '0') . 'FID' . Str::random(5);
		$orderId = substr($orderId, 0, 12);

		$data = collect([
			'fuc' => $this->school->getMeta('redsys_merchant_code'),
			'terminal' => $this->school->getMeta('redsys_merchant_terminal'),
			'language' => $inquiry->getCustomer()->getLanguage(),
			'order_id' => $orderId,
			'amount' => \Ext_Thebing_Format::Number($amount, null, $this->school) . ' ' . $currency->getIso(), // Währungszeichen funktionieren nicht
			'status' => 'authorize',
			'challenge' => []
		]);

		// Im Cache speichern, da die Daten sonst nicht existieren und damit nichts manipuliert werden kann
		Cache::put($this->buildCacheKey($data), 60 * 30, [
			'order_id' => $orderId,
			'amount_raw' => $amount,
			'currency' => (int)$inquiry->getCurrency(true)->getIsoNum()
		]);

		return $data->toArray();
	}

	/**
	 * Pre-Auth durchführen, da es keine Möglichkeit gibt, den Status in irgendeiner Weise zu überprüfen
	 *
	 * @link https://pagosonline.redsys.es/funcionalidades-preautorizacion.html
	 */
	public function checkPayment(Collection $data): bool
	{
		if (!($params = $this->getCache($data))) {
			throw $this->createError('Redsys payment expired or does not exist', $data->toArray());
		}

		$params->put('token', $data->get('token'));
		$params->put('transaction_type', 1);

		if ($data->get('status') === 'authorize') {

			// Eigenschaften der Karte abfragen
			$params->put('three_ds', ['threeDSInfo' => 'CardData']);
			$cardInformation = $this->executeRequest($params);

			// 3DS-Challenge-URL für iframe abrufen
			$params->put('three_ds', $this->buildThreeDsAuthenticationData($data, $cardInformation['Ds_EMV3DS']));
			$result = $this->executeRequest($params);

			// Karten ohne PSD2 werden von Redsys nicht mehr akzeptiert
			if (data_get($result, 'Ds_EMV3DS.threeDSInfo') === 'ChallengeRequest') {
				throw (new PaymentChallenge())->setAdditional([
					'url' => data_get($result, 'Ds_EMV3DS.acsURL'),
					'request' => data_get($result, 'Ds_EMV3DS.creq'), // JSON-encoded
				]);
			}

			// Frictionless
			if (!Str::startsWith($result['Ds_Response'], '00')) {
				// Bekannte Fehler ans Frontend
				$error = data_get(self::RESPONSE_TEXTS, ltrim($result['Ds_Response'], '0'));
				if ($error) {
					throw (new PaymentChallenge)->setAdditional(compact('error'));
				}

				// Internal Error ans Frontend
				throw $this->createError('Redsys transaction error ' . $result['Ds_Response'], [$result, $params]);
			}

			return true;

		}

		// Frictionless
		if (empty($data->get('challenge'))) {
			return true;
		}

		$challenge = json_decode(base64_decode(data_get($data, 'challenge.response')), true);
		if ($challenge['transStatus'] !== 'Y') {
			// Kunde hat 3DS-Challenge/Autorisierung nicht bestanden
			return false;
		}

		return true;
	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated
	{
		
		$this->createLogger()->debug('Redsys capture payment start', ['inquiry_id' => $inquiry->id, 'data' => $data]);
		
		if (!($params = $this->getCache($data))) {
			throw $this->createError('Redsys payment expired or does not exist', $data->toArray());
		}

		$params->put('token', $data->get('token'));
		
		if (!empty($data->get('challenge'))) {
			$secondParams = clone $params;
			$secondParams->put('transaction_type', 2); // Confirmation von Pre-Auth
			$params->put('three_ds', $this->buildThreeDsChallengeResponse(data_get($data, 'challenge.response')));
			$params->put('transaction_type', 1); // Erfolgt hier in einem weiteren Step
		} else {
			$params->put('transaction_type', 2); // Confirmation von Pre-Auth
		}

		$params->put('cardholder_name', $inquiry->getCustomer()->getName());

		$result = $this->executeRequest($params);

		if (empty($result['Ds_Response'])) {
			throw $this->createError('Redsys transaction error: No response code ', [$result, $params]);
		}

		$code = (int)$result['Ds_Response'];

		// Success-Werte: 00xx für type 0/1, 0900 für type 2
		if (($code < 0) || (($code > 99) && ($code !== 900))) {
			throw $this->createError('Redsys transaction error ' . $result['Ds_Response'], [$result, $params]);
		}
		
		$comment = [];
		$comment[] = 'Redsys payment';
		$comment[] = 'Order ID: ' . $result['Ds_Order'];
		if (isset($result['Ds_CardNumber'], $result['Ds_Card_Brand'])) {
			$comment[] = 'Card brand: ' . (self::CARD_TYPES[$result['Ds_Card_Brand']] ?? 'Unknown');
			$comment[] = 'Card number: ' . $result['Ds_CardNumber'];
		}

		// Zahlung abschliessen
		if (!empty($data->get('challenge'))) {
			
			$result = $this->executeRequest($secondParams);

			if (empty($result['Ds_Response'])) {
				throw $this->createError('Redsys transaction error: No response code ', [$result, $secondParams]);
			}

			$code = (int)$result['Ds_Response'];

			// Success-Werte: 00xx für type 0/1, 0900 für type 2
			if (($code < 0) || (($code > 99) && ($code !== 900))) {
				throw $this->createError('Redsys transaction error ' . $result['Ds_Response'], [$result, $secondParams]);
			}
			
		}
		
		$paymentUnallocated = new \Ext_TS_Inquiry_Payment_Unallocated();
		$paymentUnallocated->payment_method_id = $this->getAccountingPaymentMethod()->id;
		$paymentUnallocated->status = \Ext_Thebing_Inquiry_Payment::STATUS_PAID;
		$paymentUnallocated->transaction_code = $result['Ds_Order'];
		$paymentUnallocated->comment = join("\n", $comment);
		$paymentUnallocated->firstname = $inquiry->getCustomer()->firstname;
		$paymentUnallocated->lastname = $inquiry->getCustomer()->lastname;
		$paymentUnallocated->amount = (float)$params->get('amount_raw');
		$paymentUnallocated->amount_currency = $inquiry->getCurrency();
		$paymentUnallocated->payment_date = (new \DateTime($data['paid_date']))->format('Y-m-d');
		$paymentUnallocated->additional_info = json_encode(['type' => 'redsys', 'payment' => $result, 'data' => $data, 'params' => $params]);

		Cache::forget($this->buildCacheKey($data));

		return $paymentUnallocated;
	}

	/**
	 * Webhook wird hier tatsächlich als HTML-Seite verwendet, da die 3DS-Auth den iframe zwanghaft umleitet
	 */
	public function captureByWebhook(Collection $data): Response
	{
		if (!$data->get('cres')) {
			return response('No challenge response', 400);
		}

		$this->createLogger()->debug('Redsys webhook', ['data' => $data]);
		
		// * bei postMessage zwingend notwendig für verschachtelte iFrames
		$view = view('payment/redsys', [
			'challenge_response' => $data->get('cres')
		]);

		return response($view->render());
	}

	private function executeRequest(Collection $data): array
	{
		$payload = [
			'DS_MERCHANT_MERCHANTCODE' => $this->school->getMeta('redsys_merchant_code'),
			'DS_MERCHANT_ORDER' => $data->get('order_id'),
			'DS_MERCHANT_TERMINAL' => $this->school->getMeta('redsys_merchant_terminal')
		];
			
		// Auf int casten, da selbst Multiplikation mit 100 wieder Nachkommastellen erzeugen kann
		$payload['DS_MERCHANT_AMOUNT'] = (int)$data->get('amount_raw') * 100;
		$payload['DS_MERCHANT_CURRENCY'] = $data->get('currency');
		$payload['DS_MERCHANT_TRANSACTIONTYPE'] = $data->get('transaction_type');
		
		if ($data->get('transaction_type') !== 2 || !empty($data->get('three_ds'))) {
			$payload['DS_MERCHANT_IDOPER'] = $data->get('token');
		}

		if (!empty($data->get('three_ds'))) {
			$payload['DS_MERCHANT_EMV3DS'] = $data->get('three_ds');
			$payload['DS_MERCHANT_TRANSACTIONTYPE'] = 1; // SIS0814 (muss immer der gleiche Typ sein)
			if (data_get($data->get('three_ds'), 'threeDSInfo') === 'CardData') {
				$payload['DS_MERCHANT_EXCEP_SCA'] = 'Y'; // Supported Exemptions anfragen
			}
		}

		if ($data->has('cardholder_name')) {  
			$payload['DS_MERCHANT_TITULAR'] = mb_substr($data->get('cardholder_name'), 0, 60);
		}

		$merchantParameters = base64_encode(json_encode($payload));

		$json = [
			'Ds_MerchantParameters' => $merchantParameters,
			'Ds_Signature' => $this->buildSignature($data->get('order_id'), $merchantParameters),
			'Ds_SignatureVersion' => 'HMAC_SHA256_V1'
		];
		
		$url = $this->buildApiUrl($payload);
		
		$this->createLogger()->debug('Redsys request start', ['url' => $url, 'data' => $data, 'request' => $payload, 'json' => $json, 'backtrace' => \Util::getBacktrace()]);
		
		$response = (new Client())->post($url, [
			'json' => $json
		]);

		$result = json_decode($response->getBody(), true);

		if (!empty($result['errorCode'])) {
			throw $this->createError('Redsys error: ' . $result['errorCode'], ['request' => $payload, 'response' => $result]);
		}

		$responseData = json_decode(base64_decode($result['Ds_MerchantParameters']), true);

		$this->createLogger()->debug('Redsys request end', ['request' => $payload, 'response' => $responseData]);

		return $responseData;
	}

	public function getTranslations(Frontend $languageFrontend): array
	{
		return [
			'card_number' => $languageFrontend->translate('Card number'),
			'expiration_date' => $languageFrontend->translate('Expiration date'),
			'security_code' => $languageFrontend->translate('Security code'),
			'pay_now' => $languageFrontend->translate('Pay now: {amount}'),
			'payment_failed' => $languageFrontend->translate('The payment was not successful. Please try again or try another card.'),
		];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array
	{
		return [
			[
				'key' => 'redsys',
				'label' => $languageFrontend->translate('Redsys'),
				'icon_class' => 'fas fa-credit-card'
			],
		];
	}

	private function buildCacheKey(Collection $data): string
	{
		if (empty($data->get('order_id'))) {
			throw new \RuntimeException('order_id is empty');
		}

		return __CLASS__ . '_' . $data->get('order_id');
	}

	private function getCache(Collection $data): ?Collection
	{
		$cache = collect(Cache::get($this->buildCacheKey($data)));

		if ($cache->isEmpty() || !preg_match('/^[a-z0-9]{40}$/i', $data->get('token'))) {
			return null;
		}

		return $cache;
	}

	private function buildApiUrl(array $payload): string
	{
		$path = 'trataPeticionREST';
		if (data_get($payload, 'DS_MERCHANT_EMV3DS.threeDSInfo') === 'CardData') {
			// iniciaPeticionREST wird nur für diese eine Aktion benötigt
			$path = 'iniciaPeticionREST';
		}

		if (!$this->school->getMeta('redsys_testing')) {
			return 'https://sis.redsys.es/sis/rest/' . $path;
		}

		return 'https://sis-t.redsys.es:25443/sis/rest/' . $path;
	}

	private function buildSignature(string $orderId, string $merchantParameters): string
	{
		$key = $this->build3DESKey($orderId, base64_decode($this->school->getMeta('redsys_client_key')));

		return base64_encode(hash_hmac('sha256', $merchantParameters, $key, true));
	}

	/**
	 * TripleDES-Key für HMAC-Signatur: Kopiert aus Sermepa\Tpv (MIT), da mit phpseclib nicht möglich nachzubauen
	 */
	private function build3DESKey(string $data, string $key): string
	{
		$iv = "\0\0\0\0\0\0\0\0";
		$padded = $data;

		if (strlen($padded) % 8) {
			$padded = str_pad($padded, strlen($padded) + 8 - strlen($padded) % 8, "\0");
		}

		return openssl_encrypt($padded, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
	}

	private function buildThreeDsAuthenticationData(Collection $data, array $threeDs): array
	{
		$threeDs['threeDSInfo'] = 'AuthenticationData';
		$threeDs['browserAcceptHeader'] = $this->request->headers->get('Accept');
		$threeDs['browserUserAgent'] = $this->request->headers->get('User-Agent');
		$threeDs['browserJavaEnabled'] = false; // navigator.javaEnabled() wurde aus Standard entfernt
		$threeDs['browserJavascriptEnabled'] = true;
		$threeDs['browserLanguage'] = $this->request->getPreferredLanguage();
		$threeDs['browserColorDepth'] = data_get($data, 'browser.depth');
		$threeDs['browserScreenHeight'] = data_get($data, 'browser.height');
		$threeDs['browserScreenWidth'] = data_get($data, 'browser.width');
		$threeDs['browserTZ'] = data_get($data, 'browser.tz');
		$threeDs['notificationURL'] = sprintf('https://%s/api/1.0/ts/frontend/webhook/payment/redsys', \Util::getHost());
		$threeDs['threeDSCompInd'] = 'N';
		$threeDs['challengeWindowSize'] = data_get($data, 'browser.size', '01');
//		$threeDs['threeDSRequestorChallengeInd'] = '04'; // Frictionless verbieten (immer starke Auth)

		return $threeDs;
	}

	private function buildThreeDsChallengeResponse(string $challengeResponse): array
	{
		$challenge = json_decode(base64_decode($challengeResponse), true);

		return [
			'threeDSInfo' => 'ChallengeResponse',
			'protocolVersion' => $challenge['messageVersion'],
			'cres' => $challengeResponse
		];
	}
}