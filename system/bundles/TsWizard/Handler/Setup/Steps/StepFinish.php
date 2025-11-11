<?php

namespace TsWizard\Handler\Setup\Steps;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepFinish extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		return $this->view($wizard, '@TsWizard/setup/finish');
	}

	public function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$schools = \Ext_Thebing_School::query()->get();
		$persister = \WDBasic_Persister::getInstance();

		// Allen Level allen Schulen zuweisen
		$this->assignSchoolsToLevels($persister, $schools);
		// Allen E-Mail-Templates allen Schulen zuweisen
		$this->assignSchoolsToEmailTemplates($persister, $schools);
		// Allen PDF-Templates allen Schulen zuweisen inkl. Briefkopf
		$this->assignSchoolsToPDFTemplates($persister, $schools);

		$persister->save();

		\System::s('ts_setup_wizard_completed', 1);


		return parent::save($wizard, $request);
	}

	private function assignSchoolsToLevels(\WDBasic_Persister $persister, Collection $schools): void
	{
		$levels = \Ext_Thebing_Tuition_Level::query()->get();

		$schoolIds = $schools->map(fn ($school) => $school->id)->toArray();

		foreach ($levels as $level) {
			$missingSchools = array_diff($schoolIds, $level->schools);
			if (!empty($missingSchools)) {
				$level->schools = $schoolIds;
				$persister->attach($level);
			}
		}

	}


	private function assignSchoolsToEmailTemplates(\WDBasic_Persister $persister, Collection $schools): void
	{
		$schoolTemplateIds = \Ext_Thebing_Email_Template::query()->pluck('id');
		$coreTemplates = \Ext_TC_Communication_Template::query()->get();

		$schoolIds = $schools->map(fn ($school) => $school->id)->toArray();

		foreach ($schoolTemplateIds as $schoolTemplateId) {
			// Mit getInstance() arbeiten da in der loadData() noch jede Menge Daten geladen werden
			$schoolTemplate = \Ext_Thebing_Email_Template::getInstance($schoolTemplateId);
			$missingSchools = array_diff($schoolIds, $schoolTemplate->schools);
			if (!empty($missingSchools)) {
				$schoolTemplate->schools = $schoolIds;
				$persister->attach($schoolTemplate);
			}
		}

		foreach ($coreTemplates as $coreTemplate) {
			$missingSchools = array_diff($schoolIds, $coreTemplate->objects);
			if (!empty($missingSchools)) {
				$coreTemplate->objects = $schoolIds;
				$persister->attach($coreTemplate);
			}
		}

	}

	private function assignSchoolsToPDFTemplates(\WDBasic_Persister $persister, Collection $schools): void
	{
		/* @var \Ext_Thebing_Pdf_Template[] $templates */
		$templateIds = \Ext_Thebing_Pdf_Template::query()->pluck('id');

		$schoolIds = $letterheads = $allschoolLetterheads = [];
		foreach ($schools as $school) {
			$schoolIds[] = $school->id;

			$schoolLetterheads = $school->getSchoolFiles(1, '', true);
			if (!empty($schoolLetterheads)) {
				$letterheads[$school->id] = Arr::first($schoolLetterheads)['id'];
				$allschoolLetterheads = array_merge($allschoolLetterheads, array_column($schoolLetterheads, 'id'));
			}
		}

		foreach ($templateIds as $templateId) {
			$template = \Ext_Thebing_Pdf_Template::getInstance($templateId);
			$missingSchools = array_diff($schoolIds, $template->schools);
			if (!empty($missingSchools)) {
				$template->schools = $schoolIds;
				$persister->attach($template);
			}

			foreach ($template->languages as $language) {
				foreach ($schoolIds as $schoolId) {
					if (!isset($letterheads[$schoolId])) {
						// Kein Briefkopf vorhanden
						continue;
					}

					$value = $template->getOptionValue($language, $schoolId, 'first_page_pdf_template', false);

					if (empty($value) || !in_array($value, $allschoolLetterheads)) {
						$template->saveOptionValue($language, $schoolId, 'first_page_pdf_template', $letterheads[$schoolId]);
						$template->saveOptionValue($language, $schoolId, 'additional_page_pdf_template', $letterheads[$schoolId]);
					}
				}
			}

		}

	}

}