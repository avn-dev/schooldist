<?php

namespace Api\Controller;

use Api\Factory\OAuth2Provider;
use Api\Service\LoggingService;
use Core\Handler\SessionHandler;
use Core\Helper\Routing;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class OAuth2Controller extends Controller
{
	public function redirectHost(Request $request) {

		$host = $request->input('host');

		[$providerKey, $providerConfig] = \Api\Helper\MailserverOAuth2::getByHost($host);

		if (empty($providerKey)) {
			$json = ['success' => false, 'error' => 'Unknown host'];
			return response()
				->view('oauth2/verify', ['json' => $json]);
		}

		SessionHandler::getInstance()->set('oauth2_state', $state = \Util::generateRandomString(30));

		$providerUrl = OAuth2Provider::get($providerKey)
			->getAuthorizationUrl([
				'state' => $state,
				'scope' => Arr::wrap($providerConfig['scopes'] ?? [])
			]);

		$params = http_build_query([
			'forward' => $providerUrl,
			'fidelo_callback' => Routing::generateUrl('Api.api.oauth2.verify', ['provider' => $providerKey])
		]);

		$url = \Util::getProxyHost().'oauth2/forward/' . $providerKey . '?' . $params;

		return redirect($url);
	}

	public function verify(Request $request, string $provider)
	{
		$state = $request->get('state');

		$json = ['success' => false];

		if ($state !== SessionHandler::getInstance()->get('oauth2_state')) {
			$json['error'] = 'OAUTH2_STATE_MISMATCH';
			LoggingService::getLogger('oauth2')->error('Verify request failed (state mismatch)', ['session' => SessionHandler::getInstance()->get('oauth2_state'), 'request' => $state]);
		} else if (null !== $code = $request->get('code')) {
			$json['success'] = true;
			$json['provider'] = $provider;
			$json['state'] = $state;
			$json['code'] = $code;
		} else {
			LoggingService::getLogger('oauth2')->error('Verify request failed', ['request' => $request->all()]);
		}

		SessionHandler::getInstance()->remove('oauth2_state');

		return response()
			->view('oauth2/verify', ['json' => $json]);
	}
}