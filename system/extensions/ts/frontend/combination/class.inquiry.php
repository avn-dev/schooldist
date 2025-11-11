<?php

class Ext_TS_Frontend_Combination_Inquiry extends Ext_TS_Frontend_Combination_Inquiry_Abstract {

	const MAIL_APPLICATION_NAME = 'inquiry_form';

	/**
	 * @var array
	 */
	protected $aUploads = [];

    /**
     * {@inheritdoc}
     */
    protected function _default() {
        parent::_default();
        $this->_assign('sFormType', 'form-inquiry');
    }

	/**
	 * @return Ext_TS_Inquiry|array
	 * @throws Exception
	 */
	protected function createObject() {

		$oForm = $this->requireForm();
		$sLanguage = $this->requireLanguage();
//		$oSchool = $this->requireSchool();

		$oInquiry = $this->createInquiryObject();
		$oJourney = $oInquiry->getJourney();

		$oContact = $oInquiry->getCustomer();
		$this->setContactData($oContact, $sLanguage);

		$this->setObjectDataFromBlocks($oInquiry);

		$aJourneyErrors = $this->setJourneyDataFromBlocks($oJourney);
		if(!empty($aJourneyErrors)) {
			return $aJourneyErrors;
		}

		$aFlexValues = $this->getFlexValuesFromBlocks();

		if($this->getTypeForDocument() === 'brutto') {
			$oInquiry->confirmed = time();
			$oInquiry->has_invoice = 1;
		} else {
			$oInquiry->has_proforma = 1;
		}

		// Objekte sind noch nicht gespeichert!
		$aHookData = ['combination' => $this, 'object' => $oInquiry, 'flex_values' => $aFlexValues];
		System::wd()->executeHook('ts_frontend_combination_inquiry_save', $aHookData);

		// Kundennummer generieren bei Rechnungserstellung
//		if($oForm->getSchoolSetting($oSchool, 'generate_invoice')) {
			$oCustomerNumberService = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
			$oCustomerNumberService->saveCustomerNumber(false, false);
//		}

		$mResult = $oInquiry->validate();
		if(is_array($mResult)) {
			$this->log('Error during inquiry validation!', ['form' => $oForm->id, 'result' => $mResult]);
			throw new RuntimeException('Unknown error during inquiry form validation!');
		}

		$oInquiry->findSpecials();

		$mSave = $oInquiry->save();
		if(!$mSave instanceof Ext_TS_Inquiry) {
			$this->log('Inquiry saving has failed!', ['form' => $oForm->id, 'result' => $mSave, 'inquiry_data' => $oInquiry->getData()]);
			throw new RuntimeException('Registration form: Inquiry saving has failed!');
		}

		Ext_TS_Inquiry::setInstance($oInquiry);
		$this->oInquiry = $oInquiry;

		$this->handleUploads($oContact);

		Ext_TC_Flexibility::saveData($aFlexValues, $oInquiry->id);

		$this->prepareBackgroundTask();

		return $oInquiry;

	}

	/**
	 * @param Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry $oInquiry
	 */
	protected function setObjectDataFromBlocks(Ext_TS_Inquiry_Abstract $oInquiry) {

		parent::setObjectDataFromBlocks($oInquiry);

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();

		$oJourney = $oInquiry->getJourney();
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$oEmergencyContact	= $oInquiry->getEmergencyContact();
		$oMatchingData = $oInquiry->getMatchingData();
		$oVisaData = $oJourney->getVisa();

		$aBlocks = $oForm->getInputBlocks();
		foreach($aBlocks as $oBlock) {

//			if(
//				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
//				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_PROMOTION_CODE
//			) {
//				// Feld gibt es auch bei Enquiry, heißt da aber anders…
//				$oInquiry->promotion = $oBlock->getFormInputValue($this->_oRequest);
//				continue;
//			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_NAME
			) {
				$oEmergencyContact->lastname = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_FIRSTNAME
			) {
				$oEmergencyContact->firstname = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_PHONE
			) {
				$oEmergencyContact->setDetail(\Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE, $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_EMAIL
			) {
				$oEmergencyContact->email = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			// Matching-Selects
			if($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT) {
				$iValue = (int)$oBlock->getFormInputValue($this->_oRequest);

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_SMOKER) {
					$oMatchingData->acc_smoker = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_VEGETARIAN) {
					$oMatchingData->acc_vegetarian = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_MUSLIM) {
					$oMatchingData->acc_muslim_diat = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_CATS) {
					$oMatchingData->cats = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_DOGS) {
					$oMatchingData->dogs = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_ANIMALS) {
					$oMatchingData->pets = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_SMOKER) {
					$oMatchingData->smoker = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_DISTANCE) {
					$oMatchingData->distance_to_school = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_CLIMA) {
					$oMatchingData->air_conditioner = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_OWN_BATHROOM) {
					$oMatchingData->bath = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_AGE) {
					$oMatchingData->family_age = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_CHILDS) {
					$oMatchingData->family_kids = $iValue;
					continue;
				}

				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_INTERNET) {
					$oMatchingData->internet = $iValue;
					continue;
				}
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_MATCHING_AREA
			) {
				$oMatchingData->residential_area = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_MATCHING_COMMENT
			) {
				$oMatchingData->acc_comment = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_MATCHING_ALLERGY
			) {
				$oMatchingData->acc_allergies = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_VISA_REQUIRED
			) {
				$oVisaData->required = (int)$oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_VISA_STATUS
			) {
				$oVisaData->status = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_VISA_PASS_NUMBER
			) {
				$oVisaData->passport_number = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_FROM
			) {
				$sDate = $oBlock->getFormInputValue($this->_oRequest);
				$oVisaData->date_from = $oDateFormat->convert($sDate);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_UNTIL
			) {
				$sDate = $oBlock->getFormInputValue($this->_oRequest);
				$oVisaData->date_until = $oDateFormat->convert($sDate);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_PASS_FROM
			) {
				$sDate = $oBlock->getFormInputValue($this->_oRequest);
				$oVisaData->passport_date_of_issue = $oDateFormat->convert($sDate);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_PASS_UNTIL
			) {
				$sDate = $oBlock->getFormInputValue($this->_oRequest);
				$oVisaData->passport_due_date = $oDateFormat->convert($sDate);
				continue;
			}

			// Uploads
			if($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_UPLOAD) {
				// Analog benennen zu den Checkboxen im Student Record (sollte alles mal komplett umgestellt werden…)
				if($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_UPLOAD_PHOTO) {
					$sField = 'static_1';
				} elseif($oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_UPLOAD_PASS) {
					$sField = 'static_2';
				} else {
					$sField = str_replace('upload_', '', $oBlock->set_type);
				}

				$aFiles = $this->_oRequest->getFilesData();
				if(!isset($aFiles[$oBlock->getInputBlockName()]['tmp_name'])) {
					// Es wurde keine Datei hochgeladen
					continue;
				}

				if(
					$aFiles[$oBlock->getInputBlockName()]['error'] !== UPLOAD_ERR_OK ||
					!is_file($aFiles[$oBlock->getInputBlockName()]['tmp_name'])
				) {
					// Sollte alles schon vom Block-Validator abgefangen worden sein
					$oForm = $this->requireForm();
					$this->log('Fatal error while handling uploads: validation passed?', [$oForm->id, $oBlock->getData(), $aFiles]);
					throw new RuntimeException('Error with form upload!');
				}

				// Uploads sammeln, da diese nicht vor dem Speichern verschoben werden dürfen
				$this->aUploads[$sField] = $aFiles[$oBlock->getInputBlockName()];
				continue;
			}

		}

	}

	/**
	 * Uploads verschieben
	 *
	 * @TODO Am besten sollte man die Metadaten strippen (EXIF) und das Bild neu schreiben,
	 * 	aber dieses Level von Sicherheit passiert auch nirgends in der Software.
	 *
	 * @param Ext_TS_Inquiry_Contact_Abstract $oContact
	 */
	private function handleUploads(Ext_TS_Inquiry_Contact_Abstract $oContact) {
		foreach($this->aUploads as $sKey => $aFileData) {
			if($sKey === 'static_1') {
				$oContact->savePhoto($aFileData['name'], $aFileData['tmp_name']);
			} elseif($sKey === 'static_2') {
				$oContact->savePassport($aFileData['name'], $aFileData['tmp_name']);
			} elseif(strpos($sKey, 'flex_') !== false) {
				$iFlexId = explode('_', $sKey, 2)[1];
				if(!is_numeric($iFlexId)) {
					throw new UnexpectedValueException('Invalid flex field key "'.$sKey.'"');
				}
				$oContact->saveStudentUpload($aFileData['name'], $aFileData['tmp_name'], $iFlexId, $this->oInquiry->id);
			} else {
				throw new UnexpectedValueException('Unknown upload type "'.$sKey.'"');
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function getTypeForDocument() {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();

		if($oForm->getSchoolSetting($oSchool, 'generate_invoice')) {
			return 'brutto';
		}

		return 'proforma_brutto';


	}

	/**
	 * @inheritdoc
	 */
	public function getTypeForNumberrange() {

		if($this->getTypeForDocument() === 'proforma_brutto') {
			return 'proforma';
		}

		return 'invoice';

	}

}