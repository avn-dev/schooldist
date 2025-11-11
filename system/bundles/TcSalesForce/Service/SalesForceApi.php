<?php

namespace TcSalesForce\Service;

use Core\Helper\DateTime;
use TcSalesForce\ErrorHandler\ApiErrorHandler;
use \System;


/**
 * Class SalesForceApi SalesForce Api übermittelt Daten nach SalesForce
 *
 * @property array $aAuth
 * @property string $sRecordTypeId
 * @property \Monolog\Logger $oLog
 * @property string $sReturnType
 * @property object $last_response
 * @property $handle
 * @property array $headers
 * @property object $oEntity
 * @property bool $bStopCurrentProcess
 *
 * @package TcSalesForce
 */
abstract class SalesForceApi extends Api {

	/**
	 * Schlüssel für die Systemtabelle um den Wert zuermitteln ob die SalesForce Api aktiv oder nicht aktiv ist.
	 *
	 * @var string
	 */
	const IS_ACTIVE_KEY = 'tc_sales_force_api_active';

	/**
	 * Gibt an als welchen VariableTyp der Response erfolgen soll
	 *
	 * @var string
	 */
	protected $sReturnType = '';

	/**
	 * Letzte empfangene Antwort
	 *
	 * @var object
	 */
	protected $last_response;

	/**
	 * @var
	 */
	private $handle;

	/**
	 * @var array
	 */
	private $headers = [];

	/**
	 * Entity-Objekt
	 */
	private $oEntity;

	/**
	 * Sagt aus ob der aktuelle Prozess beendet werden soll
	 * (z.B. durch erkannter Fehlermeldung)
	 *
	 * @var bool
	 */
	protected $bStopCurrentProcess = false;

	/**
	 * Der Response soll als Objekt zurück gegeben werden
	 *
	 * @var string
	 */
	const RETURN_OBJECT = 'object';

	/**
	 * Der Response soll als Array zurück gegeben werden
	 *
	 * @var string
	 */
	const RETURN_ARRAY = 'array';

	// Supported request methods
	const
		METH_DELETE = 'DELETE',
		METH_GET    = 'GET',
		METH_POST   = 'POST',
		METH_PUT    = 'PUT',
		METH_PATCH  = 'PATCH';

	/**
	 * @var string
	 */
	const LOGIN_PATH   = '/services/oauth2/token';

	/**
	 * @var string
	 */
	const OBJECT_PATH = 'sobjects/';

	/**
	 * @var string
	 */
	const GRANT_TYPE  = 'password';

	/**
	 * Bekannte Fehlermeldung von SalesForce, wenn eine Entity bei SalesForce gelöscht wurde,
	 * jedoch die ID im Update-Request steht
	 *
	 * @var string
	 */
	const ENTITITY_IS_DELETED = 'ENTITY_IS_DELETED';

	/**
	 * Bekannte Fehlermeldung wenn ein Pflichtfeld bei SalesForce nicht im Request enthalten ist.
	 *
	 * @var string
	 */
	const REQUIRED_MISSING_FIELD = 'REQUIRED_FIELD_MISSING';

	public function __construct(\WDBasic $oEntity) {
		
		$this->oEntity = $oEntity;
		
		$this->oLog = \Log::getLogger('tc_salesforce');
	}

	/**
	 * API Login Request
	 *
	 * @return bool
	 */
	public function login() {

		$sUrl = System::d('tc_sf_api_url');

		$aParams = [];
		$aParams['grant_type'] = 'password';
		$aParams['client_id'] = System::d('tc_sf_api_client_id');
		$aParams['client_secret'] = System::d('tc_sf_api_client_secret');
		$aParams['username'] = System::d('tc_sf_api_username');
		$aParams['password'] = System::d('tc_sf_api_password');
		$sQuery = http_build_query($aParams);

		$hCurl = curl_init($sUrl);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_SSLVERSION, 4);

		curl_setopt($hCurl, CURLOPT_POST, true);
		curl_setopt($hCurl, CURLOPT_POSTFIELDS, $sQuery);

		$sJsonResponse = curl_exec($hCurl);
		$this->oLog->addInfo('Auth', array($sJsonResponse));

		if(empty($sJsonResponse)) {
			return false;
		}

		$aData = json_decode($sJsonResponse, true);

		if (empty($aData['access_token'])) {
			return false;
		}

		$this->aAuth = $aData;

		return true;

	}

	/**
	 * Create a new record
	 *
	 * @param string $sObjectName
	 * @param array $aData
	 * @return mixed
	 * @throws ApiErrorHandler
	 */
	public function create($sObjectName, $aData) {
		return $this->request($this->getSobjectUrl().(string)$sObjectName, $aData, self::METH_POST);
	}

	/**
	 * Update an existing object
	 *
	 * @param string $sObjectName
	 * @param string $sObjectId
	 * @param array $aData
	 * @return mixed
	 * @throws ApiErrorHandler
	 */
	public function update($sObjectName, $sObjectId, array $aData) {
		return $this->request($this->getSobjectUrl().(string)$sObjectName.'/'.$sObjectId, $aData, self::METH_PATCH);
	}

	/**
	 * @return array
	 */
	public function getAuth() {
		return $this->aAuth;
	}

	/**
	 * Sendet eine Anfrage
	 *
	 * @param string $url
	 * @param array $params
	 * @param string $method
	 * @param array|null $headers
	 * @return array
	 */
	protected function request($url, $params = null, $method = self::METH_GET, $headers = null) {

		$this->handle = curl_init();
		$options = [
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 240,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_BUFFERSIZE => 128000
		];
		curl_setopt_array($this->handle, $options);

		// Set the headers
		if(isset($headers) && $headers !== null && !empty($headers)) {
			$aRequestHeaders = array_merge($this->headers,$headers);
		} else {
			$aRequestHeaders = $this->headers;
		}

		// Modify the request depending on the type of request
		switch($method)
		{
			case 'POST':
				curl_setopt($this->handle, CURLOPT_POST, true);
				$json_params = json_encode($params);
				curl_setopt($this->handle, CURLOPT_POSTFIELDS, $json_params);
				break;
			case 'GET':
				curl_setopt($this->handle, CURLOPT_POSTFIELDS, []);
				curl_setopt($this->handle, CURLOPT_POST, false);
				if(isset($params) && $params !== null && !empty($params))
					$url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
				break;
			default:
				curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
				$json_params = json_encode($params);
				curl_setopt($this->handle, CURLOPT_POSTFIELDS, $json_params);
				break;
		}

		$aRequestHeaders['Authorization'] = 'OAuth '.$this->aAuth['access_token'];
		$aRequestHeaders['Content-type'] = 'application/json';

		curl_setopt($this->handle, CURLOPT_URL, $url);
		curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->createCurlHeaderArray($aRequestHeaders));
		$response = curl_exec($this->handle);
		$this->oLog->addInfo('Request Data: ' . $url, array($params));
		$this->oLog->addInfo('Response Data: ' . $response);
		$response = $this->checkForRequestErrors($response, $this->handle);
		$result = json_decode($response, true);

		return $result;
	}

	/**
	 * @param string $sSql
	 * @return array
	 */
	public function query($sSql) {

		$sSql = trim(preg_replace('/\s+/', '+', $sSql), '+');

		$hCurl = curl_init(
			$this->aAuth['instance_url']."/services/data/v32.0/query/?q=".$sSql
		);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_HTTPHEADER, array(
			'Authorization: OAuth '.$this->aAuth['access_token'],
			'Content-type: application/json'
		));
		$oResponse = curl_exec($hCurl);

		$oResponse = $this->checkForRequestErrors($oResponse, $hCurl);

		curl_close($hCurl);

		$aResultData = json_decode($oResponse, true);

		return $aResultData;

	}

	/**
	 * Checks for errors in a request
	 *
	 * @param string $response The response from the server
	 * @param Resource $handle The CURL handle
	 * @return string The response from the API
	 * @throws ApiErrorHandler
	 * @see http://www.salesforce.com/us/developer/docs/api_rest/index_Left.htm#CSHID=errorcodes.htm|StartTopic=Content%2Ferrorcodes.htm|SkinName=webhelp
	 */
	private function checkForRequestErrors($response, $handle) {

		$curl_error = curl_error($handle);

		if($curl_error !== '') {
			throw new ApiErrorHandler($curl_error);
		}

		$request_info = curl_getinfo($handle);

		if($this->knownErrorCode($response)) {
			$this->bStopCurrentProcess = true;
			return $response;
		}

		switch($request_info['http_code']) {
			case 304:
				if($response === '')
					return json_encode(['message' => "The requested object has not changed since the specified time"]);
				break;
			case 300:
			case 200:
			case 201:
			case 204:
				if($response === '')
					return json_encode(['success' => true]);
				break;
			default:
				if(empty($response) || $response !== '') {
					throw new ApiErrorHandler($response);
				} else {
					$result = json_decode($response);
					if(isset($result->error))
						throw new ApiErrorHandler($result->error_description);
				}
				break;
		}

		$this->last_response = $response;

		return $response;
	}

	/*========== Object Metadata ============*/
	/**
	 * Get metadata about an Object
	 *
	 * @param string $object_name
	 * @param bool $all Should this return all meta data including information about each field, URLs, and child relationships
	 * @param DateTime $since Only return metadata if it has been modified since the date provided
	 * @return mixed
	 * @throws ApiErrorHandler
	 */
	public function getObjectMetadata($object_name, $all = false, DateTime $since = null) {
		$headers = [];
		// Check if the If-Modified-Since header should be set
		if($since !== null && $since instanceof DateTime) {
			$headers['IF-Modified-Since'] = $since->format('D, j M Y H:i:s e');
		} elseif($since !== null && !$since instanceof DateTime) {
			// If the $since flag has been set and is not a DateTime instance, throw an error
			throw new ApiErrorHandler('To get object metadata for an object, you must provide a DateTime object');
		}
		// Should this return all meta data including information about each field, URLs, and child relationships
		if($all === true) {
			return $this->request($this->getSobjectUrl(). $object_name . '/describe/',[],self::METH_GET, $headers);
		} else {
			return $this->request($this->getSobjectUrl(). $object_name,[],self::METH_GET,$headers);
		}
	}

	public function getSobjectUrl() {
		return $this->aAuth['instance_url'].'/services/data/v36.0/sobjects/';
	}

	/**
	 * Gibt den Type zurück wie die SalesForce Api den Response zurück geben soll.
	 *
	 * @param string $sReturnType
	 */
	public function setReturnType($sReturnType = 'array') {
		$this->sReturnType = $sReturnType;
	}

	/**
	 * Objekt wird zu Array konvertiert
	 *
	 * @param $oObject
	 * @return array
	 */
	public function objectToArray($oObject) {

		if(!is_object($oObject) && !is_array($oObject)) {
			return $oObject;
		}

		if(is_object($oObject)) {
			$oObject = get_object_vars($oObject);
		}

		return array_map(array($this, 'objectToArray'), $oObject );

	}

	/**
	 * Setzt die RecordTypeId
	 *
	 * @param string $sRecordTypeId
	 */
	public function setRecordTypeId($sRecordTypeId) {
		$this->sRecordTypeId = $sRecordTypeId;
	}


	/**
	 * Prüft ob die SalesForce Api aktiviert ist oder nicht.
	 *
	 * @return bool
	 */
	public static function isActive() {

		$iActive = (int)System::d(self::IS_ACTIVE_KEY);

		if ($iActive === 1) {
			return true;
		}

		return false;

	}

	/**
	 * Makes the header array have the right format for the Salesforce API
	 *
	 * @param $headers
	 * @return array
	 */
	private function createCurlHeaderArray($headers) {
		$curl_headers = [];
		// Create the header array for the request
		foreach($headers as $key => $header) {
			$curl_headers[] = $key . ': ' . $header;
		}
		return $curl_headers;
	}

	/**
	 * Führt das senden inklusive einloggen der API aus.
	 *
	 * @return mixed
	 */
	abstract function transfer();

	/**
	 * Wenn die Fehlermeldung bekannt ist, weiß die Software auch was zu tun ist.
	 *
	 * @param $response
	 * @return bool
	 */
	private function knownErrorCode($response) {

		$aResponse = json_decode($response, true);

		$bFoundErrorCode = false;
		switch($aResponse[0]['errorCode']) {
			case self::ENTITITY_IS_DELETED:
				$this->oEntity->salesforce_id = "";
				$this->oEntity->save();
				$bFoundErrorCode = true;
				break;
			case self::REQUIRED_MISSING_FIELD:
				// Bei diesem Fall soll er nicht weiter probieren die Agentur zu SalesForce zu schicken, da
				// Pflichtfelder fehlen!
				$this->oLog->addInfo('Failed transfer to salesfoce. Reason: ' . $aResponse[0]['message']);
				break;
		}

		return $bFoundErrorCode;

	}

	/**
	 * Setzt die Entity die gerade übermittelt werden soll
	 *
	 * @param $oEntity
	 */
	public function setEntity($oEntity) {
		$this->oEntity = $oEntity;
	}

	/**
	 * Prüft ob die Buchung eine SalesForce Id hat
	 *
	 * @return bool
	 */
	private function hasSalesForceId() {
		return !empty($this->oEntity->salesforce_id);
	}

	/**
	 * Speichert die SalesForce Id zum Inquiry-Objekt
	 *
	 * @param array $aData
	 */
	private function saveSalesForceId(array $aData) {
		$this->oEntity->salesforce_id = $aData['id'];
		$this->oEntity->saveAttributes();
	}

}