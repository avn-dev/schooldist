<?php

namespace TcFrontend\Middleware;

/**
 * @link https://github.com/fruitcake/laravel-cors
 *
 * @TODO Die Middleware funktioniert leider nicht bei Exceptions usw., da das originale Package per Event die Header trotzdem ergänzen kann.
 */
class CorsHeaders {

    /**
     * Middleware für CORS-Headers
     * Über $methods kann der Zugriff auf bestimmte HTTP-Methoden begrenzt werden (z.b. :OPTIONS,GET,POST)
     *
     * @param \MVC_Request $request
     * @param \Closure $next
     * @param string ...$methods
     * @return \Illuminate\Http\Response
     */
    public function handle(\MVC_Request $request, \Closure $next, ...$methods) {

    	// Wildcard funktioniert in Safari und IE11 nicht
    	if (empty($methods)) {
			$methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
		}

        if($this->isOriginRequest($request)) {
            // Wenn keine CORS-Header notwenig sind kann die Middleware übersprungen werden
            return $next($request);
        }

        if($this->isForbidden($request, $methods)) {
            return $this->forbiddenResponse();
        }

        if($this->isPreflightRequest($request)) {
            return $this->handlePreflightRequest($methods);
        }

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        return $this->addCorsHeaders($response, $methods);
    }

    /**
     * Prüft ob der Request vom selben Host abgeschickt wurde (keine CORS-Header notwendig)
     * @param $request
     * @return bool
     */
    protected function isOriginRequest(\MVC_Request $request): bool {

        if (
			// Wenn der Request übers CMS läuft, aber über einen Proxy geht, ist Origin unverändert
        	!$request->headers->has('X-Forwarded-Host') &&
            $request->headers->has('Origin') &&
            $request->headers->get('Origin') === $request->getSchemeAndHttpHost()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Prüft ob der Request erlaubt ist (HTTP-Methode stimmt überein)
     *
     * @param \MVC_Request $request
     * @param array $methods
     * @return bool
     */
    protected function isForbidden(\MVC_Request $request, array $methods): bool {
        return (!empty($methods))
            ? !in_array($request->getMethod(), $methods)
            : false;
    }

    /**
     * Generiert eine Forbidden-Response
     *
     * @return \Illuminate\Http\Response
     */
    protected function forbiddenResponse() {
        return response('Forbidden (cors).', 403);
    }

    /**
     * Prüft ob es sich um einen OPTIONS-Request handelt (Preflight-Request)
     *
     * @param \MVC_Request $request
     * @return bool
     */
    protected function isPreflightRequest(\MVC_Request $request): bool {
        return ($request->getMethod() === 'OPTIONS');
    }

    /**
     * Response für den OPTIONS-Request generieren
     *
     * @param array $methods
     * @return \Illuminate\Http\Response
     */
    protected function handlePreflightRequest(array $methods) {
        return $this->addCorsHeaders(response(null, 204), $methods);
    }

    /**
     * Fügt alle CORS-Header zu einem Response-Objekt hinzu
     *
     * @param \Illuminate\Http\Response $response
     * @param array $methods
     * @return \Illuminate\Http\Response
     */
    protected function addCorsHeaders($response, array $methods) {

        $response->headers->set('Access-Control-Allow-Origin', $this->toHeaderString([]));

		$response->headers->set('Access-Control-Allow-Methods', $this->toHeaderString($methods));

		// TODO Bessere Lösung überlegen
        // Ältere Browser (oder auch generell Safari) unterstützen noch keine Wildcard
		// https://caniuse.com/#feat=mdn-http_headers_access-control-allow-headers_wildcard
		$response->headers->set('Access-Control-Allow-Headers', $this->toHeaderString([
            'Accept', 'Accept-Language', 'Content-Language', 'Content-Type', 'X-Requested-With', 'X-Combination-Key',
			'X-Combination-Language', 'X-Fidelo-Token', 'X-Interface-Language', 'X-App-Version', 'X-Inquiry-Id',
            'X-App-Id', 'X-App-Os', 'X-App-Os-Version', 'X-App-Device', 'X-App-Environment', 'X-Messenger-Thread'
        ]));

        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
		$response->headers->set('Access-Control-Max-Age', 7200);

        return $response;
    }

    /**
     * @param array $allowed
     * @return string
     */
    protected function toHeaderString(array $allowed) {
        return (!empty($allowed))
            ? implode(', ', $allowed)
            : '*';
    }

}
