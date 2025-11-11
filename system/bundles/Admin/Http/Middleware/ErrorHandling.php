<?php

namespace Admin\Http\Middleware;

use Admin\Facades\InterfaceResponse;
use Admin\Instance;
use Core\Enums\AlertLevel;
use Core\Interfaces\Http\HttpResponse;
use Core\Notifications\ToastrNotification;
use Core\Traits\Http\ErrorResponse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class ErrorHandling
{
	use ErrorResponse;

	public function __construct(
		private Instance $admin
	) {}

	public function handle(Request $request, $next)
	{
		try {
			return $next($request);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			throw $e;
		} catch (\Throwable $e) {}

		$debug = \Util::isDebugIP() || \System::d('debugmode') > 0;

		return $this->buildThrowableResponse($request, $e, $debug);
	}

	private function buildThrowableResponse(Request $request, \Throwable $e, bool $debug)
	{
		if ($debug) {
			dd($e); // TODO
		}

		$payload = $this->buildThrowablePayload($request, $e);

		$this->admin->getLogger()->error('API call failed', $payload);

		if ($e instanceof HttpResponse || $e instanceof Responsable) {
			if(!empty($response = $e->toResponse($request))) {
				return $response;
			}
		}

		$statusCode = 500;

		if ($request->hasHeader('x-admin')) {
			$message = ($debug)
				? $this->admin->translate('Es ist ein Fehler aufgetreten.').'<br/><br/>'.print_r($payload, true)
				: $this->admin->translate('Es ist ein Fehler aufgetreten. Bitte kontaktieren Sie den Support.');

			$notification = (new ToastrNotification($message, AlertLevel::DANGER))->persist();

			return InterfaceResponse::status($statusCode)
				->notification($notification)
				->toResponse($request);
		}

		$l10n = [
			'interface.failed.title' => $this->admin->translate('Ups, das hätte nicht passieren dürfen'),
			'interface.failed.text' => $this->admin->translate('Bitte versuchen Sie es später noch einmal oder kontaktieren Sie den Support.'),
		];

		return ($request->expectsJson())
			? $this->createErrorResponse('INTERNAL_ERROR', $statusCode, $debug ? ['exception' => $payload] : [])
			: response()->view('@Admin.error', compact('debug', 'statusCode', 'payload', 'l10n'));
	}

	private function buildThrowablePayload(Request $request, \Throwable $throw)
	{
		return [
			'message' => $throw->getMessage(),
			'path' => $request->path(),
			'file' => $throw->getFile(),
			'line' => $throw->getLine(),
			'trace' => $throw->getTrace()
		];
	}
}