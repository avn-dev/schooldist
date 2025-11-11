<?php

namespace Core\Middleware\Auth;

use Core\Handler\CookieHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

readonly abstract class AbstractAuth
{
	/**
	 * Login über Cookie-Daten
	 *
	 * @param \Access $access
	 * @return false
	 */
	protected function checkCookieSession(\Access $access)
	{
		if(
			CookieHandler::is($access->getPassCookieName()) &&
			CookieHandler::is($access->getUserCookieName())
		) {
			return $access->checkSession(CookieHandler::get($access->getUserCookieName()), CookieHandler::get($access->getPassCookieName()));
		}

		return false;
	}

	/**
	 * Anfrage ist nicht authentifiziert
	 *
	 * @param Request $request
	 * @return mixed
	 */
	protected function unauthenticated(Request $request, \Access $access)
	{
		$access->logout();

		if (
			!$request->isMethod(Request::METHOD_HEAD) &&
			$request->accepts(['text/html']) &&
			!empty($redirectTo = $this->redirectTo())
		) {
			// Wenn der Request HTML erwartet und ein redirect eingebaut wurde weiterleiten
			return redirect($redirectTo);
		}

		// Ansonsten ein 401 zurückschicken
		return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
	}

	/**
	 * Unauthentifizierte Anfrage auf eine Login-Maske weiterleiten
	 *
	 * @return string
	 */
	protected function redirectTo(): string
	{
		return '';
	}
}
