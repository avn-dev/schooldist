<?php

namespace Tc\Controller;

use Access;
use \Firebase\JWT\JWT;

/**
 * v1
 */
class WishlistController extends \MVC_Abstract_Controller {

	public function sso() {

		$sSubDomain = 'https://wishes.fidelo.com';

		$sProjectKey = \System::d('wishlist_project_key');
		$sJWTSecret = \System::d('wishlist_jwt_secret');
		$sLanguage = \System::getInterfaceLanguage();

		$token = [
			'iss' => 'school.box',
			'aud' => $sSubDomain,
			'sub' => Access::getInstance()->email,
			'name' => Access::getInstance()->getUser()->getName(),
			'project_key' => $sProjectKey,
			'lang' => $sLanguage
		];

		$sJwt = JWT::encode($token, $sJWTSecret, 'HS256');
		
		$sUrl = $sSubDomain.'/token/'.$sJwt;

		$this->redirectUrl($sUrl);

	}
}