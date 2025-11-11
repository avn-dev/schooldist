<?php

namespace Deepl;

use Deepl\Api\Request;
use Deepl\Api\Response;

class Api {
	
	private $sBaseUrl = 'https://api-free.deepl.com/v2';
	
	const CURL_CONNECTION_TIMEOUT = 5;
	
	const SERVICE = 'deepl';
	
	const ERROR_NO_AUTH_KEY = 'No auth key given!';
	
	/**
	 * translate with given source language
	 * 
	 * @param string $sSourceLang
	 * @param string $sTargetLang
	 * @param string $sText
	 * @return string
	 */
	public function translate(string $sSourceLang, string $sTargetLang, string $sText) {	
		
		if($sSourceLang == $sTargetLang) {
			return $sText;
		}
		
		$oObject = (new Api\Object\Translation($sTargetLang, $sText))
				->setSourceLanguage($sSourceLang);
		
		$oResponse = $this->send($oObject);	
		
		return $this->getSingleTranslationFromResponse($oResponse);		
	}
	
	/**
	 * translate without given source language. Deepl will try to detect it
	 * 
	 * @param string $sTargetLang
	 * @param string $sText
	 * @return string
	 */
	public function translateDetected(string $sTargetLang, string $sText) {		
		$oObject = new Api\Object\Translation($sTargetLang, $sText);
		$oResponse = $this->send($oObject);	
		
		return $this->getSingleTranslationFromResponse($oResponse);
	}
	
	/**
	 * Get single translation from response object
	 * 
	 * @param \Deepl\Api\Response $oResponse
	 * @return string
	 */
	private function getSingleTranslationFromResponse(Response $oResponse) {
		
		if($oResponse->hasError()) {
			return '';
		}
		
		return $oResponse->get('clean_translation', '');
		
	}
	
	/**
	 * Send object to Deepl api
	 * 
	 * @param \Deepl\Api\AbstractObject $oObject
	 * @return \Deepl\Api\Response
	 */
	public function send(Api\AbstractObject $oObject) {
		
		$oRequest = new Request($this->buildFullUrl($oObject->getUrl()), $oObject->getRequestMethod());
		
		try {			
			$oObject->prepareRequest($oRequest);			
		} catch(\Exception $e) {
			return new Response([
				'success' => false,
				'message' => $e->getMessage()
			], 500);
		}
		
		$oResponse = $this->request($oRequest);
		
		return (!$oResponse->hasError())
			? $oObject->prepareResponse($oResponse)
			: $oResponse;
		
	}
	
	/**
	 * Send request to Deepl api
	 * 
	 * @param \Deepl\Api\Request $oRequest
	 * @return \Deepl\Api\Response
	 */
	private function request(Request $oRequest) {

		$sAuth = \System::d('deepl_auth_key');
		
		if(empty($sAuth)) {
			return new Response([
				'success' => false,
				'message' => self::ERROR_NO_AUTH_KEY
			], 500);
		}
		
		$oRequest->add('auth_key', $sAuth);
				
		$hCurl = curl_init($oRequest->getUrl());
		
		curl_setopt($hCurl, CURLOPT_HEADER, false);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);		

		if($oRequest->isPostRequest()) {
			curl_setopt($hCurl, CURLOPT_POST, true);
			curl_setopt($hCurl, CURLOPT_POSTFIELDS, $oRequest->getData());
		} else if($oRequest->isGetRequest()) {
			curl_setopt($hCurl, CURLOPT_CUSTOMREQUEST, 'GET');
		}
		
		curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT); 
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 120);

		$sJsonResponse = curl_exec($hCurl);
		
		$aCurlInfo = curl_getinfo($hCurl);
		
		curl_close($hCurl);

		if(empty($sJsonResponse)) {
			return new Response([
				'success' => false,
				'message' => 'Authentification failed'
			], $aCurlInfo['http_code']);
		}
		
		$aResponseData = json_decode($sJsonResponse, true);
		$aResponseData['success'] = true;
			
		return new Response($aResponseData, $aCurlInfo['http_code']);
		
	}
	
	/**
	 * Build clean url with base url
	 * 
	 * @param string $sSubUrl
	 * @return string
	 */
	private function buildFullUrl($sSubUrl) {
		
		if(substr($sSubUrl, 0, 1) !== '/') {
			$sSubUrl = '/'.$sSubUrl;
		}
		
		return $this->sBaseUrl.$sSubUrl;
	}
}

