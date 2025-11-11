<?php

namespace Admin\Http\Middleware;

use Admin\Helper\ComponentParameters;
use Admin\Instance;
use Illuminate\Http\Request;

class ComponentResolving
{
	public function __construct(
		private Instance $admin
	) {}

	public function handle(Request $request, $next)
	{
		if (empty($component = $this->getComponentKeyFromRequest($request))) {
			return response('Bad Request', 400);
		}

		$parameters = $this->getComponentParametersFromRequest($request);

		$component = $this->admin->getComponent($component, $parameters);

		app()->instance(\Admin\Interfaces\Component::class, $component);

		return $next($request);
	}

	private function getComponentKeyFromRequest(Request $request): ?string
	{
		if (!empty($attribute = $request->attributes->get('component_key'))) {
			return $attribute;
		}

		if (!empty($header = $request->header('x-admin-component'))) {
			return $header;
		}

		return null;
	}

	private function getComponentParametersFromRequest(Request $request): array
	{
		// Im Debugmode werden die Parameter als Query-Parameter mitgeschickt damit man den Request in einem neuen Tab öffnen kann
		$encrypted = ($request->hasHeader('x-admin-parameters'))
			? $request->header('x-admin-parameters')
			: $request->input('init');

		$parameters = !empty($encrypted) ? ComponentParameters::decrypt($encrypted) : [];

		return $parameters;
	}
}