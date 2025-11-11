<?php

class Ext_TS_Frontend_Combination_Enquiry extends Ext_TS_Frontend_Combination_Inquiry_Abstract {

	/**
	 * @var string
	 */
	const MAIL_APPLICATION_NAME = 'enquiry_form';

    /**
     * {@inheritdoc}
     */
    protected function _default() {

        parent::_default();
        $this->_assign('sFormType', 'form-enquiry');
		
    }
	
	/**
	 * @todo Spam protection ausbauen! Das hier ist weder sicher, noch performant! (ReCaptcha?)
	 * 
	 * @return array
	 */
	protected function _submitAjax() {
		
		$aRequest = $this->_oRequest->getAll();
		
		try {
			array_walk_recursive($aRequest, array($this, 'checkInput'));
		} catch(Exception $e) {
			
			$aResult = [];
			$aResult['result'] = 'error';
			$aResult['form_errors'] = [$e->getMessage()];

			return $aResult;
		}
	
		$aResult = parent::_submitAjax();
	
		return $aResult;
	}
	
	public function checkInput($mElement, $mKey) {

		if(is_scalar($mElement)) {
			
			/*
			 * Diese beiden Abfragen basieren auf Erfahrungswerten aus #13587
			 * @todo Das macht so statisch natürlich keinen Sinn. Entweder man baut ein Captcha ein oder löst das hier 
			 * über eine Lib.
			 */
			if(
				stripos($mElement, '100%') !== false ||
				stripos($mElement, '.cn') !== false ||
				stripos($mElement, '.vip') !== false ||
				stripos($mElement, '@qq.com') !== false
			) {
				throw new Exception('Invalid content!');
			}
			
		}
		
	}

	/**
	 * @return Ext_TS_Enquiry|array
	 * @throws Exception
	 */
	protected function createObject() {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$sLanguage = $this->requireLanguage();
		$oCurrency = $this->getFormCurrency();

		if($this->oLog === null) {
			throw new RuntimeException('No frontend log for online enquiry!');
		}

//		$oEnquiry = new Ext_TS_Enquiry();
//		$oEnquiry->frontend_log_id = $this->oLog->id;
//		$oEnquiry->school_id = $oSchool->id;
//		$oEnquiry->currency_id = $oCurrency->id;
//		$oEnquiry->payment_method = 1; // 1 = brutto, sonst sind bei der PDF-Generierung die falschen Spalten bei den Items

		$oEnquiry = new \Ext_TS_Inquiry();
		$oEnquiry->type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
		$oEnquiry->currency_id = $oCurrency->id;
		$oEnquiry->payment_method = 1;
		$oEnquiry->frontend_log_id = $this->oLog->id;

		$oJourney = $oEnquiry->getJourney();
		$oJourney->school_id = $oSchool->id;
		$oJourney->productline_id = $oSchool->getProductLineId();
		$oJourney->type = \Ext_TS_Inquiry_Journey::TYPE_REQUEST;

		$oContact = $oEnquiry->getCustomer();
		$this->setContactData($oContact, $sLanguage);

		$this->setObjectDataFromBlocks($oEnquiry);

//		/** @var Ext_TS_Enquiry_Combination $oCombination */
//		$oCombination = $oEnquiry->getJoinedObjectChild('combinations');
//		$aCombinationErrors = $this->setCombinationDataFromBlocks($oCombination);
		$aCombinationErrors = $this->setJourneyDataFromBlocks($oJourney);

		// Es soll möglich sein eine Anfrage ohne Leistung abzuschicken, daher darf es dann auch keine Kombination geben
		$bHasServices = true;
		if(!empty($aCombinationErrors['no_services'])) {
			$bHasServices = false;
			$oJourney->type = \Ext_TS_Inquiry_Journey::TYPE_DUMMY;
//			$oEnquiry->deleteJoinedObjectChild('combinations', $oCombination);
		}

		unset($aCombinationErrors['no_services']);
		if(!empty($aCombinationErrors)) {
			return $aCombinationErrors;
		}

		$aFlexValues = $this->getFlexValuesFromBlocks();

		$oCustomerNumberService = new Ext_Thebing_Customer_CustomerNumber($oEnquiry);
		$oCustomerNumberService->saveCustomerNumber(false, false);

		// Objekte sind noch nicht gespeichert!
		$aHookData = ['combination' => $this, 'object' => $oEnquiry, 'flex_values' => $aFlexValues];
		System::wd()->executeHook('ts_frontend_combination_enquiry_save', $aHookData);

		$mResult = $oEnquiry->validate();
		if(is_array($mResult)) {
			$this->log('Error during enquiry validation!', ['form' => $oForm->id, 'result' => $mResult]);
			throw new RuntimeException('Unknown error during enquiry form validation!');
		}

		$mSave = $oEnquiry->save();
		if(!$mSave instanceof Ext_TS_Inquiry) {
			$this->log('Enquiry saving has failed!', ['form' => $oForm->id, 'result' => $mSave, 'enquiry_data' => $oEnquiry->getData()]);
			throw new RuntimeException('Registration form: Enquiry saving has failed!');
		}

		// Die Anfrage neu Laden damit WDBasic die Instanz in den Cache packt, dies is notwendig da bei der
		// Preisberechnung für die Angebote die Instanz irgendwo explizit aus dem Cache neu geladen wird und in
		// diesem Fall sonst die gesetzten Verknüpfungen (Kombination und Angebot) nicht mehr vorhanden sind.
		$this->oInquiry = Ext_TS_Inquiry::getInstance($oEnquiry->id);

		Ext_TC_Flexibility::saveData($aFlexValues, $oEnquiry->id, 'enquiry');

//		$this->createOffer($oEnquiry);

		$this->prepareBackgroundTask($bHasServices);

		return $oEnquiry;

	}

	/**
	 * @param Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry $oEnquiry
	 */
	protected function setObjectDataFromBlocks(Ext_TS_Inquiry_Abstract $oEnquiry) {

		parent::setObjectDataFromBlocks($oEnquiry);

		$oForm = $this->requireForm();

		$aBlocks = $oForm->getInputBlocks();
		foreach($aBlocks as $oBlock) {

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL
			) {
//				$oEnquiry->school_id = $oBlock->getFormInputValue($this->_oRequest);
				$oSchool = Ext_Thebing_School::getInstance($oBlock->getFormInputValue($this->_oRequest));
				$oEnquiry->getJourney()->school_id = $oSchool->id;
				$oEnquiry->getJourney()->productline_id = $oSchool->id;
				continue;
			}

			// Verschoben nach Parent
//			if(
//				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
//				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_PROMOTION_CODE
//			) {
//				$oEnquiry->promotion = $oBlock->getFormInputValue($this->_oRequest);
//				continue;
//			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_CATEGORY
			) {
				$oEnquiry->enquiry_course_category = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_LEVEL
			) {
				$oEnquiry->enquiry_course_intensity = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_CATEGORY
			) {
				$oEnquiry->enquiry_accommodation_category = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_ROOM_TYPE
			) {
				$oEnquiry->enquiry_accommodation_room = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_FOOD
			) {
				$oEnquiry->enquiry_accommodation_meal = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_TYPE
			) {
				$oEnquiry->enquiry_transfer_category = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_LOCATIONS
			) {
				$oEnquiry->enquiry_transfer_location = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

		}

	}

//	/**
//	 * Befüllt die Kombination mit den aktuellen Eingabedaten des Formulars.
//	 *
//	 * Gibt true zurück wenn Leistungen ausgewählt wurden, ansonsten false.
//	 *
//	 * @param Ext_TS_Enquiry_Combination $oCombination
//	 * @return boolean
//	 */
//	private function setCombinationDataFromBlocks(Ext_TS_Enquiry_Combination $oCombination) {
//
//		$aErrors = ['has_service' => false];
//		$oForm = $this->requireForm();
//		$oSchool = $this->requireSchool();
//
//		$aSelectedCourses = $oForm->getSelectedCourses($this->_oRequest, $oSchool);
//		foreach($aSelectedCourses as $oJourneyCourse) {
//			/** @var Ext_TS_Enquiry_Combination_Course $oCombinationCourse */
//			$oCombinationCourse = $oCombination->getJoinedObjectChild('course');
//			$oCombinationCourse->course_id = $oJourneyCourse->course_id;
//			$oCombinationCourse->level_id = $oJourneyCourse->level_id;
//			$oCombinationCourse->from = $oJourneyCourse->from;
//			$oCombinationCourse->units = $oJourneyCourse->units;
//			$oCombinationCourse->weeks = $oJourneyCourse->weeks;
//			$oCombinationCourse->until = $oJourneyCourse->until;
//		}
//
//		$aSelectedAccommodations = $oForm->getSelectedAccommodations($this->_oRequest, $oSchool);
//		foreach($aSelectedAccommodations as $oJourneyAccommodation) {
//			/** @var Ext_TS_Enquiry_Combination_Accommodation $oCombinationAccommodation */
//			$oCombinationAccommodation = $oCombination->getJoinedObjectChild('accommodation');
//			$oCombinationAccommodation->accommodation_id = $oJourneyAccommodation->accommodation_id;
//			$oCombinationAccommodation->roomtype_id = $oJourneyAccommodation->roomtype_id;
//			$oCombinationAccommodation->meal_id = $oJourneyAccommodation->meal_id;
//			$oCombinationAccommodation->from = $oJourneyAccommodation->from;
//			$oCombinationAccommodation->weeks = $oJourneyAccommodation->weeks;
//			$oCombinationAccommodation->until = $oJourneyAccommodation->until;
//		}
//
//		$oCombination->transfer_mode = Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE;
//		$iTransferMode = $oCombination->transfer_mode;
//
//		$aSelectedTransfers = $oForm->getSelectedTransfers($this->_oRequest, $oSchool);
//		foreach($aSelectedTransfers as $oJourneyTransfer) {
//
//			$oCombinationTransfer = null; /** @var Ext_TS_Enquiry_Combination_Transfer $oCombinationTransfer */
//			if($oJourneyTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL) {
//				$oCombinationTransfer = $oCombination->getJoinedObjectChild('arrival');
//			} elseif($oJourneyTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE) {
//				$oCombinationTransfer = $oCombination->getJoinedObjectChild('departure');
//			} else {
//				continue;
//			}
//
//			// Transfermodus für gesamte Kombination bestimmen
//			$iTransferMode |= $oJourneyTransfer->transfer_type;
//
//			$oCombinationTransfer->transfer_type = $oJourneyTransfer->transfer_type;
//			$oCombinationTransfer->start = $oJourneyTransfer->start_type.'_'.$oJourneyTransfer->start;
//			$oCombinationTransfer->end = $oJourneyTransfer->end_type.'_'.$oJourneyTransfer->end;
//			$oCombinationTransfer->transfer_date = $oJourneyTransfer->transfer_date;
//			$oCombinationTransfer->airline = $oJourneyTransfer->airline;
//			$oCombinationTransfer->flightnumber = $oJourneyTransfer->flightnumber;
//			$oCombinationTransfer->comment = $oJourneyTransfer->comment;
//			$oCombinationTransfer->transfer_time = $oJourneyTransfer->transfer_time;
//
//		}
//
//		$aSelectedInsurances = $oForm->getSelectedInsurances($this->_oRequest, $oSchool);
//		foreach($aSelectedInsurances as $oJourneyInsurance) {
//			/** @var Ext_TS_Enquiry_Combination_Insurance $oCombinationInsurance */
//			$oCombinationInsurance = $oCombination->getJoinedObjectChild('insurance');
//			$oCombinationInsurance->insurance_id = $oJourneyInsurance->insurance_id;
//			$oCombinationInsurance->from = $oJourneyInsurance->from;
//			$oCombinationInsurance->weeks = $oJourneyInsurance->weeks;
//			$oCombinationInsurance->until = $oJourneyInsurance->till;
//		}
//
//		// Muss hier gesetzt werden, da Referenz mit Magic get/set nicht funktioniert
//		$oCombination->transfer_mode = $iTransferMode;
//
//		// Pflichtfelder (Blöcke) prüfen
//		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES), $aSelectedCourses, $aErrors);
//		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS), $aSelectedAccommodations, $aErrors);
//		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS), $aSelectedTransfers, $aErrors);
//		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_INSURANCES), $aSelectedInsurances, $aErrors);
//
//		if(
//			count($aSelectedCourses) > 0 ||
//			count($aSelectedAccommodations) > 0 ||
//			count($aSelectedTransfers) > 0 ||
//			count($aSelectedInsurances) > 0
//		) {
//			$aErrors['has_service'] = true;
//		}
//
//		return $aErrors;
//
//	}
//
//	/**
//	 * Erstellt Angebote für alle Kombinationen die der Anfrage zugeordnet sind (sollte nur eine sein).
//	 *
//	 * @param Ext_TS_Enquiry $oEnquiry
//	 * @return null|Ext_TS_Enquiry_Offer
//	 */
//	private function createOffer(Ext_TS_Enquiry $oEnquiry) {
//
//		/** @var Ext_TS_Enquiry_Combination[] $aCombinations */
//		$aCombinations = $oEnquiry->getJoinedObjectChilds('combinations');
//		if(count($aCombinations) < 1) {
//			return null;
//		}
//
//		$oOffer = new Ext_TS_Enquiry_Offer();
//		$oOffer->enquiry_id = (int)$oEnquiry->id;
//
//		$iContactId = $oEnquiry->getFirstTraveller()->id;
//
//		foreach($aCombinations as $oCombination) {
//
//			$aCombinationCourses = [];
//			foreach($oCombination->getJoinedObjectChilds('course') as $oCombinationCourse) {
//				$aCombinationCourses[] = [
//					'combination_course_id' => $oCombinationCourse->id,
//					'contact_id' => $iContactId,
//				];
//			}
//			$oOffer->combination_courses = $aCombinationCourses;
//
//			$aCombinationAccommodations = [];
//			foreach($oCombination->getJoinedObjectChilds('accommodation') as $oCombinationAccommodation) {
//				$aCombinationAccommodations[] = [
//					'combination_accommodation_id' => $oCombinationAccommodation->id,
//					'contact_id' => $iContactId,
//				];
//			}
//			$oOffer->combination_accommodations = $aCombinationAccommodations;
//
//			$aCombinationTransfers = [];
//			foreach($oCombination->getJoinedObjectChilds('arrival') as $oCombinationTransfer) {
//				$aCombinationTransfers[] = [
//					'combination_transfer_id' => $oCombinationTransfer->id,
//					'contact_id' => $iContactId,
//				];
//			}
//			foreach($oCombination->getJoinedObjectChilds('departure') as $oCombinationTransfer) {
//				$aCombinationTransfers[] = [
//					'combination_transfer_id' => $oCombinationTransfer->id,
//					'contact_id' => $iContactId,
//				];
//			}
//			$oOffer->combination_transfers = $aCombinationTransfers;
//
//			$aCombinationInsurances = [];
//			foreach($oCombination->getJoinedObjectChilds('insurance') as $oCombinationInsurance) {
//				$aCombinationInsurances[] = [
//					'combination_insurance_id' => $oCombinationInsurance->id,
//					'contact_id' => $iContactId,
//				];
//			}
//			$oOffer->combination_insurances = $aCombinationInsurances;
//
//		}
//
//		if(!$oOffer->checkTransfers()) {
//			$this->log(
//				'$oOffer->checkTransfers() failed!',
//				[
//					'enquiry' => $oEnquiry->id,
//				],
//				false
//			);
//			return null;
//		}
//
//		$mValidationResult = $oOffer->validate(true);
//		if($mValidationResult !== true) {
//			$this->log(
//				'$oOffer->validate() failed!',
//				[
//					'enquiry' => $oEnquiry->id,
//					'validation_result' => $mValidationResult,
//				],
//				true
//			);
//			$sMsg = 'Failed to validate offer!';
//			throw new RuntimeException($sMsg);
//		}
//
//		$oOffer->save();
//
//		$oFirstCombination = reset($aCombinations);
//		$oEnquiry->setCombination($oFirstCombination);
//		$oEnquiry->setOffer($oOffer);
//		$oEnquiry->saveSpecialPositionRelation(null, true);
//		$fAmount = $oEnquiry->calculateAmount();
//		$oOffer->amount = $fAmount;
//		$oOffer->save();
//
//		return $oOffer;
//
//	}

	/**
	 * @inheritdoc
	 */
	protected function getTypeForDocument() {
		return 'offer_brutto';
	}

	/**
	 * @inheritdoc
	 */
	public function getTypeForNumberrange() {
		return 'enquiry';
	}

}
