<?php

namespace TsWizard\Handler\Setup\Steps\SystemSettings;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;

class StepSettings extends Step
{
	use FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$client = \Ext_Thebing_Client::getInstance();

		$frontendLanguages = collect((new \Core\Service\LocaleService())->getInstalledLocales(null, false));
		$saved = collect(\Ext_TS_Config::getInstance()->frontend_languages);

		// Nur die Hauptsprachen
		$languages = collect($frontendLanguages)->filter(fn ($name, $iso) => strlen($iso) === 2);

		if (!empty($diff = $saved->diff($languages->keys()))) {
			// Falls bereits andere Sprachen abgespeichert waren
			$languages = $languages->merge($frontendLanguages->only($diff));
		}

		return (new Form($client))
			->add('name', $wizard->translate('Installationsname'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('system_color', $wizard->translate('Systemfarbe'), Wizard\Structure\Form::FIELD_COLOR, [
				'rules' => 'required'
			])
			->add('config_frontend_languages', $wizard->translate('Verfügbare Frontend-Sprachen'), Wizard\Structure\Form::FIELD_SELECT, [
				'multiple' => true,
				'rules' => 'required',
				'options' => $languages->sort()
			]);
	}
}