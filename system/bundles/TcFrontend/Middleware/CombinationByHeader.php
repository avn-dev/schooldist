<?php

namespace TcFrontend\Middleware;

use Illuminate\Http\Request;

class CombinationByHeader {

	/**
	 * @var \Ext_TC_Frontend_Combination_Abstract
	 */
	private $combination;

	/**
	 * @param \MVC_Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\Response
	 */
	public function handle(\MVC_Request $request, \Closure $next) {

		$this->setCombination($request);

		try {
			$this->combination->initCombination($request, $request->header('X-Combination-Language'));
			$response = $next($request);
		} catch (\Throwable $e) {
			$this->combination->log('Error in '.__CLASS__.': '.get_class($e), ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'trace' => $e->getTrace()]);
			throw $e;
		}

		return $response;

	}

	/**
	 * Kombination über Header-Key
	 *
	 * @param \MVC_Request $request
	 */
	private function setCombination(\MVC_Request $request) {

		$key = $request->header('X-Combination-Key');
		/** @var \Ext_TC_Frontend_Combination_Abstract $combination */
		$combination = \Factory::executeStatic('Ext_TC_Frontend_Combination', 'getUsageObjectByKey', [$key]);

		if (
			$combination === null ||
			!method_exists($combination, 'getWidgetData')
		) {
			abort(400, \TcFrontend\Controller\WidgetController::ERROR_KEY);
		}

		if (!$combination->getCombination()->validateReferrer($request)) {
			$combination->setRequest($request);
			$combination->log('Invalid domain/referrer (middleware)', [$request->headers->get('referer'), $request->getHost(), $combination->getCombination()->items_domains]);
			abort(400, \TcFrontend\Controller\WidgetController::ERROR_DOMAIN);
		}

		$this->combination = $combination;

		(new FideloTrustedProxy())->setTrustedProxies($request);

		// In Container für Controller injecten
		app()->instance(get_class($combination), $this->combination);

	}

}
