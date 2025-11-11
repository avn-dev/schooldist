<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Traits\SchoolElement;

class StepLetterhead extends Wizard\Structure\Step
{
	use SchoolElement, FormStep;

	public function getFormTemplate(Wizard $wizard, Request $request): string
	{
		return '@TsWizard/setup/letterhead';
	}

	public function getFormTemplateData(Wizard $wizard, Request $request): array
	{
		// Bestehende Briefköpfe als Liste anzeigen
		$table = (new Wizard\Structure\Table($this->getExistingLetterheads($request)))
			->column($wizard->translate('Briefkopf'), function ($letterhead) {
				return $letterhead['description'];
			})
			->column($wizard->translate('Upload'), function ($letterhead) {
				return '<a href="/storage/ts'.$letterhead['path'].'" target="_blank">
					<i class="fa fa-file-pdf-o fa-colored"></i>
				</a>';
			}, ['style' => 'width:50px; text-align:center;'])
			->delete($wizard->translate('Löschen'), $wizard->translate('Möchten Sie den Briefkopf wirklich löschen?'),
				function ($letterhead) use ($wizard) {
					return $wizard->routeStep($this, 'step.save', ['action' => 'delete', 'file_id' => $letterhead['id']]);
				},
			)
		;

		return ['table' => $table];
	}

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);

		$upload = new \Ext_Thebing_Upload_File();
		$upload->category_id = 1; // Briefkopf
		$upload->description = sprintf('%s - %s', $school->name, $wizard->translate('Briefkopf'));
		$upload->objects = [$school->id];
		$upload->languages = $school->getLanguages();

		return (new Wizard\Structure\Form($upload,null, $wizard->translate('Briefkopf hochladen')))
			->add('filename', $wizard->translate('Briefkopf'), Wizard\Structure\Form::FIELD_UPLOAD, [
				'target' => \Ext_Thebing_Upload_File::getUploadDir(),
				'rules' => (empty($this->getExistingLetterheads($request)) ? 'required|' : 'nullable|').'mimes:pdf'
			])
		;
	}

	public function save(Wizard $wizard, Request $request): ?MessageBag
	{
		if(
			!empty($this->getExistingLetterheads($request)) &&
			null === $request->files->get('filename', null)
		) {
			// Workaround. Wenn es schon Briefköpfe für die Schule gibt und in dem Formular kein neuer Briefkopf hochgeladen
			// wurde, dann braucht man das Form-Objekt und die Entität nicht zu speichern
			return null;
		}

		[$messageBag, $entity] = $this->saveForm($wizard, $request);

		return $messageBag;
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		$school = $this->getSchool($request);

		switch ($action) {
			case 'delete':
				$entity = \Ext_Thebing_Upload_File::query()->findOrFail($request->get('file_id', 0));
				$entity->schools = array_diff($entity->schools, [$school->id]);
				if (empty($entity->schools)) {
					$entity->delete();
				} else {
					$entity->save();
				}
				return back();
		}

		return parent::action($wizard, $action, $request, $next);
	}

	private function getExistingLetterheads(Request $request): array
	{
		return $this->getSchool($request)->getSchoolFiles(1, '', true);
	}

}