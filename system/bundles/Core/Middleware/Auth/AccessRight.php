<?php

namespace Core\Middleware\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

readonly class AccessRight extends AbstractAuth {

	public function __construct(private \Access_Backend $access) {}

	public function handle(Request $request, \Closure $next, $right) {

		if (str_contains($right, '|')) {
			// Recht als Array angeben
			$right = explode('|', $right);
		}

		if(
			$this->access->checkValidAccess() === true &&
			$this->access->hasRight($right)
		) {
			return $next($request);
		}

		return response('Forbidden', Response::HTTP_FORBIDDEN);
	}

}
