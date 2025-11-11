<?php

namespace TsWizard\Controller;

use Illuminate\Support\Arr;

class HelpController extends \Illuminate\Routing\Controller
{
	/**
	 * TODO siehe \Tc\Traits\Gui2\Import::requestAsUrlGenerateExample();
	 *
	 * @return void
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function userImportFile()
	{
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		$mainSheet = $spreadsheet->getSheet(0);
		$mainSheet->setTitle(\L10N::t('Users', 'Fidelo » Setup Wizard'));

		$columns = [
			['title' => 'Firstname', 'values' => ['John']],
			['title' => 'Lastname', 'values' => ['Doe']],
			['title' => 'E-Mail', 'values' => ['john.doe@example.com']],
			['title' => 'Password', 'values' => ['MySuperSecretPassword']],
		];

		foreach ($columns as $index => $column) {
			$columnCode = \Util::getColumnCodeForExcel($index);
			$mainSheet->getColumnDimension($columnCode)->setAutoSize(true);
			$mainSheet->setCellValue($columnCode.'1', \L10N::t($column['title'], 'Fidelo » Setup Wizard'));

			foreach (Arr::wrap($column['values']) as $valueIndex => $value) {
				$mainSheet->setCellValue($columnCode.($valueIndex + 2), $value);
			}
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="import_users.xlsx"');
		header('Cache-Control: max-age=0');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->save('php://output');

		exit;
	}
}