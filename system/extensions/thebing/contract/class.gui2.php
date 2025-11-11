<?php

class Ext_Thebing_Contract_Gui2 extends Ext_Thebing_Gui2_Data {

	public function getTranslations($sL10NDescription) { 

		$aData = parent::getTranslations($sL10NDescription);
		
		$aData['contract_confirm']			= $this->t('Vertrag bestätigen');
		$aData['contract_de_confirm']		= $this->t('widerrufen');

		return $aData;
	}
	
	/**
	 * Speichert den Dialog
	 * Wenn Verträge für mehrere Lehrer gespeichert werden,
	 * wird das hier aufgesplittet auf einzelne Verträge für die Lehrer
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		$aTransfer = array();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sSchoolLanguage = $oSchool->getLanguage();
				
		switch($sAction){
			case 'edit':

				$this->_getWDBasicObject($aSelectedIds);

				// Bei PDF Templatewechsel, Inhalt aktualisieren
				if(
					!empty($aData['pdf_template_id']['kcontv']) &&
					$aData['pdf_template_id']['kcontv'] != $this->oWDBasic->pdf_template_id
				) {

					$oPdfTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['pdf_template_id']['kcontv']);
					$aData['txt_intro']['kcontv'] = $oPdfTemplate->getStaticElementValue($sSchoolLanguage, 'text1');

				}
				
				$aTransfer = $this->saveEditDialogData((array)$aSelectedIds, $aData, $bSave, $sAction);
				$aTransfer['data']['force_new_dialog'] = true;
				$aTransfer['data']['old_id'] = $aTransfer['dialog_id_tag'].implode('_', (array)$aSelectedIds);

				break;
			case 'new':

				$aTransfer['error'] = array();

				// Prüfen ob mehrere Verträge abgespeichert werden sollen
				$bMultiple = false;
				if(is_array($aData['item_id']['kcont'])) {
					if(count($aData['item_id']['kcont']) > 1) {
						$bMultiple = true;
					}
				} else {
					$aData['item_id']['kcont'] = array($aData['item_id']['kcont']);
				}

				DB::begin(__CLASS__);

				$oPdfTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['pdf_template_id']['kcontv']);
				$sPdfTemplate = $oPdfTemplate->getStaticElementValue($sSchoolLanguage, 'text1');

				// Dialog holen
				$sIconKey = self::getIconKey($sAction, $sAdditional);		
				$oDialog = $this->_getDialog($sIconKey);
				// Data-Objekt holen
				$oDialogData = $oDialog->getDataObject();
				
				// Alle Verträge speichern
				$aVersionIds = array();
				foreach((array)$aData['item_id']['kcont'] as $iItemId) {

					$aDataTemp = $aData;
					$aDataTemp['item_id']['kcont'] = $iItemId;
				
					// WDBasic reseten, damit für jedes Item ein neuer Eintrag angelegt wird (#5040)
					$oDialogData->resetWDBasicObject();
					
					$aItemTransfer = $oDialogData->saveEdit((array)$aSelectedIds, $aDataTemp, $bSave, $sAction);

					if(!empty($aItemTransfer['error'])) {
						array_shift($aItemTransfer['error']);
						$aTransfer['error'] = array_merge((array)$aTransfer['error'], (array)$aItemTransfer['error']);
					} else {

						if($bSave) {
							$oContractVersion = Ext_Thebing_Contract_Version::getInstance($aItemTransfer['save_id']);
							$oContractVersion->txt_intro = $sPdfTemplate;
							$oContractVersion->save(false, false, true);

							$aVersionIds[] = $aItemTransfer['save_id'];

							// Template in Rückgabedaten schreiben, damit es im Dialog direkt angezeigt wird
							foreach((array)$aItemTransfer['data']['values'] as $iKey=>$aValue) {
								if($aValue['db_column'] == 'txt_intro') {
									$aItemTransfer['data']['values'][$iKey]['value'] = $sPdfTemplate;
									break;
								}
							}

						}

					}

				}

				// Infos in Transfer Array schreiben
				$aTransfer['action']		= $aItemTransfer['action'];
				$aTransfer['dialog_id_tag'] = $aItemTransfer['dialog_id_tag'];
				$aTransfer['data']			= $aItemTransfer['data'];
				if(!empty($aSelectedIds)) {
					$aTransfer['save_id']	= reset($aSelectedIds);
				}

				$aTransfer['error'] = array_unique($aTransfer['error']);

				if(empty($aTransfer['error'])) {
					DB::commit(__CLASS__);
				} else {
					DB::rollback(__CLASS__);
				}

				// Wenn mehrere Verträge gespeichert wurden, Dialog schliessen
				if(
					$bMultiple &&
					empty($aTransfer['error'])
				) {

					// Sammel PDF erstellen
					if(
						$bSave &&
						!empty($aVersionIds)
					) {
						$sFilepath = Ext_Thebing_Contract_Version::generatePdf($aVersionIds);
					}
					
					$sFilepath = str_replace(\Util::getDocumentRoot(), '', $sFilepath);
					
					// #5041 
					// Aus irgendeinem Grund kam auf sms.thebing.com nur media/secure/
					if(substr($sFilepath, 0, 1) == '/') {
						$sFilepath = str_replace('/storage/', '', $sFilepath);
					} else {
						$sFilepath = str_replace('storage/', '', $sFilepath);
					}					
										
					$sUrl = '/storage/download/'.$sFilepath;
					$aTransfer['success_message'] = L10N::t('Die Verträge wurden erfolgreich angelegt.', $this->_oGui->gui_description).'<br/><a href="'.$sUrl.'">'.L10N::t('Bitte klicken Sie hier, um ein PDF mit allen Verträgen anzuzeigen.', $this->_oGui->gui_description).'</a>';
					$aTransfer['data']['options']['close_after_save'] = true;
				}	

				break;

			default:
				$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
				break;
		}
 
		if(!empty($aTransfer['error'])){
			array_unshift($aTransfer['error'], $this->t('Fehler beim Speichern'));
		
		}
		
		return $aTransfer;

	}

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 * @return unknown_type
	 */
	public function switchAjaxRequest($_VARS) {
		$aTransfer = array();

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		if(
			isset($_VARS['action']) &&
			isset($_VARS['task']) &&
			(
				(
					$_VARS['action'] == 'edit' &&
					$_VARS['task'] == 'openDialog'
				) ||
				(
					$_VARS['action'] == 'new' &&
					$_VARS['task'] == 'update_select_options'
				)
			)
		) {

			$aFieldIndexes = array();
			foreach((array)$aTransfer['data']['values'] as $iIndex=>$aValue) {
				$aFieldIndexes[$aValue['db_column']] = $iIndex;
			}

			if($this->oWDBasic->getJoinedObject('kcont')->contract_template_id > 0) {
				$oTemplate = $this->oWDBasic->getJoinedObject('kcont')->getContractTemplate();

				if($oTemplate->type == 2) {

					// Wenn Zusatzvertrag, Enddatum auf Required setzen
					if(
						isset($aFieldIndexes['valid_until']) &&
						isset($aTransfer['data']['values'][$aFieldIndexes['valid_until']])
					) {
						$aTransfer['data']['values'][$aFieldIndexes['valid_until']]['required'] = 1;
					}

				}
			}

		}

		if(
			isset($_VARS['action']) &&
			isset($_VARS['task']) &&
			$_VARS['action'] == 'new' &&
			$_VARS['task'] == 'update_select_options'
		) {

			/**
			 * Überprüfen, ob schon ein Rahmenvertrag vorhanden ist, wenn man einen Zusatzvertrag auswählt
			 * Alle Lehrer überprüfen und falls ausgewählt auch den Zeitraum prüfen
			 */

			$iContractTemplateId = $this->oWDBasic->getJoinedObject('kcont')->contract_template_id;

			if($iContractTemplateId > 0) {

				$oTemplate = Ext_Thebing_Contract_Template::getInstance($iContractTemplateId);

				// Nur prüfen, wenn Typ = Zusatzvertrag
				if($oTemplate->type == 2) {

					$dValidFrom = $this->oWDBasic->valid_from;
					$dValidUntil = $this->oWDBasic->valid_until;

					if(
						$this->oWDBasic->id > 0 &&
						!empty($dValidFrom) &&
						!empty($dValidUntil)
					) {

						$iMainContract = $this->oWDBasic->searchMainContract(true);

						if($iMainContract == 0) {
							$aTransfer['error'] = array('Für den gewählten Zeitraum gibt es keinen Rahmenvertrag!');
						}

					}

				}

			}

		} elseif(
			isset($_VARS['action']) &&
			isset($_VARS['task']) &&
			$_VARS['action'] == 'contract_confirm' &&
			$_VARS['task'] == 'request'
		) {

			$aErrors = array();
	
			$iSelectedId = (int)$_VARS['id'][0];

			$oContractVersion = Ext_Thebing_Contract_Version::getInstance($iSelectedId);
			
			// Prüfen, ob bei einem Zusatzvertrag der Rahmenvertrag schon bestätigt wurde
			$oTemplate = $oContractVersion->getJoinedObject('kcont')->getContractTemplate();
			// Nur prüfen, wenn Typ = Zusatzvertrag
			if($oTemplate->type == 2) {
				$iMainContract = $oContractVersion->searchMainContract(true);
				$oMainContract = Ext_Thebing_Contract::getInstance($iMainContract);
				$oMainVersion = $oMainContract->getLatestVersion();

				if($oMainVersion->confirmed == 0) {
					$sMessage = L10N::t('Der Rahmenvertrag "%s" dieses Zusatzvertrages wurde noch nicht bestätigt.', $this->_oGui->gui_description);
					$sMessage = sprintf($sMessage, $oMainContract->number);
					$aErrors[] = $this->_oGui->getDefaultError();
					$aErrors[] = $sMessage;
				}
			}

			if(empty($aErrors)) { 
				
				if($oContractVersion->isConfirmed()){
					$oContractVersion->deleteConfirmation();
					$aTransfer['success_message'] = L10N::t('Die Bestätigung der Vertragversion wurde erfolgreich entfernt.', $this->_oGui->gui_description);
				}else{ 
					$oContractVersion->confirm();
					$aTransfer['success_message'] = L10N::t('Diese Vertragversion wurde erfolgreich als bestätigt markiert.', $this->_oGui->gui_description);
				}
				
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['data']['id'] = 'CONFIRM_MESSAGE_'.$iSelectedId;

			} else {
				$aTransfer['action'] = 'showError';
			}

			$aTransfer['error'] = $aErrors;

		} elseif(
			isset($_VARS['action']) &&
			isset($_VARS['task']) &&
			$_VARS['action'] == 'contract_open' &&
			$_VARS['task'] == 'request'
		) {

			$iSelectedId = (int)$_VARS['id'][0];

			$oVersion = Ext_Thebing_Contract_Version::getInstance((int)$iSelectedId);
			$sFilepath = $oVersion->file;

			$sFileTest = Util::getDocumentRoot().'storage/'.$sFilepath;

			$aTransfer['action'] = 'openUrl';

			if(is_file($sFileTest)) {
				$sLink = Ext_Thebing_Util::generateSecureLink($sFilepath);
				$aTransfer['url'] = $sLink;
			} else {
				$aTransfer['error'] = array(L10N::t('Das Dokument konnte nicht gefunden werden! Bitte speichern Sie erneut.', $this->_oGui->gui_description));
			}

		} else if ($_VARS['task'] === 'request' && $_VARS['action'] === 'communication') {
			parent::switchAjaxRequest($_VARS);
			die();
		}

		echo json_encode($aTransfer);

	}

	protected function _getErrorMessage($mError, $sField='', $sLabel='', $sAction = null, $sAdditional = null) {

		$sMessage = '';

		if(empty($sLabel)) {
			$sLabel = $sField;
		}

		if(is_array($mError)) {
			switch($mError['message']) {
				case 'OTHER_BASIC_CONTRACT_IN_PERIOD':
					$sMessage = 'In diesem Zeitraum gibt es bereits einen Rahmenvertrag für "%s".';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;

				case 'NO_BASIC_CONTRACT_IN_PERIOD':
					$sMessage = 'In diesem Zeitraum gibt es keinen Rahmenvertrag für "%s".';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;

				case 'NO_VALID_FROM_INCREASE':
					$sMessage = 'Das Startdatum darf nicht erhöht werden.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;

				case 'NO_VALID_UNTIL_DECREASE':
					$sMessage = 'Das Enddatum darf nicht vermindert werden.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;

				case 'UNTIL_BEFORE_FROM':
					$sMessage = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;
				
				default:

					break;
			}

			$sMessage = sprintf($sMessage, $mError['item']);

		} else {
			$sMessage = parent::_getErrorMessage($mError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;

	}

	static public function getEditDialog(\Ext_Gui2 $oGui) {

		# START Dialog Edit #

		$oDialogEdit = $oGui->createDialog(L10N::t('{type_name} für "{name}"', $oGui->gui_description), L10N::t('Neuer Vertrag', $oGui->gui_description));
		
		if($oGui->set == 'accommodation')
		{
			$sSection = 'accommodation_contracts';
		}
		else {
			$sSection = 'teacher_contracts';
		}
		$oTab = $oDialogEdit->createTab(L10N::t('Vertrag', $oGui->gui_description));
		$oTab->aOptions = array(
				'task' => 'contract',
				'section' => $sSection
			);
		//disabled setzen
		$oTab->setElement($oDialogEdit->createRow(L10N::t('PDF-Vorlage', $oGui->gui_description), 'select', array('db_alias'=>'kcontv', 'db_column' => 'pdf_template_id', 'readonly' => 'readonly', 'selection' => new Ext_Thebing_Gui2_Selection_Contract_PdfTemplate(), 'dependency'=>array(array('db_alias'=>'kcont', 'db_column' => 'contract_template_id')), 'required'=>1)));
		$oTab->setElement($oDialogEdit->createRow(L10N::t('Startdatum', $oGui->gui_description), 'calendar', array('db_alias'=>'kcontv', 'db_column'=>'valid_from', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'required'=>1)));
		$oTab->setElement($oDialogEdit->createRow(L10N::t('Enddatum', $oGui->gui_description), 'calendar', array('db_alias'=>'kcontv', 'db_column'=>'valid_until', 'format'=>new Ext_Thebing_Gui2_Format_Date())));

		$oTab->setElement($oDialogEdit->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('db_alias'=>'kcontv', 'db_column'=>'comment')));

		$iH3 = $oDialogEdit->create('h4');
		$iH3->setElement(L10N::t('Inhalt', $oGui->gui_description));
		$oTab->setElement($iH3);

		$oTab->setElement($oDialogEdit->createRow(L10N::t('Vertragstext', $oGui->gui_description), 'html', array('db_alias'=>'kcontv', 'db_column'=>'txt_intro', 'style'=>'width: 600px; height: 300px;')));

		$oDialogEdit->setElement($oTab);

		$oTab = $oDialogEdit->createTab(L10N::t('Historie', $oGui->gui_description));
		$oTab->aOptions = array(
				'task' => 'history'
			);
		$oTab->setElement(self::getEditDialogHistory($oGui));
		$oDialogEdit->setElement($oTab);

		$oDialogEdit->width = 950;
		$oDialogEdit->height = 600;

		return $oDialogEdit;

	}

	static public function getEditDialogHistory(\Ext_Gui2 $oGui) {

		$oInnerGui = $oGui->createChildGui(md5('thebing_contract_history'), 'Ext_Thebing_Contract_Gui2');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kcontv';
		$oInnerGui->foreign_key			= 'contract_id';
		$oInnerGui->foreign_key_alias	= 'kcontv';
		$oInnerGui->parent_primary_key	= 'contract_id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;

		$oInnerGui->setWDBasic('Ext_Thebing_Contract_History');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('kcontv.created'=>'DESC'));

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelGroup = $oBar->createLabelGroup(L10N::t('Details', $oInnerGui->gui_description));
		$oBar ->setElement($oLabelGroup);

		$oIcon = $oBar->createIcon(
									Ext_Thebing_Util::getIcon('pdf'),
									'request',
									L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description)
								);
		$oIcon->label				= L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description);
		$oIcon->action				= 'contract_open';
		$oIcon->dbl_click_element	= 1;
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
		$oColumn->title = L10N::t('Kommentar', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oInnerGui->setColumn($oColumn);

		$oDefaultColumn = $oInnerGui->getDefaultColumn();
		$oDefaultColumn->setAliasForAll('kcontv');
		$oInnerGui->setDefaultColumn($oDefaultColumn);

		$oInnerGui->addDefaultColumns();

		return $oInnerGui;

	}
	
	static public function getOrderby() {


		return ['kcont.date'=>'DESC'];
	}
	
	public static function getUntilDate() {
		
		
		$oDate = new WDDate();
		$oDate->add(1, WDDate::MONTH);
		$iNow = (int)$oDate->get(WDDate::TIMESTAMP);
		return (new \Ext_Thebing_Gui2_Format_Date)->formatByValue($iNow);
	}
	
	public static function getFromDate() {
		
		$oDate = new WDDate();
		$oDate->add(1, WDDate::MONTH);	
		$oDate->sub(12, WDDate::MONTH);
		$iNow = (int)$oDate->get(WDDate::TIMESTAMP);

		return (new \Ext_Thebing_Gui2_Format_Date)->formatByValue($iNow);
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();	
		$aContractTemplates = $oSchool->getContractTemplates(true, $oGui->set);
		$aContractTemplates = Ext_Thebing_Util::addEmptyItem($aContractTemplates, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));
		
		
		if($oGui->set == 'accommodation')
		{
			$sItemLabel = 'Unterkünfte';
			$aItems = $oSchool->getAccommodationProvider(true);
		}
		else {
			$sItemLabel = 'Lehrer';
			$aItems = $oSchool->getTeacherList(true);
		}
		
		
		
		$oDialogNew = $oGui->createDialog($oGui->t('Vertrag bearbeiten'), L10N::t('Neuer Vertrag', $oGui->gui_description));

		$oDialogNew->setElement(
			$oDialogNew->createRow(
				L10N::t('Vertragsvorlage', $oGui->gui_description),
				'select',
				array(
					'db_alias'=>'kcont',
					'db_column' => 'contract_template_id',
					'select_options' => $aContractTemplates,
					'required'=>1
				)
			)
		);

		$oDialogNew->setElement(
			$oDialogNew->createRow(
				L10N::t('PDF-Vorlage', $oGui->gui_description),
				'select',
				array(
					'db_alias'=>'kcontv',
					'db_column' => 'pdf_template_id',
					'selection' => new Ext_Thebing_Gui2_Selection_Contract_PdfTemplate(),
					'dependency'=>array(array('db_alias'=>'kcont', 'db_column' => 'contract_template_id')),
					'required'=>1
				)
			)
		);

		$oDialogNew->setElement($oDialogNew->createRow(L10N::t('Vertragsdatum', $oGui->gui_description), 'calendar', array('db_alias'=>'kcont', 'db_column'=>'date', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'required'=>1)));
		$oDialogNew->setElement($oDialogNew->createRow(L10N::t('Startdatum', $oGui->gui_description), 'calendar', array('db_alias'=>'kcontv', 'db_column'=>'valid_from', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'required'=>1, 'events'=>array(array('event'=>'change', 'function'=>'prepareUpdateSelectOptions')))));
		$oDialogNew->setElement($oDialogNew->createRow(L10N::t('Enddatum', $oGui->gui_description), 'calendar', array('db_alias'=>'kcontv', 'db_column'=>'valid_until', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'events'=>array(array('event'=>'change', 'function'=>'prepareUpdateSelectOptions')))));

		$oDialogNew->setElement($oDialogNew->createRow(L10N::t($sItemLabel, $oGui->gui_description), 'select', array('db_alias'=>'kcont', 'db_column' => 'item_id', 'multiple'=>5, 'jquery_multiple'=>1, 'searchable'=>true, 'select_options' => $aItems, 'required'=>1)));

		$oDialogNew->setElement($oDialogNew->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('db_alias'=>'kcontv', 'db_column'=>'comment')));

		$oDialogNew->width = 950;
		$oDialogNew->height = 600;

		return $oDialogNew;
	}
			
	static public function getWhere($oGui) {

		$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		return [
			'kcont.school_id' => (int)$iSessionSchoolId,
			'kcont.item' => $oGui->set
		];
	}
}