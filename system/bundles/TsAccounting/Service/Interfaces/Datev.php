<?php

namespace TsAccounting\Service\Interfaces;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TsAccounting\Dto\BookingStack\ExportFileContent;

class Datev extends AbstractInterface
{
	
	public function generateAdditionalFilesForExportFile(\Ext_TS_Accounting_Bookingstack_Export $export, ExportFileContent $exportFileContent): array
	{
		$data = $exportFileContent->getData();

		[$files, $widths] = $export->prepareData(
			[
				// TODO konnte die benötigten Werte in keinem der existierenden Felder finden
				['column' => 'address_number', 'content' => '', 'headline' => 'Number'],
				['column' => 'address_name_firstname_lastname', 'content' => '', 'headline' => 'Name']
			],
			$data
		);

		// {filename}_datev.csv
		$filename = implode('', [Str::before($exportFileContent->getFileName(), '.csv'), '_datev.csv']);

		$data = Arr::first($files);
		// Adressaten nur einmal anzeigen und nicht pro Item
		$data['body'] = collect($data['body'])
			->mapWithKeys(function ($row) {
				$key = implode('_', array_column($row['items'], 'text'));
				return [$key => $row];
			})
			->values()
			->toArray();

		$csvExport = $export->buildCSVExportService($widths);
		$csvExport->setFilename($filename);

		$csv = $csvExport->createFromGuiTableData($data);

		return [
			new ExportFileContent($filename, $csv, $data)
		];
	}

}
