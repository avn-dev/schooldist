<?php

namespace TsWizard\Handler\Setup\Steps\AdditionalCost;

use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tc\Traits\Wizard\TableStep;
use TsWizard\Handler\Setup\Steps\AdditionalCost\BlockAdditionalCosts;
use TsWizard\Traits\SchoolElement;

class StepList extends Wizard\Structure\Step
{
	use SchoolElement, TableStep;

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$school = $this->getSchool($request);

		$existing = BlockAdditionalCosts::entityQuery($school)->get();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Zusätzliche Gebühr'), function ($entity) {
				return $entity->getName();
			})
			->new($wizard->translate('Neue Zusatzgebühr'), $wizard->routeStep($this, 'step.save', ['action' => 'new', 'school_id' => $school->id]))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $school->id, 'additionalcost_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Zusatzgebühr wirklich löschen?'),
				function ($entity) use ($wizard, $school) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $school->id, 'additionalcost_id' => $entity->id]);
				}
			)
		;

		return $table;
	}

	/**
	 * Bestimmte Aktion auf den Step ausführen
	 *
	 * @param Wizard $wizard
	 * @param string $action
	 * @param Request $request
	 * @param $next
	 * @return Response
	 */
	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		$school = $this->getSchool($request);

		switch ($action) {
			case 'new':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'additionalcost_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'additionalcost_id' => $request->get('additionalcost_id')]);
			case 'delete':
				$entity = BlockAdditionalCosts::entityQuery($school)->findOrFail($request->get('additionalcost_id', 0));
				$entity->delete();
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}