<?php

/**
 * @property Ext_TS_Inquiry $oWDBasic
 */
class Ext_TS_Enquiry_Gui2 extends Ext_Thebing_Document_Gui2 {

	use \Tc\Traits\Gui2\Import;

	public function _getWDBasicObject($aSelectedIds) {

		/** @var Ext_TS_Inquiry $inquiry */
		$inquiry = parent::_getWDBasicObject($aSelectedIds);

		if (!$inquiry->exist()) {
			// Korrekter Typ muss frühstmöglich gesetzt werden
			$inquiry->type = Ext_TS_Inquiry::TYPE_ENQUIRY;
		}

		return $inquiry;

	}

	public static function getListWhere2(Ext_Gui2 $oGui = null) {

		$aWhere			= array();
		$bIsAllSchools	= Ext_Thebing_System::isAllSchools();
		if(!$bIsAllSchools){
			$oSchool	= Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId	= (int)$oSchool->id;
			$aWhere		= array('school_id' => (string)$iSchoolId);
		}

		$aWhere['type'] = 'enquiry';

		$inboxKeys = array_keys(Ext_Thebing_System::getInboxListForSelect(true, false));

		$mainQuery = new \Elastica\Query\BoolQuery();

		$inboxExistQuery = new \Elastica\Query\BoolQuery();
		$inboxExistQuery->addMustNot(new \Elastica\Query\Exists('inbox'));

		$mainQuery->addShould($inboxExistQuery);

		foreach($inboxKeys as $inboxKey) {
			$inboxQuery = new \Elastica\Query\Term();
			$inboxQuery->setTerm('inbox', $inboxKey);
			$mainQuery->addShould($inboxQuery);
		}

		$aWhere['inbox'] = $mainQuery;

		return $aWhere;

	}

	/**
	 * @param bool $bEmptyEntry
	 * @return array
	 */
	public static function getStatusOptionsList($bEmptyEntry = false) {

		$aOptions = array(
			'unanswered' => L10N::t('unbeantwortet', Ext_TS_Enquiry::TRANSLATION_PATH),
			'due_follow_up' => L10N::t('Nachhaken fällig', Ext_TS_Enquiry::TRANSLATION_PATH),
			'entered_follow_up' => L10N::t('Nachhaken eingetragen', Ext_TS_Enquiry::TRANSLATION_PATH),
			'booked' => L10N::t('gebucht', Ext_TS_Enquiry::TRANSLATION_PATH),
			'not_booked' => L10N::t('nicht gebucht', Ext_TS_Enquiry::TRANSLATION_PATH)
		);

		if($bEmptyEntry) {
			$aOptions = Ext_Gui2_Util::addLabelItem($aOptions, L10N::t('Status'));
		}

		return $aOptions;

	}

	/**
	 * @param string $sType
	 * @return \Elastica\Query\AbstractQuery
	 */
	public static function getStatusOptionQuery($sType) {

		switch($sType) {
			case 'unanswered':

				$oBool = new \Elastica\Query\BoolQuery();

				// Gruppen haben immer nur den type enquiry, da kopiert werden muss
				$oQuery = new \Elastica\Query\Term();
				$oQuery->setTerm('invoice_status', 'enquiry_converted');
				$oBool->addMustNot($oQuery);

				$oQuery = new \Elastica\Query\Exists('last_message_date_original');
				$oBool->addMustNot($oQuery);

				return $oBool;

			case 'entered_follow_up':
			case 'due_follow_up':

				$oBool = new \Elastica\Query\BoolQuery();

				// Gruppen haben immer nur den type enquiry, da kopiert werden muss
				$oQuery = new \Elastica\Query\Term();
				$oQuery->setTerm('invoice_status', 'enquiry_converted');
				$oBool->addMustNot($oQuery);

				$oQuery = new \Elastica\Query\QueryString();
				$oQuery->setQuery('_exists_:follow_up_original');
				$oBool->addMust($oQuery);

				if($sType === 'due_follow_up') {
					$oQuery = new \Elastica\Query\Range('follow_up_original', [
						'lte' => date('Y-m-d')
					]);
					$oBool->addMust($oQuery);
				}

				return $oBool;

			case 'booked':

				$oQuery = new \Elastica\Query\Term();
				//$oQuery->setTerm('type', Ext_TS_Inquiry::TYPE_BOOKING_STRING);
				$oQuery->setTerm('invoice_status', 'enquiry_converted');

				return $oQuery;

			case 'not_booked':

				$oBool = new \Elastica\Query\BoolQuery();

				// Gruppen haben immer nur den type enquiry, da kopiert werden muss
				$oQuery = new \Elastica\Query\Term();
				$oQuery->setTerm('invoice_status', 'enquiry_converted');
				$oBool->addMustNot($oQuery);

				return $oBool;

			default:
				throw new InvalidArgumentException('Invalid status type');
		}

	}
	
	/**
	 * @inheritdoc
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['delete_group'] = L10N::t('Sind Sie sich sicher, dass die Gruppe gelöscht werden soll? Alle Daten im Gruppentab werden unwiederruflich gelöscht.', $sL10NDescription);
		$aData['delete_email_question'] = L10N::t('Möchten Sie die E-Mail-Adresse wirklich löschen?', $sL10NDescription);
		$aData['confirm_change_school'] = L10N::t('Durch das ändern der Schule gehen alle Kurs/Unterkunft/Transfer und Versicherungsdaten verlohren. Sind Sie sicher?', $sL10NDescription);
	
		return $aData;
	}
	
	/**
	 * Achtung, eigene Dialog-Data, wo auch nochmal Logik passiert!
	 *
	 * @see \Ext_TS_Enquiry_Gui2_Dialog_Data::saveEdit()
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $aAction='edit', $bPrepareOpenDialog = true){
		global $_VARS;
		
		$oWDBasic = $this->oWDBasic;
		if(!$oWDBasic) {
			$oWDBasic = $this->_getWDBasicObject($aSelectedIds);
		}

		// Das hat schon vorher nicht mehr funktioniert
//		// Wenn sich die Schule ändert muss es eine Sicherheitsabfrage geben!
//		if(
//			$oWDBasic->id > 0 &&
//			$bSave &&
//			$_VARS['ignore_errors'] == 1
//		){
//			// Referer Daten löschen
//			$oWDBasic->referer_id = 0;
//			// gespeicherte Kombinationen löschen
//			$oWDBasic->cleanJoinedObjectChilds('combinations');
//		}
		
		$sIconKey = self::getIconKey($aAction['action'], $aAction['additional']);
		$oDialog = $this->_getDialog($sIconKey);
		
		// Prüfen ob Gruppendaten gespeichert werden müssen
		$bSaveGroupData = true;
//		if($aSaveData['is_group']['cdb1'] != 1){
		if (!$this->request->input('save.is_group')) {
			$bSaveGroupData = false;
		}

		// Die Gruppenfelder müssen gelöscht werden, wenn die Checkbox nicht gesetzt ist! Da sonst gespeichert und validiert wird
		$aGroupFields = array();
		if(
			$bSave &&					// Ganz Wichtig!!! Sonst werden die Felder auch bei den reload Dialog Tab request gelöscht!
			!$bSaveGroupData
		){
			foreach($oDialog->aSaveData as $iKey => $aField){
				if(
					$aField['db_alias'] == 'group'
				){
					$aGroupFields[$iKey] = $aField;
					unset($oDialog->aSaveData[$iKey]);
				}
			}
		}

		// Kundensuche: Kontakt ersetzen
		if($bSave && $_VARS['replaceCustomerId'] > 0) {
			// Es gibt nur einen Eintrag in der Tabelle (Booker sind eh kaputt); Gruppenkontakte sind der Gruppe zugewiesen
			$oReplaceContact = Ext_TS_Inquiry_Contact_Traveller::getInstance((int)$_VARS['replaceCustomerId']);
			$oWDBasic->removeJoinTableObject('travellers', $oWDBasic->getCustomer());
			$oWDBasic->addJoinTableObject('travellers', $oReplaceContact);
		}

//		// Mit Buchung verknüpfen
//		$bReplaceCustomerAfterwards = false;
//		if(
//			$bSave &&
//			empty($aData['error']) &&
//			!empty($aSaveData['autocomplete_inquiry_id']) &&
//			!$oWDBasic->isConvertedToInquiry()
//		) {
//			$bReplaceCustomerAfterwards = true;
//			$oInquiry = Ext_TS_Inquiry::getInstance((int)$aSaveData['autocomplete_inquiry_id']);
//			if(!$oInquiry->exist()) {
//				throw new RuntimeException('Wrong inquiry given for linking enquiry to inquiry!');
//			}
//
//			$aInquiryIds = $oWDBasic->inquiries;
//			$aInquiryIds[] = $oInquiry->id;
//			$oWDBasic->inquiries = $aInquiryIds;
//		}

		if($bSave) {
			$oRequest = new MVC_Request();
	        $oRequest->add($_VARS);
			Ext_TS_Inquiry_Saver_Traveller::prepareEmailsStatic($oRequest, $oWDBasic->getFirstTraveller());
		}

		$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $aAction, $bPrepareOpenDialog);

//		// Kontakt der Anfrage soll mit dem aus der Buchung ersetzt werden
//		// Erst hier machen, da ansonsten die Daten des anderen Kunden überschrieben werden
//		// Bei der Kundensuche funktioniert das alles so toll, weil da ganz viel starres JavaScript ausgeführt wird…
//		if($bReplaceCustomerAfterwards) {
//			$oInquiry = Ext_TS_Inquiry::getInstance((int)$aSaveData['autocomplete_inquiry_id']);
//			$oContact = $oInquiry->getCustomer();
//			$oContact = Ext_TS_Enquiry_Contact_Traveller::getInstance($oContact->id);
//			$oWDBasic->replaceFirstTraveller($oContact);
//			$oWDBasic->save();
//
//			// Dialog-Daten müssen wegen dem ersetzten Kunden neu geladen werden
//			$aData['data'] = $this->prepareOpenDialog($aAction['action'], $aSelectedIds, false, $sAdditional, true);
//		}

		foreach((array)$aData['error'] as $iKey => $aError) {
			if(
				is_array($aError) && (
					$aError['input']['dbalias'] == 'travellers' ||
					$aError['input']['dbalias'] == 'bookers'
				)
			) {
				// save[birthday][cdb1]
				$aData['error'][$iKey]['error_id'] = null;
				$aData['error'][$iKey]['input']['name'] = 'save['.$aError['input']['dbcolumn'].'][cdb1]';
			} elseif(
				is_array($aError) &&
				$aError['input']['dbalias'] === 'tc_e' &&
				$aError['input']['dbcolumn'] === 'email'
			) {
				$aData['error'][$iKey]['error_id'] = null;
				$aData['error'][$iKey]['input']['name'] = 'contact_email['.$aError['id'].']';
			} elseif(
				is_array($aError) &&
				strpos($aError['identifier'], 'group') !== false &&
				strpos($aError['identifier'], 'contacts') !== false
			) {
				// Funktioniert irgendwie nicht, daher Felder umschreiben
				preg_match('/.+contacts\[(\d+)\].+\.(.+)/', $aError['identifier'], $aMatches);
				$aData['error'][$iKey]['error_id'] = null;
				$aData['error'][$iKey]['input']['name'] = 'save['.$aMatches[2].'][group]['.$aMatches[1].'][contacts]';
			}
		}

		// Felder wieder  hinzufügen, da beim erneuten speichern die checkbox evtl. gesetzt ist und dann mitgespeichert werden sollen
		foreach($aGroupFields as $iKey => $aField){
			$oDialog->aSaveData[$iKey] = $aField;
		}
		
		return $aData;
	}

	public function switchAjaxRequest($_VARS) {

		if($_VARS['task'] === 'searchForSameUser') {

			$oInquiry = $this->getSelectedObject();
			$oSchool = $oInquiry->getSchool();

			$aSearchData = [
				'lastname' => $_VARS['lastname'],
				'firstname' => $_VARS['firstname'],
				'birthday' => Ext_Thebing_Format::ConvertDate($_VARS['bday'], $oSchool->id, true)
			];

			$aTransfer = [
				'action' => 'resultSearchForSomeUser',
				'error' => [],
				'data' => [
					'id' => 'ID_'.$oInquiry->id,
					'searchResult' => Ext_Thebing_Customer_Search::search($aSearchData, $oSchool->id)
				]
			];

			echo json_encode($aTransfer);
			$this->_oGui->save();

		} else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}

	/**
	 * @inheritdoc
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

//		foreach($aSaveData as &$aField) {
//			if($aField['db_column'] == 'autocomplete_inquiry_id') {
//				if($this->oWDBasic->isConvertedToInquiry()) {
//					// Bei einer Gruppe gibt es zwar mehrere IDs, das kann man aber nachher ohnehin nicht mehr bearbetien
//					$aField['value'] = reset($this->oWDBasic->inquiries);
//				}
//				break;
//			}
//		}

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		if(!$this->oWDBasic) {
			$this->getWDBasicObject($aSelectedIds);
		}

		if(Ext_Thebing_Access::hasRight('thebing_invoice_sales_person')) {

			// Der Wert 0 bedeutet dass nicht mehr gesucht werden soll (Wert wurde schon gespeichert).
			if($this->oWDBasic->id === 0) {

				foreach($aData as &$aSaveFields) {
					if(
						$aSaveFields['db_alias'] === 'ts_i' &&
						$aSaveFields['db_column'] === 'sales_person_id'
					) {
						$aSaveFields['value'] = $this->oWDBasic->allocateSalesperson(true);
						break;
					}
				}
			}
		}

		return $aData;
	}

	/**
	 * @inheritdoc
	 */	
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false){

		$inquiry = $this->getSelectedObject();

		$journeys = collect($inquiry->getJourneys())->filter(function (Ext_TS_Inquiry_Journey $journey) {
			return $journey->type !== 'dummy';
		});

		/** @var \Illuminate\Support\Collection $offers */
		$offers = $journeys->reduce(function (\Illuminate\Support\Collection $offers, Ext_TS_Inquiry_Journey $journey) {
			if (($document = $journey->getDocument()) !== null) {
				$offers[] = $document;
			}
			return $offers;
		}, collect());

		/** @var Ext_Gui2_Dialog_Tab $groupTab */
		$groupTab = end($oDialogData->aElements);
		$groupTab->bReadOnly = false;

		if (
			$inquiry->isConverted() ||
			$offers->isNotEmpty()
		) {
			$groupTab->bReadOnly = true;
		}
		
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		// Information mitschicken ob die Anfrage eine Gruppe hat		
		$aData['group_id'] = $inquiry->group_id;
		
		// Währung ist nur abänderbar, wenn noch keine Kombination erstellt wurde
		// Deshalb die Information mitschicken ob Kombinationen erstellt wurden		
		$aData['combination_count'] = $journeys->count();
	
		// Zahlungsmethode/Agentur ist nur abänderbar, wenn noch kein Angebot erstellt wurde
		// Deshalb die Information mitschicken ob Angebote erstellt wurden		
		$aData['offer_count'] = $offers->count();

		// Wird benötigt, damit das Schul-Feld nicht einfach gesperrt wird, weil der Wert nicht da ist
		if (Ext_Thebing_System::isAllSchools()) {
			$aData['all_school'] = 1;
		}

		// Mit Buchung verknüpfen (Autocomplete) im JS deaktivieren
//		$aData['enquiry_converted'] = $oWDBasic->exist() && $oWDBasic->isConvertedToInquiry();

		// E-Mails für (manuellen) wiederholbaren Bereich
		\Ext_Thebing_Inquiry_Gui2::setEditDialogDataContactEmails($inquiry, $aData);

		return $aData;
	}

	
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null){

		if($sError === 'FIRSTNAME_AND_LASTNAME_EMPTY') {
			$sMessage = $this->t('Mindestens eines der Felder muss ausgefüllt sein: Vorname, Nachname');
		} elseif($sError === 'ENQUIRY_SCHOOL_CHANGE') {
			$sMessage = $this->t('Kombinations- und Referrer-Daten gehen verloren, wenn die Schule gewechselt wird.');
		} elseif ($sError == 'CURRENCY_NOT_DEFINED') {
			$sMessage = $this->t('Währung nicht definiert. Bitte speichern Sie die Anfrage neu!');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}
	
	/**
	 *
	 * @param string $sIconAction
	 * @param array $aSelectedIds
	 * @param int $iTab
	 * @param string $sAdditional
	 * @param bool $bSaveSuccess
	 * @return array 
	 */
	
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$aData2 = [];

		// Anfrage zu Buchung umwandeln
		if ($sIconAction === 'convert_enquiry_to_inquiry') {
			$aEnquiries = array_map(function ($iSelectedId) {
				return Ext_TS_Inquiry::getInstance($iSelectedId);
			}, $aSelectedIds);

			$oDialog = (new \Ext_TS_Enquiry_Gui2_Dialog_Convert($aEnquiries))->create($this->_oGui);
			$this->aIconData[$sIconAction]['dialog_data'] = $oDialog;

			$aData2['default_inbox_id'] = key(Ext_Thebing_System::getInboxList('use_id', true));
		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
		
		//Agentur Zahlmethoden, Kommentar zu den Zahlmethoden & Währungen mitschicken, 
		//weil bei Agenturwechsel müssen die Daten aktualisiert werden
		$aData['agency_payment_method'] = array();
		$aData['agency_currency'] = array();
		
		$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies();
		foreach($aAgencies as $aAgency) {
			$aData['agency_payment_method'][$aAgency['id']] = array('id'=>$aAgency['ext_26'], 'comment' =>$aAgency['ext_38']);
			$aData['agency_currency'][$aAgency['id']] = $aAgency['ext_23'];
		}

		$aData = array_merge($aData, $aData2);

		return $aData;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		// Dialog: Ext_TS_Enquiry_Gui2_Dialog_Convert
		if ($sAction === 'convert_enquiry_to_inquiry') {

			$oInbox = Ext_Thebing_Client_Inbox::getInstance($aData['inbox_id']);

			foreach ($aSelectedIds as $iSelectedId) {
				$oInquiry = Ext_TS_Inquiry::getInstance($iSelectedId);
				$oConvert = new Ext_TS_Enquiry_Convert2($oInquiry);
				$oConvert->setInbox($oInbox);
				$oConvert->convert();
			}

			return [
				'action' => 'closeDialogAndReloadTable',
				'data' => [
					'id' => 'CONVERT_'.\Illuminate\Support\Arr::first($this->request->input('id')),
					'options' => [
						'close_after_save' => false
					]
				]
			];

		}

		return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Enquiry;
	}

	protected function getImportDialogId() {
		return 'ENQUIRY_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {

		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Fehler überspringen'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'skip_errors']);
		$oDialog->setElement($oRow);

	}

	public function _validateFlexField($aFieldData, $mValue) {

		if(
			$aFieldData['section_id'] == 48 ||
			$aFieldData['section_id'] == 33
		) {
//			if($_VARS['save']['is_group']['cdb1'] != 1) {
			if ($this->request->input('save.is_group')) {
				return [];
			}
		}

		return parent::_validateFlexField($aFieldData, $mValue);

	}

	protected function deleteRowHook($iRowId) {

		$oInquiry = $this->getSelectedObject();

		if ($oInquiry->hasGroup()) {
			$oInquiry->getGroup()->delete();
		}

	}

	public function getFlexEditDataHTML(Ext_Gui2_Dialog $oDialog, $mSection, $mId, $iReadOnly = 0, $iDisabled = 1, $sFieldIdentifier = 'flex') {

		// Flex-Felder gehören anderer Entität, steht aber alles im selben Dialog
		if (
			$mSection === 'groups_enquiries_bookings' &&
			$this->oWDBasic->hasGroup()
		) {
			$mId = $this->oWDBasic->getGroup();
		}

		return parent::getFlexEditDataHTML($oDialog, $mSection, $mId, $iReadOnly, $iDisabled, $sFieldIdentifier);

	}

	protected function saveEditDialogDataFlex(Ext_Gui2_Dialog $oDialog, $iId, array $aSaveDataFlex, $sItemType = '') {

		$aGroupFieldIds = array_column(Ext_TC_Flexibility::getFields('groups_enquiries_bookings'), 'id');

		// Flex-Felder gehören anderer Entität, steht aber alles im selben Dialog und GUI ballert alles auf einmal in selbe Struktur
		// Eigentlich steht die Entitäts-ID auch nochmal in $aSaveDataFlex, aber bei neuen Einträgen ist diese natürlich 0
		$aSaveDataFlex2 = [];
		foreach ($aSaveDataFlex as $iId2 => $aFields) {
			foreach ($aFields as $iFieldId => $mValue) {
				if (in_array($iFieldId, $aGroupFieldIds)) {
					$aSaveDataFlex2[$iId2][$iFieldId] = $mValue;
					unset($aSaveDataFlex[$iId2][$iFieldId]);
				}
			}
		}

		$aReturn = parent::saveEditDialogDataFlex($oDialog, $iId, $aSaveDataFlex, $sItemType);

		if ($this->oWDBasic->hasGroup()) {
			$aReturn = array_merge($aReturn, parent::saveEditDialogDataFlex($oDialog, $this->oWDBasic->getGroup()->id, $aSaveDataFlex2));
		}

		return $aReturn;

	}

}