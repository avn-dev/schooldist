<?php

class Ext_TS_Pickup_Gui2_Data extends Ext_Thebing_Inquiry_Gui2 {

	/**
	 * @var string
	 */
	protected static $_sIdTag = 'PROVIDER_';

	/**
	 * @param array $_VARS
	 * @return void
	 */
	public function switchAjaxRequest($_VARS) {

		if(
			$_VARS['task'] == 'request' &&
			(
				$_VARS['action'] == 'confirmPickupRequest' ||
				$_VARS['action'] == 'deletePickupRequestConfirmation' ||
				$_VARS['action'] == 'confirmPickupProvider' ||
				$_VARS['action'] == 'deletePickupProviderConfirmation' ||
				$_VARS['action'] == 'confirmPickupAccommodationProvider' ||
				$_VARS['action'] == 'deletePickupAccommodationProviderConfirmation' ||
				$_VARS['action'] == 'confirmPickupCustomer' ||
				$_VARS['action'] == 'deletePickupCustomerConfirmation'
			)
		) {

			$aSelectedIds = array();
			foreach((array)$_VARS['id'] as $iId) {
				$aSelectedIds[] = $this->_oGui->decodeId($iId, 'inquiry_transfer_id');
			}

			foreach($aSelectedIds as $iJourneyTransferId) {
				$oJourneyTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iJourneyTransferId);
				$this->_switchConfirmationStatus($oJourneyTransfer, $_VARS['action']);
			}

			$aTransfer['action'] = 'loadTable';

			echo json_encode($aTransfer);

		} elseif(
			$_VARS['task'] == 'openDialog' && (
				$_VARS['action'] == 'communication' ||
				$_VARS['action'] == 'transfer_provider'
			)
		) {

			$aSelectedIds = array();
			foreach((array)$_VARS['id'] as $iId) {
				$aSelectedIds[] = $this->_oGui->decodeId($iId, 'inquiry_transfer_id');
			}
			$_VARS['id'] = $aSelectedIds;

			parent::switchAjaxRequest($_VARS);

		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	/**
	 * @param array $aIds
	 * @param array $aRowData
	 * @param $oIcon
	 * @return string
	 */
//	public function getRowIconInfoText(&$aIds, &$aRowData, &$oIcon) {
//
//		$aSelectedIds = array();
//		foreach($aRowData as $aRow) {
//			$aSelectedIds[] = $aRow['transfer_inquiry_id'];
//		}
//		$aIds = $aSelectedIds;
//
//		return parent::getRowIconInfoText($aIds, $aRowData, $oIcon);
//	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer
	 * @param string $sAction
	 */
	protected function _switchConfirmationStatus(Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer, $sAction) {
		
		switch($sAction) {
			case 'confirmPickupRequest':
				$aProviderRequests = $oJourneyTransfer->getProviderRequests();
				if(empty($aProviderRequests)) {
					$oProviderRequest = $oJourneyTransfer->getNewProviderRequest();
					$oProviderRequest->provider_type = 'provider';
					$oProviderRequest->save();
				}
				$bSave = false;
				break;
			case 'deletePickupRequestConfirmation':		
				$oJourneyTransfer->deleteProviderRequests();
				$bSave = false;
				break;
			case 'confirmPickupProvider':
				$oJourneyTransfer->provider_confirmed = time();
				$bSave = true;
				break;
			case 'deletePickupProviderConfirmation':
				$oJourneyTransfer->provider_confirmed = 0;
				$bSave = true;
				break;
			case 'confirmPickupAccommodationProvider':
				$oJourneyTransfer->accommodation_confirmed = time();
				$bSave = true;
				break;
			case 'deletePickupAccommodationProviderConfirmation':
				$oJourneyTransfer->accommodation_confirmed = 0;
				$bSave = true;
				break;
			case 'confirmPickupCustomer':
				$oJourneyTransfer->customer_agency_confirmed = time();
				$bSave = true;
				break;
			case 'deletePickupCustomerConfirmation':
				$oJourneyTransfer->customer_agency_confirmed = 0;
				$bSave = true;
				break;
			default:
				$bSave = false;
		}

		if($bSave) {
			$oJourneyTransfer->save();
		}

		if (null !== $oInquiry = $oJourneyTransfer->getJourney()?->getInquiry()) {
			Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);
		}
		
	}

	/**
	 * @param Ext_Thebing_Gui2 $oGui
	 * @param array $aSelectedIds
	 * @return mixed
	 */
	public static function getDialog($oGui, &$aSelectedIds) {

		$oDialog = $oGui->createDialog(
			$oGui->t('Anbieter zuweisen'),
			$oGui->t('Anbieter zuweisen'),
			$oGui->t('Anbieter zuweisen')
		);
		$oDialog->width = 950;
		$oDialog->height = 550;
		$oDialog->sDialogIDTag = self::$_sIdTag;

		$oProviderTab = $oDialog->createTab($oGui->t('Anbieter/Fahrer'));
		$oProviderTab->setElement(self::getProviderDialog($oDialog, $oGui, $aSelectedIds));

		$oDialog->setElement($oProviderTab);

		return $oDialog;
	}

	/**
	 * Provider Tab
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Thebing_Gui2 $oGui
	 * @param array $aSelectedIds
	 * @return Ext_Gui2_Html_Div
	 * @throws Exception
	 */
	public static function getProviderDialog($oDialog, &$oGui, $aSelectedIds) {

		$aProvider = Ext_TS_Inquiry_Journey_Transfer::getAllProvider($aSelectedIds);

		$aProviderEmpty = array();
		$aProviderEmpty['name'] = $oGui->t('Kein Provider');
		$aProviderEmpty['driver'] = array();

		$aProvider = array($aProviderEmpty) + $aProvider;

		// prüfen, ob die Provider für den Transfertag gültig ist
		$aTemp = $aProvider;
		foreach((array)$aSelectedIds as $iKey => $iInquiry_trasfer_id) {
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiry_trasfer_id);
			foreach($aTemp as $iId => $sProvider) {
				$oProvider = Ext_Thebing_Pickup_Company::getInstance($iId);
				if($oProvider->isValid($oTransfer->transfer_date) === false) {
					unset($aProvider[$iId]);
				}
			}
		}

		// Daten aufbereiten
		$aProviderSelect = array();
		foreach((array)$aProvider as $iProviderId => $aProvider){
			$aProviderSelect[$iProviderId] = $aProvider['name'];
		}

		$oDiv = new Ext_Gui2_Html_Div();

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = 'hidden';
		$oHidden->name = 'save[transfer][ids]';
		$oHidden->value = implode('_', $aSelectedIds);
		$oDiv->setElement($oHidden);

		## START Anreise
		$oDivTransferContainer = new Ext_Gui2_Html_Div();

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Transfer'));
		$oDivTransferContainer->setElement($oH3);

		$oDivTransferProviderContainer = new Ext_Gui2_Html_Div();
		$oDivTransferProviderContainer->class = 'provider_container';

		$oRow = $oDialog->createRow($oGui->t('Anbieter'), 'select', array('db_column' => 'transfer][provider', 'select_options' => $aProviderSelect));
		$oDivTransferProviderContainer->setElement($oRow);

		$oRow = $oDialog->createRow($oGui->t('Fahrer'), 'select', array('db_column' => 'transfer][driver', 'select_options' => array()));
		$oDivTransferProviderContainer->setElement($oRow);

		$oDivTransferContainer->setElement($oDivTransferProviderContainer);
		## ENDE

		$oDiv->setElement($oDivTransferContainer);

		return $oDiv;
	}

	/**
	 * Funktion speichert das zuweisen der provider an einen Transfer über die extra Providerliste
	 *
	 * @param array $aSelectedIds
	 * @param Ext_Thebing_Gui2 $oGui
	 * @return array
	 */
	public static function saveProviderAssign($aSelectedIds, &$oGui) {

		foreach($aSelectedIds as $iTransferRequestId) {
			if($iTransferRequestId > 0) {
				$oTransferRequest = Ext_Thebing_Inquiry_Provider_Request::getInstance($iTransferRequestId);
				$oTransfer = $oTransferRequest->getTransfer();
				$oTransfer->provider_id	= $oTransferRequest->provider_id;
				$oTransfer->provider_type = $oTransferRequest->provider_type;
				$oTransfer->driver_id = 0;
				$oTransfer->provider_updated = time();
				$oTransfer->provider_confirmed = 0;
				$oTransfer->save();
			}
		}

		$aTransfer = array();
		$aTransfer['action'] = 'loadTable';
		$aTransfer['dialog_id_tag'] = 'PROVIDERASSIGN_';
		$aTransfer['error'] = array();

		return $aTransfer;
	}

	/**
	 * Funktion speichert den Provider/Driver Dialog
	 *
	 * @param array $aSaveData
	 * @param array $aSelectedIds
	 * @param Ext_Gui2 $oGui
	 * @return array
	 * @throws Exception
	 */
	public static function saveProviderDialog($aSaveData, $aSelectedIds, &$oGui) {

		$aErrors = array();
		$iErrorCount = 0;
		$aTransfers = array();

		$oData = $oGui->getDataObject();

		foreach((array)$aSelectedIds as $iInquiry_trasfer_id) {

			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiry_trasfer_id);

			$oTransfer->provider_id	= abs($aSaveData['provider']);

			if($aSaveData['provider'] > 0) {
				$oTransfer->provider_type = 'provider';
			}elseif($aSaveData['provider'] < 0) {
				$oTransfer->provider_type = 'accommodation';
			}

			$oTransfer->driver_id = (int)$aSaveData['driver'];
			if($aSaveData['provider'] != 0) {
				$oDate = new WDDate();
				$oTransfer->provider_updated = $oDate->get(WDDate::DB_TIMESTAMP);
			} else {
				$oTransfer->provider_updated = '';
			}

			$mValidate = $oTransfer->validate();

			$aTransfers[] = $oTransfer;

			if($mValidate !== true) {
				foreach((array)$mValidate as $sColumn => $sMessage) {
					$aErrors[$iErrorCount]['message'] = $oData->_getErrorMessage($sMessage, $sColumn);
					$aErrors[$iErrorCount]['input'] = array();
					$aErrors[$iErrorCount]['input']['dbcolumn']	= 'transfer';
					$aErrors[$iErrorCount]['input']['dbalias'] = $sColumn; // vertauscht hier
					$iErrorCount++;
				}
			}

		}

		if(count($aErrors) > 0) {
			array_unshift($aErrors, array('message' => $oData->t('Fehler')));
		} else {
			foreach((array)$aTransfers as $oTransfer) {
				$oTransfer->save();

				if (null !== $oInquiry = $oTransfer->getJourney()?->getInquiry()) {
					Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);
				}
			}
		}

		$aTransfer = array();
		$aTransfer['action'] = 'saveDialogCallback';
		$aTransfer['dialog_id_tag'] = 'PROVIDER_';
		$aTransfer['dialog_id'] = 'PROVIDER_' . implode('_', (array) $aSelectedIds);
		$aTransfer['error'] = $aErrors;
		$aTransfer['success_message'] = $oGui->t('Transfer wurde gespeichert.');

		$aTransferData = $oGui->getDataObject()->prepareOpenDialog('transfer_provider', $aSelectedIds);
		$aTransfer['data'] = $aTransferData;

		return $aTransfer;
	}

	/**
	 * Inner Gui um Provider einem Transfer zuweisen zu können
	 *
	 * @param Ext_Gui2 $oGui
	 * @return mixed
	 * @throws Exception
	 */
	public static function getProviderAssignDialog($oGui) {

		$oDate = new WDDate();
		$oDate->sub((abs($oDate->get(WDDate::DAY_OF_WEEK)-6) * -1 + 7), WDDate::DAY);
		$iFilterStart = (int)$oDate->get(WDDate::TIMESTAMP);
		$oDate->add(13, WDDate::DAY);
		$iFilterEnd = (int)$oDate->get(WDDate::TIMESTAMP);

		$oInnerGui = $oGui->createChildGui(md5('thebing_provider_assign'), 'Ext_Thebing_Gui2_Data');
		$oInnerGui->query_id_column	= 'id';
		//$oInnerGui->query_id_alias = 'kitpr';
		$oInnerGui->load_admin_header = false;
		$oInnerGui->multiple_selection = true;
		$oInnerGui->calendar_format = new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Transfer_Provider();
		$oInnerGui->class_js = 'StudentlistGui';

		$oInnerGui->setWDBasic('Ext_Thebing_Inquiry_Provider_Request');
		$oInnerGui->setTableData('limit', 30);

		// Listen Optionen
		$oInnerGui->gui_title = $oGui->t('Anbieter zuweisen');

		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelgroup = $oBar->createLabelGroup($oInnerGui->t('Filter'));
		$oBar ->setElement($oLabelgroup);

		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column = array('created'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias = array('');
		$oFilter->label	= $oInnerGui->t('Erstellt')." ".strtolower($oInnerGui->t('Von'));
		$oFilter->default_from = Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until	= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->search_type = 'between';
		$oFilter->label_between	= $oInnerGui->t('bis');
		$oBar ->setElement($oFilter);

		// Filter nach Anfragen
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'transfer_provider_assign';
		$oFilter->label = $oInnerGui->t('Anbieter');

		$oFilter->value = '';

		// Alle Provider/Unterkünfte für die es Anfrage gibt
		$oFilter->select_options = self::getProviderAssignAllFilter();
		$oFilter->filter_query = self::getProviderAssignAllFilter(true);
		$oBar ->setElement($oFilter);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 1 #

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelgroup = $oBar->createLabelGroup($oInnerGui->t('Aktionen'));
		$oBar ->setElement($oLabelgroup);

		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('provider_confirm'), 'request', $oInnerGui->t('Zuweisen'));
		$oIcon->action = 'transfer_provider_assign';
		//	$oIcon->request_data = '&action=transfer_provider_assign';
		$oIcon->active = 1;
		$oIcon->multipleId  = 1;
		$oIcon->label = $oInnerGui->t('Zuweisen');
		$oBar ->setElement($oIcon);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';

		$oPagination = $oBar->createPagination();
		$oBar ->setElement($oPagination);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'created';
		$oColumn->db_alias = '';
		$oColumn->title = $oInnerGui->t('Anfrage vom');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_time');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Date_Time();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'transfer_id';
		$oColumn->db_alias = '';
		$oColumn->title = $oInnerGui->t('Transfer');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_user');
		$oColumn->width_resize = true;
		$oColumn->format	= new Ext_Thebing_Gui2_Format_Transfer_Assign_Info();
		$oInnerGui->setColumn($oColumn);

		// Angefragter Provider
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'provider_id';
		$oColumn->db_alias = '';
		$oColumn->title = $oInnerGui->t('Provider angefragt');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format	= new Ext_Thebing_Gui2_Format_Transfer_ProviderName();
		$oInnerGui->setColumn($oColumn);

		// Bearbeiter, Erstellt & Verändert
		$oInnerGui->addDefaultColumns();

		$oDialog = $oInnerGui->createDialog($oInnerGui->t('Anbieter zuweisen'));
		$oDialog->sDialogIDTag = 'PROVIDERASSIGN_';

		$oDialog->setElement($oInnerGui);

		$oDialog->readonly = true;
		$oDialog->width = 950;
		$oDialog->height = 650;
		$oDialog->save_button = false;

		return $oDialog;
	}

	/**
	 * @param bool $bReturnQueryParty
	 * @param bool $bEmptyItem
	 * @return array
	 */
	public static function getProviderAssignAllFilter($bReturnQueryParty = false, $bEmptyItem = true) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		// Alle TransferProvider der Schule
		$aTransferProvider	= $oSchool->getTransferProvider(true);
		// Alle Familien die auch als Provider fungieren können der Schule
		$aTransferAcc = $oSchool->getTransferLocations(true);

		$aBack = array();

		// Leerer Eintrag
		if($bEmptyItem) {
			$aBack['empty'] = '';
		}

		foreach((array)$aTransferProvider as $iKey => $sProvider) {
			$aBack[$iKey] = $sProvider;
		}

		foreach((array)$aTransferAcc as $iKey => $sProvider) {
			$aBack[$iKey * (-1)] = $sProvider;
		}

		if($bReturnQueryParty) {
			foreach($aBack as $iKey => $sName) {
				if($iKey == 'empty'){
					$aBack[$iKey] = " `provider_id` > 0 ";
				} elseif($iKey > 0) {
					// provider
					$aBack[$iKey] = " `provider_type` = 'provider' AND `provider_id` = " . $iKey;
				} elseif($iKey < 0) {
					// unterkunft
					$aBack[$iKey] = " `provider_type` = 'accommodation' AND `provider_id` = " . abs($iKey);
				}
			}
			return $aBack;
		}

		return $aBack;
	}

	/**
	 * Filteroptionen für die Provider assign Liste dynamisch
	 * Wird zZ noch NICHT gebraucht :)
	 *
	 * @param bool $bReturnQueryParty
	 * @param bool $bEmptyItem
	 * @return mixed
	 */
	public static function getProviderAssignFilter($bReturnQueryParty = false, $bEmptyItem = true){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$sSql = "
			SELECT
				`kitpr`.*
			FROM
				`kolumbus_inquiries_transfers_provider_request` `kitpr` INNER JOIN
				`ts_inquiries_journeys_transfers` `kit` ON
					`kit`.`id` = `kitpr`.`transfer_id` AND
					`kit`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kit`.`journey_id` AND
					`ts_i_j`.`active` = 1 AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`school_id` = :school_id INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki`.`active` = 1
			WHERE
				`kitpr`.`active` = 1
		";

		$aResult = DB::getPreparedQueryData($sSql, array(
			'school_id' => (int)$oSchool->id
		));

		// Leerer Eintrag
		if($bEmptyItem) {
			$aResult[-1] = '';
		}

		ksort($aResult);

		$aBack = array();
		$oFormat = new Ext_Thebing_Gui2_Format_Transfer_ProviderName();

		foreach((array)$aResult as $aData) {
			$sFormat = $oFormat->format(true, $aData, $aData);
			if($aData['provider_type'] == 'provider') {
				$aBack[$aData['provider_id']] = $sFormat;
			} elseif($aData['provider_type'] == 'accommodation') {
				$aBack[($aData['provider_id']) * (-1)] = $sFormat;
			} else {
				// leer
				$aBack['empty'] = '';
			}
		}

		if($bReturnQueryParty) {
			foreach($aBack as $iKey => $sName) {
				if($iKey == 'empty') {
					$aBack[$iKey] = " `provider_id` = 0 ";
				} elseif($iKey > 0) {
					// provider
					$aBack[$iKey] = " `provider_type` = 'provider' AND `provider_id` = " . $iKey;
				} elseif($iKey < 0) {
					// unterkunft
					$aBack[$iKey] = " `provider_type` = 'accommodation' AND `provider_id` = " . abs($iKey);
				}
			}
			return $aBack;
		}

		return $aBack;
	}

}
