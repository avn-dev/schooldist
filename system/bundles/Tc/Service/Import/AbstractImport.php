<?php

namespace Tc\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Language\Backend;

/**
 * @deprecated Es gibt mit Ext_TC_Import und den Ableitungen schon entsprechende Klassen. Diese sollten erweitert werden und nicht parallel dazu was neues weitergeführt werden.
 * 
 * Aktuell nur für Agentur- und Unterkunftsimport verwenden
 * Kann später allgemein erweitert werden, vieles ist schon allgemein ausgelegt.
 */
abstract class AbstractImport {

	const L10N_PATH = 'Fidelo » Import';

	protected $aItems = [];
	protected $aSettings = [];
	protected $aFlexFields = [];
	protected $sImportKey;
	protected $sTable;
	protected $oL10N;

	protected $aFields = [];

	protected $sEntity;
	protected $aTitles;

	protected $aReport = [];

	/**
	 * @var array
	 */
	protected $aErrors = [];

	/**
	 * @var \Ext_TC_Import
	 */
	protected $oImport;

	protected $sExcelDateFormat = 'n/j/Y';

	/**
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	protected $oSpreadsheet;

	protected $aWorksheetTitles = [];
	
	public $provideExport = false;

	public function __construct() {
		
		$oEntity = new $this->sEntity;
		$this->sTable = $oEntity->getTableName();
		
		$this->aFields = $this->getFields();

		$this->oL10N = (new Backend(\System::getInterfaceLanguage()))
			->setContext(self::L10N_PATH);
	}

	/**
	 * Nummernkreise die für den Import gesperrt werden müssen
	 *
	 * @return array
	 */
	protected function getNumberranges(): array {
		return [];
	}

	public function getFlexibleFields() {

		/** @var \Ext_TC_Basic $oEntity */
		$oEntity = new $this->sEntity;
		$aFlexFields['Main'] = $oEntity->getFlexibleFields();

		return $aFlexFields;
	}
	
	public function setFlexFields(array $aFlexFields) {
		
		$this->aFlexFields = [];

		if(!empty($aFlexFields)) {

			$aColumnIndex = [];
			$aColumnIndex['Main'] = count($this->aFields);
			$aAdditionalWorksheets = $this->getAdditionalWorksheets();
			foreach($aAdditionalWorksheets as $sAdditionalWorksheet => $aAdditionalWorksheet) {
				$aColumnIndex[$sAdditionalWorksheet] = count($aAdditionalWorksheet);
			}

			foreach($aFlexFields as $sSpreedSheet => $aFlexFieldIds) {

				$iFlexFieldIndex = $aColumnIndex[$sSpreedSheet];

				foreach($aFlexFieldIds as $iFlexFieldId) {

					$oFlexField = \Ext_TC_Flexibility::getInstance($iFlexFieldId);

					$aRepeats = [null];
					if($oFlexField->i18n) {
						$aRepeats = \Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages');
					}

					foreach($aRepeats as $sRepeat=>$sRepeatLabel) {

						$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex] = ['target'=>$iFlexFieldId];

						switch($oFlexField->type) {
							case \Ext_TC_Flexibility::TYPE_CHECKBOX:
								$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['special'] = 'yes_no';
								break;
							case \Ext_TC_Flexibility::TYPE_SELECT:
								$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['special'] = 'array';
								$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['additional'] = array_flip(\Ext_TC_Flexibility::getOptions($oFlexField->id, \System::getInterfaceLanguage()));
								break;
							case \Ext_TC_Flexibility::TYPE_MULTISELECT:
								$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['special'] = 'array_split';
								$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['additional'] = array_flip(\Ext_TC_Flexibility::getOptions($oFlexField->id, \System::getInterfaceLanguage()));
								break;
						}

						if($sRepeatLabel !== null) {
							$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['array_index'] = $sRepeat;
							$this->aFlexFields[$sSpreedSheet][$iFlexFieldIndex]['repeat_label'] = $sRepeatLabel;
						}

						++$iFlexFieldIndex;
					}
				}

			}
		}

		return $this->aFlexFields;
	}
	
	public function setSpreadsheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $oSpreadsheet) {
		$this->oSpreadsheet = $oSpreadsheet;
	}

	public function setItems($aItems, bool $bSkipFirstRow) {
			
		if($bSkipFirstRow === true) {
			// Überschrift entfernen
			// Mit unset() arbeiten damit der Zeilenindex der Items in Fehlermeldungen stimmt
			//$this->aTitles = array_shift($aItems);
			$this->aTitles = $aItems[0];
			unset($aItems[0]);
		}

		$this->aItems = $aItems;
	}
	
	/**
	 * @param array $aSettings
	 */
	public function setSettings(array $aSettings) {
		
		$this->aSettings = $aSettings;
		
	}

	public function getErrors(): array {
		return $this->aErrors;
	}
	
	/**
	 * @param string $sString
	 *
	 * @return string
	 */
	protected function replaceBreaks($sString) {
		
		$sString = preg_replace("/(\r\n|\n|\r)/", ", ", $sString);
		
		return $sString;
	}
	
	public function execute() {

		ini_set("memory_limit", "8G");
		set_time_limit(3600);

		$aItems = $this->aItems;

		$sImportClass = \Factory::getClassName(\Ext_TC_Import::class);

		$this->sImportKey = \Util::getCleanFilename(get_class($this)).'_'.date('YmdHis');
		
		$this->oImport = new $sImportClass($this->sImportKey);
		$this->oImport->activateSave();
		$sImportClass::$oDb = \DB::getDefaultConnection();
		$sImportClass::setAutoIncrementReset(false);
		
		$bImportEntities = true;
		
		$this->aReport = [
			'insert'=>0,
			'update'=>0,
			'error'=>0
		];

		if($bImportEntities) {

			$aNumberranges = $this->getNumberranges();

			// Vor der Transaktion die Nummernkreise sperren
			foreach ($aNumberranges as $oNumberrange) {
				if (!$oNumberrange->acquireLock()) {
					#throw new \RuntimeException($this->t('Es werden gerade bereits Nummernkreise an anderer Stelle benutzt! Bitte versuchen Sie es gleich erneut.'));
				}
			}

			// Ressorcen
			// Das muss auf jeden Fall vor dem Start der Transaktion passieren da sonst die Transaktion durch die DDL statements
			// beendet wird: DDL statements, atomic or otherwise, implicitly end any transaction that is active in the current session
			$aTables = $this->getBackupTables();
			$sImportClass::prepareImport($aTables, $this->sImportKey, false);

			\DB::begin(__METHOD__);

			try {
							
				$oMainSheet = $this->oSpreadsheet->getSheet(0);
				$this->aWorksheetTitles[0] = $oMainSheet->getTitle();

				$aAdditionalWorksheets = $this->getAdditionalWorksheets();

				// Entsprechende Datensätze aus den anderen Tabellenblättern holen
				if(!empty($aAdditionalWorksheets)) {
					$aAdditionalWorksheetDataRows = [];
					$i=1;
					foreach($aAdditionalWorksheets as $sAdditionalWorksheet=>$aAdditionalWorksheet) {
						$oWorksheet = $this->oSpreadsheet->getSheet($i);
						$this->aWorksheetTitles[$sAdditionalWorksheet] = $oWorksheet->getTitle();

						$aRows = $oWorksheet->toArray();

						foreach($aRows as $iRowIndex => $aRow) {
							$iForeignKey = reset($aRow);
							$aAdditionalWorksheetDataRows[$iForeignKey][$sAdditionalWorksheet][($iRowIndex + 1)] = $aRow;
						}
						
						$i++;
					}
				}

				foreach((array)$aItems as $iItem => $aItem) {
					// Die Zeile 0 existiert nicht, daher den Array-Index um 1 erhöhen
					$iRowIndex = ($iItem + 1);

					// Leere Zeile überspringen
					$aTest = array_filter($aItem);
					if(empty($aTest)) {
						continue;
					}					
					
					// Erstes Feld ist ID, falls zusätzliche Arbeitsblätter verwendet werden
					$iPrimaryKey = reset($aItem);
					$aAdditionalWorksheetData = null;
					if(
						isset($aAdditionalWorksheetDataRows) &&
						!empty($aAdditionalWorksheetDataRows[$iPrimaryKey])
					) {
						$aAdditionalWorksheetData = $aAdditionalWorksheetDataRows[$iPrimaryKey];
					}

					$iEntityId = $this->processItem($aItem, $iRowIndex, $aAdditionalWorksheetData);

					if(
						!empty($iEntityId) &&
						!empty($this->aFlexFields['Main'])
					) {
						$oEntity = $this->sEntity::getInstance($iEntityId);
						$this->oImport->saveFlexValues($this->aFlexFields['Main'], $aItem, $iEntityId, $oEntity->getEntityFlexType());
					}

					
					if($iRowIndex % 100 === 0) {
						\WDBasic::clearInstances($this->sEntity);
					}
					
				}

				\DB::commit(__METHOD__);
				
			} catch(\Throwable $e) {

				if ($e instanceof \PhpOffice\PhpSpreadsheet\Exception) {
					$this->aErrors['file1'] = [['message' => $this->t('Bitte überprüfen Sie die Excel-Datei.')]];
					$this->aErrors['file2'] = [['message' => $e->getMessage()]];
				}

				$this->aReport['terminated'] = true;
				\DB::rollback(__METHOD__);

			}

			foreach ($aNumberranges as $oNumberrange) {
				$oNumberrange->removeLock();
			}

		}

		return $this->aReport;
	}

	protected function getWorksheetTitle($index) {

		if(isset($this->aWorksheetTitles[$index])) {
			return (string) $this->aWorksheetTitles[$index];
		}

		return $index;
	}

	abstract public function getFields();
	
	public function getAdditionalWorksheets():? array {
		return null;
	}
	
	abstract protected function getBackupTables();

	abstract protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null);
	
	protected function getCheckItemFields(array $aPreparedData) {}
	
	protected function checkArraySplitFields(array $aItem, array $aData, array $fields=null) {
		
		if($fields === null) {
			$fields = $this->aFields;
		}

		foreach($fields as $fieldIndex=>$field) {
			
			// Wenn das Feld an sich leer ist und kein Pflichtfeld ist, brauch nix überprüft werden
			if(
				empty($field['mandatory']) &&
				empty($aItem[$fieldIndex])
			) {
				continue;
			}
			
			if($field['special'] == 'array_split') {
				if(
					!is_array($aData[$field['target']]) || // Wenn das Feld im Import leer ist, dann wird hier ein leerer String sein. Es wird aber ein Array erwartet.
					in_array(null, $aData[$field['target']])
				) {
					throw new ImportRowException(sprintf($this->t('%s nicht gefunden!'), $field['field']).' ('.$this->t('Mögliche Werte').': '.implode(', ', array_keys($field['additional'])).')');
				}
			} elseif($field['special'] == 'array') {
				if(
					$aData[$field['target']] === null ||
					$aData[$field['target']] === ""
				) {
					throw new ImportRowException(sprintf($this->t('%s nicht gefunden!'), $field['field']).' ('.$this->t('Mögliche Werte').': '.implode(', ', array_keys($field['additional'])).')');
				}
			}
		}
		
	}
	
	protected function t(string $translate) {
		return \L10N::t($translate, 'TS » Import');
	}
	
	public function getExportRowFieldValue(\WDBasic $entity, array $field, $additionalWorksheet=null) {
		return $entity->{$field['target']};
	}
	
	public function prepareExportRowField(&$value, \WDBasic $entity, array $field, $additionalWorksheet=null) {
		
		$copy = $value;
		switch($field['special']) {
			case 'array_split':
				$value = array_intersect_key(array_flip($field['additional']??[]), array_flip($value??[]));
				$value = implode(', ', $value);
				break;
			case 'array':
				$value = $field['additional'][$value] ?? '';
				break;
			case 'gender':
				$genders = \Ext_TC_Util::getGenders();
				$value = $genders[$value];
				break;
			case 'yes_no':
				$yesno = \Ext_TC_Util::getYesNoArray();
				$value = $yesno[$value];
				break;
			case 'date_object':
				$value = new \DateTime($value);
				break;
		}
		
	}
	
	public function getExportEntities($additionalWorksheet=null) {
		
		$entities = $this->sEntity::getRepository()->findAll();
		
		return $entities;
	}
	
	public function getExportData(array $fields, $additionalWorksheet=null) {

		$entities = $this->getExportEntities($additionalWorksheet);

		$data = [];
		
		foreach($entities as $entity) {
			foreach($fields as $field) {
				if(!empty($field['target'])) {
					$value = $this->getExportRowFieldValue($entity, $field, $additionalWorksheet);
					$this->prepareExportRowField($value, $entity, $field, $additionalWorksheet);
					$data[$entity->id][] = $value;
				} elseif(
					!empty($field['unique_id'])
				) {
					$data[$entity->id][] = $entity->{$field['unique_id']};
				} else {
					$data[$entity->id][] = '';
				}
			}
		}

		return $data;
	}

	public function translate(string $sTranslate): string {
		return $this->oL10N->translate($sTranslate);
	}
}
