<?php

namespace TsFrontend\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use TsFrontend\Exceptions\PaymentError;

/**
 * Wrapper für XNL-API von TransferMate Education
 *
 * @link https://redmine.fidelo.com/projects/ts-frontend/wiki/TransferMate
 */
class TransferMateApi {

	const STATUS_REGISTERED = 0;

	const STATUS_PENDING = 1;

	const STATUS_PAID = 2;

	const STATUS_INACTIVE = 3;

	const STATUS_THIRDPARTY_SUCCESSFUL = 2;

	const STATUS_THIRDPARTY_CANCELLED = 3;

	/**
	 * @var string
	 */
	private string $username;

	/**
	 * @var string
	 */
	private string $password;

	/**
	 * @var Client
	 */
	private Client $client;

	public function __construct(string $client, string $username, string $password) {

		$this->username = $username;
		$this->password = $password;

		$this->client = new Client([
			'base_uri' => 'https://'.$client.'.transfermateeducation.com/',
			RequestOptions::TIMEOUT => 30
		]);

	}

	/**
	 * Request gegenüber TransferMate-API ausführen (alles XML und Status 200)
	 *
	 * @param string $path
	 * @param array $params
	 * @return \SimpleXMLElement
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function executeRequest(string $path, $params = []): \SimpleXMLElement {

		// Muss immer als x-www-form-urlencoded mitgeschliffen werden (die 90er rufen an)
		$allParams = array_merge([
			'username_loginto' => $this->username,
			'password_loginto' => $this->password,
		], $params);

		// Alles ist POST
		$response = $this->client->post($path, [
			RequestOptions::FORM_PARAMS => $allParams
		]);

		// Wenn irgendwelche Werte fehlen, gibt es einfach eine HTML-Seite mit Login, da das hier irgendwie eine gefakte API ist
		if (str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
			throw (new PaymentError('TransferMate: Content-Type text/html'))->setAdditional(['params' => $allParams]);
		}

		$body = (string)$response->getBody();

		$xml = simplexml_load_string($body);

		if (!$xml instanceof \SimpleXMLElement) {
			throw (new PaymentError('TransferMate: XML not valid'))->setAdditional(['response' => $body, 'path' => $path, 'params' => $allParams]);
		}

		// Da das keine REST-API ist, ist immer alles 200 und ein möglicher Fehler ist in der XML-Struktur
		if ((string)$xml->operation->result === 'failure') {
			throw (new PaymentError('TransferMate: XML failure'))->setAdditional(['error' => (array)$xml->operation, 'response' => $body, 'path' => $path, 'params' => $allParams]);
		}

		return $xml;

	}

	/**
	 * Countries API: Unterstützte Länder von TransferMate
	 *
	 * @link https://transfermateeducation.com/example/api/4.2/countries/parameters/
	 * @link https://transfermateeducation.com/example/api/4.2/countries/response/breakdown/
	 */
	public function getCountries(): Collection {

		$xml = $this->executeRequest('Countries');

		$countries = collect();
		foreach ($xml->countries->country as $country) {
			$countries[] = ['key' => (string)$country->iso_2_code, 'label' => (string)$country->name];
		}

		return $countries;

	}

	/**
	 * FXConversion API: Zahlungsmethoden müssen immer vorab mit Betrag und Land ermittelt werden
	 *
	 * @link https://transfermateeducation.com/example/api/4.2/fxconversion/parameters/
	 * @link https://transfermateeducation.com/example/api/4.2/fxconversion/response/breakdown/
	 *
	 * @param int $bankAccountId
	 * @param float $paymentAmount
	 * @param string $countryIso
	 * @return Collection
	 */
	public function getPaymentMethods(int $bankAccountId, float $paymentAmount, string $countryIso): Collection {

		$xml = $this->executeRequest('FXConversion', [
			'bank_account_id' => $bankAccountId,
			'payment_amount' => $paymentAmount,
			'country_pay_from' => $countryIso
		]);

		$methods = collect();

		foreach ($xml->rates->rate as $rate) {

			$methods[] = [
				'type' => (string)$rate->attributes()['type'],
				'payment_method_id' => (int)$rate->attributes()['payment_method_id'],
				'payment_method_flow' => (string)$rate->attributes()['payment_method_flow'],
				'locked' => (string)$rate->attributes()['locked'] === 'true',
				'lock_reason' => (string)$rate->attributes()['lock_reason'],
				'icon' => (string)$rate->attributes()['logo'],
				'converted_amount' => (float)$rate->converted_amount, // Amount + TransferMate Taxes (Fees)
				'converted_currency' => (string)$rate->converted_currency,
//				'icon_class' => $this->icons[(int)$rate->attributes()['payment_method_id']] ?? null
			];

		}

		return $methods;

	}

	/**
	 * MakeAPayment API: Zahlung erstellen
	 *
	 * @link https://transfermateeducation.com/example/api/4.2/makeapayment/parameters/
	 * @link https://transfermateeducation.com/example/api/4.2/makeapayment/response/flow/direct/breakdown/
	 * @link https://transfermateeducation.com/example/api/4.2/makeapayment/response/flow/redirect/breakdown/
	 *
	 * @param int $bankAccountId
	 * @param array $params
	 * @return array
	 */
	public function createPayment(int $bankAccountId, array $params): array {

		$params['bank_account_id'] = $bankAccountId;

		$xml = $this->executeRequest('MakeAPayment', $params);

		return json_decode(json_encode($xml->payment), true);

	}

	/**
	 * PaymentHistory: Historie/Status über Zahlungen
	 *
	 * @link https://transfermateeducation.com/example/api/4.2/paymenthistory/parameters/
	 * @link https://transfermateeducation.com/example/api/4.2/paymenthistory/response/breakdown/
	 *
	 * @param array $filters
	 * @return Collection
	 */
	public function getPaymentHistory(array $filters): Collection {

		$xml = $this->executeRequest('PaymentHistory', $filters);

		$payments = collect();
		foreach ($xml->payments->payment as $payment) {
			$payments->push(json_decode(json_encode($payment), true));
		}

		return $payments;

	}

	/**
	 * @see getPaymentHistory()
	 * @param int $id
	 * @return array
	 */
	public function findPaymentById(int $id): array {

		$payments = $this->getPaymentHistory(['filter_payment_id' => $id]);

		if ($payments->count() !== 1) {
			throw (new PaymentError('TransferMate: Wrong count of payments: '.$payments->count()))->setAdditional(['id' => $id]);
		}

		return $payments->first();

	}

}
