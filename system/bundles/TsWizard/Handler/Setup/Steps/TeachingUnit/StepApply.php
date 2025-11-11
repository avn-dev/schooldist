<?php

namespace TsWizard\Handler\Setup\Steps\TeachingUnit;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\TableStep;
use TsWizard\Traits\SchoolElement;

class StepApply extends Step
{
	use SchoolElement, TableStep;

	protected function getTableTemplate(Wizard $wizard, Request $request): string
	{
		return '@TsWizard/setup/apply_step';
	}

	protected function getTableTemplateData(Wizard $wizard, Request $request): array
	{
		$school = $this->getSchool($request);

		return [
			'schoolName' => $school->name
		];
	}

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$school = $this->getSchool($request);

		$others = BlockTeachingUnits::othersQuery($school)->get();

		$table = (new Wizard\Structure\Table($others))
			->column($wizard->translate('Bezeichnung'), function ($entity) {
				return $entity->title;
			})
			->action($wizard->translate('Übernehmen'), 'fa fa-plus-circle', 'btn-info', function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'apply', 'school_id' => $school->id, 'teaching_unit_id' => $entity->id]);
			})
		;

		return $table;
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		$school = $this->getSchool($request);

		switch ($action) {
			case 'apply':
				$entity = \Ext_Thebing_School_TeachingUnit::query()->findOrFail($request->get('teaching_unit_id', 0));
				$schools = $entity->schools;
				$schools[] = $school->id;
				$entity->schools = array_unique($schools);
				$entity->save();
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

}