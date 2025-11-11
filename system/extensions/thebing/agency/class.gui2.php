<?php

class Ext_Thebing_Agency_Gui2 extends Ext_Thebing_Gui2_Data {

	use \Tc\Traits\Gui2\Import;
	
	/** @var Ext_Gui2_Dialog */
	protected $_oDialog;

	/** @var Ext_Gui2_Dialog_Tab */
	protected $_oTab;
	protected $_aAgencyCategories	= null;
	protected $_aAgencyGroups		= null;
	protected $_aGenders			= null;
	protected $_aSchools			= null;

	public function switchAjaxRequest($_VARS)
	{
		global $system_data;
	
        if($_VARS['action'] == 'extend_export'){
            $this->createExtendedExport((array)$_VARS['id']);
            die();
        } else if(
			$_VARS['task'] == 'sendPassword'
		) {
				$aTransfer = array();

				$aSelectedIds = (array)$_VARS['id'];
				$iSelectedId = (int)reset($aSelectedIds);

				$oAgency = Ext_Thebing_Agency::getInstance($iSelectedId);

				// E-Mail-Adresse prüfen
				$bEmailCheck = false;
				$oMaster = $oAgency->getMasterContact();
				if($oMaster) {
					$sEmail = $oMaster->email;
					if(checkEmailMx($sEmail)) {
						$bEmailCheck = true;
					}
				}

				if($bEmailCheck) {

					$bSuccess = $oAgency->sendPassword();
				
					if( $bSuccess ) {
						$aTransfer['success']	= 1;
						$aTransfer['message']	= L10N::t('Das Passwort wurde erfolgreich versendet!', $this->_oGui->gui_description);
						$aTransfer['action']	= 'showSuccess';
						$aTransfer['error']	= array();
					} else {
						$aTransfer['success']	= 0;
						$aTransfer['error']		= array(
							L10N::t('Das Passwort konnte nicht versendet werden! Bitte kontrollieren Sie Ihre E-Mail-Einstellungen.', $this->_oGui->gui_description)
						);
						$aTransfer['action']	= 'showError';
					}

				} else {
					$aTransfer['success']	= 0;
					$aTransfer['error']		= array(
						L10N::t('Das Passwort konnte nicht versendet werden! Bitte kontrollieren Sie die E-Mail-Adresse der Hauptkontaktperson.', $this->_oGui->gui_description)
					);
					$aTransfer['action']	= 'showError';
				}

				echo json_encode($aTransfer);
		}elseif(
			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'agency_pdf'
		){
			// Agentur PDF übersicht erstellen

			// Daten
			$iTemplateId	= (int)$_VARS['save']['pdf_template_id'];
			$aSelectedIds	= (array)$_VARS['id'];

			if($iTemplateId > 0) {

				$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplateId);

				if (!in_array($_VARS['save']['language'], $oTemplate->languages)) {
					$aTransfer = [
						'action' => 'showError',
						'error' => [$this->t('Die gewählte Sprache ist für diese Vorlage nicht verfügbar.')]
					];
					echo json_encode($aTransfer);
					return;
				}

				// Schulobjekt muss aus Session kommen
				$oSchool = Ext_Thebing_School::getSchoolFromSession();

				$oPdf = new Ext_Thebing_Pdf_Basic($oTemplate->id);
				$oPdf->sDocumentType = 'agency_overview';

				// Für jede Agentur PDF Seite erstellen
				// Vorbereiten der Daten für PDF				
				foreach((array)$aSelectedIds as $iAgencyId) {

					$oReplace = new Ext_Thebing_Agency_Placeholderoverview($iAgencyId);
					$oReplace->sTemplateLanguage = $_VARS['save']['language'];
//					$oReplace->sTemplateLanguage = $oSchool->getLanguage(); // Lief vorher genauso über PDF-Klasse
//					$oReplace->setAdditionalData('overview_contacts', $_VARS['save']['agency_contact_id']);
//					$oReplace->setAdditionalData('overview_date', $_VARS['save']['overview_date']);
//					$oReplace->setAdditionalData('overview_time', $_VARS['save']['overview_time']);

					$oVersion = new Ext_Thebing_Inquiry_Document_Version();
					$oVersion->template_id = $oTemplate->id;
					$oVersion->setDefaultTemplateTexts($oReplace, $oSchool);

					// Platzhalter-Klasse übergeben, damit das bei eigenen Elementen im Layout funktioniert
					$oPdf->createDummyDocument($oVersion->getData(), [], [], ['placeholder' => $oReplace]);

				}

				// Name der PDF Vorlage
				$sNumber = 'agency_overview';

				// Nummer des Vertrages
				$sNumber .= '_'.date('YmdHis');

				$sFileName = \Util::getCleanFileName($sNumber);

				$oClient = Ext_Thebing_Client::getInstance();
				$sClientPath = $oClient->getFilePath();
				$sPath = $sClientPath."agency_overview/";

				## ENDE
				$sError = '';
				try {
					$sFilepath = $oPdf->createPdf($sPath, $sFileName);
				} catch (PDF_Exception $e) {
					$sFilepath	= false;
					$sError		= L10N::t('PDF konnte nicht erstellt werden! Bitte überpüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors');
					if(System::d('debugmode') > 0) {
						$sError .= '<br>'.$e->getMessage();
					}
				} catch(DB_QueryFailedException $e) {
					throw $e;
				} catch (Exception $e) {
					Ext_Thebing_Util::reportError($e->getMessage());
					$sFilepath	= false;
					$sError		= L10N::t('PDF konnte nicht erstellt werden! Bitte überpüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors');
					if(System::d('debugmode') > 0) {
						$sError .= '<br>'.$e->getMessage();
					}
				}

				if(is_file($sFilepath)) {
					$sFilepath = str_replace(\Util::getDocumentRoot(), '', $sFilepath);
					$sFilepath = str_replace('storage/', '', $sFilepath);
					$sUrl = '/storage/download/'.$sFilepath;

					$aTransfer['success_message'] = $this->t('Agentur-PDF wurde erfolgreich erstellt.').'<br><a download href="'.$sUrl.'">'.$this->t('Bitte klicken Sie hier um das PDF zu öffnen.').'</a>';
					$aTransfer['data']['options']['close_after_save'] = true;
					$aTransfer['data']['id'] = 'AGENCYOVERVIEW_'.implode($aSelectedIds);
					$aTransfer['error']	= array();
					$aTransfer['action']	= "saveDialogCallback";
				} else {
					$aTransfer['action']	= "showError";

					if(!empty($sError)){
						$aTransfer['error'][] = $sError;
					}else{
						$aTransfer['error'][] = L10N::t('PDF konnte nicht gespeichert werden', $this->_oGui->gui_description);
					}
					
				}
 
			}

			echo json_encode($aTransfer);
		
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	
	}

	/**
	 * @inheritdoc
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$this->_getWDBasicObject($aSelectedIds);

		// Ersten Tab neu generieren, da hier das Nummernfeld je Eintrag manipuliert werden muss (aSaveData nimmt disabled nicht an)
		if(Ext_Thebing_Access::hasRight('thebing_marketing_agencies_edit_agency_number')) {
			unset($oDialogData->aElements[0]);
			array_unshift($oDialogData->aElements, $this->getDataTab());
		}

		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		if($bSave){
			$aCosts = $aSaveData['costs'];
			if(empty($aTransfer['error']) )	{
				$this->oWDBasic->saveInitalCosts($aCosts);
			}
		}

		// Daten nochmal neu holen, nach dem Speicher der Checkboxen
		$oDialogData = null;
		$aIconKey = self::getIconKey($sAction, false);

		if($this->aIconData[$aIconKey['action']]){
			$oDialogData = $this->aIconData[$aIconKey['action']]['dialog_data'];
		}

		$aData = $this->getEditDialogData(array($this->oWDBasic->id), $oDialogData->aSaveData);

		$aTransfer['data']['values'] = $aData;
		
		return $aTransfer;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {

		if($sIconAction === 'agency_pdf') {
			$oDialog = $this->getAgencyOverviewDialog($aSelectedIds);
		}

		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);

		return $aData;
	}

	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = (int)reset($aSelectedIds);

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData);

		$oAgency	= Ext_Thebing_Agency::getInstance($iSelectedId);
		$aCosts		= $oAgency->getInitalCosts(1, true);
		
		foreach((array)$aCosts as $iCostId)
		{
			$aItem = array();
			$aItem['id'] = 'costs_'.$iCostId;
			$aItem['value'] = 1;

			$aData[] = $aItem;
		}

		foreach($aData as $iKey => $mValue)
		{
			if($mValue['db_column'] == 'ext_34')
			{
				unset($aData[$iKey]['value'][0]);
			}
		}

		return $aData;

	}


	public static function getDescriptionPart()
	{
		return 'Thebing » Marketing » Agenciegroups';
	}

	public function setDialogObject(Ext_Gui2_Dialog $oDialog)
	{
		$this->_oDialog = $oDialog;

		return $this;
	}

	public function getAgencyCategories() {

		if(is_null($this->_aAgencyCategories)) {
			$this->_aAgencyCategories = Ext_Thebing_Agency::getCategoryList();
			$this->_aAgencyCategories = Ext_Thebing_Util::addEmptyItem($this->_aAgencyCategories);
		}

		return $this->_aAgencyCategories;
	}

	public function getAgencyGroups() {

		if(is_null($this->_aAgencyGroups)) {

			$aAgencyGroups = Ext_Thebing_Agency::getGroupList(true);

			if(in_array(' --- ', $aAgencyGroups)) {
				unset($aAgencyGroups[0]);
			}

			$this->_aAgencyGroups = $aAgencyGroups;
		}

		return $this->_aAgencyGroups;
	}
	
	/*
	 * Liefert alle Länder in denen man nach Agenturen filtern kann in der Liste
	 * Evtl. anpassen das nur Länder aufgelistet werden die auch Agenturen 
	 */
	public function getCountryOptions() {
		$aCountries				= Ext_Thebing_Data::getCountryList();
		$aCountries				= Ext_Thebing_Util::addEmptyItem($aCountries);

		return $aCountries;
	}

	public function getInvoiceTypes() {

		$aInvoiceTypes = array(
			1 => $this->t('Brutto'),
			2 => $this->t('Netto'),
		);

		return $aInvoiceTypes;
	}

	public function getSchoolList() {

		if(is_null($this->_aSchools)) {
			$this->_aSchools = Ext_Thebing_Client::getSchoolList(true);
		}

		return $this->_aSchools;
	}

	/**
	 * Liefert das Array für den Agenturlistenfilter zurück
	 */
	public static function getFilterAgencyListArray() {

		$oClient = Ext_Thebing_Client::getFirstClient();
		$aAllAgencyLists = $oClient->getAgencyLists(true);

		$aBack = array();
		foreach((array)$aAllAgencyLists as $iKey => $sValue){
			$aBack['list_'.$iKey] = $sValue;
		}

		return $aBack;
	}

	public static function getFilterAgencyListQueryArray() {

		$oClient = Ext_Thebing_Client::getFirstClient();
		$aAllAgencyLists = $oClient->getAgencyLists();
		$aBack = array();

		foreach((array)$aAllAgencyLists as $oList){
			if(!empty($oList->join_agencies)){
				$sAgencies = implode(", ", $oList->join_agencies);
			} else {
				$sAgencies = "0";
			}

			$aBack['list_'.$oList->id] = "`ka`.`id` IN (".$sAgencies.")";
		}

		return $aBack;
	}

	public static function getFilterCountryGroupQueryArray() {

		$countryGroups = Ext_TC_Countrygroup::query()
			->get();

		$result = [];
		foreach($countryGroups as $countryGroup) {

			$countryGroupObject = reset($countryGroup->getJoinedObjectChilds('SubObjects'));

			$countryIsos = $countryGroupObject->countries;

			if(!empty($countryIsos)) {
				$countryIsos = implode("', '", $countryIsos);
			}

			$result[$countryGroup->id] = "`ka`.`ext_6` IN ('".$countryIsos."')";
		}

		return $result;
	}

	public static function getFilterStatusArray() {

		// Select teil des Filters
		$aStatusOptions			= array();
		$aStatusOptions['status_1']	= L10N::t('aktiv');
		$aStatusOptions['status_0']	= L10N::t('inaktiv');

		return $aStatusOptions;
	}

	public static function getFilterStatusQueryArray() {
		$aBack = array();
		$aBack['status_1'] = " `ka`.`status` = 1 ";
		$aBack['status_0'] = " `ka`.`status` = 0 ";

		return $aBack;
	}

	public function addValiditySql(&$aSqlParts, &$aSql)	{

		$aSqlParts['select'] .= " ,`kcg`.`name` AS `item_title`";

		$aSqlParts['from'] .= " INNER JOIN
			`tc_cancellation_conditions_groups` `kcg` ON
				`kv`.`item_id` = `kcg`.`id`
		";
	}

	public function getValidityOptions($aSelectedIds) {

		$oCancellationGroup = new Ext_Thebing_Cancellation_Group();

		return $oCancellationGroup->getList('dialog');
	}

    public function createExtendedExport($aSelectedIds) {

    	$aAgencies = [];
		foreach($aSelectedIds as $iId) {
			$aAgencies[] = Ext_Thebing_Agency::getInstance($iId);
		}

        $sCharset = $this->_oGui->getDataObject()->getCharsetForExport();
		$sSeparator	= $this->_oGui->getDataObject()->getSeparatorForExport();
		$oExport = new Gui2\Service\Export\Csv('Export');
		$oExport->setCharset($sCharset);
		$oExport->setSeperator($sSeparator);
        $oExport->sendHeader();

        $aLine = array(
	        $this->_oGui->t('Name'),
	        $this->_oGui->t('Nickname'),
	        $this->_oGui->t('Kategorie'),
	        $this->_oGui->t('Adresse'),
	        $this->_oGui->t('Adresszusatz'),
	        $this->_oGui->t('Stadt'),
	        $this->_oGui->t('PLZ'),
	        $this->_oGui->t('Land'),
	        $this->_oGui->t('Anrede').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('Vorname').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('Nachname').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('Tel.').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('Fax').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('E-Mail').' ('.$this->_oGui->t('Mitarbeiter').')',
	        $this->_oGui->t('Web'),
	        $this->_oGui->t('Agenturgruppen'),
	        $this->_oGui->t('Provisionskategorien'),
	        $this->_oGui->t('Zahlungsmodalitäten'),
	        $this->_oGui->t('Kommentar zur Zahlungsart')
        );
		System::wd()->executeHook('ts_extend_agency_extended_export_headline', $aLine);
        $oExport->sendLine($aLine);

        $aCountries = Ext_TC_Country::getSelectOptions();

        foreach($aAgencies as $oAgency) {

            $aProvisionGroups = (array)$oAgency->getProvisionGroups();

			$oCommissionGroup = null;
			// Prüfen ob eine Provisionsgruppe passt
			foreach($aProvisionGroups as $oProvisionGroup) {

                $oNow = new DateTime();
                $oUntil = null;
                $oFrom = new DateTime($oProvisionGroup->valid_from);

				if($oProvisionGroup->valid_until != 0) {
                    $oUntil = new DateTime($oProvisionGroup->valid_until);
                }

				if(
					(
                        !$oUntil &&
                        $oFrom < $oNow
                    ) || // ist noch nicht abgelaufen ;)
					(
                        $oUntil &&
						$oFrom < $oNow &&
						$oUntil > $oNow
					)
				) {
					$oCommissionGroup = $oProvisionGroup;
					break;
				}

			}

            if($oCommissionGroup) {
                $oCommissionGroup = $oCommissionGroup->getProvisionGroup();
            } else {
                $oCommissionGroup = new stdClass();
            }

            $oPaymentGroup = $oAgency->getValidPaymentCondition();

            if(!$oPaymentGroup) {
                $oPaymentGroup = new stdClass();
            }

			$aGroups = (array)$oAgency->getJoinTableObjects('groups');
			$aGroups = array_map(function($oGroup) {
				/** @var Ext_Thebing_Agency_Group $oGroup */
				return $oGroup->getName();
			}, $aGroups);

            $aContacts = (array)$oAgency->getContacts(false, true);
            if(empty($aContacts)) {
                $aContacts = array(new stdClass());
            }

            $sCountry = $oAgency->ext_6;
            $sCountry = $aCountries[$sCountry];

			$iCategory = (int)$oAgency->ext_39;
			$oCategory = Ext_Thebing_Admin_Agency_Category::getInstance($iCategory);

            foreach($aContacts as $oContact) {

				$aExportLine = array(
					'language' => Ext_TC_System::getInterfaceLanguage(),
					'agency' => $oAgency,
					'line' => array(
						$oAgency->ext_1,
						$oAgency->ext_2,
						$oCategory->getName(),
						$oAgency->ext_3,
						$oAgency->ext_35,
						$oAgency->ext_5,
						$oAgency->ext_4,
						$sCountry,//TODO Land
						Ext_TC_Util::getPersonTitles()[$oContact->gender],
						$oContact->firstname,
						$oContact->lastname,
						$oContact->phone,
						$oContact->fax,
						$oContact->email,
						$oAgency->ext_10,
						join(', ', $aGroups),
						$oCommissionGroup->name,
						$oPaymentGroup->name,
						$oAgency->ext_38
					)
				);
				System::wd()->executeHook('ts_extend_agency_extended_export_line', $aExportLine);
                $oExport->sendLine($aExportLine['line']);

            }

        }

        $oExport->end();

    }

	/**
	 * @param array $aSelectedIds
	 * @return Ext_Gui2_Dialog
	 */
    public function getAgencyOverviewDialog(array $aSelectedIds) {

//		/** @var Ext_Thebing_Agency $oAgency */
//		$oAgency = $this->_getWDBasicObject($aSelectedIds);

		$aLanguages = \Ext_Thebing_Data::getCorrespondenceLanguages();

		$aTemplateTypes = 'agency_overview';
		$aPdfTemplates = Ext_Thebing_Pdf_Template_Search::s($aTemplateTypes, array_keys($aLanguages), false);
		$aPdfTemplates = Ext_Thebing_Util::convertArrayForSelect($aPdfTemplates);
		$aPdfTemplates = Ext_Thebing_Util::addEmptyItem($aPdfTemplates);

		$oDialog = $this->_oGui->createDialog($this->t('Agentur PDF'));
		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->sDialogIDTag = 'AGENCYOVERVIEW_';

		$oDialog->setElement($oDialog->createRow($this->t('PDF-Vorlage'), 'select', [
			'db_column' => 'pdf_template_id',
			'select_options' => $aPdfTemplates,
			'required' => true
		]));

		$oDialog->setElement($oDialog->createRow($this->t('Sprache'), 'select', [
			'db_column' => 'language',
			'select_options' => $aLanguages,
			'default_value' => Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getLanguage(),
			'required' => true
		]));

		/*if(count($aSelectedIds) === 1) {
			$aAgencyContacts = $oAgency->getContacts(true);
			$oDialog->setElement($oDialog->createRow($this->_oGui->t('Mitarbeiter (Platzhalter)'), 'select', [
				'db_column' => 'company_contact_id',
				'select_options' => $aAgencyContacts,
				'multiple' => 5,
				'jquery_multiple' => true,
				'searchable' => true
			]));
		}

		$oDialog->setElement(Ext_Thebing_Gui2_Util::getDateTimeRow($oDialog, [
			'id_1' => 'overview_date',
			'name_1' => 'save[overview_date]',
			'calendar_id' => 'overview_date_img',
			'value_1' => '',
			'name_2'=> 'save[overview_time]',
			'value_2' => ''
		], $this->_oGui->t('Datum (Platzhalter)'), '&nbsp;'.$this->_oGui->t('Zeit')));*/

		return $oDialog;

	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \TsCompany\Service\Import\Agency;
	}
	
	protected function getImportDialogId() {
		return 'AGENCY_IMPORT_';
	}
	
	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {
		
		$oRow = $oDialog->createRow($this->t('Vorhandene Kontakte und Kommentare leeren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'delete_existing']);
		$oDialog->setElement($oRow);
		
		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);
		
	}
	
}
