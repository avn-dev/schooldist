<?php

namespace Core\Middleware;

use Closure;
use Illuminate\Http\Request;

abstract class AbstractInterface {

	protected string $interface;

	protected string $access;

	final public function handle(Request $request, Closure $next) {

		if ($request->method() === 'OPTIONS') {
			return $next($request);
		}

		\System::setInterface($this->interface);

		// Hooks
		$wd = \webdynamics::getInstance($this->interface);
		$wd->getIncludes();

		// Access-Objekt: Wird nur erzeugt, wenn per Container/DI angefragt
		app()->singleton($this->access, function () {
			return new $this->access(\DB::getDefaultConnection());
		});

		$this->setup();

		return $next($request);

	}

	public function priority() {
		return 2;
	}

	abstract protected function setup(): void;

}
