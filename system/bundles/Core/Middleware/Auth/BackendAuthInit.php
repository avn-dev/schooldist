<?php

namespace Core\Middleware\Auth;

use Illuminate\Http\Request;

readonly class BackendAuthInit extends AbstractAuth
{
	public function __construct(
		private \Access_Backend $access
	) {}

	public function handle(Request $request, \Closure $next)
	{
		// Login über Cookie-Daten
		$hasAccess = $this->checkCookieSession($this->access);

		if(
			$hasAccess &&
			$this->access->checkValidAccess() === true
		) {
			$this->access->saveAccessData();

			$userData = [];
			$this->access->reworkUserData($userData);
		}

		// Achtung! Request wird trotzdem weitergeleitet
		return $next($request);
	}
}
