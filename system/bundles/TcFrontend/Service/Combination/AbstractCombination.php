<?php

namespace TcFrontend\Service\Combination;

use TcFrontend\Service\Session\Handler as FrontendSessionHandler;
use Smarty\Smarty;

abstract class AbstractCombination extends \Ext_TC_Frontend_Combination_Abstract {
	
	/**
	 * @var \TcFrontend\Service\FrontendInstance 
	 */
	protected $oFrontendInstance;

	/**
	 * @var \TcFrontend\Service\Session\Handler
	 */
	protected $oFrontendSession;
	
	/**
	 * @param \Ext_TC_Frontend_Combination $oCombination
	 * @param Smarty $oSmarty
	 */
	public function __construct(\Ext_TC_Frontend_Combination $oCombination, Smarty $oSmarty = null) {
		parent::__construct($oCombination, $oSmarty);
		$this->oFrontendSession = new FrontendSessionHandler();
	}
	
	protected function _default() {
		$this->oFrontendInstance = $this->initFrontendInstanceFromSession();
	}
	
	public function executePostParsingHook() {
		$this->oFrontendSession->putFrontendInstance($this->oFrontendInstance);
	}

	abstract public function initFrontendInstanceFromSession();
}
