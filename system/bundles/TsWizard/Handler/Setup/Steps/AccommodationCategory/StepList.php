<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationCategory;

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

		$existing = BlockAccommodationCategories::entityQuery($school)->get();

		$priceOptions = \Ext_Thebing_Accommodation_Category_Gui2::getPriceOptions();

		$table = (new Wizard\Structure\Table($existing))
			->column($wizard->translate('Kategorie'), function ($entity) {
				return $entity->getName();
			})
			->column($wizard->translate('Preis'), function ($entity) use ($school, $priceOptions) {
				$setting = $entity->getSetting($school)?->price_night;
				return $priceOptions[$setting] ?? '';
			})
			->new($wizard->translate('Neue Unterkunftskategorie'), $wizard->routeStep($this, 'step.save', ['action' => 'new', 'school_id' => $school->id]))
			->edit($wizard->translate('Editieren'), function ($entity) use ($wizard, $school) {
				return $wizard->routeStep($this, 'step.save', ['action' => 'edit', 'school_id' => $school->id, 'category_id' => $entity->id]);
			})
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie die Unterkunftskategorie wirklich löschen?'),
				function ($entity) use ($wizard, $school) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'school_id' => $school->id, 'category_id' => $entity->id]);
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
				return $wizard->redirect($form, ['school_id' => $school->id, 'category_id' => 0]);
			case 'edit':
				$form = $this->parent->get('form');
				return $wizard->redirect($form, ['school_id' => $school->id, 'category_id' => $request->get('category_id')]);
			case 'delete':
				/* @var \Ext_Thebing_Accommodation_Category $entity */
				$entity = BlockAccommodationCategories::entityQuery($school)->findOrFail($request->get('category_id', 0));

				$setting = $entity->getSetting($school);
				$setting->schools = array_diff($setting->schools, [$school->id]);
				if (empty($setting->schools)) {
					$entity->deleteJoinedObjectChild('school_settings', $setting);

					if (empty($entity->getJoinedObjectChilds('school_settings', true))) {
						$entity->delete();
					} else {
						$entity->save();
					}
				} else {
					$setting->save();
				}

				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$school = $this->getSchool($request);

		$first = BlockAccommodationCategories::entityQuery($school)->first();

		if ($first === null) {
			return new MessageBag([$wizard->translate('Bitte fügen Sie mindestens eine Unterkunftskategorie hinzu.')]);
		}

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirect($wizard->getStructure()->getNextStep($this->parent));
	}

}