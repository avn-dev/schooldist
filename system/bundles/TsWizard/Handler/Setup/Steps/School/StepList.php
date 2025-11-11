<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\MessageBag;
use Tc\Traits\Wizard\TableStep;

class StepList extends Step
{
	use TableStep;

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$existing = \Ext_Thebing_School::query()->get();

		return (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Schule'), function ($entity) {
				return $entity->getName();
			})
			->new($wizard->translate('Neue Schule'), $wizard->routeStep($this, 'step.save', ['action' => 'new', 'school_id' => 0]))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Schule wirklich löschen?'),
				function ($entity) use ($wizard) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $entity->id]);
				},
				function ($entity) use ($wizard) {
					return \Ext_Thebing_School::query()->pluck('id')->count() > 1;
				}
			)
		;
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$firstSchool = \Ext_Thebing_School::query()->first();

		if ($firstSchool === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens eine Schule hinzu.')]);
		}

		return null;
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		switch ($action) {
			case 'new':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $request->get('school_id')]);
			case 'delete':
				$entity = \Ext_Thebing_School::query()->findOrFail($request->get('school_id', 0));
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