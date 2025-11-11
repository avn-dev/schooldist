<?php

namespace Licence\Service\Office;

use Illuminate\Support\Str;
use Licence\Service\Office\Api\Response;

class Api {
	
	protected $sHost = 'https://fidelo.com';
	
	public function requestBillings(\DateTime $dFrom, \DateTime $dUntil): array {
		
		$oObject = new Api\Object\BillingsForPeriod(new \Core\DTO\DateRange($dFrom, $dUntil));

		$oResponse = $this->request($oObject);

		if($oResponse->isSuccessful()) {
			return $oResponse->get('data', []);
		}
		
		return [];
	}
	
	public function requestBillingPdf($iDocumentId): array {
		
		$oObject = new Api\Object\BillingsPdf((int) $iDocumentId);

		$oResponse = $this->request($oObject);
		
		if($oResponse->isSuccessful()) {			
			return $oResponse->get('data', []);
		}
		
		return [];
	}
	
	public function addLog(string $sSubject, string $sMessage = '', string $errorLevel = ''): bool {
		
		$oObject = new Api\Object\Log($sSubject, $sMessage, $errorLevel);

		$oResponse = $this->request($oObject);
		
		if($oResponse->isSuccessful()) {			
			return true;
		}
		
		return false;
	}

	public function getAccessRights(): Response {
		$object = new Api\Object\AccessRights();
		return $this->request($object);
	}

	/**
	 * Registriert eine Rechnung im Office
	 *
	 * @param \DateTimeInterface $created
	 * @param string $hash
	 * @param string $documentNumber
	 * @return Response
	 */
	public function registerInvoice(\DateTimeInterface $created, string $hash, string $documentNumber): Response
	{
		$object = new Api\Object\RegisterInvoice($created, $hash, $documentNumber);
		return $this->request($object);
	}

	/**
	 * Registriert eine Rechnung im Verifactu System
	 *
	 * @param string $operation
	 * @param string $payload
	 * @param string $certificate
	 * @param string $certificatePassword
	 * @param bool $test
	 * @return Response
	 */
	public function verifactuCall(
		string $operation,
		string $payload,
		string $certificate,
		string $certificatePassword,
		bool $test = false
	): Response {
		$object = new Api\Object\Verifactu(
			$operation,
			$payload,
			$certificate,
			$certificatePassword,
			$test
		);
		return $this->request($object);
	}

	public function request(Api\AbstractObject $oObject, int $timeout = 120): Response {
		
		$sAuthKey = Api\AuthKey::encode(\System::d('license_auth_key'), \System::d('license'), \Util::getHost());

		$oRequest = new Api\Request($this->buildFullUrl($oObject->getUrl()), $oObject->getRequestMethod());
		
		$oObject->prepareRequest($oRequest);

		$aHeader = [
			sprintf('Authorization: Bearer %s', $sAuthKey)
		];

		$hCurl = curl_init($oRequest->getUrl());

		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_CUSTOMREQUEST, $oObject->getRequestMethod());
		
		if($oRequest->isPostRequest() === true) {
			curl_setopt($hCurl, CURLOPT_POSTFIELDS, json_encode($oRequest->getData()));
			$aHeader[] = 'Content-Type:application/json';
		}

		curl_setopt($hCurl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($hCurl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($hCurl, CURLOPT_HTTPHEADER, $aHeader);
		
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, 5); 
		curl_setopt($hCurl, CURLOPT_TIMEOUT, $timeout);

		$sJsonResponse = curl_exec($hCurl);

		$aCurlInfo = curl_getinfo($hCurl);

		curl_close($hCurl);

		if(empty($sJsonResponse) || $aCurlInfo["http_code"] !== 200) {
			return new Api\Response([
				'success' => false,
				'message' => $sJsonResponse
			], $aCurlInfo['http_code']);
		}

		$aResponseData = (array)json_decode($sJsonResponse, true);

		$oResponse = new Api\Response($aResponseData, $aCurlInfo['http_code']);

		return $oObject->prepareResponse($oResponse);
	}
	
	/**
	 * Build clean url with base url
	 * 
	 * @param string $sSubUrl
	 * @return string
	 */
	private function buildFullUrl(string $sSubUrl): string {
		return $this->sHost.Str::start($sSubUrl, '/');
	}

}

