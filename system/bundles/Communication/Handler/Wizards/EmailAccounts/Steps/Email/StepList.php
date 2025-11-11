<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\TableStep;

class StepList extends Step
{
	use TableStep;

	const ACCESS_RIGHT = 'core_admin_emailaccounts';

	public function getTable(Wizard $wizard, Request $request): Wizard\Structure\Table
	{
		$existingAccounts = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->orderBy('email')
			->get();

		$access = $wizard->getAccess();

		$table = (new Wizard\Structure\Table($existingAccounts))
			->column($wizard->translate('E-Mail'), function ($entity) {
				return $entity->email;
			})
			->column($wizard->translate('E-Mail-Eingang'), function ($entity) {
				return (new \Ext_TC_Gui2_Format_YesNo())->formatByValue($entity->imap);
			})
			->column($wizard->translate('Erstellt'), function ($entity) {
				/* @var \Ext_TC_Gui2_Format_Date $format */
				$format = \Factory::getObject('Ext_TC_Gui2_Format_Date');
				return $format->formatByValue($entity->created);
			});

		if (!$wizard->isIndexStep($this) || $access->hasRight([self::ACCESS_RIGHT, 'new'])) {
			$table->new($wizard->translate('Neues E-Mail-Konto'), $wizard->routeStep($this, 'step.save', ['action' => 'new']));
		}

		if (!$wizard->isIndexStep($this) || $access->hasRight([self::ACCESS_RIGHT, 'edit'])) {
			$table->edit($wizard->translate('Editieren'), function ($entity) use ($wizard) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'account_id' => $entity->id]);
			});
		}

		if (!$wizard->isIndexStep($this) || $access->hasRight([self::ACCESS_RIGHT, 'delete'])) {
			$table->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie das E-Mail-Konto wirklich löschen?'),
				function ($entity) use ($wizard) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'account_id' => $entity->id]);
				}
			);
		}

		$table->action($wizard->translate('Verbindung testen'), 'fas fa-link', 'btn-success', function ($entity) use ($wizard) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'check', 'account_id' => $entity->id]);
			});

		if ($wizard->isIndexStep($this)) {
			$table->globalAction($wizard->translate('Zugriffsrechte'), 'fa fa-key', 'btn-info', $wizard->routeStep($this, 'step.save', ['action' => 'access']));
		}

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
				return $wizard->redirect($form, ['account_id' => 0]);
			case 'access':
				$access = $this->parent->get('access')->getFirstStep();
				return $wizard->redirect($access);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['account_id' => $request->get('account_id')]);
			case 'delete':
				return $this->actionDelete($wizard, $request);
			case 'check':
				return $this->actionCheck($wizard, $request);
		}

		return parent::action($wizard, $action, $request, $next);
	}

	public function actionDelete(Wizard $wizard, Request $request): Response
	{
		$entity = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->findOrFail($request->get('account_id', 0));

		$inUse = $entity->getUse();

		if ($inUse) {
			$errorMessage = ' (' . implode(", ", array_column($inUse, 'label')) . ') ';
			$wizard->message('error', $wizard->translate('Der E-Mail-Account wird noch verwendet.') . $errorMessage);
			return back();
		}

		$entity->bValidateSettings = false;
		$success = $entity->delete();

		if (is_array($success)) {
			$messageBag = $wizard->toMessageBag(
				$success,
				['ACCOUNT_IN_USE' => $wizard->translate('Der E-Mail-Account wird noch verwendet.')],
				function ($error) {
					return \Ext_TC_Communication_EmailAccount_Gui2_Data::convertErrorKeyToMessage($error);
				}
			);
			foreach ($messageBag->messages() as $message) {
				$wizard->message('error', $message);
			}
		}

		return back();
	}

	public function actionCheck(Wizard $wizard, Request $request): Response
	{
		/* @var \Ext_TC_Communication_Imap $entity */
		$entity = \Ext_TC_Communication_Imap::query()
			->findOrFail($request->get('account_id', 0));

		try {
			$smtp = $entity->checkSmtp();
		} catch (\Throwable $e) {
			$smtp = $e->getMessage();
		}

		try {
			$imap = ($entity->imap) ? $entity->checkConnection() : true;
		} catch (\Exception $e) {
			$imap = $e->getMessage();
		}

		if ($smtp !== true || $imap !== true) {
			if ($smtp !== true) {
				$wizard->getSession()->getFlashBag()->add('error', rtrim($wizard->translate('Die SMTP-Verbindung konnte nicht hergestellt werden.'), '.').'('.$smtp.').');
			}
			if ($imap !== true) {
				$wizard->getSession()->getFlashBag()->add('error', rtrim($wizard->translate('Die IMAP-Verbindung konnte nicht hergestellt werden.'), '.').'('.$imap.').');
			}
		} else {
			$wizard->getSession()->getFlashBag()->add('success', $wizard->translate('Die Verbindung wurde erfolgreich getestet.'));
		}

		return back();
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$firstAccount = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->first();

		if ($firstAccount === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens einen E-Mail-Account hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}