<?php

namespace TcApi\Middleware;

use Illuminate\Http\Request;
use TcApi\Entity\ApiToken;

class Auth {

	public function handle(Request $request, \Closure $next, string $application) {

		$token = $this->getTokenFromRequest($request);

		if ($token && $this->validate($request, $token, $application)) {
			return $next($request);
		}

		return response('Unauthorized', 401);
	}

	private function getTokenFromRequest(Request $request): ?string {

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

	private function validate(Request $request, string $token, string $application): bool {

		/* @var ApiToken $tokenEntity */
		$tokenEntity = ApiToken::query()
			->select('tc_wt.*')
			->join('tc_wdmvc_tokens_applications as applications', function ($join) use ($application) {
				$join->on('applications.token_id', '=', 'tc_wt.id')
					->where('applications.application', $application);
			})
			->where('token', $token)
			->first();

		if ($tokenEntity) {
			$ips = $tokenEntity->getIPs();
			if (
				empty($ips) ||
				in_array($request->ip(), $ips)
			) {
				app()->instance(ApiToken::class, $tokenEntity);
				return true;
			}
		}

		return false;

	}

}
