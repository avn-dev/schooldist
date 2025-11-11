<?php

/**
 * Class Ext_TS_Accounting_Provider_Grouping_Accommodation_Gui2_Data
 */
class Ext_TS_Accounting_Provider_Grouping_Accommodation_Gui2_Data extends Ext_Thebing_Gui2_Data {

	/**
	 * Teilpfad für Dateien der bezahlten Unterkunftsanbieter
	 * @var string
	 */
	const FILE_PATH = '/provider_payment/accommodation/';

	/**
	 * Ordnername für XML-Dateien
	 * @var string
	 */
	const PATH_XML = 'xml/';

	/**
	 * Ordnername für TXT-Dateien
	 * @var string
	 */
	const PATH_TXT = 'txt/';

	/**
	 * Ordnername für CSV-Dateien
	 * @var string
	 */
	const PATH_CSV = 'csv/';

	/**
	 * @var array
	 */
	private $aPlaceholders = [
		'firstname',
		'lastname',
		'start',
		'end',
		'start_week',
		'end_week',
		'provider_name',
	];
	
	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 * @throws Exception
	 */
	public static function getInterfaceExportHistoryDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Historie'), $oGui->t('Historie'));
		$oDialog->save_button = false;
		$oFactory = new Ext_Gui2_Factory('ts_accounting_provider_payment_overview_accommodation_history');
		$oGui = $oFactory->createGui();
		$oDialog->setElement($oGui);

		return $oDialog;

	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	static public function getInterfaceExportDialog(Ext_Gui2 $oGui){
		$oDialog = $oGui->createDialog($oGui->t('Weiterverarbeitung'), $oGui->t('Weiterverarbeitung'));
		$oInfo = $oDialog->createNotification(
			$oGui->t('Bitte beachten Sie folgendes:'),
			$oGui->t(
				'Die selektierten Datensätze werden für die Weiterverarbeitung in einem externen Programm vorbereitet. <br/> 
				 Bitte beachten Sie, dass dieser Schritt nur einmalig möglich ist und dass die Exportierung der Buchungssätze nicht umkehrbar ist!
			'), 'info');
		$oDialog->setElement($oInfo);
		$oDialog->save_button = false;
		$aButton = array(
			'label'	=> $oGui->t('Exportieren'),
			'task'	=> 'saveDialog',
			'id'	=> 'interface_export'
		);
		$oDialog->aButtons = array($aButton);
		$oDialog->height = 350;
		return $oDialog;
	}

	/**
	 * Verarbeitet den angekommenden Request und erstellt einen Export
	 *
	 * @param array $aVars
	 * @return array
	 */
	protected function requestAsUrlInterfaceExport(array $aVars) {

		$aError = [];
		$sFilePath = '';
		$aSelectedIds = $aVars['id'];

		// Export Optionen der Schule laden
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if((int)$oSchool->id > 0) {
			switch($oSchool->sepa_file_format) {
				case 'txt':
					$sFilePath = $this->exportTo($aSelectedIds, $oSchool);
					if(!$sFilePath) {
						$aError[] = $this->t('Es konnte keine Export-Datei erstellt werden! Export wurde abgebrochen.');
					}
					break;
				case 'csv':
					$sFilePath = $this->exportTo($aSelectedIds, $oSchool, 'csv');
					if(!$sFilePath === '') {
						$aError[] = $this->t('Es konnte keine Export-Datei erstellt werden! Export wurde abgebrochen.');
					}
					break;
				case 'sepa':
					$bIsValid = $this->validateSchoolAndProvider($aSelectedIds, $oSchool);
					if($bIsValid) {
						$sFilePath = $this->exportToSEPA($aSelectedIds, $oSchool);
					} else {
						$aError[] = $this->t('Es fehlen Bankinformationen in der Schule, oder bei den markierten Unterkunftsanbietern');
					}
					break;
				default:
					$aError[] = $this->t('Die aktuelle Schule hat keine Export-Einstellungen für die bezahlten Unterkunftsanbieter. Bitte überprüfen Sie die Einstellungen der Schule!');
			}
		}

		$aGetBack = [
			'errors' => $aError,
			'success' => $sFilePath,
		];

		return $aGetBack;

	}

	/**
	 * Exportiert eine CSV oder TXT Datei
	 *
	 * @param array $aSelectedIds
	 * @param Ext_Thebing_School $oSchool
	 * @param string $sExportType
	 * @return string
	 */
	private function exportTo(array $aSelectedIds, Ext_Thebing_School $oSchool, $sExportType = 'txt') {

		$dNow = new \Core\Helper\DateTime('now');
		
		$sFileName = $oSchool->getName().'_'.$dNow->format('YmdHis');

		$sFileName = Util::getCleanFilename($sFileName);
		$sReturnPath = $oSchool->getSchoolFileDir(false).self::FILE_PATH;

		if($sExportType === 'txt') {
			$sFileName .= '.txt';
			$sPath = $oSchool->getSchoolFileDir().self::FILE_PATH.self::PATH_TXT;
			$sReturnPath .= self::PATH_TXT;
			Util::checkDir($sPath);
		} else {
			$sFileName .= '.csv';
			$sPath = $oSchool->getSchoolFileDir().self::FILE_PATH.self::PATH_CSV;
			$sReturnPath .= self::PATH_CSV;
			Util::checkDir($sPath);
		}
		
		$oGuiCSVExport = new Gui2\Service\Export\Csv($sFileName);
		$oGuiCSVExport->setCharset($oSchool->sepa_file_coding);
		$oGuiCSVExport->setSeperator($oSchool->sepa_export_separator);
		
		$aColumns = $this->getColumns($oSchool->sepa_columns, $aSelectedIds, $oSchool);


		$sCSV = $oGuiCSVExport->createFromGuiTableData($aColumns);

		// Datei speichern
		$bSuccess = file_put_contents($sPath.$sFileName, $sCSV);

		if($bSuccess) {
			$this->saveObjects($aSelectedIds, $sReturnPath, $sFileName);
		}

		return $sReturnPath.$sFileName;
	}

	/**
	 * Exportiert ein SEPA-XML
	 *
	 * @param array $aSelectedIds
	 * @param Ext_Thebing_School $oSchool
	 * @return string
	 */
	private function exportToSEPA(array $aSelectedIds, Ext_Thebing_School $oSchool) {

		$dNow = new \DateTime('now');

		$sPath = $oSchool->getSchoolFileDir().self::FILE_PATH.self::PATH_XML;
		$sReturnPath = $oSchool->getSchoolFileDir(false).self::FILE_PATH.self::PATH_XML;
		Util::checkDir($sPath);

		$sSchoolName = Util::getCleanFilename($oSchool->account_holder);
		$sSchoolName = substr($sSchoolName, 0, 20);
		
		$sUniqueMessageIdentifier = $sSchoolName.'_'.$dNow->format('Ymdhis');

		// Create the initiating information
		$oGroupHeader = new Digitick\Sepa\GroupHeader($sUniqueMessageIdentifier, $oSchool->getName());
		
		$oGroupHeader->setCreationDateTimeFormat('Y-m-d\TH:i:s');
		
		$sSepaOrgId = $oSchool->sepa_org_id;
		if(!empty($sSepaOrgId)) {
			$oGroupHeader->setInitiatingPartyId($sSepaOrgId);
		}

		$oSepaFile = new Digitick\Sepa\TransferFile\CustomerCreditTransferFile($oGroupHeader);

		// Create a PaymentInformation the Transfer belongs to
		$oPayment = new Digitick\Sepa\PaymentInformation(
			$sUniqueMessageIdentifier,
			$this->cleanIban($oSchool->iban), // IBAN the money is transferred from
			$oSchool->bic,  // BIC
			$oSchool->account_holder // Debitor Name
		);

		foreach($aSelectedIds as $iId) {

			$oProviderGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($iId);

			if($oSchool->sepa_export_per === 'family') {

				$oAccommodation = $oProviderGrouping->getItem();

				$oTransfer = new Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation(
					(int)round($oProviderGrouping->amount * 100), // Amount in Cent-Beträgen
					$this->cleanIban($oAccommodation->bank_account_iban), //IBAN of creditor
					mb_substr($oAccommodation->getName(), 0, 140) //Name of Creditor
				);
				$oTransfer->setBic($oAccommodation->bank_account_bic); // Set the BIC explicitly
				$oTransfer->setRemittanceInformation($this->convertPlaceholder(
						$oSchool->sepa_comment,
						$oProviderGrouping
					));
				$oTransfer->setEndToEndIdentification($oProviderGrouping->id);
				$oPayment->addTransfer($oTransfer);

			} else {

				$oAccommodation = $oProviderGrouping->getItem();
				$aPayments = $oProviderGrouping->getJoinedObjectChilds('payments');

				foreach($aPayments as $oTmpPayment) {

					$oTransfer = new Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation(
						(int)round($oTmpPayment->amount * 100), // Amount in Cent-Beträgen
						$this->cleanIban($oAccommodation->bank_account_iban), //IBAN of creditor
						mb_substr($oAccommodation->getName(), 0, 140)
					);
					$oTransfer->setBic($oAccommodation->bank_account_bic); // Set the BIC explicitly
					$oTransfer->setRemittanceInformation($this->convertPlaceholder(
						$oSchool->sepa_comment,
						$oProviderGrouping, $oTmpPayment
					));
					$oTransfer->setEndToEndIdentification($oTmpPayment->id);
					
					$oPayment->addTransfer($oTransfer);
				}

			}

		}

		// It's possible to add multiple payments to one SEPA File
		$oSepaFile->addPaymentInformation($oPayment);

		// Attach a dombuilder to the sepaFile to create the XML output
		$oDomBuilder = Digitick\Sepa\DomBuilder\DomBuilderFactory::createDomBuilder($oSepaFile, $oSchool->sepa_pain_format, false);

		$sXml = $oDomBuilder->asXml();
		$sXmlName = $oSchool->getName().'_'.$dNow->format('YmdHis').'.xml';

        $sXmlName = \Util::getCleanFilename($sXmlName);

		$sXml = $this->modifyXMLForEUStandards($sXml);

		// Datei speichern
		$bSuccess = file_put_contents($sPath.$sXmlName, $sXml);

		if($bSuccess) {
			$this->saveObjects($aSelectedIds, $sReturnPath, $sXmlName);
		}

		return $sReturnPath.$sXmlName;
	}

	protected function cleanIban($sIban) {

		$sIban = strtoupper($sIban);
		$sIban = str_replace(' ', '', $sIban);

		return $sIban;
	}
	
	/**
	 * Erstetzt die Platzhalter gegen die entsprechenden Werte.
	 *
	 * @param string $sText
	 * @param Ext_TS_Accounting_Provider_Grouping_Accommodation $oProviderGrouping
	 * @param Ext_Thebing_Accommodation_Payment $oPayment
	 * @return string
	 */
	private function convertPlaceholder(
		$sText, 
		Ext_TS_Accounting_Provider_Grouping_Accommodation $oProviderGrouping,
		Ext_Thebing_Accommodation_Payment $oPayment=null
	) {

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, Ext_Thebing_School::getSchoolFromSession()->getId());

		foreach($this->aPlaceholders as $sPlaceholder) {

			$sReplace = '';
			
			if($oPayment !== null) {
				switch($sPlaceholder) {
					case 'firstname':
						$oInquiry = Ext_TS_Inquiry::getInstance($oPayment->inquiry_id);
						$oBooker = $oInquiry->getBooker();
						if($oBooker !== null) {
							$sReplace = $oBooker->firstname;
						} else {
							$sReplace = $oInquiry->getFirstTraveller()->firstname;
						}
						break;
					case 'lastname':
						$oInquiry = Ext_TS_Inquiry::getInstance($oPayment->inquiry_id);
						$oBooker = $oInquiry->getBooker();
						if($oBooker !== null) {
							$sReplace = $oBooker->lastname;
						} else {
							$sReplace = $oInquiry->getFirstTraveller()->lastname;
						}
						break;
					case 'start':
						$iFromTimeStamp = $oPayment->getFromDate()->get(WDDate::TIMESTAMP);
						$dFrom = \Core\Helper\DateTime::createFromLocalTimestamp($iFromTimeStamp);
						$sReplace = $oDateFormat->format($dFrom);
						break;
					case 'end':
						$iUntilTimeStamp = $oPayment->getUntilDate()->get(WDDate::TIMESTAMP);
						if($iUntilTimeStamp !== false) {
							$dUntil = \Core\Helper\DateTime::createFromLocalTimestamp($iUntilTimeStamp);
							$sReplace = $oDateFormat->format($dUntil);
						} else {
							$sReplace = '';
						}
						break;
					case 'start_week':
						$iStartWeek = $oPayment->getFromDate()->get(WDDate::TIMESTAMP);
						$sReplace = \Core\Helper\DateTime::createFromLocalTimestamp($iStartWeek)->format('W');
						break;
					case 'end_week':
						$iEndWeek = $oPayment->getUntilDate()->get(WDDate::TIMESTAMP);
						if($iEndWeek !== false) {
							$sReplace = \Core\Helper\DateTime::createFromLocalTimestamp($iEndWeek)->format('W');
						} else {
							$sReplace = '';
						}
						break;
				}
			}

			switch($sPlaceholder) {
				case 'provider_name':
					$sReplace = $oProviderGrouping->getItem()->getName();
					break;
			}

			$sText = str_replace('{'.$sPlaceholder.'}', $sReplace, $sText);
		}

		return $sText;
	}

	/**
	 * Speichert die Objekte ab.
	 *
	 * @param array $aSelectedIds
	 * @param string $sPath
	 * @param string $sFilename
	 * @return void
	 */
	private function saveObjects(array $aSelectedIds, $sPath, $sFilename) {

		// Historie abspeichern
		$oHistory = new Ext_TS_Accounting_Provider_Grouping_Accommodation_History();
		$oHistory->absolute_path = $sPath.$sFilename;
		$oHistory->file = $sFilename;
		$oHistory->save();

		// Eintrag in die Zwischentabelle beginnen
		foreach($aSelectedIds as $iId) {

			$oProviderGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($iId);

			$aData = [
				'payment_grouping_id' => $oProviderGrouping->getId(),
				'history_id' => $oHistory->getId(),
			];

			DB::insertData('ts_accommodations_payments_groupings_to_histories', $aData);

			// Flag setzen, dass dieser Eintrag weiterverarbeitet wurde
			$oProviderGrouping->processed = date('Y-m-d H:i:s');
			$oProviderGrouping->index_processed_user_id = $oHistory->creator_id;
			$oProviderGrouping->save();

		}

	}

	/**
	 * Prüft ob die Schule und die / der Anbieter eine Iban haben
	 *
	 * @param array $aSelectedIds
	 * @param Ext_Thebing_School $oSchool
	 * @return bool
	 */
	private function validateSchoolAndProvider(array $aSelectedIds, Ext_Thebing_School $oSchool) {

		$bIsValid = true;

		// Schule prüfen ob diese IBAN hat
		if(empty($oSchool->iban)) {
			$bIsValid = false;
		}

		if($bIsValid) {
			// Die Provider müssen ebenfalls kontrolliert werden ob Bankdaten verfügbar sind
			foreach ($aSelectedIds as $iId) {

				$oProviderGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($iId);
				$oAccommodation = $oProviderGrouping->getItem();

				// Prüfen ob der Anbieter eine Iban hat
				if (empty($oAccommodation->bank_account_iban)) {
					$bIsValid = false;
					break;
				}

			}
		}

		return $bIsValid;

	}

	/**
	 * Gibt alle benötigten Spalten zurück
	 *
	 * @param array $aUsedColumns
	 * @param array $aSelectedIds
	 * @param Ext_Thebing_School $oSchool
	 * @return array
	 */
	private function getColumns(array $aUsedColumns, array $aSelectedIds, Ext_Thebing_School $oSchool) {
		global $_VARS;

		$this->_oGui->column_flexibility = false;
		$aData = $this->_oGui->getTableData([], [], $aSelectedIds, 'api', true);

		$oPage = $this->_oGui->getPage();

		$aElements = $oPage->getElements();
		// Element [0] ist die Gui der ersten Liste
		$oChildGui = $aElements[1];

		$_VARS['parent_gui_id'] = $aSelectedIds;

		if($oSchool->sepa_export_per == 'payment_entry') {

			// Einträge der unteren Liste zu den in der oberen Liste gewählten Einträge
			$aSecondList = $oChildGui->getTableData([], [], [], 'csv', true);

			// Daten der unteren Liste in Ergebnis packen
			$aReturn = $aSecondList;

			// Spalten der oberen Liste in Ergebnis ergänzen
			foreach ($aData['head'] as $iKey => $aColumn) {
				if($aColumn['db_column'] !== 'amount') {
					$aReturn['head'][] = $aColumn;
				}
			}

			/*
			 * Spalte "amount" aus Daten der oberen Liste entfernen, da der Wert aus den Daten der unteren Liste
			 * verwendet werden solll
			 */
			foreach ($aData['body'] as $iKey => $aRow) {
				foreach($aData['body'][$iKey]['items'] as $iItem=>$aItem) {
					if($aItem['db_column'] === 'amount') {
						unset($aData['body'][$iKey]['items'][$iItem]);
					}
				}
			}

			// Daten der unteren Liste mit den Daten der oberen Liste ergänzen
			foreach ($aData['body'] as $firstListRowKey => $firstListRow) {
				foreach ($aSecondList['body'] as $secondListRowKey => $secondListRow) {
					if ($firstListRow['id'] === $secondListRow['parent_gui_id']) {
						$aReturn['body'][$secondListRowKey]['items'] = array_merge($aReturn['body'][$secondListRowKey]['items'], $aData['body'][$firstListRowKey]['items']);
					}
				}
			}

		} else {
			$aReturn = $aData;
		}

		$aColumnMapping = [];
		foreach ($aReturn['head'] as $iColumn => $aColumn) {
			$aColumnMapping[$aColumn['db_column']] = $iColumn;
		}

		$aResult = $aReturn;
		$aReturn['head'] = [];
		$aReturn['body'] = [];
		foreach($aResult as $sKey => $aRow) {

			if($sKey === 'head') {

				foreach($aUsedColumns as $sUsedColumn) {
					$aReturn[$sKey][$aColumnMapping[$sUsedColumn]] = $aRow[$aColumnMapping[$sUsedColumn]];
				}
				$aReturn[$sKey] = array_values($aReturn[$sKey]);
			} elseif($sKey === 'body') {

				foreach($aRow as $mKey => $aBodyRow) {
					$aReturn[$sKey][$mKey]['id'] = $aBodyRow['id'];
					foreach($aUsedColumns as $sUsedColumn) {
						$aReturn[$sKey][$mKey]['items'][$aColumnMapping[$sUsedColumn]] = $aBodyRow['items'][$aColumnMapping[$sUsedColumn]];
					}

					$aReturn[$sKey][$mKey]['items'] = array_values($aReturn[$sKey][$mKey]['items']);
				}


			}

		}

		// Zusätzliche Columns als Array hinzufügen die nicht aus Guilisten kommen.
		#$aReturn = $this->addSpecialColumns($aReturn, $oSchool);

		return $aReturn;
	}

	/**
	 * Generiert die verfügbaren Spalten.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return array
	 */
	private function generateColumnArray(Ext_Thebing_School $oSchool) {

		$aSpecialColumns = [
			'head' => [
				[
					'db_column' => 'account_holder',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'account_holder',
					'title' => $this->_oGui->t('Kontoinhaber'),
				],
				[
					'db_column' => 'account_number',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'account_number',
					'title' => $this->_oGui->t('Kontonummer'),
				],
				[
					'db_column' => 'bank_code',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'bank_code',
					'title' => $this->_oGui->t('Bankleitzahl'),
				],
				[
					'db_column' => 'bank',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'bank',
					'title' => $this->_oGui->t('Name der Bank'),
				],
				[
					'db_column' => 'bank_address',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'bank_address',
					'title' => $this->_oGui->t('Bankadresse'),
				],
				[
					'db_column' => 'iban',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'iban',
					'title' => $this->_oGui->t('IBAN'),
				],
				[
					'db_column' => 'bic',
					'db_alias' => 'cdb2',
					'db_type' => 'varchar',
					'select_column' => 'bic',
					'title' => $this->_oGui->t('BIC'),
				],
			],
			'body' => [
				[
					'text' => $oSchool->account_holder,
					'db_alias' => 'cdb2',
					'db_column' => 'account_holder',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->account_number,
					'db_alias' => 'cdb2',
					'db_column' => 'account_number',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->bank,
					'db_alias' => 'cdb2',
					'db_column' => 'bank',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->bank_code,
					'db_alias' => 'cdb2',
					'db_column' => 'bank_code',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->bank_address,
					'db_alias' => 'cdb2',
					'db_column' => 'bank_address',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->iban,
					'db_alias' => 'cdb2',
					'db_column' => 'iban',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
				[
					'text' => $oSchool->bic,
					'db_alias' => 'cdb2',
					'db_column' => 'bic',
					'db_type' => 'varchar',
					'id' => $oSchool->getId(),
				],
			],
		];

		return $aSpecialColumns;

	}

	/**
	 * @param array $_VARS
	 * @throws Exception
	 */
	public function switchAjaxRequest($_VARS) {

		if(
			$_VARS['task'] === 'request' &&
			$_VARS['action'] === 'interface_export'
		) {
			$aTransfer = $this->requestAsUrlInterfaceExport($_VARS);
			echo json_encode($aTransfer);
		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array|mixed
	 */
	public function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;

		$aErrors = [];
		$bError = false;
		$bHasIds = false;
		$aTransfer = [];

		$aTransfer['action'] = 'saveDialogCallback';

		if($sAction == 'interface_export') {

			$aSelectedIds = null;
			if(!empty($aSelectedIds)) {
				$bHasIds = true;
			// SelectedIds ist bei dem Request mit der Checkbox nicht mehr gefüllt
			} elseif(!empty($_VARS['id'])) {
				$aSelectedIds = $_VARS['id'];
				$bHasIds = true;
			}

			if((int)$_VARS['ignore_errors'] !== 1) {
				$aErrors[0] = [
					'message' => $this->t('Sind Sie sich sicher, das die Daten final exportiert werden sollen? Dieser Schritt ist nicht umkehrbar!'),
					'type' => 'hint'
				];
				//Checkbox Fehler ignorieren einblenden
				$aTransfer['error'] = $aErrors;
				$aTransfer['data']['show_skip_errors_checkbox'] = 1;
			} elseif($bHasIds) {

				// Export erstellen
				$aExportResult = self::requestAsUrlInterfaceExport($_VARS);

				if (!empty($aExportResult['errors'])) {
					$sErrorMessages = implode('<br />', $aExportResult['errors']);
					$aErrors[0] = [
						'message' => $this->t($sErrorMessages),
						'type'    => 'error'
					];
					$bError = true;
				} else {
					try {
						$sFile = $aExportResult['success'];
						$sUrl = str_replace('/storage/', '/storage/download/', $sFile);
						$aTransfer['action'] = 'openUrlAndCloseDialog';
						$aTransfer['load_table'] = true;
						$aTransfer['url'] = $sUrl;
						$aTransfer['data']['id'] = 'ID_' . implode('_', $aSelectedIds);
					} catch (InvalidArgumentException $ex) {
						$aErrors[0] = [
							'message' => $this->t($ex->getMessage()),
							'type'    => 'error'
						];
						$bError = true;
					}
				}
			}

		// TODO Was ist das für ein komischer Key?
		// TODO Warum ist das so aufgeblasen?
		} elseif($sAction === 'delete-interface_export') {
			if((int)$_VARS['ignore_errors'] !== 1) {

				/*
			 * Muss eigentlich nicht instanziert werden, denn wenn $sIconAction den Wert "delete" beinhaltet
			 * dann muss immer mindestens ein Datensatz selektiert sein.
			 */
				$isProcessed = false;
				foreach($aSelectedIds as $sCurrentId) {
					$oAccommodationPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance((int)$sCurrentId);
					if($isProcessed = $oAccommodationPaymentGrouping->isProcessed()) {
						break;
					}
				}

				if($isProcessed) {
					$sMessage = 'Sind Sie sich sicher, dass Sie den Löschvorgang fortsetzen wollen? Dieser Datensatz wurde bereits weiterverarbeitet, wenn Sie fortsetzen ist dieser Schritt nicht mehr umkehrbar!';
				} else {
					$sMessage = 'Sind Sie sich sicher, dass Sie den Löschvorgang fortsetzen wollen? Dieser Schritt ist nicht umkehrbar!';
				}


				$aErrors[0] = [
					'message' => $this->t($sMessage),
					'type' => 'hint'
				];
				//Checkbox Fehler ignorieren einblenden
				$aTransfer['error'] = $aErrors;
				$aTransfer['data']['show_skip_errors_checkbox'] = 1;
			} else {

				// TODO Warum passiert das hier und oben doppelt?
				$aErrorMessages = [];
				foreach($aSelectedIds as $iSelectedId) {
					$oAccommodationPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance((int)$iSelectedId);
					$mCallBack = $oAccommodationPaymentGrouping->delete();

					if ($mCallBack !== true) {
						$aErrorMessages[] = $mCallBack;
					}
				}

				if(!empty($aErrorMessages)) {
					$aErrors[0] = [
						'message' => $aErrorMessages,
						'type'    => 'error'
					];
					$bError = true;
				} else {
					$aTransfer['error'] = [];
				}

			}
		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

		if($bError) {
			$aErrors = Ext_TC_Util::addEmptyItem($aErrors, $this->t('Es ist ein Fehler aufgetreten!'), -1);
			$aErrors = array_values($aErrors);
			$aTransfer['error'] = $aErrors;
		}

		return $aTransfer;

	}

	/**
	 * Wird genutzt um den Ablauf des Löschvorgangs zu ändern
	 *
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 * @throws Exception
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {

		// TODO Was ist das für ein komischer Key?
		// TODO Warum ist das so aufgeblasen?
		if($sIconAction === 'delete-interface_export') {

            $oDialog = $this->_oGui->createDialog($this->_oGui->t('Löschvorgang'));

			$bIsProcessed = false;
			foreach($aSelectedIds as $sCurrentId) {
				$oAccommodationPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance((int)$sCurrentId);
				if($bIsProcessed = $oAccommodationPaymentGrouping->isProcessed()) {
					break;
				}
			}

			if($bIsProcessed === false) {
				$sHint = 'Wenn Sie diese Bezahlung löschen, kann der Schritt nicht mehr rückgängig gemacht werden.';
			} else {
				$sHint = 'Diese Bezahlung wurde bereits weiterverarbeitet. Sie können die Bezahlung löschen, jedoch ist dieser Schritt nicht umkehrbar.';
			}

            $oInfo = $oDialog->createNotification(
                $oDialog->oGui->t('Achtung!'),
                $oDialog->oGui->t($sHint), 'info');
            $oDialog->setElement($oInfo);
            $oDialog->save_button = false;
            $aButton = array(
                'label'	=> $oDialog->oGui->t('Löschen'),
                'task'	=> 'saveDialog',
                'id'	=> 'deleteRow'
            );
            $oDialog->aButtons = array($aButton);
            $oDialog->height = 350;

		}

		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		return $aData;

	}

	/**
	 * Modifies urn:iso:std:iso:20022:tech:xsd:pain.001.001.03 XML for EU Banks Rules
	 * by removing PmtTpInf in CdtTrfTxInf, if the same already exists in PmtInf
	 *
	 * @param string $xml
	 * @return string
	 */
	private function modifyXMLForEUStandards(string $xml): string
	{
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;

		try {
			if (!$dom->loadXML($xml)) {
				\Log::getLogger()->error('Could not load XML for EU standards modification');
				return $xml;
			}
		} catch (Exception $e) {
			\Log::getLogger()->error('Could not load XML for EU standards modification', ['message' => $e->getMessage()]);
		}

		$root = $dom->documentElement;
		$namespace = $root->namespaceURI;

		// Nur 001.001.003 soll verändert werden
		if ($namespace !== 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03') {
			\Log::getLogger()->info('Not supported namespace for XML EU standards modification: '.$namespace);
			return $xml;
		}

		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('ns', $namespace);

		$parentNode = $xpath->query('//ns:PmtInf/ns:PmtTpInf')->item(0);
		$parentXml = '';
		if ($parentNode) {
			$parentXml = $dom->saveXML($parentNode);
		}

		$txNodes = $xpath->query('//ns:CdtTrfTxInf/ns:PmtTpInf');
		$removed = 0;

		foreach ($txNodes as $txNode) {
			$txXml = $dom->saveXML($txNode);

			$cleanParent = preg_replace('/\s+/', '', $parentXml);
			$cleanTx = preg_replace('/\s+/', '', $txXml);

			if ($cleanParent === $cleanTx) {
				$txNode->parentNode->removeChild($txNode);
				$removed++;
			}
		}
		\Log::getLogger()->info('Modified XML for EU standards. Removed '.$removed.' PmtTpInf nodes.');
		return $dom->saveXML();
	}

}