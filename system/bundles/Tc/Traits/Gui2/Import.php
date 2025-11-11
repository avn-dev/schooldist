<?php

namespace Tc\Traits\Gui2;

use Ts\Service\Import\Group;

trait Import {
	
	public static $sExcelDateFormat = null;
	
	abstract protected function getImportDialogId();
	
	abstract protected function getImportService(): \Tc\Service\Import\AbstractImport;
	
	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {
		
	}

	public function requestExecuteImport($_VARS) {

		$file = $_VARS['save']['import'];

		try {

			ini_set("memory_limit", "4G");

			/**  Load $inputFileName to a Spreadsheet Object  **/
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->path());
			$worksheet = $spreadsheet->getSheet(0);
			$rows = $worksheet->toArray();

		} catch (\Throwable $e) {
            return [
                'id' => $this->getImportDialogId().'0',
                'action' => 'showError',
                'error' => [$this->t('Die Datei konnte nicht gelesen werden!')]
            ];
		}
	
		$oImport = $this->getImportService();

		$oImport->setSpreadsheet($spreadsheet);
		$oImport->setItems($rows, (bool)$_VARS['save']['skip_first_row']);

		$aFlexFields = $this->splitFlexFieldsFromSaveData($_VARS['save']['flex_fields']);

		$oImport->setFlexFields($aFlexFields);
		
		$oImport->setSettings((array)$_VARS['save']['settings']);

		$aReport = $oImport->execute();

		$aErrors = $oImport->getErrors();

		$oErrorMessages = collect([]);
		foreach($aErrors as $iItem => $aItemErrors) {

			$oItemMessages = collect($aItemErrors)
				->mapToGroups(function($aError) use ($iItem) {

					$sPrefix = '';
					if(is_numeric($iItem)) {
						$sPrefix = sprintf($this->t('Zeile %d'), $iItem).': ';
					}

					if(
						!empty($aError['pointer']) && 
						!empty($aError['pointer']->getWorksheet())
					) {
						$sPrefix = $aError['pointer']->getWorksheet().', '.sprintf($this->t('Zeile %d'), $aError['pointer']->getRowIndex()).': ';
					}

					return [$sPrefix => $aError['message']];
				})
				->map(fn ($oRowCollection, $sPrefix) => $sPrefix.$oRowCollection->implode(' '))
				->values();

			$oErrorMessages = $oErrorMessages->merge($oItemMessages);
		}

		$sErrors = $oErrorMessages->implode('<br/>');
		
		$aTransfer = [
			'action' => 'showSuccessAndReloadTable',
			'data' => [
				'id'=>$this->getImportDialogId().'0'
			],
			'success_title' => $this->t('Import ausgeführt'),
			'message' => [sprintf($this->t('Es wurden %d Einträge erstellt, %d aktualisiert und %d wurden wegen Fehler übersprungen.'), $aReport['insert'], $aReport['update'], $aReport['error'])]
		];

		if ($oImport instanceof Group) {
			// Beim Gruppenimport auch die Buchungsliste direkt automatisch neu laden
			$aTransfer['parent_gui'] = $this->_oGui->getParentGuiData();
		}

		if(isset($aReport['terminated'])) {
			$aTransfer['action'] = 'showError';
			$aTransfer['error'] = [
				$this->t('Import abgebrochen'),
				$sErrors
			];
		} elseif(!empty($sErrors)) {
			$aTransfer['message'][] = $sErrors;
		}
		
		return $aTransfer;
	}

	/**
	 * TODO: Beispieldatei anhand der ausgewählten individuellen Feldern generieren
	 *
	 * @param $_VARS
	 * @return array
	 */
	public function requestAsUrlGenerateExample($_VARS, $fillWithData=false) {

		ini_set("memory_limit", "4G");

		$oService = $this->getImportService();

		$oReflection = new \ReflectionClass($oService);

		$oWriteSheetColumns = function($oSheet, array $aColumns) use ($fillWithData) {
			foreach($aColumns as $iColumnIndex => $aColumn) {

				if(isset($aColumn['individual']) && $aColumn['individual'] === true) {
					$sTitle = $this->t('Individuelles Feld').': '.$aColumn['field'];
				} else {
					$sTitle = $this->t($aColumn['field']);
				}

				// Head
				$sCode = \Util::getColumnCodeForExcel($iColumnIndex);
				$oSheet->getColumnDimension($sCode)->setAutoSize(true);
				$oSheet->setCellValue($sCode.'1', $sTitle);
				// First row
				if(
					!$fillWithData && 
					isset($aColumn['example'])
				) {
					$mValue = $aColumn['example'];
					if($mValue instanceof \Closure) {
						$mValue = $aColumn['example']();
					}

					$oSheet->setCellValue($sCode.'2', $mValue);
				}
			}
		};

		$oSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		$oMainSheet = $oSpreadsheet->getSheet(0);
		$oMainSheet->setTitle($this->t('Hauptdaten'));
		
		$aFlexFields = $this->splitFlexFieldsFromSaveData($_VARS['save']['flex_fields']);
		$aFlexFields = $oService->setFlexFields($aFlexFields);

		$aFields = $oService->getFields();

		$aSheetFlexFields = [];
	
		foreach($aFlexFields as $section=>$fields) {
			$aSheetFlexFields[$section] = [];
			foreach($fields as $columnIndex=>$field) {
				$oFlexField = \Ext_TC_Flexibility::getInstance($field['target']);
				$label = $oFlexField->getName();
				if(!empty($field['repeat_label'])) {
					$label.= ' ('.$field['repeat_label'].')';
				}
				$aSheetFlexFields[$section][] = [
					'field'=>$label,
					'individual' => true
				];
			}
		}
		
		if(!empty($aSheetFlexFields['Main'])) {
			$aFields = array_merge($aFields, $aSheetFlexFields['Main']);
		}

		$oWriteSheetColumns($oMainSheet, $aFields);

		if($fillWithData) {
			$this->fillWithData($oService, $oMainSheet, $aFields);								
		}
		
		$aAdditionalWorkSheets = $oService->getAdditionalWorksheets();

		if(is_array($aAdditionalWorkSheets)) {
			$iIndex = 1;
			foreach($aAdditionalWorkSheets as $sTitle => $aColumns) {

				if(!empty($aSheetFlexFields[$sTitle])) {
					$aColumns = array_merge($aColumns, $aSheetFlexFields[$sTitle]);
				}

				$oAdditionalWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($oSpreadsheet, $this->t($sTitle));

				$oWriteSheetColumns($oAdditionalWorkSheet, $aColumns);

				$oSpreadsheet->addSheet($oAdditionalWorkSheet, $iIndex);
				
				if($fillWithData) {
					$this->fillWithData($oService, $oAdditionalWorkSheet, $aColumns, $sTitle);
				}
				
				++$iIndex;
			}
		}

		// Mit Buffering, damit man Fehlermeldungen sieht
		ob_start();
		/** @var Writer\BaseWriter $oWriter */
		$oWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($oSpreadsheet);
		$oWriter->save('php://output');
		$content = ob_get_clean();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="import_'.\Util::getCleanFilename($oReflection->getShortName()).'.xlsx"');
		header('Cache-Control: max-age=0');

		echo $content;

		die();
	}

	public function fillWithData($service, $sheet, $fields, $additionalSheet=null) {
		
		$aData = $service->getExportData($fields, $additionalSheet);

		$rowIndex = 2;
		foreach($aData as $row) {
			$columnIndex = 0;
			foreach($row as $cell) {

				$columnCode = \Util::getColumnCodeForExcel($columnIndex);
				if($cell instanceof \DateTime) {

					// Format nur einmal pro Request ermitteln
					if(self::$sExcelDateFormat === null) {						
						$oFormat = \Factory::getObject('Ext_Gui2_View_Format_Date');
						$dTestDate = new \DateTime('1990-12-31');
						$sTestDate = $oFormat->formatByValue($dTestDate);
						self::$sExcelDateFormat = str_replace(array('31','12','1990','90'), array('dd', 'mm', 'yyyy', 'yy'), $sTestDate);
					}

					$sheet->setCellValue($columnCode.$rowIndex, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($cell));
					$sheet->getStyle($columnCode.$rowIndex)->getNumberFormat()->setFormatCode(self::$sExcelDateFormat);
				} elseif(is_numeric($cell)) {
					$sheet->setCellValueExplicit($columnCode.$rowIndex, $cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
			    } elseif(is_bool($cell)) {
					$sheet->setCellValueExplicit($columnCode.$rowIndex, $cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_BOOL);
				} elseif($cell === null) {
					$sheet->setCellValueExplicit($columnCode.$rowIndex, $cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NULL);
				} else {
					$sheet->setCellValueExplicit($columnCode.$rowIndex, $cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				}
				$columnIndex++;
			}
			$rowIndex++;
		}
		
	}
	
	/**
	 * TODO: Beispieldatei anhand der ausgewählten individuellen Feldern generieren
	 *
	 * @param $_VARS
	 * @return array
	 */
	public function requestAsUrlExport($_VARS) {

		ini_set("memory_limit", "4G");
		
		$this->requestAsUrlGenerateExample($_VARS, true);

	}
	
	public function requestImport($_VARS) {

		/* @var $oDialog \Ext_Gui2_Dialog */
		$oDialog = $this->_oGui->createDialog();
		$oDialog->sDialogIDTag = $this->getImportDialogId();
		$oDialog->save_button = false;
		$oDialog->bBigLabels = true;
				
		$oImport = $this->getImportService();
		$aImportFlexFields = $oImport->getFlexibleFields();

		$aFlexFieldOptions = [];
		if(!empty($aImportFlexFields)) {
			foreach($aImportFlexFields as $sSheet => $aFlexFields) {
				foreach($aFlexFields as $oFlexField) {
					$sName = $oFlexField->getName();
					if($sSheet !== 'Main') {
						$sName = $this->t($sSheet).' - '.$sName;
					}

					$aFlexFieldOptions[$sSheet.'_'.$oFlexField->getId()] = $sName;
				}
			}
		}
		
		asort($aFlexFieldOptions);
		
		$aFlexFieldSelectOptions = [
			'db_column'=>'flex_fields', 
			'multiple'=>5, 
			'jquery_multiple'=>true,
			'select_options'=>$aFlexFieldOptions,
			'events' => array(
				array(
					'event' => 'change',
					'function' => 'prepareAction',
					'parameter' => "{task:'saveDialog'}, {id:'".$this->getImportDialogId()."0',task:'request',action:'import'}"
				)
			)
		];
		$oRow = $oDialog->createRow($this->t('Wähle die individuellen Felder aus, die Du importieren möchtest'), 'select', $aFlexFieldSelectOptions);
		$oDialog->setElement($oRow);
		
		$oRow = $this->_oGui->createDialogUpload($this->t('Excel-Datei'), $oDialog, 'import');
		$oDialog->setElement($oRow);
		
		$oRow = $oDialog->createRow($this->t('Die erste Zeile der Datei enthält die Spaltennamen'), 'checkbox', ['db_column'=>'skip_first_row']);
		$oDialog->setElement($oRow);
		
		$this->addSettingFields($oDialog);
		
		$oHeadline = new \Ext_Gui2_Html_H4;
		$oHeadline->setElement($this->t('Spalten'));
		$oDialog->setElement($oHeadline);
		
		$oTabArea = $oDialog->createTabArea();
		$oTabContent1 = $oTabArea->createTab($this->t('Haupt-Tabellenblatt'));
		
		$aColumns = [];
		foreach($oImport->getFields() as $iField=>$aField) {
			
			$sLabel = '';
			if(!empty($aField['language'])) {
				$sLabel .= '<img src="'.\Util::getFlagIcon($aField['language']).'" alt="'.$aField['language'].'" title="'.$aField['language'].'"> ';
			}
			$sLabel .= $this->t($aField['field']);
			
			$aColumns[\Util::getColumnCodeForExcel($iField)] = $sLabel;

		}

		$aFlexFields = $this->splitFlexFieldsFromSaveData($_VARS['save']['flex_fields']);

		$addIndividualFieldColumns = function(array $aFlexFields, &$aColumns) {
			foreach($aFlexFields as $iFlexField) {

				if(str_contains($iFlexField, '_')) {
					continue;
				}

				$oFlexField = \Ext_TC_Flexibility::getInstance($iFlexField);

				if($oFlexField->i18n) {

					$aObjectLanguages = \Factory::executeStatic('Ext_TC_Object', 'getLanguages');

					foreach($aObjectLanguages as $sIso=>$sLanguage) {
						$aColumns[\Util::getColumnCodeForExcel(count($aColumns))] = '<img src="'.\Util::getFlagIcon($sIso).'" alt="'.\Util::getEscapedString($sLanguage).'" title="'.\Util::getEscapedString($sLanguage).'"> '.$this->t('Individuelles Feld').': '.$oFlexField->getName();
					}

				} else {
					$aColumns[\Util::getColumnCodeForExcel(count($aColumns))] = $this->t('Individuelles Feld').': '.$oFlexField->getName();
				}
			}
		};

		if(!empty($aFlexFields['Main'])) {
			$addIndividualFieldColumns($aFlexFields['Main'], $aColumns);
		}

		$oTabContent1->setElement($this->printColumnTable($aColumns));
		
		$aAdditionalWorksheets = $oImport->getAdditionalWorksheets();
		
		if(!empty($aAdditionalWorksheets)) {

			foreach($aAdditionalWorksheets as $sWorksheet=>$aAdditionalColumns) {
				$aColumns = [];
				$iColumn = 0;
				foreach($aAdditionalColumns as $aAdditionalColumn) {
					$label = $this->t($aAdditionalColumn['field']);
					if(!empty($aAdditionalColumn['hint'])) {
						$label .= ' ('.$this->t($aAdditionalColumn['hint']).')';
					}
					$aColumns[\Util::getColumnCodeForExcel($iColumn++)] = $label;
				}

				if(!empty($aFlexFields[$sWorksheet])) {
					$addIndividualFieldColumns($aFlexFields[$sWorksheet], $aColumns);
				}

				$oTabContent = $oTabArea->createTab($this->t('Tabellenblatt').' '.$this->t($sWorksheet));
				$oTabContent->setElement($this->printColumnTable($aColumns));

				
			}
		
		}
				
		$oDialog->setElement($oTabArea);
		
		$aData = $oDialog->generateAjaxData([], $this->_oGui->hash);

		// Array mit Events für die Eingabefelder
		$aData['events'] = $oDialog->getEvents();

		$aData['values'] = [
			[
				'db_column' => 'flex_fields',
				'value' => (array)$_VARS['save']['flex_fields']
			]
		];

		$aData['title'] = $this->t('Import');
		$aData['buttons'] = [];
		
		if($oImport->provideExport) {
			$aData['buttons'][] = [
				'label' => $this->t('Aktuelle Daten exportieren'),
				'task' => 'saveDialogAsUrl',
				'action' => 'export',
				'request_data' => '&task=requestAsUrl&action=export',
				'default' => true
			];
		}
		
		$aData['buttons'][] = [
			'label' => $this->t('Beispieldatei generieren'),
			'task' => 'requestAsUrl',
			'action' => 'generate-example',
			'request_data' => '&task=requestAsUrl&action=generate-example',
			'default' => true
		];
		$aData['buttons'][] = [
			'label' => $this->t('Import starten'),
			'task' => 'saveDialog',
			'action' => 'execute-import'
		];
		
		

		$aData['task'] = 'request';
		$aData['action'] = 'execute-import';
		
		$aTransfer = [
			'action' => 'openDialog',
			'data' => $aData
		];

		return $aTransfer;
	}
	
	protected function printColumnTable($aColumns) {
		
		$sColumnTable = '<table class="table table-condensed">';
		$sColumnTable .= '<tr><th style="width: 100px">'.$this->t('Spalte').'</th><th style="width: auto">'.$this->t('Beschreibung').'</th></tr>';
		
		foreach($aColumns as $sColumnCode=>$sLabel) {
			$sColumnTable .= "<tr><td>".$sColumnCode."</td><td>".$sLabel.'</td></tr>';
		}

		$sColumnTable .= '</table>';
		
		return $sColumnTable;
	}

	protected function splitFlexFieldsFromSaveData($aSaveData) {
		$aFlexFields = [];
		if(is_array($aSaveData)) {
			foreach($aSaveData as $mFlexField) {
				list($sSpreedSheet, $iFlexFieldId) = explode('_', $mFlexField);
				$aFlexFields[$sSpreedSheet][] = $iFlexFieldId;
			}
		}

		return $aFlexFields;
	}

}
