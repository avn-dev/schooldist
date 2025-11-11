<?php

namespace TcFrontend\Middleware;

use Illuminate\Http\Request;

class FideloTrustedProxy
{
	public function handle(Request $request, \Closure $next)
	{
		$this->setTrustedProxies($request);

		return $next($request);
	}

	/**
	 * Nach Referrer-Check anfragende IP als trusted Proxy setzen, damit ->ip(), ->host() usw. korrekt funktionieren
	 *
	 * @param Request $request
	 * @return void
	 */
	public function setTrustedProxies(Request $request): void
	{
		if ((new \Ext_TC_Frontend_Combination())->validateReferrer($request)) {
			$request->setTrustedProxies([$request->server->get('REMOTE_ADDR')], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
		}
	}
}
