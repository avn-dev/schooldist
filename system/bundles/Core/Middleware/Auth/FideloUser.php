<?php

namespace Core\Middleware\Auth;

use Illuminate\Http\Request;

class FideloUser
{
	public function __construct(private \Access_Backend $access) {}

	public function handle(Request $request, \Closure $next)
	{
		if ($this->access->checkValidAccess()) {
			$user = $this->access->getUser();
			if (\Util::isInternEmail($user->email)) {
				return $next($request);
			}
		}

		return response('Forbidden', 403);
	}
}
