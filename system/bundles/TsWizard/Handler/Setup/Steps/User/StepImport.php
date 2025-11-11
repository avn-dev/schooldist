<?php

namespace TsWizard\Handler\Setup\Steps\User;

use Core\Factory\ValidatorFactory;
use Core\Helper\Routing;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ts\Service\Import\AbstractImport;

class StepImport extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		$templateData = [
			'demoFile' => Routing::generateUrl('TsWizard.setup.help.user_import.file')
		];

		return $this->view($wizard, '@TsWizard/setup/user_import', $templateData);
	}

	public function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$validator = $this->generateValidator($wizard, $request);

		if ($validator->fails()) {
			return $validator->getMessageBag();
		}

		/* @var UploadedFile $file */
		$file = $request->files->get('import_file');
		$skipFirstRow =  $request->get('ignore_first_row', 0);

		try {
			ini_set("memory_limit", "2G");
			/**  Load $inputFileName to a Spreadsheet Object  **/
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
			$worksheet = $spreadsheet->getSheet(0);
			$rows = $worksheet->toArray();
		} catch (\Throwable $e) {
			return new MessageBag([$wizard->translate('Die Datei konnte nicht gelesen werden!')]);
		}

		$import = new \Ts\Service\Import\User();
		$import->setSpreadsheet($spreadsheet);
		$import->setItems($rows, (bool)$skipFirstRow);
		$import->setSettings([
			'skip_errors' => false
		]);

		return $this->executeImport($import);
	}

	private function generateValidator(Wizard $wizard, Request $request)
	{
		return (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(),
				['import_file' => 'required|mimes:csv,txt,xlsx'],
				[],
				['import_file' => $wizard->translate('Datei')]
			);
	}

	public function executeImport(AbstractImport $import): ?MessageBag
	{
		$summary = $import->execute();
		$errors = $import->getErrors();

		if (!empty($errors)) {

			$fields = $import->getFields();
			$messageBag = new MessageBag();
			foreach ($errors as $rowIndex => $rowErrors) {
				foreach ($rowErrors as $error) {
					$message = $error['message'];
					if ($error['pointer']) {
						$column = \Util::getColumnCodeForExcel($error['pointer']->getColumnIndex()) . $error['pointer']->getRowIndex();;
						$message = sprintf('<strong>%s</strong>: %s', $column, $message);

						if ($fields[$error['pointer']->getColumnIndex()]) {
							$message = str_replace('%s', $fields[$error['pointer']->getColumnIndex()]['field'], $message);
						}
					}
					$messageBag->add($rowIndex, $message);
				}
			}

			return $messageBag;
		}

		return null;
	}

}