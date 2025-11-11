<?php

namespace TsTuition\Service\Export;

use Core\Factory\ValidatorFactory;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet;
use Tc\Service\Language\Frontend;
use Tc\Service\LanguageAbstract;

class TELCExport
{
	const L10N_PATH = 'Fidelo » Export » TELC';

	private array $rows = [];

	private array $config = [];

	private ?LanguageAbstract $l10n = null;

	public function __construct()
	{
		$this->config = require_once __DIR__.'/../../Resources/config/export/tecl.php';
		$this->l10n = (new Frontend(\System::getInterfaceLanguage()))->setContext(self::L10N_PATH);
	}

	public function l10n(LanguageAbstract $l10n)
	{
		$this->l10n = $l10n;
		return $this;
	}

	public function row(array $data)
	{
		$this->rows[] = $data;
		return $this;
	}

	public function rows(array $rows)
	{
		$this->rows = array_merge($this->rows, $rows);
		return $this;
	}

	public function save(string $filePath)
	{
		// TODO
	}

	public function output(string $fileName)
	{
		$spreadsheet = $this->generate();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="telc_'.\Util::getCleanFilename($fileName).'.xlsx"');
		header('Cache-Control: max-age=0');

		$writer = new PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->save('php://output');
	}

	public function translate(string $text) {
		return $this->l10n->translate($text);
	}

	public function getColumn(string $column)
	{
		return $this->getConfig('columns.'.$column);
	}

	public function mapValue(string $column, $value, $default = null)
	{
		if (!empty($value)) {
			$column = $this->getColumn($column);

			if ($column && !empty($column['options'])) {
				foreach ($column['options'] as $telcValue => $mapping) {
					if (in_array($value, Arr::wrap($mapping))) {
						return $telcValue;
					}
				}

				if (!empty($column['fallback'])) {
					return $column['fallback'];
				}
			}
		}

		return $default;
	}

	private function getConfig(string $key)
	{
		return Arr::get($this->config, $key);
	}

	private function generate(): PhpSpreadsheet\Spreadsheet
	{
		ini_set('memory_limit','8G');
		ini_set('max_execution_time','1800');

		$spreadsheet = new PhpSpreadsheet\Spreadsheet();

		$mainSheet = $spreadsheet->getSheet(0);
		$mainSheet->setTitle($this->translate('INPUT v.8'));

		$optionsSheet = new PhpSpreadsheet\Worksheet\Worksheet($spreadsheet);
		$optionsSheet->setTitle($this->translate('Parameters'));
		$spreadsheet->addSheet($optionsSheet);

		$this->generateHead($mainSheet, $optionsSheet);

		$this->generateBody($mainSheet);

		return $spreadsheet;
	}

	private function generateHead(PhpSpreadsheet\Worksheet\Worksheet $worksheet, PhpSpreadsheet\Worksheet\Worksheet $parametersWorksheet): PhpSpreadsheet\Worksheet\Worksheet
	{
		$worksheet->setCellValue('A1', $this->translate('Participants').':');

		$columns = $this->getConfig('columns');

		$headStyle = [
			'font' => ['bold' => true, 'size' => 10],
			'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => $this->getConfig('colors.head.background')]],
			'borders' => ['bottom' => ['borderStyle' => PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
		];

		$worksheet->getStyle('A1')->applyFromArray(array_merge($headStyle, [
			'font' => ['bold' => true, 'size' => 12]
		]));

		$worksheet->setCellValue('B1', $this->translate('Please make sure to fill in all fields. You will find instructions on uploading and the form of the template in the telc Community.'));

		$worksheet->getStyle('B1:'.\Util::getColumnCodeForExcel(count($columns) - 1).'1')->applyFromArray(array_merge($headStyle, [
			'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $this->getConfig('colors.head.text.description')]]
		]));

		$worksheet->mergeCells('B1:'.\Util::getColumnCodeForExcel(count($columns) - 1).'1');

		$worksheet->getRowDimension(2)->setRowHeight(150);

		$htmlHelper = new PhpSpreadsheet\Helper\Html();
		$index = 0;

		foreach ($columns as $column) {

			$columnLetter = \Util::getColumnCodeForExcel($index);
			$columCode = $columnLetter.'2';

			$title = (string)$this->translate($column['title']);
			if (!empty($column['description'])) {
				$title .= '<br/>'.
					'<font color="#'.$this->getConfig('colors.head.text.description').'">'
						.$this->translate($column['description']).
					'</font>';
			}

			$worksheet->setCellValue($columCode, $htmlHelper->toRichTextObject('<strong>'.$title.'</strong>'));
			$worksheet->getColumnDimension($columnLetter)->setWidth($column['width']);

			$columnStyle = [];
			if (!empty($backgroundColor = Arr::get($column, 'color.background'))) {
				$columnStyle['fill'] = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => $backgroundColor]];
			}

			$worksheet->getStyle($columCode)->applyFromArray(array_merge($headStyle, [
				'font' => ['bold' => true, 'size' => 10],
				'alignment' => ['wrapText' => true, 'horizontal' => PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM],
				'borders' => ['bottom' => ['borderStyle' => PhpSpreadsheet\Style\Border::BORDER_MEDIUM], 'right' => ['borderStyle' => ($index < (count($columns) - 1)) ? PhpSpreadsheet\Style\Border::BORDER_THIN : PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
			], $columnStyle));

			if (!empty($column['options'])) {
				$parametersWorksheet->getColumnDimension($columnLetter)->setAutoSize(true);

				foreach (array_keys($column['options']) as $optionIndex => $option) {
					$parametersWorksheet->setCellValue($columnLetter.($optionIndex + 1), $option);
				}
			}

			++$index;
		}

		return $worksheet;
	}

	private function generateBody(PhpSpreadsheet\Worksheet\Worksheet $worksheet): PhpSpreadsheet\Worksheet\Worksheet
	{
		$columns = $this->getConfig('columns');

		$htmlHelper = new PhpSpreadsheet\Helper\Html();
		// 1 und 2 für Header
		$rowIndex = 3;

		$validate = [];
		foreach ($columns as $key => $column) {
			if (!empty($column['validate'])) {
				$validate[$key] = $column['validate'];
			}
			if (!empty($column['options'])) {
				$validate[] = Rule::in(array_keys($column['options']));
			}
		}

		foreach ($this->rows as $row) {

			$worksheet->getRowDimension($rowIndex)->setRowHeight(25);

			if (!empty($validate)) {
				$validator = (new ValidatorFactory($this->l10n->getLanguage()))
					->make($row, $validate);

				if ($validator->fails()) {
					throw new ValidationException($validator);
				}
			}

			$columnIndex = 0;
			foreach ($columns as $key => $column) {
				$columnLetter = \Util::getColumnCodeForExcel($columnIndex);
				$columCode = $columnLetter.$rowIndex;

				$value = $row[$key] ?? '';

				if (!empty($value)) {
					$validate = $column['validate'] ?? [];
					if (!empty($column['options'])) {
						$validate[] = Rule::in(array_keys($column['options']));
					}



					$worksheet->setCellValue($columCode, $htmlHelper->toRichTextObject($value));
				}

				$worksheet->getStyle($columCode)->applyFromArray([
					'font' => ['size' => 10],
					'alignment' => ['wrapText' => false, 'vertical' => PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM],
					'borders' => [
						'bottom' => ['borderStyle' => PhpSpreadsheet\Style\Border::BORDER_THIN],
						'right' => ['borderStyle' => ($columnIndex < (count($columns) - 1)) ? PhpSpreadsheet\Style\Border::BORDER_THIN : PhpSpreadsheet\Style\Border::BORDER_MEDIUM]
					]
				]);

				if (!empty($column['options'])) {
					$worksheet->getCell($columCode)->getDataValidation()
						->setType(PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
						->setAllowBlank(true)
						->setShowDropDown(true)
						->setFormula1('\''.$this->translate('Parameters').'\'!$'.$columnLetter.'$1:$'.$columnLetter.'$'.count($column['options']));
					;
				}

				++$columnIndex;
			}

			++$rowIndex;
		}

		return $worksheet;
	}

}