<?php

namespace TsWizard\Handler\Setup\Steps\TransferLocation;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Traits\SchoolElement;

class StepSettings extends Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);

		if (0 < $entityId = $request->get('location_id', 0)) {
			$entity = BlockTransferLocations::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = new \Ext_TS_Transfer_Location();
			$entity->schools = [$school->id];
			$entity->position = BlockTransferLocations::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neuer Reiseort');
		}

		return (new Wizard\Structure\Form($entity, 'location_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'i18n_table' => 'i18n',
				'rules' => 'required'
			])
			->add('short', $wizard->translate('Kürzel'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			]);
	}
}