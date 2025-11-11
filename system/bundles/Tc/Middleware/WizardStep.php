<?php

namespace Tc\Middleware;

use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WizardStep
{
	public function __construct(private Wizard $wizard) {}

	public function handle(Request $request, $next)
	{
		$stepKey = Wizard\Structure\AbstractElement::toKey($request->attributes->get('stepKey', ''));

		$step = $this->wizard->getStructure()->getStep($stepKey);

		if ($step === null) {
			return response(Response::HTTP_NOT_FOUND);
		}

		if ($this->wizard->getIteration() === null) {
			return $this->wizard->redirectToHome();
		}

		// Loop-Variablen setzen
		$queryParameters = $step->getQueryParameters();

		foreach (array_keys($queryParameters) as $key) {
			if (null !== $value = $request->query($key)) {
				// Die Ebenen so weit zurückgehen bis es einen Block gibt dessen Loop zu dem Parameter passt.
				if (null !== $parent = $step->getParent()) {
					do {
						$query = $parent->getQuery($key);
						if ($query) {
							$parent->query($key, $value);
							$parent = null;
						} else {
							$parent = $parent->getParent();
						}
					} while($parent !== null);
				}
			}
		}

		app()->instance(Wizard\Structure\Step::class, $step);

		return $next($request);

	}
}