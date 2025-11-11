<?php

namespace TsWizard\Handler\Setup\Steps\TeachingUnit;

use TsWizard\Traits\SchoolElement;
use Tc\Service\Wizard;
use Tc\Traits\Wizard\TableStep;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

class StepList extends Wizard\Structure\Step
{
	use SchoolElement, TableStep;

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$school = $this->getSchool($request);

		$existing = BlockTeachingUnits::entityQuery($school)->get();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Bezeichnung'), function ($entity) {
				return $entity->title;
			})
			->new($wizard->translate('Neue Lektion'), $wizard->routeStep($this, 'step.save', ['action' => 'new', 'school_id' => $school->id]))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $school->id, 'teaching_unit_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Lektion wirklich löschen?'),
				function ($entity) use ($wizard, $school) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $school->id, 'teaching_unit_id' => $entity->id]);
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
				return $wizard->redirect($form, ['school_id' => $school->id, 'teaching_unit_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'teaching_unit_id' => $request->get('teaching_unit_id')]);
			case 'delete':
				$entity = BlockTeachingUnits::entityQuery($school)->findOrFail($request->get('teaching_unit_id', 0));
				$entity->schools = array_diff($entity->schools, [$school->id]);
				if (empty($entity->schools)) {
					$entity->delete();
				} else {
					$entity->save();
				}
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$school = $this->getSchool($request);

		$first = BlockTeachingUnits::entityQuery($school)->first();

		if ($first === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens eine Lektion hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}