<?php

namespace Tc\Traits\Wizard;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\MessageBag;
use Tc\Service\Wizard;

trait FormStep
{
	abstract public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form;

	protected function getFormTemplate(Wizard $wizard, Request $request): string
	{
		return '@Tc/wizard/form_step';
	}

	protected function getFormTemplateData(Wizard $wizard, Request $request): array
	{
		return [];
	}

	public function render(Wizard $wizard, Request $request): Response
	{
		$form = $this->getForm($wizard, $request)
			->setTooltipValues(
				Arr::except($this->helpTexts, ['description', 'manual'])
			);

		$templateData = $this->getFormTemplateData($wizard, $request);

		$templateData['form'] = $form;
		$templateData['errors'] = (array)$wizard->getSession()->getFlashBag()->get('errorFields');
		$templateData['old'] = (array)$wizard->getSession()->getFlashBag()->get('old_input');

		if (null !== $title = $form->getTitle()) {
			$templateData['title'] = $this->getTitle($wizard).' &raquo; '.$title;
		}

		return $this->view($wizard, $this->getFormTemplate($wizard, $request), $templateData);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		[$messageBag, $entity] = $this->saveForm($wizard, $request);
		return $messageBag;
	}

	protected function saveForm(Wizard $wizard, Request $request): array
	{
		$form = $this->getForm($wizard, $request);
		$messageBag = $form->save($wizard, $request, $this);

		if ($messageBag) {
			// @todo Anständige Fehler anzeigen
			$wizard->getSession()->getFlashBag()->set('errorFields', $messageBag->messages());
			return [
				new MessageBag([$wizard->translate('Nicht alle Felder wurden korrekt ausgefüllt.')]),
				$form->getEntity()
			];
		}

		return [$messageBag, $form->getEntity()];
	}

	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void {}

}