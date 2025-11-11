<?php

namespace TsWizard\Handler\Setup\Steps\Accommodation;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepSkip extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		$templateData = [
			'blockName' => $this->parent->getTitle($wizard)
		];

		return $this->view($wizard, '@TsWizard/setup/skip', $templateData);
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		switch ($action) {
			case 'skip':
				$next = $wizard->getStructure()->getNextStep($this->parent);
				return $wizard->redirect($next);
			case 'edit':
				$data = $this->parent->get('resources');
				return $wizard->redirect($data);
		}

		return parent::action($wizard, $action, $request, $next);
	}

}