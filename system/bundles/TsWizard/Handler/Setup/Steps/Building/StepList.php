<?php

namespace TsWizard\Handler\Setup\Steps\Building;

use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\MessageBag;
use Tc\Traits\Wizard\TableStep;
use TsWizard\Traits\SchoolElement;

class StepList extends Wizard\Structure\Step
{
	use SchoolElement, TableStep;

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$school = $this->getSchool($request);

		$existing = BlockBuildings::entityQuery($school)->get();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Gebäude'), function ($entity) {
				return $entity->getName();
			})
			->new($wizard->translate('Neues Gebäude'), $wizard->routeStep($this, 'step.save', ['action' => 'new']))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $school->id, 'building_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie das Gebäude wirklich löschen?'),
				function ($entity) use ($wizard, $school) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $school->id, 'building_id' => $entity->id]);
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
				return $wizard->redirect($form, ['school_id' => $school->id, 'building_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'building_id' => $request->get('building_id')]);
			case 'delete':
				$entity = BlockBuildings::entityQuery($school)->findOrFail($request->get('building_id', 0));
				$entity->delete();
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$school = $this->getSchool($request);

		$first = BlockBuildings::entityQuery($school)->first();

		if ($first === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens ein Gebäude hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}