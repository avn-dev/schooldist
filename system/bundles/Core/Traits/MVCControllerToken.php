<?php

namespace Core\Traits;

use Illuminate\Http\Request;

/**
 * Siehe auch \TcApi\Middleware\Auth
 */
trait MVCControllerToken {

	protected function getTokenFromRequest(Request $request): ?string {

		if (null !== $bearer = $request->bearerToken()) {
			return $bearer;
		}

		if (null !== $param = $request->input('_token')) {
			return $param;
		}

		if (null !== $param = $request->input('token')) {
			return $param;
		}

		if (null !== $legacyToken = $request->attributes->get('legacy_token')) {
			return $legacyToken;
		}

		return null;
	}
	
	protected function checkToken($sApplication = null, $sToken = null) {

		if($sApplication === null) {
			$sApplication = $this->sTokenApplication;
		}

		if($sToken === null) {
			$sToken = (string) $this->getTokenFromRequest($this->_oRequest);
		}

		$mValid = \Factory::executeStatic('Ext_TC_WDMVC_Token', 'validateToken', [$sToken, $sApplication, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_HOST']]);

		return $mValid;

	}

	public function __call($sMethod, $aArguments) {

		if(!$this->checkToken()) {
			$this->_setErrorCode('e0001', 500, $_SERVER['REMOTE_ADDR']);
		}

		return parent::__call($sMethod, $aArguments);

	}

	protected function _setErrorCode($sError, $sHTTPCode = 500, $mOptinal = '') {
		$this->set('errorCode', $sError);
		$this->set('status', $sHTTPCode);
		$this->set('optional_info', $mOptinal);
		$this->set('token', $this->_oRequest->get('_token'));
		$this->_oView->setHTTPCode($sHTTPCode);
		$this->getOutput();

		die();

	}

	static public function getErrorCodeDescriptions() {

		$aDescriptions = array();

		$aDescriptions['e0001'] = 'Tooken/Ip falsch';
		$aDescriptions['e0002'] = 'REST API - HTTP Methode wird nicht unterstützt';
		$aDescriptions['e0003'] = 'Memorylimit';

		return $aDescriptions;
	}

}
