<?php

namespace TsWizard\Handler\Setup\Steps;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class StepSchoolStart extends Step
{
	use SchoolElement;

	public function render(Wizard $wizard, Request $request): Response
	{
		$school = $this->getSchool($request);

		$templateData = [
			'schoolName' => $school->name
		];

		return $this->view($wizard, '@TsWizard/setup/school_start', $templateData);
	}

}