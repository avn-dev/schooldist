<?php

namespace TsWizard\Handler\Setup\Steps\Season;

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

		$existing = BlockSeasons::entityQuery($school)->get();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Saison'), function ($entity) {
				return $entity->getName();
			})
			->new($wizard->translate('Neue Saison'), $wizard->routeStep($this, 'step.save', ['action' => 'new', 'school_id' => $school->id]))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $school->id, 'season_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Saison wirklich löschen?'),
				function ($entity) use ($wizard, $school) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $school->id, 'season_id' => $entity->id]);
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
				return $wizard->redirect($form, ['season_id' => 0, 'school_id' => $school->id]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'season_id' => $request->get('season_id')]);
			case 'delete':
				$entity = BlockSeasons::entityQuery($school)->findOrFail($request->get('season_id', 0));
				$entity->delete();
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$school = $this->getSchool($request);

		$first = BlockSeasons::entityQuery($school)->pluck('id')->first();

		if ($first === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens eine Saison hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}