<?php

namespace TsWizard\Handler\Setup\Steps\AdditionalCost;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Handler\Setup\Steps\AdditionalCost\BlockAdditionalCosts;
use TsWizard\Traits\SchoolElement;

class StepSettings extends Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);

		if (0 < $entityId = $request->get('additionalcost_id', 0)) {
			$entity = BlockAdditionalCosts::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = $this->newAdditionalCost($school);
			$title = $wizard->translate('Neue Zusatzgebühr');
		}

		return (new Wizard\Structure\Form($entity, 'additionalcost_id', $title))
			->addI18N('name', $wizard->translate('Bezeichnung'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('timepoint', $wizard->translate('Zeitpunkt'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_School_Additionalcost::getTimepointOptions($wizard->getLanguageObject()),
				'rules' => 'required'
			])
		;
	}

	private function newAdditionalCost(\Ext_Thebing_School $school): \Ext_Thebing_School_Additionalcost
	{
		$entity = new \Ext_Thebing_School_Additionalcost();
		$entity->idSchool = $school->id;
		$entity->type = 2; // Generelle Kosten
		$entity->charge = 'manual'; // Manuell
		$entity->calculate = 0; // Einmalig
		$entity->group_option = 1; // pro Gruppenmitglied inkl. Leader

		return $entity;
	}
}