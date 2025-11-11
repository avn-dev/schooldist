<?php

namespace Tc\Traits\Wizard;

use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait TableStep
{
	abstract public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table;

	protected function getTableTemplate(Wizard $wizard, Request $request): string
	{
		return '@Tc/wizard/table_step';
	}

	protected function getTableTemplateData(Wizard $wizard, Request $request): array
	{
		return [];
	}

	public function render(Wizard $wizard, Request $request): Response
	{
		$table = $this->getTable($wizard, $request);

		$templateData = $this->getTableTemplateData($wizard, $request);
		$templateData['table'] = $table;

		return $this->view($wizard, $this->getTableTemplate($wizard, $request), $templateData);
	}

}