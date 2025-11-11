<?php

namespace TsWizard\Handler\Setup\Steps\Season;

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

		if (0 < $entityId = $request->get('season_id', 0)) {
			$season = BlockSeasons::entityQuery($school)->findOrFail($entityId);
			$title = $season->getName();
		} else {
			$season = $this->newSeason($school);
			$title = $wizard->translate('Neues Saison');
		}

		return (new Wizard\Structure\Form($season, 'season_id', $title))
			->setDateFormat($school->date_format_long)
			->addI18N('title', $wizard->translate('Titel'), Wizard\Structure\Form::FIELD_INPUT, [
				'languages' => $school->getLanguages(),
				'rules' => 'required'
			])
			->add('valid_from', $wizard->translate('Startdatum'), Wizard\Structure\Form::FIELD_DATE, [
				'rules' => 'required'
			])
			->add('valid_until', $wizard->translate('Enddatum'), Wizard\Structure\Form::FIELD_DATE, [
				'rules' => 'required|after:valid_from'
			]);
	}

	private function newSeason(\Ext_Thebing_School $school): \Ext_Thebing_Marketing_Saison
	{
		$season = new \Ext_Thebing_Marketing_Saison();
		$season->active = 1;
		$season->idPartnerschool = $school->id;
		$season->idClient = \Ext_Thebing_Client::getClientId();

		$season->saison_for_price = 1;
		$season->saison_for_teachercost = 1;
		$season->saison_for_transfercost = 1;
		$season->saison_for_accommodationcost = 1;
		$season->saison_for_fixcost = 1;
		$season->saison_for_insurance = 1;
		$season->season_for_activity = 1;

		return $season;
	}

}