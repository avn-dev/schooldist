<?php

namespace Api\Middleware;

use Api\Service\LoggingService;
use Core\Interfaces\Http\HttpResponse;
use Core\Traits\Http\ErrorResponse;
use Core\Exception\ReportErrorException;

class ErrorHandling
{
	use ErrorResponse;

	public function handle(\Illuminate\Http\Request $request, $next)
	{
		try {
			return $next($request);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			throw $e;
		} catch (\Throwable $e) {
			LoggingService::getLogger()->error('API call failed', [
				'message' => $e->getMessage(),
				'path' => $request->path(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTrace(),
				'request' => $request->all(),
				'ip' => $request->ip(),
			]);
		}

		if ($e instanceof ReportErrorException) {
			(new \Core\Exception\ExceptionHandler)->report($e);
		}

		if ($e instanceof HttpResponse) {
			return $e->toResponse($request);
		}

		return $this->createErrorResponse('INTERNAL_ERROR', 500);
	}

}
