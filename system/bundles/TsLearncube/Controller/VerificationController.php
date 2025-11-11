<?php

namespace TsLearncube\Controller;
use Core\Facade\Cache;
use Illuminate\Http\Request;
use TsLearncube\Service\LearncubeWebService;

class VerificationController extends \MVC_Abstract_Controller
{

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	public function verify(Request $request) {

		$tokenFromRequest = $request->input('token');
		$userReference = $request->input('profile.user_reference');

		$verifiedToken = Cache::get(LearncubeWebService::getCacheKey($userReference));

		if ($tokenFromRequest === $verifiedToken) {
			$status = ['status' => true];
		} else {
			$status = [
				'status' => false,
				'message' => 'Token not valid'
			];
		}

		Cache::forget(LearncubeWebService::getCacheKey($userReference));

		return response()
			->json($status);
	}

}
