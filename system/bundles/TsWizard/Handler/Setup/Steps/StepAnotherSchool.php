<?php

namespace TsWizard\Handler\Setup\Steps;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class StepAnotherSchool extends Step
{
	use SchoolElement;

	public function render(Wizard $wizard, Request $request): Response
	{
		return $this->view($wizard, '@TsWizard/setup/another_school', []);
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		switch ($action) {
			case 'new_school':
				$next = $wizard->getStructure()->get('schools')->get('form')->getFirstStep();
				return $wizard->redirect($next);
		}

		return parent::action($wizard, $action, $request, $next);
	}

}