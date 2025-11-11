<?php

/**
 * Class Ext_TS_Accounting_BookingStack_Gui2_Data
 *
 * @property boolean $bExport
 */
class Ext_TS_Accounting_BookingStack_Gui2_Data extends Ext_Thebing_Gui2_Data {
	
	/**
	 * Bedingung, ob in $this->_oGui->_getTableData() die Filter eingebaut werden
	 * Das ist wichtig für das Export-Dokument in der Historie, da ansosnten nicht alle Einträge 
	 * übernommen werden
	 * 
	 * @var boolean 
	 */
	public $bExport = false;

	/**
	 * @return array
	 */
	public static function getListWhere() {

		$aWhere = array();
		if(!Ext_Thebing_System::isAllSchools()){
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aWhere = array('school_id' => $oSchool->id);
		}

		return $aWhere;
	}

	/**
	 * Sortiert die Einträge in der Liste
	 *
	 * @return array
	 */
	public static function getOrderBy() {
		return [];
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getCostCenterExistsOptions(Ext_Gui2 $oGui) {
		$aOptions = array(
			'exists'		=> $oGui->t('Buchungssätze mit Kostenstelle'),
			'not_exists'	=> $oGui->t('Buchungssätze mit fehlenden Angaben'),
		);
		
		return $aOptions;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getAccountExistsOptions(Ext_Gui2 $oGui) {

		$aOptions = array(
			'exists'		=> $oGui->t('Buchungssätze mit Kontonummer'),
			'not_exists'	=> $oGui->t('Buchungssätze mit fehlenden Angaben'),
		);
		
		return $aOptions;
	}

	/**
	 * @return array|null
	 */
	public static function getPositionKeysFilterOptions() {
		
		$sCacheKey = 'accounting_posting_keys_filter_options';
		
		$aOptions = WDCache::get($sCacheKey);
		
		if($aOptions === null) {

			$sSql = "
				SELECT
					*
				FROM
					(
						SELECT
							`posting_key_positive` AS `key`,
							`posting_key_positive` AS `value`
						FROM
							`ts_accounting_companies`
					UNION
						SELECT
							`posting_key_negative` AS `key`,
							`posting_key_negative` AS `value`
						FROM
							`ts_accounting_companies`
					) `union`
				WHERE
					`union`.`value` != ''
				GROUP BY
					`union`.`value`
				ORDER BY
					`union`.`value`
			";

			$aOptions = DB::getQueryPairs($sSql);
			$aOptions = Ext_Thebing_Util::addEmptyItem($aOptions);
			
			// Eine Stunde cachen
			WDCache::set($sCacheKey, (60*60), $aOptions);
			
		}

		return $aOptions;
	}

	/**
	 * @return array
	 */
	public static function getCurrencyFilterOptions() {

		$oStack	= new Ext_TS_Accounting_BookingStack();
		$aCurrencyList = $oStack->getCurrencyListFromEntries();

		return $aCurrencyList;
	}

	/**
	 * @return \TsAccounting\Entity\Company[]
	 */
	public static function getCompanyFilterOptions() {
		$aCompanies = Ext_Thebing_System::getAccountingCompanies(true);
		return $aCompanies;
	}

	/**
	 * @return array
	 */
	public static function getSchoolFilterOptions() {
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		return $aSchools;
	}

	/**
	 * @return array
	 */
	public static function getInboxFilterOptions() {
		$aInboxList = Ext_Thebing_System::getInboxList('use_id');
		return $aInboxList;
	}

	public static function getSourceFilterOptions($oGui) {
		$aOptions = [
			'document' => $oGui->t('Rechnung'),
			'payment' => $oGui->t('Zahlung'),
		];

		return $aOptions;
	}

	/**
	 * @return mixed
	 */
	public static function getCompanyFilterFirstValue() {

		$aInboxList = self::getCompanyFilterOptions();
		reset($aInboxList);
		$mValue = key($aInboxList);

		return $mValue;
	}

	/**
	 * @return mixed
	 */
	public static function getSchoolFilterFirstValue() {

		$aList = Ext_Thebing_Client::getStaticSchoolListByAccess(false);
		reset($aList);
		$mValue = key($aList);

		return $mValue;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getSchoolFilterEntries(Ext_Gui2 $oGui) {

        if(php_sapi_name() === 'cli') {
            // Bei der automatischen Weiterverarbeitung werden die Daten anhand der Gui geladen, da hier aber kein
            // Access-Objekt zur Verfügung steht können auch keine Schulen geladen werden
	        return [];
        }

		$oClient = Ext_Thebing_Client::getFirstClient();
		$aSchools = $oClient->getStaticSchoolListByAccess(false);
		$aSchools = Ext_Gui2_Util::addLabelItem($aSchools, $oGui->t('Schulen'), 'xNullx');

		return $aSchools;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	static public function getClearDialog(Ext_Gui2 $oGui){

		$oDialog = $oGui->createDialog($oGui->t('Buchungsstapel bereinigen'), $oGui->t('Buchungsstapel bereinigen'));
		$oTab = $oDialog->createTab($oGui->t('Bereinigung'));

		$oFactory = new Ext_Gui2_Factory('ts_booking_stack_clear_dialog');
		$oGuiChild = $oFactory->createGui('booking_stack_clear_dialog');
		
		$oTab->setElement($oGuiChild);
		$oDialog->setElement($oTab);

		return $oDialog;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	static public function getInterfaceExportDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Weiterverarbeitung'), $oGui->t('Weiterverarbeitung'));
		$oInfo = $oDialog->createNotification($oGui->t('Bitte beachten Sie folgendes:'), $oGui->t('Die selectierten Buchungssätze werden für die Weiterverarbeitung in einem externen Buchhaltungsprogramm vorbereitet. <br/> Bitte beachten Sie, dass dieser Schritt nur einmalig möglich ist und dass die Exportierung der Buchungssätze nicht umkehrbar ist!'), 'info');
		$oDialog->setElement($oInfo);
		$oDialog->save_button = false;

		$aButton = array(
			'label'	=> $oGui->t('Exportieren'),
			'task'	=> 'saveDialog',
			'id'	=> 'interface_export'
		);
		$oDialog->aButtons = array($aButton);
		$oDialog->height = 330;

		return $oDialog;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	static public function getInterfaceExportHistoryDialog(Ext_Gui2 $oGui){
		$oDialog = $oGui->createDialog($oGui->t('Historie'), $oGui->t('Historie'));
		$oDialog->save_button = false;
		$oFactory	= new Ext_Gui2_Factory('ts_booking_stack_history');
		$oGui       = $oFactory->createGui();
		$oDialog->setElement($oGui);
		return $oDialog;
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

		if($sAction == 'clear_stack') {

			$aErrors = array();
			$aClearedStacks = array();

			$aTransfer = array();
			$aTransfer['data']['id'] = 'ID_0';

			$aSelectedEntries = (array)$_SESSION['selected_booking_stack_entries'];
			if(empty($aSelectedEntries)){
				$aErrors[] = $this->t('Bitte wählen Sie mindestens eine Position aus!');
			}
			else {
				foreach($aSelectedEntries as $iDocumentId) {
					$aEntries = (array)$_SESSION['clear_booking_stack_entries'][$iDocumentId];
					if(!empty($aEntries)) {
						foreach($aEntries as $iStack){
							// Cache um Bereiniung von doppelten Stack-Einträge zu verhindern
							// Bsp. bei Parent- und Child-Dokument
							if(in_array($iStack, $aClearedStacks)) {
								continue;
							}
							$aClearedStacks[] = $iStack;
						}
						// Einträge aus der Session nehmen
						unset($_SESSION['clear_booking_stack_entries'][$iDocumentId]);
					}
				}
				if(!empty($aClearedStacks)) {
					$oStack = Ext_TS_Accounting_BookingStack::getInstance($aClearedStacks[0]);
					try {
						\TsAccounting\Service\BookingStackService::saveHistory($aClearedStacks,  $oStack->getCompany(), 'clear');
					}
					catch(InvalidArgumentException $ex) {
						$aErrors[] = $this->t($ex->getMessage());
					}
					// Muss leider in einer zweiten foreach-schleife durchlaufen werden, da
					// die saveHistory() mit Einträgen mit active=1 funktioniert
					foreach ($aClearedStacks as $iStack) {
						$oStack = Ext_TS_Accounting_BookingStack::getInstance($iStack);
						$oStack->delete();
					}
				}
				unset($_SESSION['clear_booking_stack_entries']);
				unset($_SESSION['selected_booking_stack_entries']);
			}

			if(!empty($aErrors)) {
				$aErrors = Ext_TC_Util::addEmptyItem($aErrors, $this->t('Es ist ein Fehler aufgetreten!'), -1);
				$aErrors = array_values($aErrors);
				$aTransfer['action'] = 'saveDialogCallback';
			} else {
				$aTransfer['action'] = 'closeDialogAndReloadTable';
			}
			$aTransfer['error'] = $aErrors;

			return $aTransfer;
		}
		else if($sAction == 'interface_export') {

			global $_VARS;

			$aErrors = array();
			$bError = false;

			$aTransfer  = array();
			$aTransfer['action'] = 'saveDialogCallback';

			$oStack = null;
			if(!empty($aSelectedIds)) {
				$oStack = Ext_TS_Accounting_BookingStack::getInstance($aSelectedIds[0]);
			}

			if($_VARS['ignore_errors'] != 1) {

				$aErrors[0]  = array(
					'message' => $this->t('Sind Sie sich sicher, das die Daten final exportiert werden sollen? Dieser Schritt ist nicht umkehrbar!'),
					'type' => 'hint'
				);
				//Checkbox Fehler ignorieren einblenden
				$aTransfer['error'] = $aErrors;
				$aTransfer['data']['show_skip_errors_checkbox'] = 1;
				
			} elseif(
				$oStack && 
				$oStack->exist()
			) {

				$oCompany = $oStack->getCompany();

				if(!$oCompany) {
					
					$aErrors[0]  = array(
						'message' => $this->t('Es konnte keine Firma ermittelt werden!'),
						'type' => 'error'
					);
					$bError = true;
					
				} else {
					
					try {
						$sFile = '/'.\TsAccounting\Service\BookingStackService::saveHistory($aSelectedIds, $oCompany);
						$sUrl = '/storage/download'.$sFile;

						$aTransfer['action'] = 'openUrlAndCloseDialog';
						$aTransfer['load_table'] = 1;
						$aTransfer['url'] = $sUrl;
						$aTransfer['data']['id'] = 'ID_' . implode('_', $aSelectedIds);

					} catch(InvalidArgumentException $ex) {
						$aErrors[0]  = array(
							'message' => $this->t($ex->getMessage()),
							'type' => 'error'
						);
						$bError = true;
					}
				}
				
			} else {
				
				$aErrors[0]  = array(
					'message' => $this->t('Keine Einträge gefunden!'),
					'type' => 'error'
				);
				$bError = true;
			}

			if($bError) {
				$aErrors = Ext_TC_Util::addEmptyItem($aErrors, $this->t('Es ist ein Fehler aufgetreten!'), -1);
				$aErrors = array_values($aErrors);
			}

			$aTransfer['error'] = $aErrors;

			return $aTransfer;
		} else {
			
			return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
	}

	/**
	 * @param type $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	public function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false) {

		if($sIconAction == 'interface_export') {
			$iCompleteStack = 0;
			$iMissingStack  = 0;
			foreach($aSelectedIds as $iStack){
				$oStack = Ext_TS_Accounting_BookingStack::getInstance($iStack);
				if(!$oStack->hasMissingInformations()){
					$iCompleteStack++;
				} else {
					$iMissingStack++;
				}
			}
			$oDialogData = self::getInterfaceExportDialog($this->_oGui);
			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->setElement('<br/><br/>');
			$oDiv->setElement($this->t('Anzahl der kompletten Buchungsätze:').' '.$iCompleteStack);
			$oDiv->setElement('<br/><br/>');
			$oDiv->setElement($this->t('Anzahl der Buchungsätze mit fehlenden Angaben:').' '.$iMissingStack);
			$oDialogData->setElement($oDiv);
		}

		return parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
	}

	/**
	 * @param bool $bWDSearch
	 */
	protected function _setFilterDataByRef($bWDSearch = false){

		// Bei dem Export-Dokument dürfen die Filter nicht in _getTableData() eingebaut werden,
		// da ansonsten zu wenig Einträge erscheinen
		if($this->bExport !== true) {
			parent::_setFilterDataByRef($bWDSearch);
		}

	}

	/**
	 * @inheritdoc
	 */
	public function prepareColumnListByRef(&$aColumnList) {
		global $_VARS;

		// Beim Export (Icon) keine Manipulierung durchführen, da das der Export selber regelt (Firmeneinstellung)
		if($this->bExport === true) {
			return;
		}

		parent::prepareColumnListByRef($aColumnList);

		// Spalten je nach ausgewähltem Filter
		// $this->_aFilter funktioniert leider nicht, da das zu spät gesetzt wird
		if(!empty($_VARS['filter']['company_id'])) {
			$oCompany = \TsAccounting\Entity\Company::getInstance($_VARS['filter']['company_id']);
		} else {
			$aCompanies = Ext_Thebing_System::getAccountingCompanies();
			if(!empty($aCompanies)) {
				$oCompany = reset($aCompanies);
			}
		}

		// Nichts machen, wenn es keine Firma gibt
		// Das Flex-Menü muss in den Fall rein, sonst fehlen Spalten!
		if(
			!isset($oCompany) ||
			$_VARS['task'] === 'loadFlexmenu'
		) {
			return;
		}

		foreach($aColumnList as $iColumnKey => $oColumn) {

			// Sollkonto nur bei doppelter Buchhaltung
			if($oColumn->db_column === 'account_number_expense') {
				if($oCompany->accounting_type !== 'double') {
					unset($aColumnList[$iColumnKey]);
				}
			}

			// Kostencenter und Buchungsschlüssel nur bei Datev
//			if(
//				$oColumn->db_column === 'cost_center' ||
//				$oColumn->db_column === 'posting_key'
//			) {
//				if($oCompany->interface !== 'datev') {
//					unset($aColumnList[$iColumnKey]);
//				}
//			}

			// QB Nummer bei Quickbooks
			if($oColumn->db_column === 'qb_number') {
				if(strpos($oCompany->interface, 'quickbooks') === false) {
					unset($aColumnList[$iColumnKey]);
				}
			}

			// Nur bei Single-Line-Export (vorher Sage Basic)
			// Warum sollten diese Spalten dann nicht angezeigt werden?
//			if(
//				$oColumn->db_column === 'customer_number' ||
//				$oColumn->db_column === 'document_type'
//			) {
//				if($oCompany->interface !== 'sage_basic') {
//					unset($aColumnList[$iColumnKey]);
//				}
//			}

		}

		// Neu durchnummerieren für json_encode()
		$aColumnList = array_values($aColumnList);

	}
	
	public function manipulateTableDataResultsByRef(&$aResult) {

		parent::manipulateTableDataResultsByRef($aResult);
		
		System::wd()->executeHook('ts_accounting_bookingstack_manipulate', $aResult);
		
	}
	
}
