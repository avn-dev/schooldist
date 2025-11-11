<?php

namespace Core\Controller;

use Core\Helper\Bundle as BundleHelper;

class LoadingIndicatorController extends \MVC_Abstract_Controller {

	protected $oBundleHelper = null;

	/**
	 * @var \Core\Handler\LoadingIndicatorHandler[]
	 */
	private $aHandlers = array();

	function __construct($sExtension, $sController, $sAction, $oAccess = null) {
		parent::__construct($sExtension, $sController, $sAction, $oAccess);
		$this->oBundleHelper = new BundleHelper();
	}

	/**
	 * Eingehende IDs gruppiert an ihre entsprechenden Handler geben und Rückgaben sammeln
	 *
	 * @return array
	 */
	public function statusAction() {

		$aElements = (array)$this->_oRequest->input('elements');
		$aReturn = array();

		foreach($aElements as $sType => $aIds) {
			$oHandler = $this->getHandler($sType);
			$aReturn[$sType] = $oHandler->getStatus($aIds);
		}

		$this->set('data', $aReturn);

		// Icon-Mapping der Handler mitschicken
		$aIcons = array();
		foreach($this->aHandlers as $sType => $oHandler) {
			$aIcons[$sType] = $oHandler->getIcons();
		}

		$this->set('icons', $aIcons);
	}

	/**
	 * Handler des Typs liefern
	 *
	 * @param string $sType
	 * @return \Core\Handler\LoadingIndicatorHandler
	 */
	protected function getHandler($sType) {

		if(!isset($this->aHandlers[$sType])) {
			list($sBundle, $sHandler) = explode('/', $sType);
			$sBundle = $this->oBundleHelper->convertBundleName($sBundle);
			$sHandler = $this->oBundleHelper->convertBundleName($sHandler);
			$sClass = '\\'.$sBundle.'\\Handler\\LoadingIndicator\\'.ucfirst($sHandler);

			$this->aHandlers[$sType] = new $sClass();
		}

		return $this->aHandlers[$sType];
	}
}