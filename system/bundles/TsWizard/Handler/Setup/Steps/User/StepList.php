<?php

namespace TsWizard\Handler\Setup\Steps\User;

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
		$query = \Ext_Thebing_User::query();

		if (\Ext_TC_Util::isDebugIP() !== true) {
			foreach (config('app.intern.emails.domains') as $domain) {
				$query->where('email', 'not like', '%'.$domain.'%');
			}
		}

		$table = (new Wizard\Structure\Table($query->get()))
			->column($wizard->translate('Benutzer'), function ($entity) {
				return $entity->getName();
			})
			->new($wizard->translate('Neuer Benutzer'), $wizard->routeStep($this, 'step.save', ['action' => 'new']))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'user_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie den Benutzer wirklich löschen?'),
				function ($entity) use ($wizard) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'user_id' => $entity->id]);
				},
				fn ($entity) => $this->canDeleteUser($wizard, $entity),
			)
			->globalAction($wizard->translate('Importieren'), 'fa fa-upload', 'btn-info', $wizard->routeStep($this, 'step.save', ['action' => 'import']))
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
		switch ($action) {
			case 'new':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['user_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['user_id' => $request->get('user_id')]);
			case 'delete':
				$user = \Ext_Thebing_User::query()->findOrFail($request->get('user_id', 0));
				if ($this->canDelete($wizard, $user)) {
					$user->delete();
				}
				return back();
			case 'import':
				$import = $this->parent->get('import');
				return $wizard->redirect($import);
		}

		return parent::action($wizard, $action, $request, $next);
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

	private function canDelete(Wizard $wizard, \Ext_Thebing_User $user): bool
	{
		return (int)$user->id !== (int)$wizard->getIteration()->getUser()->id;
	}

}