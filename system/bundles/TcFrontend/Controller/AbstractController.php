<?php

namespace TcFrontend\Controller;

use Core\Handler\CookieHandler;
use Core\Handler\SessionHandler;
use TcFrontend\Service\FrontendInstance;

abstract class AbstractController extends \MVC_Abstract_Controller {
	
	/**
	 * Controller hat kein CMS-Recht
	 * @var null
	 */
	protected $_sAccessRight = null;
	
	/**
	 * @var \TcFrontend\Service\Session\Handler 
	 */
	protected $oSession;
	
	/**
	 * @var \TcFrontend\Service\FrontendInstance 
	 */
	protected $oFrontendInstance;

	/**
	 * Abwärtskompatibilität - nicht alle Module haben Frontend Instanzen
	 * @var bool
	 */
	protected $bFrontendInstanceRequired = true;
	
	/**
	 * Methode wird vor der angeforderten Controller-Action ausgeführt
	 */
	public function beforeAction($sAction=null) {
		// Jeder Request hat Zugriff
		header('Access-Control-Allow-Origin: *');
		// Session starten, da das aktuelle Registration-Objekt in der Session abgelegt wird
        $oSession = SessionHandler::getInstance();
		//session_start();
		// Session ID und Name des Session Cookies übergeben
		CookieHandler::set('thebing_snippet_session_id', $oSession->getId());
		CookieHandler::set('thebing_snippet_session_name', $oSession->getName());
		// Session-Handler aufbauen
		$this->oSession = new \TcFrontend\Service\Session\Handler();
		// Hook
		$this->beforeActionHook();
		// Frontend-Instanz anhand des InstanceHash holen
		$this->oFrontendInstance = $this->getFrontendInstanceFromRequest();
	}
	
	protected function getInstanceHashFromRequest() {
		return $this->_oRequest->get('instance_hash', '');
	}
	
	protected function getFrontendCombinationKeyFromRequest() {
		return $this->_oRequest->get('c_key', '');
	}
	
	protected function getTemplateKeyFromRequest() {
		return $this->_oRequest->get('t_key', '');
	}
	/**
	 * @return \TcFrontend\Service\FrontendInstance
	 */
	protected function getFrontendInstanceFromRequest() {		
		$sInstanceHash = $this->_oRequest->get('instance_hash', '');		
		return $this->getFrontendInstance($sInstanceHash);		
	}
	
	/**
	 * @param string $sInstanceHash
	 * @return \TcFrontend\Service\FrontendInstance
	 */
	protected function getFrontendInstance($sInstanceHash) {
		
		$oFrontendInstance = $this->oSession->getFrontendInstance($sInstanceHash);

		if($oFrontendInstance instanceof FrontendInstance) {
			return $oFrontendInstance;
		}
		
		if($this->bFrontendInstanceRequired === true) {
			// Abwärtskompatibilität - nicht alle Module haben Frontend Instanzen
			$this->_setErrorCode('fr0001', 500);
		}
		
		return null;
	}
	
	/**
	 * @param string $sInstanceHash
	 * @return \TcFrontend\Service\FrontendInstance
	 */
	protected function getSingletonFrontendInstance($sSingletonKey, $sRequestInstanceHash) {
		
		$oFrontendInstance = $this->oSession->getSingletonFrontendInstance($sSingletonKey);

		if($oFrontendInstance instanceof FrontendInstance) {
			
			if($oFrontendInstance->getInstanceHash() !== $sRequestInstanceHash) {
				$this->_setErrorCode('fr0002', 500);
			}
			
			return $oFrontendInstance;
		}
		
		$this->_setErrorCode('fr0001', 500);
		
		return null;
	}
	
	/**
	 * @param string $sInstanceHash
	 * @return \Ext_TC_Frontend_Combination_Abstract
	 */
	protected function getCombinationObject($sInstanceHash) {	
		
		$oFrontendInstance = $this->getFrontendInstance($sInstanceHash);
				
		$oFrontendCombination = $this->getFrontendCombination($oFrontendInstance->getCombinationKey());

		$oCombination = $oFrontendCombination->getObjectForUsage();
		
		return $oCombination;
	}
	
	/**
	 * @param string $sInstanceHash
	 * @return \Ext_TC_Frontend_Combination
	 */
	protected function getFrontendCombination($sInstanceHash) {
		
		$oFrontendInstance = $this->getFrontendInstance($sInstanceHash);
		
		$oFrontendCombination = $this->getFrontendCombinationByKey($oFrontendInstance->getCombinationKey());
		
		return $oFrontendCombination;
		
	}
	
	/**
	 * @param string $sKey
	 * @return \Ext_TC_Frontend_Combination
	 */
	protected function getFrontendCombinationByKey($sKey) {
		if(!empty($sKey)) {
			$oFrontendCombination = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getByKey', array($sKey));
		
			if($oFrontendCombination instanceof \Ext_TC_Frontend_Combination) {
				return $oFrontendCombination;
			}
		}

		$this->_setErrorCode('fr0003', 500);
	}
	
	/**
	 * @todo redundant
	 * @param string $sTemplateKey
	 * @return \Ext_TC_Frontend_Template
	 */
	protected function getFrontendTemplate($sTemplateKey) {
		return $this->getFrontendTemplateByKey($sTemplateKey);
	}
	
	protected function getFrontendTemplateByKey($sKey) {
		if(!empty($sKey)) {
			$oFrontendTemplate = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template', 'getByKey', array($sKey));

			if($oFrontendTemplate instanceof \Ext_TC_Frontend_Template) {
				return $oFrontendTemplate;
			}
		}
		
		$this->_setErrorCode('fr0004', 500);
	}
	
	protected function beforeActionHook() {
		// Do nothing
 	}
}
