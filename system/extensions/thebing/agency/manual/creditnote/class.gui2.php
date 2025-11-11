<?php

class Ext_Thebing_Agency_Manual_Creditnote_Gui2 extends Ext_Thebing_Gui2_Data {

	/**
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @return array
	 * @throws Exception
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$aElements = $oDialogData->aElements;

		$iSelectedId = $this->_getFirstSelectedId();

		if($iSelectedId <= 0) {

			$aOptions = [
				'db_column' => 'numberrange_id',
				'db_alias' => 'kamc',
				'selection' => new Ext_Thebing_Gui2_Selection_School_Numberrange('manual_creditnote'),
				'dependency' => [
					[
						'db_column' => 'school_id',
						'db_alias' => 'kamc',
					],
				],
			];

			$oNumberRangeRow = Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangeRow($this->_oGui, $oDialogData, $aOptions, 'manual_creditnote');

			if($oNumberRangeRow) {
				$oDialogData->setElement($oNumberRangeRow);
				// Move last element up in dialog position for optics
				array_splice($oDialogData->aElements, -3, 0, array_splice($oDialogData->aElements, -1, 1));
			}

		}

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
		$oDialogData->aElements = $aElements;
		$oSchool = Ext_Thebing_Client::getFirstSchool();

		$iTemplateId = 0;
		if(count($aSelectedIds) > 0) {

			$iSelectedId = (int)reset($aSelectedIds);

			$oCN = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iSelectedId);

			$oVersion = $oCN->getLastVersion();

			if(is_object($oVersion)){
				$iTemplateId = $oVersion->template_id;
			}

			$sLanguage = $oCN->language;

		} else {

			$sLanguage = $oSchool->getLanguage();

		}

		// Felder  mitschicken welche gebraucht werden pro Template
//		$aPdfTemplates = Ext_Thebing_Pdf_Template_Search::s('manual_creditnotes', $sLanguage, $oSchool->id);

		// TODO Total bescheuert, wie das hier läuft, da das auch vorher nur beim Edit angezeigt werden konnte
		// Das Template wird nämlich direkt im Dialog ausgewählt
		$aPdfTemplates = [Ext_Thebing_Pdf_Template::getInstance($iTemplateId)];

		$aFields = array();
		foreach((array)$aPdfTemplates as $oTemplate) {

			$oTemplateType = $oTemplate->getJoinedObject('template_type');

			if($oTemplateType->element_date) {
				$aFields[$oTemplate->id]['date'] = 1;
			}
			if($oTemplateType->element_address) {
				$aFields[$oTemplate->id]['address'] = 1;
			}
			if($oTemplateType->element_subject) {
				$aFields[$oTemplate->id]['subject'] = 1;
			}
			if($oTemplateType->element_text1) {
				$aFields[$oTemplate->id]['intro'] = 1;
			}
			if($oTemplateType->element_text2) {
				$aFields[$oTemplate->id]['outro'] = 1;
			}

		}

		$aData['fields'] = $aFields;
		$aData['template_id'] = $iTemplateId;

		return $aData;

	}

	/**
	 * @return string
	 */
	public static function getDescriptionPart() {
		return 'Thebing » Accounting » Manual Creditnotes';
	}

	/**
	 * @param miex[] $_VARS
	 * @throws Exception
	 */
	public function switchAjaxRequest($_VARS) {

		if(
			$_VARS['action'] == 'creditnote_open' &&
			$_VARS['task'] == 'request'
		) {

			$iSelectedId = (int)$_VARS['id'][0];

			$oVersion = Ext_Thebing_Accounting_Manual_Version::getInstance($iSelectedId);

			$aTransfer['action'] = 'openUrl';

			if(is_file($oVersion->getPath(true))) {
				$aTransfer['url'] = '/storage/download'.$oVersion->getPath(false);
			} else {
				$aTransfer['error'] = [
					$this->_oGui->t('Das Dokument konnte nicht gefunden werden! Bitte speichern Sie erneut.')
				];
			}

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
	 * @return array
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {

		if($sAction == 'storno') {

			$aError = array();

			foreach((array)$aSelectedIds as $iCN) {

				$oCN = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iCN);

				if($oCN->school_id > 0) {

					$oCN->storno($aData['comment'], $aData['reason_id'], $aData['note']);

				} else {

					if(empty($aError)) {
						$aError[] = $this->_oGui->t('Fehler beim Stornieren');
					}

					$oDocument = $oCN->getDocument();
					$aError[] = sprintf($this->_oGui->t('Die manuelle Gutschrift ("%s") ist noch keiner Schule zugewiesen!'), $oDocument->document_number);

				}

			}

			$aTransfer = [];
			$aTransfer['data'] = [];
			$aTransfer['data']['id'] = 'CN_STORNO_'.implode('_', $aSelectedIds);
			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['dialog_id_tag'] = 'CN_STORNO_';
			$aTransfer['error'] = $aError;

			if(empty($aError)) {
				$aTransfer['data']['options']['close_after_save'] = true;
				$aTransfer['success_message'] = $this->_oGui->t('erfolgreich storniert');
			}

		} else {

			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		}

		return $aTransfer;

	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $bSave
	 * @param string $sAction
	 * @return array
	 * @throws Exception
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$aPdfData = [];

		// PDF Daten beim editieren
		if(isset($aSaveData['txt_intro']['kmv'])) {
			$aPdfData['txt_intro'] = $aSaveData['txt_intro']['kmv'];
			unset($aSaveData['txt_intro']);
		}
		if(isset($aSaveData['txt_outro']['kmv'])) {
			$aPdfData['txt_outro'] = $aSaveData['txt_outro']['kmv'];
			unset($aSaveData['txt_outro']);
		}
		if(isset($aSaveData['txt_subject']['kmv'])) {
			$aPdfData['txt_subject'] = $aSaveData['txt_subject']['kmv'];
			unset($aSaveData['txt_subject']);
		}
		if(isset($aSaveData['txt_address']['kmv'])) {
			$aPdfData['txt_address'] = $aSaveData['txt_address']['kmv'];
			unset($aSaveData['txt_address']);
		}
		if(isset($aSaveData['date']['kmv'])) {
			$oFormat = new Ext_Thebing_Gui2_Format_Date();
			$aPdfData['date'] = $oFormat->convert($aSaveData['date']['kmv']);
			unset($aSaveData['date']);
		}
		if(isset($aSaveData['txt_signature']['kmv'])) {
			$aPdfData['txt_signature'] = $aSaveData['txt_signature']['kmv'];
			unset($aSaveData['txt_signature']);
		}
		if(isset($aSaveData['signature']['kmv'])) {
			$aPdfData['signature'] = $aSaveData['signature']['kmv'];
			unset($aSaveData['signature']);
		}

		if(is_array($sAction)) {
			$sAdditional = $sAction['additional'];
			$sAction = $sAction['action'];
		}

		$aErrors = [];
		$iCount = 1;

		if(
			$bSave &&
			isset($aSaveData['count']['kamc'])
		) {

			$iCount = $aSaveData['count']['kamc'];

			if(
				!is_numeric($iCount) ||
				(int)$iCount > 10
			) {

				$sField	= 'kamc.count';
				$sLabel	= '';

				if($this->aIconData[$sAction]) {
					$sLabel = $this->aIconData[$sAction]['dialog_data']->aLabelCache[$sField];
				}

				if(empty($aErrors)) {
					$aErrors[] = L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N);
				}

				$sMessage = $this->_getErrorMessage('INVALID_INT_POSITIVE', $sField, $sLabel);
				if((int)$iCount > 10) {
					$sMessage = $this->_getErrorMessage('TO_HIGH', $sField, $sLabel);
				}

				$aErrors[] = [
					'input' => [
						'dbcolumn' => 'count',
						'dbalias' => 'kamc',
					],
					'message' => $sMessage
				];

			}

		}

		unset($aSaveData['count']);

		if(
			$bSave &&
			isset($aSaveData['amount']['kamc'])
		) {

			$aSaveData['amount']['kamc'] = Ext_Thebing_Format::convertFloat($aSaveData['amount']['kamc']);

			$oValidate = new WDValidate();
			$oValidate->check = 'FLOAT';
			$oValidate->value = $aSaveData['amount']['kamc'];

			if(!$oValidate->execute()) {

				$sField	= 'kamc.amount';
				$sLabel	= '';

				if($this->aIconData[$sAction]) {
					$sLabel = $this->aIconData[$sAction]['dialog_data']->aLabelCache[$sField];
				}

				if(empty($aErrors)) {
					$aErrors[] = L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N);
				}

				$aErrors[] = [
					'input' => [
						'dbcolumn' => 'amount',
						'dbalias' => 'kamc',
					],
					'message' => $this->_getErrorMessage('INVALID_FLOAT', $sField, $sLabel),
				];

			}

		}

		if($sAction == 'new') {
			$aSelectedIds = [];
		}

		if(count($aSelectedIds) > 0) {
			$iSelectedId = reset($aSelectedIds);
		} else {
			$iSelectedId = 0;
		}

		$aCNIds = [];

		if(empty($aErrors)) {

			$iErrorCount = 0;

			if($iCount <= 0) {
				$iCount = 1;
			}

			$sAdditional = '';

			if(is_array($sAction)) {
				$sAdditional = $sAction['additional'];
				$sAction = $sAction['action'];
			}

			$sIconKey = self::getIconKey($sAction, $sAdditional);
			$oDialog = $this->_getDialog($sIconKey);

			for($i = 1; $i <= $iCount; $i++) {

				$this->oWDBasic = null;
				$oDialog->getDataObject()->resetWDBasicObject();

				$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction);

				if(
					$bSave &&
					empty($aTransfer['error'])
				) {

					/** @var Ext_Thebing_Agency_Manual_Creditnote $oCN */
					$oCN = $this->oWDBasic;
					$aCNIds[] = $oCN->id;

					$oTemplate = $oCN->getJoinedObject('template');
					$sLang = $oCN->language;
					$oSchool = Ext_Thebing_Client::getFirstSchool();

					// Vorbereiten der Daten für PDF Platzhalter müssen je doc. ersetzt werden!!!
					if($iSelectedId <= 0) {
						$aPdfData['txt_intro'] = $oTemplate->getStaticElementValue($sLang, 'text1');
						$aPdfData['txt_outro'] = $oTemplate->getStaticElementValue($sLang, 'text2');
						$aPdfData['txt_subject'] = $oTemplate->getStaticElementValue($sLang, 'subject');
						$aPdfData['txt_address'] = $oTemplate->getStaticElementValue($sLang, 'address');
						$aPdfData['date'] = $oTemplate->getStaticElementValue($sLang, 'date');
						$aPdfData['txt_signature'] = $oTemplate->getOptionValue($sLang, $oSchool->id, 'signatur_text');
						$aPdfData['signature'] = $oTemplate->getOptionValue($sLang, $oSchool->id, 'signatur_img');
					}

					$oFormat = new Ext_Thebing_Gui2_Format_Date();
					$aPdfData['date'] = $oFormat->convert($aPdfData['date']);

					// Sicherheit falls aus irgendeinem Grund das Datum nicht valide sein sollte
					if(!WDDate::isDate($aPdfData['date'], WDDate::DB_DATE)) {
						$aPdfData['date'] = '0000-00-00';
					}

					// Version speichern
					$oVersion = $oCN->newVersion();
					$oVersion->template_id = $oCN->template_id;
					$oVersion->comment = $oCN->comment;
					// PDF Daten
					$oVersion->txt_intro = $aPdfData['txt_intro'];
					$oVersion->txt_outro = $aPdfData['txt_outro'];
					$oVersion->txt_subject = $aPdfData['txt_subject'];
					$oVersion->txt_address = $aPdfData['txt_address'];
					$oVersion->date	= $aPdfData['date'];
					$oVersion->txt_signature = $aPdfData['txt_signature'];
					$oVersion->signature = $aPdfData['signature'];

					$mError = $oVersion->validate();

					if($mError === true) {

						try {
							$oVersion->save();
						} catch (PDF_Exception $e) {

							$iVersion = $oVersion->getVersion();
							$oVersion->active = 0;
							$oVersion->save(true, false);

							if($iVersion == 1) {
								// Komplette CN löschen
								$oCN->delete();
							}

							$aErrors[$iErrorCount]['message'] = L10N::t('PDF konnte nicht erstellt werden! Bitte überprüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')';
							$iErrorCount++;

						}

					} else {
						// Validierungsfehler
						$aErrors[] = L10N::t('Creditnote Version enthält falsche Werte.', 'Thebing » Errors');
					}

				} else {
					$aErrors = $aTransfer['error'];
				}

			}

		}

		$aTransfer = [];
		$aData = $this->prepareOpenDialog($sAction, $aSelectedIds, false, $sAdditional, true);

		if(empty($aErrors)) {
			$aTransfer['action'] = 'closeDialogAndReloadTable';
		} else {
			$aTransfer['action'] = 'saveDialogCallback';
		}

		$aTransfer['data'] = $aData;
		if($iSelectedId > 0) {
			$aTransfer['data']['id'] = 'CN_EDIT_' . $iSelectedId;
		} else {
			$aTransfer['data']['id'] = 'CN_0';
		}

		$aTransfer['data']['hash'] = $this->_oGui->hash;
		$aTransfer['data']['cn_ids'] = $aCNIds;
		$aTransfer['data']['selectedRows'] = $aCNIds;

		$aTransfer['error'] = $aErrors;

		return $aTransfer;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @return array
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		foreach((array)$aSaveData as $iKey => $aData) {
			if($aData['db_column'] == 'count') {
				unset($aSaveData[$iKey]);
			}
		}

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData);
		return $aData;

	}

	/**
	 * @return Ext_Gui2
	 * @throws Exception
	 */
	public function getHistoryGui() {

		$oInnerGui = $this->_oGui->createChildGui(md5('thebing_manualcreditnotes_history'), 'Ext_Thebing_Agency_Manual_Creditnote_Gui2');
		$oInnerGui->query_id_column = 'id';
		$oInnerGui->query_id_alias = 'kmv';
		$oInnerGui->foreign_key	= 'manual_creditnote_id';
		$oInnerGui->foreign_key_alias = 'ts_m_c_to_d';
		$oInnerGui->parent_primary_key = 'id';
		$oInnerGui->load_admin_header = false;
		$oInnerGui->multiple_selection = false;

		$oInnerGui->setWDBasic('Ext_Thebing_Accounting_Manual_Version');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', ['kmv.created'=>'DESC']);

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelGroup = $oBar->createLabelGroup($oInnerGui->t('Details'));
		$oBar->setElement($oLabelGroup);

		$oIcon = $oBar->createIcon(
			Ext_Thebing_Util::getIcon('pdf'),
			'request',
			$oInnerGui->t('Creditnoteversion öffnen')
		);
		$oIcon->label = $oInnerGui->t('Creditnoteversion öffnen');
		$oIcon->action = 'creditnote_open';
		$oIcon->dbl_click_element = 1;
		$oBar->setElement($oIcon);

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
		$oColumn->db_column = 'comment';
		$oColumn->db_alias = '';
		$oColumn->title = $oInnerGui->t('Kommentar');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oInnerGui->setColumn($oColumn);

		$oDefaultColumn = $oInnerGui->getDefaultColumn();
		$oDefaultColumn->setAliasForAll('kmv');
		$oInnerGui->setDefaultColumn($oDefaultColumn);

		$oInnerGui->addDefaultColumns();

		return $oInnerGui;
	}

	/**
	 * @todo getNewDialog und getEditDialog in einer Methode zusammenfassen
	 *
	 * @param Ext_Gui2 $oGuiCN
	 * @return Ext_Gui2_Dialog
	 * @throws Exception
	 */
	static public function getNewDialog(\Ext_Gui2 $oGuiCN) {

		$oDialog = $oGuiCN->createDialog($oGuiCN->t('Creditnote bearbeiten'), $oGuiCN->t('Neue Creditnote'));

		$oClient = Ext_Thebing_Client::getInstance(\Ext_Thebing_Client::getClientId());
		$aReasons = $oClient->getReasons();
		$aAgencies = $oClient->getAgencies(true);
		$aAgencies = Ext_Gui2_Util::addLabelItem($aAgencies, L10N::t('Agentur', $oGuiCN->gui_description));
		$aReasons = Ext_Gui2_Util::addLabelItem($aReasons, L10N::t('Grund', $oGuiCN->gui_description));

		if(Ext_Thebing_System::isAllSchools()) {
			$aSchools = Ext_Thebing_Client::getStaticSchoolListByAccess(false, true);
			$aSchools = Ext_Gui2_Util::addLabelItem($aSchools, L10N::t('Schule', $oGuiCN->gui_description));
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aSchools = array($oSchool->id => $oSchool->getName());
		}

		$oRow = $oDialog->createRow(
			$oGuiCN->t('Agentur'),
			'select',
			[
				'db_column' => 'agency_id',
				'db_alias' => 'kamc',
				'select_options' => $aAgencies,
				'required' => 1,
			]
		);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow(
			$oGuiCN->t('Schule'),
			'select',
			[
				'db_column' => 'school_id',
				'db_alias' => 'kamc',
				'select_options' => $aSchools,
				'required' => 1,
			]
		);
		$oDialog->setElement($oRow);

		if(Ext_Thebing_Access::hasRight('thebing_companies')) {
			$aList = Ext_Thebing_System::getInboxList('use_id');
			$oRow = $oDialog->createRow(
				$oGuiCN->t('Inbox'),
				'select',
				[
					'db_column' => 'inbox_id',
					'db_alias' => 'kamc',
					'select_options' => $aList,
					'required' => 1,
				]
			);
			$oDialog->setElement($oRow);
		}

		$oDialog->setElement(
			$oDialog->createRow(
				$oGuiCN->t('PDF-Vorlage'),
				'select',
				[
					'db_column' => 'template_id',
					'db_alias' => 'kamc',
					'selection' => new Ext_Thebing_Gui2_Selection_Accounting_Manual_PdfTemplate(),
					'required'=> 1,
					'dependency'=> [['db_column' => 'agency_id', 'db_alias' => 'kamc']],
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGuiCN->t('Sprache'),
				'select',
				[
					'db_column' => 'language',
					'db_alias' => 'kamc',
					'selection' => new Ext_TS_Document_Gui2_Selection_TemplateLanguage(),
					'required' => 1,
					'dependency' => [['db_column' => 'template_id', 'db_alias' => 'kamc']],
				]
			)
		);

//		$aTypes = array('unique' => $oGuiCN->t('Einmalig'));
//
//		$oRow = $oDialog->createRow(
//			$oGuiCN->t('Art'),
//			'select',
//			[
//				'db_column' => 'type',
//				'db_alias' => 'kamc',
//				'select_options' => $aTypes,
//				'required' => 1,
//			]
//		);
//		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($oGuiCN->t('Anzahl'), 'input', ['db_column' => 'count', 'db_alias' => 'kamc', 'required' => 1, 'default_value' => 1]);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($oGuiCN->t('Betrag'), 'input', ['db_column' => 'amount', 'db_alias' => 'kamc', 'required' => 1]);
		$oDialog->setElement($oRow);

		$aSchoolCurrencies = $oClient->getSchoolsCurrencies();

		$oRow = $oDialog->createRow(
			$oGuiCN->t('Währung'),
			'select',
			[
				'db_column' => 'currency_id',
				'db_alias' => 'kamc',
				'select_options' => $aSchoolCurrencies,
				'required' => 1,
				'dependency' => [
					[
						'db_column' => 'school_id',
						'db_alias' => 'kamc',
					],
				]
			]
		);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow(
			$oGuiCN->t('Grund'),
			'select',
			[
				'db_column' => 'reason_id',
				'db_alias' => 'kamc',
				'select_options' => $aReasons,
				'required' => 0,
			]
		);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($oGuiCN->t('Kommentar'), 'textarea', ['db_column' => 'comment', 'db_alias' => 'kamc', 'required' => 0]);
		$oDialog->setElement($oRow);

		$iH3 = $oDialog->create('h3');
		$iH3->setElement($oGuiCN->t('Interne Verwendung'));
		$oDialog->setElement($iH3);
		$oDialog->setElement($oDialog->createRow($oGuiCN->t('Notiz'), 'textarea', ['db_column' => 'note', 'db_alias' => 'kamc', 'required' => 0]));

		#############################################################################################################

		$oDialog->width = 950;
		$oDialog->sDialogIDTag = 'CN_';

		return $oDialog;
	}

	static public function getEditDialog(\Ext_Gui2 $oGuiCN) {

		$oClient = Ext_Thebing_Client::getInstance(\Ext_Thebing_Client::getClientId());
		$aAgencies = $oClient->getAgencies(true);
		$aAgencies = Ext_Gui2_Util::addLabelItem($aAgencies, L10N::t('Agentur', $oGuiCN->gui_description));
		$aReasons = $oClient->getReasons();
		$aReasons = Ext_Gui2_Util::addLabelItem($aReasons, L10N::t('Grund', $oGuiCN->gui_description));
		if	(Ext_Thebing_System::isAllSchools()) {
			$aSchools = Ext_Thebing_Client::getStaticSchoolListByAccess(false, true);
			$aSchools = Ext_Gui2_Util::addLabelItem($aSchools, L10N::t('Schule', $oGuiCN->gui_description));
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aSchools = array($oSchool->id => $oSchool->getName());
		}
		$aTypes = array('unique' => $oGuiCN->t('Einmalig'));
		$oClient = Ext_Thebing_Client::getInstance(\Ext_Thebing_Client::getClientId());
		$aSchoolCurrencies = $oClient->getSchoolsCurrencies();

		if(!\Ext_Thebing_Access::hasRight("thebing_marketing_agencies_creditnotes_edit")) {
			$title = 'Creditnote anschauen';
		} else {
			$title = 'Creditnote "{document_number}" bearbeiten';
		}

		$oDialogEdit = $oGuiCN->createDialog($oGuiCN->t($title), $oGuiCN->t('Neue Creditnote'));

		$oTab = $oDialogEdit->createTab($oGuiCN->t('Creditnote'));

		$oRow = $oDialogEdit->createRow(
			$oGuiCN->t('Agentur'),
			'select',
			[
				'db_column' => 'agency_id',
				'db_alias' => 'kamc',
				'select_options' => $aAgencies,
				'required' => 1,
			]
		);
		$oTab->setElement($oRow);

		$oRow = $oDialogEdit->createRow(
			$oGuiCN->t('Schule'),
			'select',
			[
				'db_column' => 'school_id',
				'db_alias' => 'kamc',
				'select_options' => $aSchools,
				'dependency' => [
					[
						'db_column' => 'school_id',
						'db_alias' => 'kamc',
					],
				],
				'required' => 1,
			]
		);
		$oTab->setElement($oRow);

		if(Ext_Thebing_Access::hasRight('thebing_companies')){
			$aList = Ext_Thebing_System::getInboxList('use_id');
			$oRow = $oDialogEdit->createRow(
				$oGuiCN->t('Inbox'),
				'select',
				[
					'db_column' => 'inbox_id',
					'db_alias' => 'kamc',
					'select_options' => $aList,
					'required' => 1,
				]
			);
			$oTab->setElement($oRow);
		}

//		$oRow = $oDialogEdit->createRow(
//			$oGuiCN->t('Art'),
//			'select',
//			[
//				'db_column' => 'type',
//				'db_alias' => 'kamc',
//				'select_options' => $aTypes,
//				'required' => 1,
//			]
//		);
//		$oTab->setElement($oRow);

		$oRow = $oDialogEdit->createRow($oGuiCN->t('Betrag'), 'input', ['db_column' => 'amount', 'db_alias' => 'kamc', 'required' => 1, 'format'=>new Ext_Thebing_Gui2_Format_Float()]);
		$oTab->setElement($oRow);

		$oRow = $oDialogEdit->createRow(
			$oGuiCN->t('Währung'),
			'select',
			[
				'db_column' => 'currency_id',
				'db_alias' => 'kamc',
				'select_options' => $aSchoolCurrencies,
				'required' => 1
			]
		);
		$oTab->setElement($oRow);

		$oRow = $oDialogEdit->createRow(
			$oGuiCN->t('Grund'),
			'select',
			[
				'db_column' => 'reason_id',
				'db_alias' => 'kamc',
				'select_options' => $aReasons,
				'required' => 0,
			]
		);
		$oTab->setElement($oRow);

		$oRow = $oDialogEdit->createRow($oGuiCN->t('Kommentar'), 'textarea', ['db_column' => 'comment', 'db_alias' => 'kamc', 'required' => 0]);

		$iH3 = $oDialogEdit->create('h4');
		$iH3->setElement($oGuiCN->t('Inhalt'));
		$oTab->setElement($iH3);

		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Datum'), 'calendar', ['db_alias'=>'kmv', 'db_column'=>'date', 'format' => new Ext_Thebing_Gui2_Format_Date()]));
		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Adresse'), 'textarea', ['db_alias'=>'kmv', 'db_column'=>'txt_address']));
		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Betreff'), 'input', ['db_alias'=>'kmv', 'db_column'=>'txt_subject']));
		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Einleitung'), 'html', ['db_alias'=>'kmv', 'db_column'=>'txt_intro']));
		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Schlussbemerkung'), 'html', ['db_alias'=>'kmv', 'db_column'=>'txt_outro']));
		//$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Signatur'), 'html', ['db_alias'=>'kmv', 'db_column'=>'txt_signature']));

		$oTab->setElement($oRow);

		$iH3 = $oDialogEdit->create('h3');
		$iH3->setElement($oGuiCN->t('Interne Verwendung'));
		$oTab->setElement($iH3);
		$oTab->setElement($oDialogEdit->createRow($oGuiCN->t('Notiz'), 'textarea', ['db_column' => 'note', 'db_alias' => 'kamc', 'required' => 0]));

		$oDialogEdit->setElement($oTab);

		############################################################################################################

		$oTab = $oDialogEdit->createTab($oGuiCN->t('Historie'));
		/*
		$oTab->aOptions = [
			'access' => 'thebing_tuition_teacher_contracts',
			'task' => 'contracts'
		];
		*/

		$oGuiCNData = $oGuiCN->getDataObject();

		$oHistoryGui = $oGuiCNData->getHistoryGui();
		$oTab->setElement($oHistoryGui);
		$oDialogEdit->setElement($oTab);

		$oDialogEdit->width = 950;
		$oDialogEdit->sDialogIDTag = 'CN_EDIT_';

		return $oDialogEdit;
	}

	static public function getStornoDialog(\Ext_Gui2 $oGuiCN) {

		$oClient = Ext_Thebing_Client::getInstance(\Ext_Thebing_Client::getClientId());
		$aReasons = $oClient->getReasons();

		$oDialogStorno = $oGuiCN->createDialog($oGuiCN->t('Creditnote stornieren'));
		$oRow = $oDialogStorno->createRow(
			$oGuiCN->t('Grund'),
			'select',
			[
				'db_column' => 'reason_id',
				'select_options' => $aReasons,
			]
		);
		$oDialogStorno->setElement($oRow);

		$oRow = $oDialogStorno->createRow($oGuiCN->t('Kommentar'), 'textarea', ['db_column' => 'comment', 'required' => 0]);
		$oDialogStorno->setElement($oRow);

		$oRow = $oDialogStorno->createRow($oGuiCN->t('Notiz'), 'textarea', ['db_column' => 'note', 'required' => 0]);
		$oDialogStorno->setElement($oRow);

		$oDialogStorno->width = 950;
		$oDialogStorno->sDialogIDTag = 'CN_STORNO_';

		return $oDialogStorno;
	}

	static public function getOrderby(){

		return ['created' => 'DESC'];
	}

	static public function getWhere() {

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aAgencies = $oClient->getAgencies(true);

		$aAgenciesIds = array_keys($aAgencies);

		return ['agency_id' => ['IN', $aAgenciesIds]];
	}

	static public function getAgencySelectOptions() {

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aAgencies = $oClient->getAgencies(true);

		return $aAgencies;
	}

	static public function getDefaultFilterFrom(){

		$oDate = new WDDate();
		$oDate->sub(6, WDDate::MONTH);
		$iFrom = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iFrom);
	}

	static public function getDefaultFilterUntil() {

		$oDate = new WDDate();
		$iUntil = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iUntil);
	}

	static public function getReasonSelectOptions(){

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aReasons = $oClient->getReasons();
		unset($aReasons[0]);

		return $aReasons;
	}

	static public function getStatusSelectOptions(\Ext_Thebing_Gui2 $oGui) {

		$aPaymentStatus = [
			'not_paid' => $oGui->t('offen'),
			'paid' => $oGui->t('bereits verrechnet'),
			'storno' => $oGui->t('storniert'),
		];

		return $aPaymentStatus;
	}

	static public function getFormatParamsTypeColumn() {

		$sCNHash = md5('thebing_accounting_manual_creditnote');
		$oGuiCN = new Ext_Thebing_Gui2($sCNHash, 'Ext_Thebing_Agency_Manual_Creditnote_Gui2');
		$aTypes = ['unique' => $oGuiCN->t('Einmalig')];

		return $aTypes;
	}

	static public function getFormatParamsReasonColumn() {

		$oClient = \Ext_Thebing_Client::getInstance(\Ext_Thebing_Client::getClientId());
		$aReasons = $oClient->getReasons();

		return $aReasons;
	}

}
