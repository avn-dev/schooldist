<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Illuminate\Support\Arr;
use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Traits\SchoolElement;

class StepSettings2 extends Wizard\Structure\Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);
		$title = $school->ext_1;

		$times = \Ext_Thebing_Util::getTimeRows('format', 5, 0, 86400);

		return (new Wizard\Structure\Form($school, 'school_id', $title))
			// Auf anderem Step da für getSchoolFileDir() die ID gebraucht wird
			->add('logo', $wizard->translate('Logo'), Wizard\Structure\Form::FIELD_UPLOAD, [
				'target' => $school->getSchoolFileDir(true),
				'post_process' => function ($entity, $file, $config) {
					$helper = new \Ts\Gui2\School\LogoUpload();
					$helper->execute(
						[$file->getClientOriginalName()],
						['upload_path' => str_replace(\Util::getDocumentRoot(false), '', $config['target'])],
						$entity
					);
				},
				'filename' => 'logo.png',
				'rules' => (empty($school->getLogo()) ? 'required|': 'nullable|').'mimes:jpeg,jpg,png'
			])
			->add('timezone', $wizard->translate('Zeitzone'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Util::addEmptyItem(\Ext_TC_Util::getTimeZones()),
				'rules' => 'required'
			])
			->add('date_format_long', $wizard->translate('Datumsformat lang'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('date_format_short', $wizard->translate('Datumsformat kurz'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->heading($wizard->translate('Unterrichtszeiten'))
			->add('class_time_from', $wizard->translate('Startzeit'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => $times,
				'rules' => 'required'
			])
			->add('class_time_until', $wizard->translate('Endzeit'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => $times,
				'rules' => 'required'
			])
			->heading($wizard->translate('Sprachen'))
			->add('languages', $wizard->translate('Sprachen'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_Data::getSystemLanguages(),
				'multiple' => true,
				'rules' => 'required'
			])
			->heading($wizard->translate('Preise'))
			->add('price_structure_week', $wizard->translate('Preisstruktur - Wochenunterricht'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_School_Gui2::getPriceStructureWeeks(),
				'rules' => 'required'
			])
			->add('price_structure_unit', $wizard->translate('Preisstruktur - Einzelunterricht'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_School_Gui2::getPriceStructureSingle(),
				'rules' => 'required'
			])
			->add('price_calculation', $wizard->translate('Preisberechnung'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_School_Gui2::getPriceCalculations(),
				'rules' => 'required'
			])
			->add('tax', $wizard->translate('Steuern'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_School_Gui2::getTaxCalculations(),
				'rules' => 'required'
			])
		;
	}

	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void
	{
		if (in_array($entity->language, $entity->languages)) {
			return;
		}

		$entity->language = Arr::first($entity->languages);
	}
}