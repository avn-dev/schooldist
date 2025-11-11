<?php

namespace TcExternalApps\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TcExternalApps\Service\AppService;

class AppInstalled
{
	public function handle(Request $request, \Closure $next, string $appKey) {

		if (AppService::hasApp($appKey)) {
			return $next($request);
		}

		return response('Forbidden', Response::HTTP_FORBIDDEN);
	}
}