<?php

use TsAccounting\Dto\BookingStack\ExportFileContent;

class Ext_TS_Accounting_Bookingstack_Export {

	/** @var \TsAccounting\Entity\Company */
	protected $company;

	/** @var [] */
	protected $data;

	/** @var string */
	protected $sFilename = '';

	/** @var string */
	protected $sFileExtension;

//	/** @var array  */
//	protected $aExportPaths = [
//		'universal' => 'booking_stack/universal',
//		'datev' => 'booking_stack/datev',
//		'sage' => 'booking_stack/sage',
//		'quickbooks' => 'booking_stack/quickbooks',
//		'xero' => 'booking_stack/xero',
//	];

	/**
	 * @param \TsAccounting\Entity\Company $oCompany
	 * @param Ext_Gui2 $oGui
	 * @param array $aSelectedIds
	 */
	public function __construct(\TsAccounting\Entity\Company $oCompany) {
		
		$this->company = $oCompany;

		$this->sFileExtension = $oCompany->export_file_extension;
		
    }

	public function loadDataFromGui(array $aSelectedIds) {
		
		$oFactory = new Ext_Gui2_Factory(sConfigFile: 'ts_booking_stack', showUnvisibleColumns: true);
		$oGui = $oFactory->createGui();

		// Leeres Request-Objekt, da es später verwendet wird
		$oGui->setRequest(new \MVC_Request());

        $oGui->load_table_head_data = 1;

		// Flex abschalten, sonst pfuscht das rum und es fehlen ggf. Spalten
		$oGui->column_flexibility = false;

		/** @var Ext_TS_Accounting_BookingStack_Gui2_Data $oData */
		$oData = $oGui->getDataObject();

		// Filter deaktivieren und immer alle Spalten holen
		$oData->bExport = true;
        $this->data = $oGui->getTableData([], [], $aSelectedIds, 'csv', true);
		$oData->bExport = false;

	}

	public function prepareData(array $columns, array $gui2Data, bool $splitIntoFinancialYears = false) {

		if (empty($gui2Data)) {
			throw new Exception('No data!');
		}

		$widths = [];
		$aContents = [];
		$financialYears = [];

		// Columns nach Firmeneinstellung filtern und sortieren
		$aNewHead = $aNewBody = [];
		$iNewHead = 0;
		foreach ($columns as $aExportColumn) {
			$sColumn = $aExportColumn['column'];
			if (strpos($sColumn, 'empty') === 0) {
				$aNewHead[$iNewHead] = [
					'title' => '',
					'db_alias' => '',
					'db_column' => 'empty'
				];
			} elseif (strpos($sColumn, 'static') === 0) {
				$aNewHead[$iNewHead] = [
					'title' => '',
					'db_alias' => '',
					'db_column' => 'static'
				];
			} else {
				foreach ($gui2Data['head'] as $aColumn) {
					if ($sColumn === $aColumn['db_column']) {
						$aNewHead[$iNewHead] = $aColumn;
						break;
					}
				}
			}

			// Überschrift überschrieben?
			if (!empty($aExportColumn['headline'])) {
				$aNewHead[$iNewHead]['title'] = $aExportColumn['headline'];
			}

			$widths[] = $aExportColumn['width'];
			$aContents[] = $aExportColumn['content'];
			$iNewHead++;
		}

		$gui2Data['head_full'] = $gui2Data['head'];
		$gui2Data['body_full'] = $gui2Data['body'];
		$gui2Data['head'] = $aNewHead;

		$documentBookingDates = $paymentBookingDates = [];

		// Erstes Buchungsdatum des Belegs ermitteln
		foreach ($gui2Data['body'] as &$aRow) {
			$aNewItems = [];
			$bookingDate = $documentId = $paymentId = null;

			foreach ($aRow['items'] as $aCol) {

				if ($aCol['db_column'] === 'booking_date') {
					$bookingDate = new Carbon\Carbon($aCol['original']);
				} elseif ($aCol['db_column'] === 'document_date') {
					$documentDate = new Carbon\Carbon($aCol['original']);
				} elseif ($aCol['db_column'] === 'document_id') {
					$documentId = (int)$aCol['original'];
				} elseif ($aCol['db_column'] === 'payment_id') {
					$paymentId = (int)$aCol['original'];
				}

			}

			$aRow['document_date'] = $documentDate;
			$aRow['payment_id'] = $paymentId;
			$aRow['document_id'] = $documentId;

			if (!empty($paymentId)) {
				if (!isset($paymentBookingDates[$paymentId])) {
					$paymentBookingDates[$paymentId] = $bookingDate;
				}
				$paymentBookingDates[$paymentId] = min($paymentBookingDates[$paymentId], $bookingDate);
			} elseif (!empty($documentId)) {
				if (!isset($documentBookingDates[$documentId])) {
					$documentBookingDates[$documentId] = $bookingDate;
				}
				$documentBookingDates[$documentId] = min($documentBookingDates[$documentId], $bookingDate);
			}

		}

		unset($aRow);

		// Columns der Zeilen neu schreiben, da CSV-Export nur stumpf nach Index durchläuft
		foreach ($gui2Data['body'] as &$aRow) {
			$aNewItems = [];
			$aFullItems = $aRow['items'];

			foreach ($gui2Data['head'] as $i => $aHeadColumn) {

				if ($aHeadColumn['db_column'] === 'empty') {
					$aNewItems[] = ['text' => ''];
				} elseif ($aHeadColumn['db_column'] === 'static') {
					$aNewItems[] = ['text' => $aContents[$i]];
				} else {

					foreach ($aRow['items'] as $aCol) {
						if (
							$aCol['db_alias'] === $aHeadColumn['db_alias'] &&
							$aCol['db_column'] === $aHeadColumn['db_column']
						) {

							if (
								!empty($aContents[$i]) &&
								!empty($aCol['original'])
							) {
								// @todo Das sollte man sauberer lösen und für konkrete Spalte definieren
								// TODO Aber wie, solange jede Spalte auf der GUI basieren muss?
								if (str_contains($aCol['db_column'], 'date')) {
									$dDate = new Carbon\Carbon($aCol['original']);
									$aCol['text'] = $dDate->format($aContents[$i]);
								}

								// Nummern formatieren (intl komplett unabhängig von installierten Locales)
								// Format: https://unicode.org/reports/tr35/tr35-numbers.html#Number_Format_Patterns
								if (str_contains($aCol['db_column'], 'amount')) {
									[$format, $locale] = explode('|', $aContents[$i]);
									$formatter = new NumberFormatter($locale ?? null, NumberFormatter::PATTERN_DECIMAL, $format);
									$aCol['text'] = $formatter->format($aCol['original']);
								}
							}

							$aNewItems[] = $aCol;
							break;
						}
					}

				}
			}

			$aRow['items'] = $aNewItems;

			$documentDate = $aRow['document_date'];
			$paymentId = $aRow['payment_id'];
			$documentId = $aRow['document_id'];

			// Erstes Buchungsdatum des Belegs verwenden
			if (!empty($paymentId)) {
				$bookingDate = $paymentBookingDates[$paymentId] ?? null;
			} elseif (!empty($documentId)) {
				$bookingDate = $documentBookingDates[$documentId] ?? null;
			}

			$aRow['booking_date'] = $bookingDate;

			if ($splitIntoFinancialYears && $bookingDate !== null) {

				$iFinancialYear = $this->company->getFinancialYear($bookingDate);

				if (!isset($financialYears[$iFinancialYear])) {
					$financialYears[$iFinancialYear]['head'] = $aNewHead;
					$financialYears[$iFinancialYear]['head_full'] = $gui2Data['head_full'];
				}

				$financialYears[$iFinancialYear]['body'][] = [
					'items' => $aNewItems,
					'booking_date' => $bookingDate,
					'document_date' => $documentDate
				];

				$financialYears[$iFinancialYear]['body_full'][] = [
					'items' => $aFullItems,
					'booking_date' => $bookingDate,
					'document_date' => $documentDate
				];

			}

		}

		unset($aRow);

		if ($splitIntoFinancialYears) {
			return [$financialYears, $widths];
		}

		return [[$gui2Data], $widths];
	}

	/**
	 * Erstellt die Export Datei und gibt den Pfad
	 * zu dieser Datei zurück
	 *
	 * @throws UnexpectedValueException
	 * @return string
	 */
	public function export(): string {

		[$aFiles, $widths] = $this->prepareData($this->company->columns_export_full, $this->data, (bool)$this->company->split_export_by_financial_year);

		$oExport = $this->buildCSVExportService($widths);

		$aCsv = [];
		foreach($aFiles as $iFile => $aData) {

			$firstDocumentDate = null;		
			foreach($aData['body'] as $aRow) {
				if($firstDocumentDate === null) {
					$firstDocumentDate = $aRow['document_date'];
				}
				$firstDocumentDate = min($firstDocumentDate, $aRow['document_date']);
			}

			$filename = $this->getFileName(true, $iFile, $firstDocumentDate);

			$oExport->setFilename($filename);

			$sCsv = $oExport->createFromGuiTableData($aData);

			$aFullData = $aData;
			$aFullData['head'] = $aFullData['head_full'];
			$aFullData['body'] = $aFullData['body_full'];
			unset($aFullData['body_full']);
			unset($aFullData['head_full']);

			$exportFile = new ExportFileContent($filename, $sCsv, $aFullData);

			$aCsv[] = $exportFile;

			$accountingService = \TsAccounting\Factory\AccountingInterfaceFactory::get($this->company);

			if ($accountingService) {
				// Je nach Service kann es sein dass weitere Dateien erstellt werden müssen (z.b. Datev)
				$serviceFiles = $accountingService->generateAdditionalFilesForExportFile($this, $exportFile);
				$aCsv = array_merge($aCsv, array_filter($serviceFiles, fn ($file) => $file instanceof ExportFileContent));
			}

			unset($aData);
		}

		unset($aFiles);

//        if(!isset($this->aExportPaths[$this->company->interface])) {
//        	throw new RuntimeException('Invalid company interface');
//		}

		$finalFiles = collect($aCsv)->mapWithKeys(fn ($fileContent) => [$fileContent->getFilename() => $fileContent]);

		if(
			$this->company->split_export_by_financial_year ||
			$finalFiles->count() > 1
		) {
			
			$sTarget = $this->preparePath('bookingstack_'.date('YmdHis').'.zip');
			
			$oZip = new \ZipArchive();
			$oZip->open($sTarget, \ZIPARCHIVE::CREATE);

			foreach($finalFiles as $exportContent) {
				/* @var ExportFileContent $exportContent */
				$oZip->addFromString($exportContent->getFileName(), $exportContent->getContent());
			}
			
			$mResult = $oZip->close();

		} else {
			/* @var ExportFileContent $exportContent */
			$exportContent = $finalFiles->first();

			$this->preparePath('bookingstack_'.date('YmdHis').'.zip');
			$sTarget = $this->preparePath($exportContent->getFileName());
		
			$mResult = file_put_contents($sTarget, $exportContent->getContent());
			
		}
		
		if($mResult === false) {
			throw new InvalidArgumentException('Generation of export file failed!');
		}

		Util::changeFileMode($sTarget);

		return $sTarget;

    }

	public function buildCSVExportService(array $widths): \Gui2\Service\Export\Csv
	{
		$oExport = new Gui2\Service\Export\Csv('Export');
		$oExport->setCharset($this->company->export_charset);
		$oExport->setSeperator($this->company->export_delimiter);
		$oExport->setLinebreak($this->company->export_linebreak);
		$oExport->setEnclosure($this->company->export_enclosure);
		$oExport->setHeadlines((bool)$this->company->export_headlines);

		if($this->company->export_delimiter == Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH) {
			$oExport->setEnclosure('');
			$oExport->setWidths($widths);
		}

		return $oExport;
	}

	protected function preparePath(string $file): string {

//		if(!isset($this->aExportPaths[$this->company->interface])) {
//			throw new RuntimeException('Invalid company interface');
//		}
//
//		$sDir = \Util::getDocumentRoot().'storage/'.$this->aExportPaths[$this->company->interface];
		$sDir = \Util::getDocumentRoot().'storage/booking_stack';
		Util::checkDir($sDir);

		return $sDir.'/'.$file;

	}

	/**
	 * @param array $aSelectedIds
	 * @return string
	 */
    public function exportJson(array $aSelectedIds) {

		$aJsonData = [];
		foreach($aSelectedIds as $iStackId) {
			$oStack = Ext_TS_Accounting_BookingStack::getInstance($iStackId);
			$aJsonData[] = $oStack->getArray();
		}

		$sJson = json_encode($aJsonData);

//		$sExportPath = $this->aExportPaths[$this->company->interface];
		$sExportPath = 'booking_stack';
		$sDir = \Util::getDocumentRoot().'storage/'.$sExportPath;
		$sPath = $sExportPath.'/'.$this->getFileName(false).'.json';
        $sTarget = \Util::getDocumentRoot().'storage/'.$sPath;

		Util::checkDir($sDir);
		
        file_put_contents($sTarget, $sJson);
		Util::changeFileMode($sTarget);

		return $sPath;
	}

	/**
	 * Gibt den Dateinamen zurück
	 *
	 * @param bool $bFileExtension
	 * @return string
	 */
	public function getFileName($bFileExtension = true, $iFinancialYear=null, $firstDocumentDate=null) {

		$filenameTemplate = $this->company->export_filename;
		if(empty($filenameTemplate)) {
			$filenameTemplate = '{$smarty.now}';
			if($iFinancialYear !== null) {
				$filenameTemplate .= '-{$year}';
			}
		}
		
		$templateEngine = new Core\Service\Templating();
		$templateEngine->assign('year', $iFinancialYear);
		$templateEngine->assign('first_document_date', $firstDocumentDate);
		
		$this->sFilename = $templateEngine->fetch('string:'.$filenameTemplate);
		
		$this->sFilename .= '.'.$this->sFileExtension;

		$sFileName = $this->sFilename;
		if(!$bFileExtension) {
			$sFileName = str_replace('.'.$this->sFileExtension, '', $sFileName);
		}
		
		return $sFileName;
	}
	
}
