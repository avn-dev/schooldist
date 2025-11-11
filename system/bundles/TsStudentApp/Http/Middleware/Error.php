<?php

namespace TsStudentApp\Http\Middleware;

use Closure;
//use Core\Exception\ReportErrorException;
use TsStudentApp\Service\LoggingService;

class Error {

	/**
	 * Die App braucht immer eine Response mit CORS-Headers, ansonsten kommt nur der Status = 0 an
	 *
	 * @param Illuminate\Http\Request $request
	 * @param Closure $next
	 * @return Illuminate\Http\Response
	 */
	public function handle($request, Closure $next) {

		try {
			$response = $next($request);
		} catch(\Throwable $e) {
			$response = response('Internal error', 500);
			$this->getLogger()->error($e);

			//if ($e instanceof ReportErrorException) {
			//	(new \Core\Exception\ExceptionHandler)->report($e);
			//}
		}

		return $response;
	}

	/**
	 * @return LoggingService
	 */
	private function getLogger(): LoggingService {
		return app()->make(LoggingService::class);
	}

}
