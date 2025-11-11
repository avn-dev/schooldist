<?php

namespace TcSalesForce\Service;

class Api {
	
	/**
	 * Connection timeout für curl
	 */
	const CURL_CONNECTION_TIMEOUT = 5;	
	
	/**
	 * Passwort for RefreshKey AES encryption
	 */
	const ENCRYPTION_PASSWORD = 'Vzyjw0Xd2alSSaa3uxEn';
	
	/**
	 * @var array
	 */
	private $aAuth = array();

	/**
	 * @var string
	 */
	private $sRecordTypeId;

	/**
	 * @var \Monolog\Logger
	 */
	private $oLog;
	
	private $sClientId;
	
	private $sClientSecret;

	private $sTokenUrl = 'https://login.salesforce.com/services/oauth2/token';

	private $sRedirectUriProxy = 'https://update.fidelo.com/salesforce.php';

	public function __construct($sClientId, $sClientSecret) {

		$this->oLog = \Log::getLogger('salesforce');

		$this->sClientId = $sClientId;
		$this->sClientSecret = $sClientSecret;
		
	}

	public function checkConnection() {
	
		$sRefreshToken = $this->getRefreshToken();
		
		if(!empty($sRefreshToken)) {

			$aParams = [];
			$aParams['grant_type'] = 'refresh_token';
			$aParams['client_id'] = $this->sClientId;
			$aParams['client_secret'] = $this->sClientSecret;
			$aParams['refresh_token'] = $sRefreshToken;

			$sJsonResponse = $this->sendTokenRequest($aParams);

			$this->oLog->addInfo('RefreshToken', array($sJsonResponse));

			$this->aAuth = json_decode($sJsonResponse, true);

			if(!empty($this->aAuth['access_token'])) {
				return true;
			}

		}

		return false;		
	}

	public function getAuthorizationLink($sRedirectUrl) {

		$sLink = 'https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id='.$this->sClientId.'&redirect_uri='.rawurlencode($this->sRedirectUriProxy).'&state='.rawurlencode($sRedirectUrl).'&scope=full%20refresh_token';

		return $sLink;
	}

	protected function sendTokenRequest(array $aParameters) {
		
		$sQuery = http_build_query($aParameters);

		$hCurl = curl_init($this->sTokenUrl.'?'.$sQuery);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_POST, true);
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT); 
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 120);

		$sJsonResponse = curl_exec($hCurl);
		
		return $sJsonResponse;
	}
	
	public function authByPassword($sUsername, $sPassword) {

		$aParams = [];
		$aParams['grant_type'] = 'password';
		$aParams['client_id'] = $this->sClientId;
		$aParams['client_secret'] = $this->sClientSecret;
		$aParams['username'] = $sUsername;
		$aParams['password'] = $sPassword;

		$sJsonResponse = $this->sendTokenRequest($aParams);
		
		$this->oLog->addInfo('AuthByPassword', array($sJsonResponse));

		$this->aAuth = json_decode($sJsonResponse, true);

		if (empty($this->aAuth['access_token'])) {
			return false;
		}
		
		return true;
	}

	public function authByCode($sCode) {

		$sTargetUrl = 'https://update.fidelo.com/salesforce.php';
		
		$aParams = [];
		$aParams['grant_type'] = 'authorization_code';
		$aParams['client_id'] = $this->sClientId;
		$aParams['client_secret'] = $this->sClientSecret;
		$aParams['redirect_uri'] = $sRedirectUrl;
		$aParams['code'] = $sCode;

		$sJsonResponse = $this->sendTokenRequest($aParams);

		$this->oLog->addInfo('AuthByCode', array($sJsonResponse));

		$this->aAuth = json_decode($sJsonResponse, true);

		if (empty($this->aAuth['access_token'])) {
			return false;
		}

		$this->saveRefreshToken();

		return true;
	}

	public function saveRefreshToken() {

		if(!empty($this->aAuth['refresh_token'])) {
			
			// Refesh token verschlüsselt speichern
			$oAes = new \WDAES();
			$sEncryptedRefreshToken = $oAes->encrypt($this->aAuth['refresh_token'], self::ENCRYPTION_PASSWORD);
			
			\System::s('tc_salesforce_refresh_token', $sEncryptedRefreshToken);
			
		}
			
		return false;
	}
	
	protected function getRefreshToken() {
		
		$sEncryptedRefreshToken = \System::d('tc_salesforce_refresh_token');
		
		if(!empty($sEncryptedRefreshToken)) {
			
			$oAes = new \WDAES();
			$sRefreshToken = $oAes->decrypt($sEncryptedRefreshToken, self::ENCRYPTION_PASSWORD);
			
			if(!empty($sRefreshToken)) {
				return $sRefreshToken;
			}
			
		}

	}
	
	public function getAuth() {
		return $this->aAuth;
	}

	/**
	 * @param string $sUrl
	 * @param array $aData
	 * @return array
	 */
	public function post($sUrl, array $aData) {

		$sJsonData = json_encode($aData);
__out($this->aAuth['instance_url'].$sUrl);
		$hCurl = curl_init($this->aAuth['instance_url'].$sUrl);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT); 
		curl_setopt($hCurl, CURLOPT_HTTPHEADER, array(
			'Authorization: OAuth '.$this->aAuth['access_token'],
			'Content-type: application/json'
		));
		curl_setopt($hCurl, CURLOPT_POST, true);
		curl_setopt($hCurl, CURLOPT_POSTFIELDS, $sJsonData);
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 120);

		$sJsonResponse = curl_exec($hCurl);
		curl_close($hCurl);
__out($sJsonResponse);
		$aResponseData = json_decode($sJsonResponse, true);

		$this->oLog->addInfo('Post', array('url' => $sUrl, 'data' => $aData, 'response_data' => $aResponseData, 'response' => $sJsonResponse));

		return $aResponseData;
	}
	
	/**
	 * 
	 * @param string $sNextRecordsUrl
	 * @return array
	 */
	public function getNextRecords($sNextRecordsUrl) {
		
		$hCurl = curl_init(
			$this->aAuth['instance_url'].$sNextRecordsUrl
		);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT); 
		curl_setopt($hCurl, CURLOPT_HTTPHEADER, array(
			'Authorization: OAuth '.$this->aAuth['access_token'],
			'Content-type: application/json'
		));
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 120);

		$sJsonResponse = curl_exec($hCurl);
		curl_close($hCurl);

		$aResponseData = json_decode($sJsonResponse, true);

		$this->oLog->addInfo('NextRecords', array('next_records_url' => $sNextRecordsUrl, 'response_data' => $aResponseData, 'response' => $sJsonResponse));

		return $aResponseData;
	}

	/**
	 * @param string $sSql
	 * @return array
	 */
	public function query($sSql) {

		$sSql = trim(rawurlencode($sSql));

		$hCurl = curl_init(
			$this->aAuth['instance_url']."/services/data/v32.0/query/?q=".$sSql
		);
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT); 
		curl_setopt($hCurl, CURLOPT_HTTPHEADER, array(
			'Authorization: OAuth '.$this->aAuth['access_token'],
			'Content-type: application/json'
		));
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 120);

		$sJsonResponse = curl_exec($hCurl);
		curl_close($hCurl);

		$aResponseData = json_decode($sJsonResponse, true);

		$this->oLog->addInfo('Query', array('sql' => $sSql, 'response_data' => $aResponseData, 'response' => $sJsonResponse));

		return $aResponseData;
	}

	public function createApexClass() {
		
		$sUrl = '/services/data/v38.0/tooling/sobjects/ApexClass';

		$sBody = file_get_contents(__DIR__.'/../Resources/Templates/classes/webhook.txt');

		$aPost = [
			'Body' => $sBody,
			'Name' => 'ThebingSchoolAgency'
		];

		$aResponse = $this->post($sUrl, $aPost);
		
		__pout($aResponse, 1);
		
	}

}
