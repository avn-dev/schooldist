<?php

namespace TsWizard\Handler\Setup\Steps\Building\Floor;

use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\MessageBag;
use Tc\Traits\Wizard\TableStep;
use TsWizard\Traits\BuildingElement;

class StepList extends Wizard\Structure\Step
{
	use BuildingElement, TableStep;

	protected function getTableTemplateData(Wizard $wizard, Request $request): array
	{
		$building = $this->getBuilding($request);

		return [
			'title' => $building->getName().' &raquo; '.$this->getTitle($wizard)
		];
	}

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$building = $this->getBuilding($request);

		$existing = BlockFloors::entityQuery($building)->get();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Etage'), function ($entity) {
				return $entity->title;
			})
			->new($wizard->translate('Neue Etage'), $wizard->routeStep($this, 'step.save', ['action' => 'new']))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $building) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $building->school_id, 'building_id' => $building->id, 'floor_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Etage wirklich löschen?'),
				function ($entity) use ($wizard, $building) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $building->school_id, 'building_id' => $building->id, 'floor_id' => $entity->id]);
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
		$building = $this->getBuilding($request);

		switch ($action) {
			case 'new':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $building->school_id, 'building_id' => $building->id, 'floor_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $building->school_id, 'building_id' => $building->id, 'floor_id' => $request->get('floor_id')]);
			case 'delete':
				$entity = BlockFloors::entityQuery($building)->findOrFail($request->get('floor_id', 0));
				$entity->delete();
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$building = $this->getBuilding($request);

		$first = BlockFloors::entityQuery($building)->first();

		if ($first === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens eine Etage hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}