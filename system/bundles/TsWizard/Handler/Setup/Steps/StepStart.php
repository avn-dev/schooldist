<?php

namespace TsWizard\Handler\Setup\Steps;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepStart extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		return $this->view($wizard, '@TsWizard/setup/start');
	}

}