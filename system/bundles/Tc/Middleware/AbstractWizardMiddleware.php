<?php

namespace Tc\Middleware;

use Tc\Service\Wizard;
use Illuminate\Http\Request;

abstract class AbstractWizardMiddleware
{
	abstract protected function init(Request $request): Wizard;

	final public function handle(Request $request, $next)
	{
		app()->instance(Wizard::class, $this->init($request));

		$response = $next($request);

		// Ohne Caching, damit $step->render() auch beim Zurück-Button des Browsers aufgerufen wird und der Step in der
		// Session aktualisiert wird (Invalid save operation)
		$response->header('Expires', '0');
		$response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->header('Pragma', 'no-cache');

		return $response;
	}
}