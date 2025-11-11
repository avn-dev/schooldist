<?php

namespace Core\Middleware\Auth;

use Illuminate\Http\Request;

/*
 * @TODO Was macht die Klasse? Das Access-Objekt setzt diese jdfs. nicht.
 *
 * TODO evtl. \TcFrontend\Middleware\TokenAuth hier übernehmen (Login über Header-Token)
 * TODO Möglichkeit über $setting zu steuern welche Logins geprüft werden sollen (siehe \Core\Middleware\AbstractInterface)
 * TODO wird aktuell noch nicht global benutzt, nur in vereinzelten Bundles
 */
readonly class FrontendAuth extends AbstractAuth
{
	public function __construct(
		private \Access_Frontend $access
	) {}

	public function handle(Request $request, \Closure $next, $setting = null)
	{
		// Direkter Login über Parameter
		$hasAccess = $this->loginViaRequestParameter($request);

		if(!$hasAccess) {
			// Login über Cookie-Daten
			$hasAccess = $this->checkCookieSession($this->access);
		}

		if(
			$hasAccess &&
			$this->access->checkValidAccess() === true
		) {
			$this->access->saveAccessData();
			return $next($request);
		}

		return $this->unauthenticated($request, $this->access);
	}

	/**
	 * Login via Request Parameter t= und ac=
	 *
	 * @param Request $request
	 * @return bool
	 */
	private function loginViaRequestParameter(Request $request): bool
	{
		// TODO Was ist t und was ist ac?
		$input = $request->only(['t', 'ac']);

		if(isset($input['t']) && isset($input['ac'])) {
			$this->access->checkDirectLogin($input['t'], $input['ac']);
			return $this->access->executeLogin();
		}

		return false;
	}
}
