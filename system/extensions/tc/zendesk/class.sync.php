<?php

use \Firebase\JWT\JWT;

class Ext_TC_ZenDesk_Sync {
	
	/**
	 * Sub-Domain
	 * 
	 * @var string 
	 */
	protected $_sSubDomain = 'https://thebingsoftwaregmbh.zendesk.com';
	
	/**
	 * API-Token
	 * 
	 * @var string 
	 */
	protected $_sApiToken = 'eiFB2g1d4oMpBSq1U1SjSBMLD0IeH1nwZcOlgcGx';
	
	/**
	 * JSON-Token
	 * 
	 * @var string 
	 */
	protected $_sJsonToken = 'vp45VtUemr49e2gJXyC74HOvVtJpsZj8CVuTM6Y9tbo1KgMm';
	
	/**
	 * aktueller User
	 * 
	 * @var Ext_TC_User 
	 */
	protected $_oUser = null;
	
	/**
	 * E-Mail zur Authetifizierung
	 * 
	 * @var string 
	 */
	protected $_sAuthEmail = 'zendesk@thebing.com';

	/**
	 * System des Clienten (Agency/School)
	 * 
	 * @var string 
	 */
	protected $_sSystem = '';
	
	/**
	 * Name der Organisation
	 * 
	 * @var string 
	 */
	protected $_sOrganization = '';
	
	/**
	 *	ID unter der die Organisation bei ZenDesk gespeichert wird
	 * 
	 * @var mixed 
	 */
	protected $_mZenDeskId = 0;
	
	/**
	 * Array mit allen aufgetretenen Fehlern
	 * 
	 * @var array 
	 */
	protected $oErrorCollection = array();
	
	/**
	 *
	 * @var Log
	 */
	protected $oLog;

	/**
	 * Konstruktor
	 * 
	 * @param Ext_TC_User $oUser
	 * @param string $sApiToken
	 * @param string $sSystem
	 */
	public function __construct(Ext_TC_User $oUser, $sSystem) {		
		$this->_oUser		= $oUser;
		$this->_sSystem		= $sSystem;
		
		$this->oErrorCollection = new Ext_TC_ZenDesk_Errors_Collection;
		
		$this->oLog = Log::getLogger('tc_zendesk');
		
	}
	
	/**
	 * Möglichkeit, den Api-Token zu überschreiben
	 * 
	 * @param string $sApiToken
	 */
	public function setApiToken($sApiToken) {
		$this->_sApiToken = $sApiToken;
	}
	
	/**
	 * Möglichkeit, den Json-Token zu überschreiben
	 * 
	 * @param string $sJsonToken
	 */
	public function setJsonToken($sJsonToken) {
		$this->_sJsonToken = $sJsonToken;
	}
	
	/**
	 * führt die nötigen Anfragen an die ZenDesk-API aus
	 * 
	 * @param string $sClientName
	 * @param mixed $mZenDeskId
	 */
	public function sync($sClientName, $mZenDeskId) {

		$this->_sOrganization = $sClientName;
		$this->_mZenDeskId = $mZenDeskId;

		if($this->_mZenDeskId == 0) {		
			$this->getOrganizationZenDeskId($sClientName);
		} else {
			$oResponse = $this->updateOrganizationName($sClientName);
			if($oResponse->error == 'RecordNotFound') {
				throw new RuntimeException('Organization with zendesk id "'.$mZenDeskId.'" not found!');
			}
		}

		$this->syncZenDeskUser();

	}
	
	/**
	 * sucht bei ZenDesk nach der Organisation und generiert eine neue, falls zu
	 * diesem Client noch keine existiert
	 * 
	 * @param string $sClientName
	 */
	public function getOrganizationZenDeskId($sClientName) {

		$aJson = array('name' => $sClientName);
		$oRequest = $this->curlWrap('/organizations/autocomplete.json', 'POST', $aJson);

		if($oRequest) {
			if(!empty($oRequest->organizations)) {
				
				if(count($oRequest->organizations) > 1) {
					throw new RuntimeException('More than one organization found. Please change client name!');
				}
				
				// Erste gefundene Organization nehmen
				$oOrganization = reset($oRequest->organizations);				
				$this->_mZenDeskId = $this->_getOrganizationId($oOrganization);
				$this->_sOrganization = $oOrganization->name;		
			} else {
				// Neue Organization generieren
				$this->createNewOrganization($sClientName);				
			}
		}

	}
	
	/**
	 * generiert eine neue Organisation
	 * 
	 * @param string $sClientName
	 * @return mixed
	 */
	public function createNewOrganization($sClientName) {		
		
		$sDomain = $_SERVER['HTTP_HOST'];
		$sTag = $this->_getDefaultTag();
		
		$aJson = array(
			'organization' => array(
				'name' => $sClientName,
				'domain_names' => array(
					$sDomain
				),
				'tags' => array(
					$sTag					
				)
			)
		);

		$oRequest = $this->curlWrap('/organizations.json', 'POST', $aJson);	
		
		if($oRequest) {
			$oOrganization = $oRequest->organization;
			$this->_mZenDeskId = $this->_getOrganizationId($oOrganization);
		}

	}
	
	protected function updateVersionOptions() {
		
		// Update Version Options
		$oResponse = $this->curlWrap('/organization_fields/360000211433.json', 'GET');
		
		if($oResponse->error) {
			Util::reportError('Custom field system_version not found!');
			throw new RuntimeException('Custom field "system_version" not found!');
		}
		
		$bFound = false;
		$sVersion = System::d('version');
		foreach($oResponse->organization_field->custom_field_options as $oOption) {
			if($oOption->value == $sVersion) {
				$bFound = true;
				break;
			}
		}
		
		if($bFound === false) {
			$oNewOption = new stdClass();
			$oNewOption->id = null;
			$oNewOption->name = (string)$sVersion;
			$oNewOption->raw_name = (string)$sVersion;
			$oNewOption->value = (string)$sVersion;
			$oResponse->organization_field->custom_field_options[] = $oNewOption;

			$oResponse = $this->curlWrap('/organization_fields/360000211433.json', 'PUT', ['organization_field'=> $oResponse->organization_field]);
			
		}
		
	}


	/**
	 * setzt den Namen der Organisation
	 * 
	 * @param string $sClientName
	 */
	public function updateOrganizationName($sClientName) {

		$this->updateVersionOptions();		

		// Get current information
		$oResponse = $this->curlWrap('/organizations/'.$this->_mZenDeskId.'.json', 'GET');
				
		if($oResponse->error) {
			throw new RuntimeException('Organization not found. Possibly removed from ZenDesk. Please contact our support team!');
		}

		$oOrganizationFields = $oResponse->organization->organization_fields;
		$oOrganizationFields->system_version = (string)System::d('version');
		
		$aDomains = (array)$oResponse->organization->domain_names;
		$aDomains[] = Util::getSystemHost();
		$aDomains = array_unique($aDomains);

		$aJson = array(
			'organization' => array(
				'name' => $sClientName,
				'domain_names' => $aDomains,
				'organization_fields' => $oOrganizationFields	
			)
		);

		$this->_sOrganization = $sClientName;

		$oResponse = $this->curlWrap('/organizations/'.$this->_mZenDeskId.'.json', 'PUT', $aJson);		

		return $oResponse;
	}
	
	/**
	 * prüft, ob es für den Systembenutzer einen Account bei ZenDesk gibt
	 */
	public function syncZenDeskUser() {

		$sEmail = $this->_oUser->email;
		$oRequest = $this->curlWrap('/users/search.json?query='.$sEmail, 'GET');
		if($oRequest) {
			if(empty($oRequest->users)) {
				$this->createNewUser();
			} else {
				
				if(count($oRequest->users) > 1) {
					throw new RuntimeException('User "'.$sEmail.'" not unique!');
				}
				
				$oUser = reset($oRequest->users);
				$this->updateUser($oUser);

			}
		}

	}

	/**
	 * generiert einen neuen User auf ZenDesk
	 */
	public function createNewUser() {
		
		$sTag = $this->_getDefaultTag();
		
		$aJson = array(
			'user' => array(
				'name' => $this->_getUserName(),
				'verified' => true,
				'email' => $this->_oUser->email,
				'tags' => array(
					$sTag
				),
				'role' => 'end-user',
				'organization_id' => $this->_mZenDeskId,
				'ticket_restriction' => 'organization'
			)
		);
		
		$this->curlWrap('/users.json', 'POST', $aJson);		
	}

	/**
	 * generiert einen neuen User auf ZenDesk
	 */
	public function updateUser($oUser) {

		$aJson = array(
			'user' => array(
				'name' => $this->_getUserName(),
				'organization_id' => $this->_mZenDeskId,
				'ticket_restriction' => 'organization'
			)
		);

		$this->curlWrap('/users/'.(int)$oUser->id.'.json', 'PUT', $aJson);		
	}
	
	/**
	 * führt einen Request an die ZenDesk-API aus und liefert das Ergebnis
	 * 
	 * @param string $sUrl
	 * @param string $sAction
	 * @param array $aJson
	 * @return stdClass
	 */
	public function curlWrap($sUrl, $sAction, array $aJson = array()) {
		
		if(
			empty($this->_sAuthEmail) ||
			empty($this->_sApiToken)
		) {
			$this->oErrorCollection->add('No email-address or api token');
			return new stdClass();
		}
		
		$aJson	= json_encode($aJson);
		$sUrl	= $this->_cleanUrl($sUrl);		
		
		$sUrl	= $this->_sSubDomain.'/api/v2'.$sUrl;

		$rCurl = curl_init();
		curl_setopt($rCurl, CURLOPT_URL, $sUrl);
		#curl_setopt($rCurl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($rCurl, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($rCurl, CURLOPT_USERPWD, $this->_sAuthEmail.'/token:'.$this->_sApiToken);
		switch($sAction){
			case 'POST':
				curl_setopt($rCurl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($rCurl, CURLOPT_POSTFIELDS, $aJson);
				break;
			case 'GET':
				curl_setopt($rCurl, CURLOPT_CUSTOMREQUEST, 'GET');
				break;
			case 'PUT':
				curl_setopt($rCurl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($rCurl, CURLOPT_POSTFIELDS, $aJson);
				break;
			case 'DELETE':
				curl_setopt($rCurl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			default:
				break;
		}

		curl_setopt($rCurl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rCurl, CURLOPT_TIMEOUT, 10);

		$sOutput = curl_exec($rCurl);
		curl_close($rCurl);

		$oRequest = json_decode($sOutput);

		$aLog = array(
			$sAction,
			$sUrl,
			$aJson,
			$oRequest
		);

		// Wenn ein Fehler aufgetreten ist
		if(!empty($oRequest->error)) {
			$this->oErrorCollection->add($oRequest);
			$this->oLog->addError('ZenDesk API call', $aLog);
		} else {
			$this->oLog->addInfo('ZenDesk API call', $aLog);
		}

		return $oRequest;		
	}

	/**
	 * Single-sign-on (SSO) mit JSON-Web-Token (JWT)
	 *
	 * @param $sReturnTo
	 * @return void
	 */
	public function sso($sReturnTo = null) {

		$iNow = time();

		$token = array(
			'jti'   => md5($iNow . rand()),
			'iat'   => $iNow,
			'name'  => $this->_getUserName(),
			'email' => $this->_oUser->email
		);

		// https://github.com/zendesk/zendesk_jwt_sso_examples/blob/master/jwt_generation/php_jwt.php
		$sJWT = JWT::encode($token, $this->_sJsonToken, 'HS256');

		$sUrl = $this->_sSubDomain.'/access/jwt?jwt=' . $sJWT;

		if ($sReturnTo !== null) {
			// https://support.zendesk.com/hc/de/articles/4408845838874#topic_hkm_kst_kk
			$sUrl .= '&return_to='.urlencode($sReturnTo);
		}

		header('Location: '.$sUrl);
	}
	
	/**
	 * setzt die E-Mail-Adresse mit der sich mit ZenDesk verbunden wird
	 * 
	 * @param string $sEmail
	 */
	public function setAuthEmail($sEmail) {
		$this->_sAuthEmail = $sEmail;
	}
	
	/**
	 * liefert die aktuelle ZenDesk-ID
	 * 
	 * @return mixed
	 */
	public function getZenDeskId() {
		return $this->_mZenDeskId;
	}
	
	/**
	 * liefert den ZenDesk-Namen der Organisation
	 * 
	 * @return string
	 */
	public function getZenDeskOrganizationName() {
		return $this->_sOrganization;
	}

	/**
	 * liefert alle Fehler, die während der Verbindung zur ZenDesk-API aufgetreten sind
	 * 
	 * @return Ext_TC_ZenDesk_Errors_Collection
	 */
	public function getErrorCollection() {
		return $this->oErrorCollection;
	}
	
	/**
	 * Setzt den Fehlerspeicher zurück 1
	 */
	public function resetErrorCollection() {
		$this->oErrorCollection = new Ext_TC_ZenDesk_Errors_Collection;
	}
	
	/**
	 * liefert die ZenDesk-Id der Organisation
	 * 
	 * @param stdClass $oOrganization
	 * @return mixed
	 */
	protected function _getOrganizationId($oOrganization) {
		return $oOrganization->id;
	}
	
	/**
	 * bereinigt die übergebene Url
	 * 
	 * @param string $sUrl
	 * @return string
	 */
	protected function _cleanUrl($sUrl) {
		if(substr($sUrl, 0, 1) !== '/') {
			$sUrl = '/'.$sUrl;
		}
		
		return $sUrl;
	}

	/**
	 * Liefert den default tag
	 * 
	 * @return string
	 */
	protected function _getDefaultTag() {		
		$sTag = $this->_sSystem . '-forum';
		return $sTag;
	}
	
	/**
	 * gibt den Username des Systembenutzers für ZenDesk zurück
	 * 
	 * @return string
	 */
	protected function _getUserName() {
		return $this->_oUser->firstname . ' ' . $this->_oUser->lastname;
	}
}