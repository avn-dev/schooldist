<?php

namespace Admin\Service\Auth\AuthenticationAddon;

use Core\Handler\CookieHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleTwoFactor extends Simple {
	
	/**
	 * @param \MVC_View_Smarty $oView
	 */
	public function prepareView(\MVC_View_Smarty $oView) {

		parent::prepareView($oView);
		
		$oView->setTemplate('system/bundles/Admin/Resources/views/authentication/googletwofactor.tpl');
		
		$oView->set('bGoogleAuthenticator', $this->aViewValues['google_authenticator']);
		$oView->set('sQrUrl', $this->aViewValues['qr_url']);

	}
	
	/**
	 * @param \MVC_Request $oRequest
	 * @return boolean
	 */
	protected function handleOtp(\MVC_Request $oRequest) {
		
		$sOtp = $oRequest->get('otp');
		
		$sSecret = $this->oAccess->secret;

		$oGoogleAuthenticator = new \Google\Authenticator\GoogleAuthenticator;
		$bCheckOtp = $oGoogleAuthenticator->checkCode($sSecret, $sOtp);

		if($bCheckOtp === false) {

			$this->oSession->getFlashBag()->add('error', \L10N::t('Der eingegebene Code ist nicht gültig!'));
		} else {

			if(
				$oRequest->get('remember_device') === "1"
			) {
				$this->setOTPCookie();
			}

			return true;

		}
		
		return false;
	}

	/**
	 * 
	 */
	protected function initSecret() {

		$sSecret = $this->oAccess->generateSecret();

		$oGoogleAuthenticator = new \Google\Authenticator\GoogleAuthenticator;
		$sQrUrl = $oGoogleAuthenticator->getUrl($this->oAccess->getAccessUser(), $_SERVER['HTTP_HOST'], $sSecret);

		$this->aViewValues['qr_url'] = $sQrUrl;

		$this->oSession->set('admin_opt', true);
				
	}

	/**
	 * @param \MVC_Request $oRequest
	 */
	public function handleRequest(Request $oRequest, bool $isViewRequest = true): ?RedirectResponse {
	
		if($this->oAccess->checkValidAccess()) {

			$bExecuteLogin = false;

			$this->saveTmpAccess();

			$sSecret = $this->oAccess->secret;

			if(
				$this->hasValidOTPCookie() === true ||
				$this->oSession->get('otp_authentificated') === true
			) {
				
				$bExecuteLogin = true;
				
			} elseif($oRequest->exists('otp') === true) {

				$bExecuteLogin = $this->handleOtp($oRequest);

			} elseif($isViewRequest && empty($sSecret)) {

				$this->initSecret();

			}

			$this->aViewValues['google_authenticator'] = true;

			if($bExecuteLogin === true) {
				return $this->handleLogin($oRequest);
			}

		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	protected function handleLogin(Request $oRequest) {

		// Erfolgreiche OTP-Auth zwischenspeichern für möglichen Zwischenschritt bei bereits eingeloggtem User
		$this->oSession->set('otp_authentificated', true);

		return parent::handleLogin($oRequest);

	}

	/**
	 * @inheritdoc
	 */
	protected function executeLogin(Request $request) {

		$success = parent::executeLogin($request);

		$this->oSession->set('otp_authentificated', false);

		return $success;
	}

	private function getOTPCookieTime() {
		
		// Approx. one month
		$iTime = floor(time() / (3600 * 24 * 31));

		return $iTime;
	}

	/**
	 * Wird nach jedem Browser-Update ungültig
	 * @return type
	 */
	private function getOTPCookieValue() {
		
		$iTime = $this->getOTPCookieTime();

		$sValue = hash_hmac('sha1', $this->oAccess->id.':'.$iTime.':'.\Core\Helper\Agent::getHTTPUserAgent(), $this->oAccess->secret);

		return $sValue;
	}
	
    private function setOTPCookie() {

		$iTime = $this->getOTPCookieTime();
		$sValue = $this->getOTPCookieValue();
        //about using the user agent: It's easy to fake it, but it increases the barrier for stealing and reusing cookies nevertheless
        // and it doesn't do any harm (except that it's invalid after a browser upgrade, but that may be even intented)
        $sCookie = $iTime.':'.$sValue;
		
		$iExpire = time() + (30 * 24 * 3600);
		
		CookieHandler::set('admin_otp', $sCookie, $iExpire);

    }

    private function hasValidOTPCookie() {

		// TODO #20250
		// [$device, $payload] = static::readDeviceFromCookie();
		// $device->isTrustedBy($this->oAccess->getUser());

		$iTime = $this->getOTPCookieTime();
		$sValue = $this->getOTPCookieValue();
		
		$sCookie = CookieHandler::get('admin_otp');

        if (!empty($sCookie)) {
            list($iCookieTime, $sHash) = explode(':', $sCookie);

            if(
				$iCookieTime == $iTime && 
				$sHash === $sValue
			) {
				return true;
            }
        }

        return false;
    }
	
}