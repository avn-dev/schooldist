<?php

namespace TsReporting\Services;

use Carbon\Carbon;
use Core\Service\LocaleService;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
	private Spreadsheet $spreadsheet;

	private array $head;

	private array $body;

	private array $foot;

	private int $rowIndex = 1;

	public function __construct(array $head, array $body, array $foot)
	{
		$this->head = $head;
		$this->body = $body;
		$this->foot = $foot;
	}

	public function create(): void
	{
		$this->spreadsheet = new Spreadsheet();

		$this->setTableSection('head', $this->head);
		$this->setTableSection('body', $this->body);
		$this->setTableSection('foot', $this->foot);
	}

	private function setTableSection(string $type, array $section)
	{
		$sheet = $this->spreadsheet->getActiveSheet();

		foreach ($section as $line) {
			foreach ($line as $colIndex => $cell) {
				$this->setCell($cell, $this->rowIndex, $colIndex + 1, $sheet);
				$this->mergeCell($cell, $this->rowIndex, $colIndex + 1, $sheet);

				if ($type === 'head') {
					$sheet->getColumnDimensionByColumn($colIndex + 1)->setAutoSize(true);
					$sheet->getStyle([$colIndex + 1, $this->rowIndex])->getFont()->setBold(true);
				}

				if (!empty($cell['label'])) {
					$comment = $sheet->getComment([$colIndex + 1, $this->rowIndex]);
					$comment->getText()->createTextRun($cell['label']);
					$comment->setWidth('192pt'); // Doppelt so breit wie normal
					$comment->setHeight('111pt'); // Doppelt so hoch wie normal
				}
			}

			$this->rowIndex++;
		}
	}

	public function finish(): string
	{
		$file = tmpfile();
		$writer = new Xlsx($this->spreadsheet);
		$writer->save($file);

		$content = stream_get_contents($file, null, 0);

		fclose($file);

		return $content;
	}

	private function setCell(array $cell, int $rowIndex, int $colIndex, Worksheet $sheet) {

		$value = $cell['value'] ?? '';

		if (Arr::get($cell, 'format', true)) {
			switch (Arr::get($cell, 'definition.format.type')) {
				case 'number':
					$currencyCode = Arr::get($cell, 'definition.format.currency');
					if ($currencyCode) {
						$currency = \Ext_Thebing_Currency::getByIso($currencyCode);
						$localeService = new LocaleService();
						$currenyFormat = $localeService->getLocaleValue(Arr::get($cell, 'definition.format.locale'), null, 'currencynumber');
						$formatCode = str_replace('¤', $currency->getSign(), $currenyFormat.';[Red]-'.$currenyFormat);
						$sheet->getStyle([$colIndex, $rowIndex, $colIndex, $rowIndex])->getNumberFormat()->setFormatCode($formatCode);
					}
					break;
				case 'date':
					$date = Carbon::parse($cell['value']);
					$value = Date::PHPToExcel($date);

					// https://www.ablebits.com/office-addins-blog/change-date-format-excel/#custom-date-format
					$formatCode = match (Arr::get($cell, 'definition.format.unit')) {
						'year' => 'yyyy',
						'quarter' => sprintf('"%s %d", yyyy', Arr::get($cell, 'definition.format.labels.quarter', 'Q'), $date->isoFormat('Q')),
						'month' => 'mmmm yyyy',
						'week' => sprintf('"%s %d", yyyy', Arr::get($cell, 'definition.format.labels.week', 'W'), $date->isoFormat('W')),
						default => NumberFormat::FORMAT_DATE_DDMMYYYY // Sollte je nach Region automatisch ersetzt werden
					};

					$sheet->getStyle([$colIndex, $rowIndex, $colIndex, $rowIndex])->getNumberFormat()->setFormatCode($formatCode);
					break;
			}
		}

		$sheet->setCellValue([$colIndex, $rowIndex], $value);
	}

	private function mergeCell(array $cell, int $rowIndex, int $colIndex, Worksheet $sheet)
	{
		$rowspan = Arr::get($cell, 'rowspan', 1);
		$colspan = Arr::get($cell, 'colspan', 1);
		if ($rowspan > 1 || $colspan > 1) {
			$sheet->mergeCells([$colIndex, $rowIndex, $colIndex + $colspan - 1, $rowIndex + $rowspan - 1]);
		}
	}

	/**
	 * @return Spreadsheet
	 */
	public function getSpreadsheet(): Spreadsheet
	{
		return $this->spreadsheet;
	}
}