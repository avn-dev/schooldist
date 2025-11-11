<?php

class Ext_TS_Inquiry_Saver_Inquiry extends Ext_TS_Inquiry_Saver_Abstract {

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $_oObject;

	/**
	 * @var Ext_TS_Inquiry_Saver_Journey
	 */
	protected $_oJourneySaver;

	protected $iReplaceCustomerId;
	protected $iReplaceBookerId;
	protected $oReplacedContact;
	protected $_aRoomSharingItems = array();

	/** @var SplObjectStorage */
	protected $oSponsoringGuranteesUploads;

	/**
	 * @var null|Ext_TC_NumberRange
	 */
	public $oNumberRange = null;

	public function _prepare() {

		$aInquiry = (array)$this->_oRequest->input('id');
		$iInquiry = reset($aInquiry);
		$oObject = Ext_TS_Inquiry::getInstance($iInquiry);
		$this->setObject($oObject, 'ki');
		$this->setInbox();
		$this->prepareRequestFlags();
		$this->prepareContacts();
		$this->prepareJourney();
		$this->prepareMatchingDetails();
		$this->prepareVisum();
		$this->prepareHolidays();
		$this->prepareSponsoringGurantees();

		$aHookData = [
			'inquiry' => $oObject,
			'saver' => $this,
			'request' => $this->_oRequest
		];

		System::wd()->executeHook('ts_inquiry_saver_prepare', $aHookData);

	}

	/**
	 * bereitet alle "Flags" vor welche Einfluss auf das Speichern haben
	 * und im Request übergeben wurden
	 * - Kunde ersetzen
	 */
	public function prepareRequestFlags() {

		if(!empty($this->_oRequest->get('replaceCustomerId'))) {
			$this->iReplaceCustomerId = (int)$this->_oRequest->get('replaceCustomerId');
		}

		if(!empty($this->_oRequest->get('replaceBookerId'))) {
			$this->iReplaceBookerId = (int)$this->_oRequest->get('replaceBookerId');
		}

		if(
			$this->_oObject->isNewCustomerNumberNeeded() &&
			$this->_oRequest->get('task') != 'update_select_options'
		) {

			$iIgnoreErrors = $this->_oRequest->get('ignore_errors');

			if(
				$iIgnoreErrors == 1 &&
				!$this->hasErrors()
			) {
				$oNumber = new Ext_Thebing_Customer_CustomerNumber($this->_oObject);
				$aNumberErrors = $oNumber->saveCustomerNumber(true);
				foreach($aNumberErrors as $sError){
					$aError = array('message' => $sError);
					$this->addError($aError);
				}
			} else if(
				!$this->hasErrors() &&
				$iIgnoreErrors === null
			) {
				$aError = array(
					'message' => $this->_oGui->t('Soll die Kundennummer neu generiert werden?'),
					'input' => array(
						'dbcolumn' => 'agency_id',
						'dbalias' => 'ki'
					),
					'type' => 'hint',
					'hintMessage' => L10N::t('ja, neugenerieren!')
				);
				$this->addError($aError);
			}

		}

		$this->_aRoomSharingItems = $this->_oRequest->input('roomSharingSelectedItems');

	}

	public function prepareContacts() {
		$this->prepareTraveller();
		$this->prepareBooker();
		$this->prepareEmergencyContact();
	}

	public function prepareJourney() {
		$oObject = $this->_oObject->getJourney();
		$oSaver = new Ext_TS_Inquiry_Saver_Journey($this->_oRequest, $this->_oGui);
		$oSaver->setInquiry($this->_oObject);
		$oSaver->setObject($oObject, 'ts_ij');
		$this->_oJourneySaver = $oSaver;
		$this->_mergeErrors($oSaver);
	}

	public function prepareTraveller() {

		// TODO Hier fehlt wohl der Booker, aber das ist eh kaputt und es gibt nur einen Traveller pro Buchung
		if($this->iReplaceCustomerId) {
			$oNewCustomer = Ext_TS_Inquiry_Contact_Traveller::getInstance($this->iReplaceCustomerId);
			$aTravellers = (array)$this->_oObject->getJoinTableObjects('travellers');
			foreach($aTravellers as $oTraveller) {
				$this->_oObject->removeJoinTableObject('travellers', $oTraveller);
				$this->oReplacedContact = $oTraveller;
			}
			$this->_oObject->addJoinTableObject('travellers', $oNewCustomer);
			$this->_oObject->resetFirstTraveller();
		}

		// Saver starten
		$oContact = $this->_oObject->getTraveller();
		$oSaver = new Ext_TS_Inquiry_Saver_Traveller($this->_oRequest, $this->_oGui);
		$oSaver->setObject($oContact, 'cdb1');

		$this->checkContactNumber($oContact);
		
	}

	protected function checkContactNumber($oContact, $onlyIfNumberrange=false) {
		
		if(
			$this->_bSave &&
			$oContact->getCustomerNumber() == ""
		) {
			
			$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($this->_oObject);
			$oCustomerNumber->setCustomer($oContact);

			// Eigentlich holt Ext_Thebing_Customer_CustomerNumber die Schule selbst.
			// Zu dem Zeitpunkt gibt es aber noch keine Journeys und die Schule wird dann aus der Session geholt.
			// Das funktioniert natürlich nicht in der AllSchools-Ansicht! R-#5207
			$oSchool = Ext_Thebing_School::getInstance($this->_oRequest->get('school_for_data'));
			$oCustomerNumber->setSchool($oSchool);

			$oNumberrange = $oCustomerNumber->generateNumberRangeObject(false);

			$this->oNumberRange = $oNumberrange;

			if(!$oNumberrange || !$oNumberrange->acquireLock()){
				$sError = Ext_TC_NumberRange::getNumberLockedError();
				if($sError){
					$aError = array();
					$aError['message']	= $sError;
					$this->addError($aError);
				}
			} else {
				
				// Wenn Nummernkreis nicht existiert (id=0) und keine Pflicht, dann nix machen
				if(
					$onlyIfNumberrange === true &&
					!$oNumberrange->exist()
				) {
					return;
				}
				
				$sNumber = $oNumberrange->generateNumber();
				$oContact->saveCustomerNumber($sNumber, $oNumberrange->id, false, false);
			}
		}
		
	}


	public function prepareBooker() {
		
		if($this->iReplaceBookerId) {
			$newCustomer = Ext_TS_Inquiry_Contact_Booker::getInstance($this->iReplaceBookerId);
			$booker = (array)$this->_oObject->getJoinTableObjects('bookers');
			foreach($booker as $booker) {
				$this->_oObject->removeJoinTableObject('bookers', $booker);
				#$this->oReplacedContact = $oTraveller;
			}
			$this->_oObject->addJoinTableObject('bookers', $newCustomer);
		}
		
		// Saver starten
		$booker = $this->_oObject->getBooker();
		
		if($booker === null) {
			
			$isEmpty = $this->checkRequestForObjectData(['tc_bc', 'tc_a_b']);
			
			if(!$isEmpty) {
				$booker = $this->_oObject->getJoinTableObject('bookers');
			}
		}
		
		// Keinen leeren Booker speichern
		if($booker !== null) {
			
			$oSaver = new Ext_TS_Inquiry_Saver_Booker($this->_oRequest, $this->_oGui);
			$oSaver->setObject($booker, 'tc_bc');
			
			$this->checkContactNumber($booker, true);
			
		}

	}

	public function prepareEmergencyContact() {
		
		$oSaver = new Ext_TS_Inquiry_Saver_EmergencyContact($this->_oRequest, $this->_oGui);
		$oSaver->setObjects($this->_oObject, 'tc_c_e');
		
	}

	public function prepareHolidays() {

		// Wenn Ferien eingetragen wurden, aber auf löschen geklickt wird, würden die anderen Ferien direkt gespeichert
		if($this->_oRequest->has('dontSaveCourseAndAcco')) {
			return;
		}

		$aHolidays      = $this->_oRequest->input('holidays');

		// muss immer hier rein kommen da die Splittungsklasse auch die Schulferien macht
		//if(!empty($aHolidays) && $aHolidays['new']['from'] != ""){

			$oSplitter = new Ext_TS_Inquiry_Journey_Holiday_Split($this->_oObject->getJourney());

			$aFinalHolidays = array();

			if(!empty($aHolidays['new']['from']) && !empty($aHolidays['new']['until'])){
				$aHolidays['new']['from'] = $this->_oDateFormat->convert($aHolidays['new']['from']);
				$aHolidays['new']['until'] = $this->_oDateFormat->convert($aHolidays['new']['until']);
				$aFinalHolidays[] = $aHolidays['new'];
				$oHoliday = $this->_oObject->getJoinedObjectChild('holidays');
				$oHoliday->type = 'student';
				$oHoliday->from = $aHolidays['new']['from'];
				$oHoliday->until = $aHolidays['new']['until'];
				$oHoliday->weeks = $aHolidays['new']['weeks'];

				$oSplitter->setServiceHolidays($oHoliday, $aFinalHolidays);
			}

			$oSplitter->split();

			$iIgnoreErrors = (int)$this->_oRequest->get('ignore_errors');

			$aErrors = $oSplitter->getErrors();

			foreach($aErrors as $aError){
				if($aError['type'] == 'error' || $iIgnoreErrors != 1){
					$this->addError($aError);
				}
			}

		//}

		$this->_oJourneySaver->checkCourseData();
		$this->_oJourneySaver->checkAccommodationData();
		$this->_mergeErrors($this->_oJourneySaver);

	}

	protected function prepareSponsoringGurantees() {

		$this->oSponsoringGuranteesUploads = new SplObjectStorage();

		$aGurantees = (array)$this->_oRequest->input('sponsoring_gurantee');

		foreach($aGurantees as $iGuranteeId => $aGurantee) {

			/** @var TsSponsoring\Entity\InquiryGuarantee $oGurantee */
			$oGurantee = $this->_oObject->getJoinedObjectChild('sponsoring_guarantees', $iGuranteeId);
			$oGurantee->from = Ext_Thebing_Format::ConvertDate($aGurantee['from'], $this->_oSchoolForFormat->id, 1, true);
			$oGurantee->until = Ext_Thebing_Format::ConvertDate($aGurantee['until'], $this->_oSchoolForFormat->id, 1, true);
			$oGurantee->number = $aGurantee['number'];

			if(
				empty($oGurantee->from) &&
				empty($oGurantee->until) &&
				empty($aGurantee['path'])
			) {
				$this->_oObject->removeJoinedObjectChildByKey('sponsoring_guarantees', $iGuranteeId);
			} else {
				if($aGurantee['path'] instanceof Illuminate\Http\UploadedFile) {
					$this->oSponsoringGuranteesUploads[$oGurantee] = $aGurantee['path'];
				}
			}

		}

		$aAllGurantees = $this->_oObject->getJoinedObjectChilds('sponsoring_guarantees', true);
		foreach($aAllGurantees as $oGurantee) {
			if($this->_oRequest->input('deleted.sponsoring_guarantee.'.$oGurantee->id)) {
				$oGurantee->active = 0;
			}
		}

	}

	protected function beforeSave() {
		parent::beforeSave();
		$this->handleUploads();
	}

	/**
	 * @return bool
	 */
	public function _save() {

		$bSuccess = parent::_save();

		return $bSuccess;
	}

	/**
	 * @param bool $bSave
	 * @return bool
	 */
	public function _finish($bSave) {

		$aSelectedIds = $this->_oRequest->input('id');
		if(empty($aSelectedIds)) {
			$iSelectedId = 0;
		} else {
			$iSelectedId = (int)reset($aSelectedIds);	
		}

		$bSuccess = parent::_finish($bSave);

		if(
			$bSave &&
			!$this->hasErrors()
		) {
			
			$this->_oObject->findSpecials(true);
			
			// Validierung findet bereits in Ext_TC_Gui2_Data::saveDialogData() statt
			$aFlexData = (array)$this->_oRequest->input('flex')[$iSelectedId];
			Ext_TC_Flexibility::saveData($aFlexData, $this->_oObject->id);

			$this->_oObject->removeRoomSharingCustomers();

			// Die zusammenreisenden Schüler erst hier speichern, da Inquiry-Id benötigt wird
			if(!empty($this->_aRoomSharingItems)) {
				$this->_oObject->saveRoomSharingCustomers($this->_aRoomSharingItems);
			}

			$this->_oJourneySaver->addJourneySaveWarnings($this->aWarnings);

			$this->handleSponsoringGuranteesUploads();

			$this->_oJourneySaver->finish($bSave);

			$this->updateDocumentItemContactIds();
			
		}

		return $bSuccess;

	}

	/**
	 * Wenn man bei einer Buchung den Traveller austauscht, dann muss die ID in den Items der Rechnungen auch ausgetautscht werden.
	 * Das ist wichtig, da sonst beim Umwandeln einer Proforma die Items nicht kopiert werden wenn die contact_id nicht übereinstimmt.
	 */
	protected function updateDocumentItemContactIds() {
		
		if($this->oReplacedContact) {
		
			$contact = $this->_oObject->getTraveller();

			$items = Ext_Thebing_Inquiry_Document_Version_Item::query()
				->select('kidvi.*')
				->join('kolumbus_inquiries_documents as kid', 'kid.latest_version', '=', 'kidvi.version_id')
				->where('kidvi.contact_id', $this->oReplacedContact->id)
				->where('kid.entity', Ext_TS_Inquiry::class)
				->where('kid.entity_id', $this->_oObject->id)
				->get();
			
			$items->each(function (Ext_Thebing_Inquiry_Document_Version_Item $item) use($contact) {
				$item->contact_id = $contact->id;
				$item->updateField('contact_id');
			});
			
		}
		
	}
		
	protected function handleUploads() {

		$oTraveller = $this->_oObject->getFirstTraveller();

		$aDelete = (array)$this->_oRequest->input('delete');
		foreach($aDelete as $sColumn => $aDeleteFiles) {
			foreach($aDeleteFiles as $sAlias=>$sFile) {
				if(!empty($sFile)) {
					if($sColumn === 'upload1') {
						$mDelete = $oTraveller->deletePhoto($sFile);
					} else if($sColumn === 'upload2') {
						$mDelete = $oTraveller->deletePassport($sFile);
					} else if(strpos($sColumn, 'studentupload_') !== false) {
						$mDelete = $oTraveller->deleteStudentUpload($sFile, $this->_oObject->id);
					}
				}
				if(!empty($mDelete)) {
					$this->addError(array('message' => $mDelete));
				}
			}
		}

		$aReleasedForSLCheckboxes = (array)$this->_oRequest->input('save')['studentupload_released_sl'];

		// In Variable schreiben, da Magic Getter keine Referenzen zurück liefert
		$aFlexUploads = $this->_oObject->flex_uploads;

		$aFiles = (array)$_FILES['save']['name'];
		
		// $_FILES ist immer vorhanden, daher wird das hier immer durchlaufen!
		foreach($aFiles as $sColumn => $aUploads) {
			foreach($aUploads as $sAlias => $sFileName) {
				$sTmpPath = $_FILES['save']['tmp_name'][$sColumn][$sAlias];

				if($sColumn === 'upload1') {
					$aFlexConfig = ['static', 1];
					$sError = $oTraveller->savePhoto($sFileName, $sTmpPath);
					$sExistsPath = $oTraveller->getPhoto();
				} else if($sColumn === 'upload2') {
					$aFlexConfig = ['static', 2];
					$sError = $oTraveller->savePassport($sFileName, $sTmpPath);
					$sExistsPath = $oTraveller->getPassport();
				} else if(strpos($sColumn, 'studentupload_') !== false) {
					$aFlexConfig = ['flex', (int)str_replace('studentupload_', '', $sColumn)];
					$sError = $oTraveller->saveStudentUpload($sFileName, $sTmpPath, $aFlexConfig[1], $this->_oObject->id);
					$sExistsPath = $oTraveller->getStudentUpload($aFlexConfig[1], $oTraveller->getSchool()->id, $this->_oObject->id);
				} else {
					continue;
				}

				if(!empty($sError)) {
					$this->addError(array('message' => $sError));
				} else {
					if(Ext_Thebing_Access::hasRight('thebing_release_documents_sl')) {
						// ts_inquiries_flex_uploads aktualisieren: Key im Array suchen
						$iFlexUploadEntryKey = $this->_oObject->searchFlexUploadEntryForUpload($aFlexConfig[0], $aFlexConfig[1], true);

						// $sExistsPath benutzen, da das hier auch ausgeführt wird, wenn eine Datei gelöscht wurde
						if(empty($sExistsPath)) {
							// Wenn Datei nicht existiert: Wert löschen
							if($iFlexUploadEntryKey !== null) {
								unset($aFlexUploads[$iFlexUploadEntryKey]);
							}
						} else {
							// Wenn Datei existiert: Wert aktualisieren oder ergänzen (nur dann, sonst müllt die Datenbank zu!)
							$iCheckboxValue = (int)$aReleasedForSLCheckboxes[$aFlexConfig[0]][$aFlexConfig[1]];
							if($iFlexUploadEntryKey !== null) {
								$aFlexUploads[$iFlexUploadEntryKey]['released_student_login'] = $iCheckboxValue;
							} else {
								$aFlexUploads[] = [
									'type' => $aFlexConfig[0],
									'type_id' => $aFlexConfig[1],
									'released_student_login' => $iCheckboxValue
								];
							}
						}
					}
				}
			}
		}

		$this->_oObject->flex_uploads = $aFlexUploads;

	}

	protected function handleSponsoringGuranteesUploads() {

		if(empty($this->oSponsoringGuranteesUploads)) {
			return;
		}

		Util::checkDir($this->_oObject->getSchool()->getSchoolFileDir().'/inquiry_sponsoring');

		foreach($this->oSponsoringGuranteesUploads as $oGurantee) {

			/** @var Illuminate\Http\UploadedFile $oFile */
			$oFile = $this->oSponsoringGuranteesUploads[$oGurantee];
			$sFileName = Util::getCleanFilename($oGurantee->id.'_'.$oFile->getClientOriginalName());
			$oFile->storeAs($this->_oObject->getSchool()->getSchoolFileDir(false, false).'/inquiry_sponsoring', $sFileName);

			$oGurantee->path = $sFileName;
			$oGurantee->save();

		}

	}

	public function prepareMatchingDetails() {
		$oMatching = $this->_oObject->getMatchingData();
		$oSaver = new Ext_TS_Inquiry_Saver_Basic($this->_oRequest, $this->_oGui);
		$oSaver->setObject($oMatching, 'ts_i_m_d');
	}

	public function prepareVisum() {

		$oJourney = $this->_oObject->getJourney();
		$oContact = $this->_oObject->getCustomer();

		// Visum muss auf zu ersetzenden Kontakt umgeschrieben werden, da es nur ein Visum pro Journey gibt
		if($this->oReplacedContact !== null) {
			$oVisa = Ext_TS_Inquiry_Journey_Visa::searchData($oJourney, $this->oReplacedContact, false);
			// Visa-Object ist optional
			if($oVisa) {
				$oVisa->setJoinedObject('traveller', $oContact);
			}
		} else {
			$oVisa = Ext_TS_Inquiry_Journey_Visa::searchData($oJourney, $oContact, false);
		}

		if($oVisa === null) {
			$isEmpty = $this->checkRequestForObjectData(['ts_ijv']);
			if(!$isEmpty) {
				$oVisa = Ext_TS_Inquiry_Journey_Visa::searchData($oJourney, $oContact, true);
			}
		}
		
		if($oVisa !== null) {
			$oSaver = new Ext_TS_Inquiry_Saver_Visa($this->_oRequest, $this->_oGui);
			$oSaver->setObject($oVisa, 'ts_ijv');
		}

	}

	public function setInbox() {
		$aInboxData = $this->_oGui->getDataObject()->getInboxData();
		if (
			empty($this->_oObject->inbox) &&
			$aInboxData['short'] != ''
		) {
			$this->_oObject->inbox = $aInboxData['short'];
		} 
	}

}
