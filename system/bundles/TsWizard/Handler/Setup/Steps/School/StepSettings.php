<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;

class StepSettings extends Step
{
	use FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		if (0 < $schoolId = $request->get('school_id', 0)) {
			$school = \Ext_Thebing_school::getInstance($schoolId);
			$title = $school->ext_1;
		} else {
			$school = $this->newSchool();
			$title = $wizard->translate('Neue Schule anlegen');
		}

		$form = (new Wizard\Structure\Form($school, 'school_id', $title))
			->add('ext_1', $wizard->translate('Name der Schule'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('short', $wizard->translate('Abkürzung des Schulnamens'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('system_color', $wizard->translate('Systemfarbe'), Wizard\Structure\Form::FIELD_COLOR, [
				'rules' => 'required'
			])
			->heading($wizard->translate('Kontaktdaten'))
			->add('url', $wizard->translate('Webseite'), Wizard\Structure\Form::FIELD_INPUT, [
				//'rules' => 'url'
			])
			->add('phone_1', $wizard->translate('Telefon'), Wizard\Structure\Form::FIELD_INPUT, [
				//'rules' => 'phone_itu'
			]);

		if ($school->exist()) {
			$form->add('email', $wizard->translate('E-Mail'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'email_mx'
			]);
		}

		if (\Ext_TC_Communication_EmailAccount::query()->count() === 1) {
			$form->add('email_account_id', $wizard->translate('E-Mail-Konto (SMTP)'), Wizard\Structure\Form::FIELD_HIDDEN, [
				'rules' => 'required'
			]);
		} else {
			$form->add('email_account_id', $wizard->translate('E-Mail-Konto (SMTP)'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Communication_EmailAccount::getSelectOptions(),
				'rules' => 'required'
			]);
		}

		$form->heading($wizard->translate('Adresse'))
			->add('address', $wizard->translate('Adresse'), Wizard\Structure\Form::FIELD_INPUT, [])
			->add('address_addon', $wizard->translate('Adresszusatz'), Wizard\Structure\Form::FIELD_INPUT, [])
			->add('zip', $wizard->translate('PLZ'), Wizard\Structure\Form::FIELD_INPUT, [])
			->add('city', $wizard->translate('Stadt'), Wizard\Structure\Form::FIELD_INPUT, [])
			->add('country_id', $wizard->translate('Land'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_Data::getCountryList(true, true),
				'rules' => 'required'
			])
		;

		return $form;
	}

	/**
	 * Generiert eine neue Schule mit Defaultwerten für alle Pflichtfelder aus dem Dialog (Werte stammen aus
	 * der Template-Schule)
	 *
	 * Siehe auch prepareEntity()
	 *
	 * @return \Ext_Thebing_School
	 */
	private function newSchool(): \Ext_Thebing_School
	{
		$school = new \Ext_Thebing_School();
		$school->idClient = \Ext_Thebing_Client::getClientId();
		$school->active = 1;
		$school->critical_attendance = 80;
		$school->adult_age = 18;
		$school->payment_condition_id = \Ext_TS_Payment_Condition::query()->pluck('id')->first();
		$school->email_account_id = \Ext_TC_Communication_EmailAccount::query()->pluck('id')->first();
		$school->inclusive_nights = 6;
		$school->extra_nights_price = 5;
		$school->extra_nights_cost = 5;
		$school->class_time_from = '08:00';
		$school->class_time_until = '20:00';
		$school->class_time_interval = 15;
		$school->activity_starttime = '09:00';
		$school->activity_endtime = '20:00';
		$school->position = \Ext_Thebing_School::query()->max('position') + 1;

		return $school;
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		[$messageBag, $entity] = $this->saveForm($wizard, $request);

		if ($messageBag === null) {
			// Schule dem aktuellen Benutzer zuweisen (falls noch nicht zugewiesen)
			$firstRole = \Ext_Thebing_Admin_Usergroup::query()->pluck('name')->first();
			$wizard->getUser()
				->addSchool($entity, [$firstRole])
				->save();
		}

		return $messageBag;
	}

	/**
	 * Bestimmte Werte anhand des Landes der Schule ermitteln und setzen
	 *
	 * @param Wizard $wizard
	 * @param Request $request
	 * @param \WDBasic $entity
	 * @return void
	 * @throws \Exception
	 */
	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void
	{
		if ($entity->exist()) {
			return;
		}

		$motherTongue = \Ext_Thebing_Nationality::getMotherTonguebyNationality($entity->country_id);

		$locale = $motherTongue.'_'.$entity->country_id;

		$localeService = new \Core\Service\LocaleService();
		$symbols = $localeService->getLocaleData($locale, 'symbols');
		$dateFormats = $localeService->getLocaleData($locale, 'date');
		$timezonesData = $localeService->getLocaleData($locale, 'timezonetoterritory');
		$languagesData = $localeService->getLocaleData($locale, 'territorytolanguage');
		$currencyData = $localeService->getLocaleData($locale, 'currencytoregion');

		if (isset($timezonesData[strtoupper($entity->country_id)])) {
			$entity->timezone = $timezonesData[strtoupper($entity->country_id)];
		}

		$entity->languages = ['en'];
		$entity->language = 'en';
		if (isset($languagesData[strtolower($entity->country_id)])) {
			$systemLanguages = \Ext_Thebing_Data::getSystemLanguages();
			$localeLanguages = array_map(fn ($language) => strtolower($language), explode(' ', $languagesData[strtolower($entity->country_id)]));
			$entity->languages = array_intersect(array_keys($systemLanguages), $localeLanguages);
			$entity->language = $entity->languages[0];
		}

		$entity->aCurrencies = [1]; // EUR
		if (isset($currencyData[strtoupper($entity->country_id)])) {
			$systemCurrencies = \Ext_Thebing_Data_Currency::getCurrencyList(false);
			$currency = Arr::first($systemCurrencies, fn($currency) => $currency['iso4217'] === $currencyData[strtoupper($entity->country_id)]);
			if ($currency) {
				$entity->aCurrencies = [$currency['id']];
			}
		}

		$dateFormat = $this->convertDateFormat($dateFormats['short']);
		
		$entity->date_format_short = $dateFormat;
		$entity->date_format_long = $dateFormat;
		
		$entity->number_format = $this->getNumberFormatBySeparator($symbols['decimal'], $symbols['group']);

		if (empty($entity->email)) {
			$emailAccount = \Ext_TC_Communication_EmailAccount::getInstance($entity->email_account_id);
			$entity->email = $emailAccount->email;
		}
		
		// Date-Format vorab prüfen, damit das Speichern der Schule deswegen nicht in einen Fehler läuft
		$validateEntity = $entity->validate();

		// Fallback
		if(
			!empty($validateEntity['cdb2.date_format_short']) || 
			!empty($validateEntity['cdb2.date_format_long'])
		) {
			$entity->date_format_short = '%d.%m.%Y';
			$entity->date_format_long = '%d.%m.%Y';
		}

		if(
			!empty($validateEntity['cdb2.timezone'])
		) {
			$entity->timezone = 'UTC';
		}		
		
	}

	private function convertDateFormat(string $format): string
	{
		$format = \Core\Service\LocaleService::convertIsoToPhpFormat($format);
		
		// Nur vierstelle Jahreszahlen
		$format = str_replace('%y', '%Y', $format);
		$format = str_replace('%n', '%m', $format);

		return $format;
	}


	private function getNumberFormatBySeparator($decimal, $thousands): int
	{
		if ($decimal == '.' && $thousands == ',') {
			return 1;
		} else if ($decimal === '.' && $thousands === ' ') {
			return 2;
		} else if ($decimal === ',' && $thousands === ' ') {
			return 3;
		} else if ($decimal === '.' && $thousands === "'") {
			return 4;
		}
		return 0;
	}

}