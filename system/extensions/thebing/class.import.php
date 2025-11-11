<?php

/**
 * v2
 */
class Ext_Thebing_Import extends Ext_TC_Import {

	/**
	 * @var string
	 */
	protected $sImportKey;

	/**
	 * @var Ext_Thebing_Import_Matching
	 */
	protected $oMatching = array();

	/**
	 * @var array
	 */
	protected $aItem = array();

	/**
	 * @var null|int
	 */
	protected $iSchoolId;

	public $bCreateResources = true;

	/**
	 * @var array
	 */
	protected $aData = array();

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $oInquiry;
	
	/**
	 * @var \Core\Entity\ParallelProcessing\StackRepository
	 */
	protected $oStackRepository;
	
	/**
	 * @var boolean
	 */
	protected $bFirstInvoice = true;
	
	/**
	 * @var Ext_Thebing_Inquiry_Document[]
	 */
	protected $aInvoices = [];
	
	/**
	 * @var Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	public $aInvoiceItems = [];
	
	/**
	 * @var Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	public $aInvoicesInvoiceItems = [];

	/**
	 * @var Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	public $aCurrentInvoiceItems = [];
	
	public $sTransferType = null;
	
	static protected $aCache = [];
	
	/**
	 * Ext_Thebing_Import constructor.
	 *
	 * @param string $sImportKey
	 * @param null|int $iSchoolId
	 */
	public function __construct($sImportKey, $iSchoolId=null) {
		
		$this->sImportKey = $sImportKey;
		
		if($iSchoolId !== null) {
			$this->iSchoolId = $iSchoolId;
		}
		
		$this->oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();

	}
	
	public function getImportKey() {
		return $this->sImportKey;
	}

	public static function prepareImport(array $aTables, $sImportKey, $bRemoveOldEntries=true, $bBackup=true) {
		
		parent::prepareImport($aTables, $sImportKey, $bRemoveOldEntries, $bBackup);
		
		if(
			in_array('kolumbus_inquiries_documents_versions', $aTables) &&
			!in_array('kolumbus_inquiries_documents_versions_priceindex', $aTables)
		) {
			// Da der Price-Index vom Dokument erzeugt wird, muss auch dort ein import_key existieren
			// Ansonsten gibt es lustige Anomalien mit falschen Beträgen aufgrund von ID-Kollision
			throw new BadMethodCallException('Version table included but not priceindex!');
		}
		
	}

	public function resetState() {

		$this->aInvoices = [];
		$this->aInvoiceItems = [];
		$this->aInvoicesInvoiceItems = [];
		$this->aCurrentInvoiceItems = [];

		$this->bFirstInvoice = true;

	}

	/**
	 * @param int $iSchoolId
	 *
	 * @return void
	 */
	public function setSchoolId($iSchoolId) {
		$this->iSchoolId = $iSchoolId;
	}

	/**
	 * @param Ext_Thebing_Import_Matching $oMatching
	 *
	 * @return void
	 */
	public function setMatching(Ext_Thebing_Import_Matching $oMatching) {
		$this->oMatching = $oMatching;
	}

	/**
	 * @param array $aItem
	 *
	 * @return void
	 */
	public function setItem($aItem) {
		$this->aItem = $aItem;
	}



	/**
	 * @param string $sKey
	 * @param array $aData
	 *
	 * @return void
	 */
	public function setData($sKey, array $aData) {
		$this->aData[$sKey] = $aData;
	}

	/**
	 * @TODO Wofür gibt es diese Methode wenn es in der Parent eine gibt?
	 *
	 * @param array $aFlexFields
	 * @param array $aItem
	 * @param int $iItemId
	 *
	 * @return void
	 */
	public function saveFlexValues($aFlexFields, $aItem, $iItemId, $sItemType='') {

		$aFlexData = array();

		self::processItems($aFlexFields, $aItem, $aFlexData);

		foreach($aFlexData as $mKey=>&$mValue) {
			if(is_scalar($mValue)) {
				$mValue = (string)$mValue;
			}
		}

		if($this->bSave) {
			Ext_Thebing_Flexibility::saveData($aFlexData, $iItemId, $sItemType);

			foreach($aFlexData as $iFieldId => $sValue) {
				$aData = ['import_key' => $this->sImportKey];
				$aWhere = [
					'field_id' => $iFieldId,
					'item_id' => $iItemId,
					'item_type' => $sItemType
				];
				self::$oDb->update('tc_flex_sections_fields_values', $aData, $aWhere);
			}
		}

	}

	/**
	 * @param int $iJourneyId
	 * @param int $iContactId
	 *
	 * @return void
	 */
	public function saveVisumValues($iJourneyId, $iContactId) {

		$aVisumData = array();
		$aVisumData['journey_id'] = $iJourneyId;
		$aVisumData['traveller_id'] = $iContactId;

		self::processItems($this->oMatching->getVisumMatching(), $this->aItem, $aVisumData);

		// Visa type
		$this->getOrCreateEntity($aVisumData, 'status', 'visum', 'visa_types');

		if($this->bSave) {
			self::addEntry('ts_journeys_travellers_visa_data', null, $aVisumData, null, $this->sImportKey);	
		}

	}

	public function saveContact($aContactData, $aContactEmailData, $aContactDetailData, $aContactAddressData, $iOriginalId=null) {

		$iContactId = self::addEntry('tc_contacts', null, $aContactData, $iOriginalId, $this->sImportKey);

		if(!empty($aContactEmailData)) {

			$aContactEmailData['active'] = 1;
			$aContactEmailData['master'] = 1;

			// Mehrere E-Mails mit Semikolon separieren
			$aEmails = explode(';', $aContactEmailData['email']);
			$aEmails = array_unique($aEmails);
			foreach($aEmails as $sEmail) {

				$aContactEmailData['email'] = $sEmail;

				if($this->bSave) {
					$iEmailId = self::addEntry('tc_emailaddresses', null, $aContactEmailData, null, $this->sImportKey);
				}

				$aContact2Email = array(
					'contact_id' => $iContactId,
					'emailaddress_id' => $iEmailId
				);
				if($this->bSave) {
					self::addEntry('tc_contacts_to_emailaddresses', $aContact2Email, $aContact2Email, null, $this->sImportKey);
				}
			}

		}
	
		if(!empty($aContactAddressData)) {
			$this->saveContactAddress($iContactId, $aContactAddressData);
		}
		
		foreach($aContactDetailData as $sKey=>$mValue) {
			if(!empty($mValue)) {
				$aDetail = array();
				$aDetail['contact_id'] = $iContactId;
				$aDetail['type'] = $sKey;
				$aDetail['value'] = (string)$mValue;
				
				if($this->bSave) {
					self::addEntry('tc_contacts_details', null, $aDetail, null, $this->sImportKey);
				}
			}
		}
		
		return $iContactId;		
	}

	public function saveContactAddress($iContactId, $aContactAddressData) {

		$aContactAddressData['active'] = 1;
		if(empty($aContactAddressData['label_id'])) {
			$aContactAddressData['label_id'] = 1;
		}
		$aContactAddressData['created'] = date('Y-m-d H:i:s');

		if($this->bSave) {
			$iAddressId = self::addEntry('tc_addresses', null, $aContactAddressData, null, $this->sImportKey);
		}

		$aContact2Address = array(
			'contact_id' => $iContactId,
			'address_id' => $iAddressId
		);

		if($this->bSave) {
			self::addEntry('tc_contacts_to_addresses', $aContact2Address, $aContact2Address, null, $this->sImportKey);
		}

	}

	public function joinContactToInquiry($iInquiryId, $iContactId, $sType='traveller') {
		
		$aInquiry2Contact = array(
			'inquiry_id' => $iInquiryId,
			'contact_id' => $iContactId,
			'type' => $sType
		);

		if($this->bSave) {
			self::addEntry('ts_inquiries_to_contacts', $aInquiry2Contact, $aInquiry2Contact, null, $this->sImportKey);
		}

	}
	
	public function saveInquiry($iCurrencyId=1, $iOriginalId=null, $iGroupId = 0, $bConfirmed=true) {

		$this->sTransferType = null;
		
		$aData = array();
		$aData['active'] = 1;
		$aData['currency_id'] = $iCurrencyId;
		$aData['group_id'] = $iGroupId;
		$aData['type'] = 2;

		self::processItems($this->oMatching->getInquiryMatching(), $this->aItem, $aData);

		if(empty($aData['created'])) {
			$aData['created'] = date('Y-m-d H:i:s');
		}

		if(empty($aData['inbox'])) {
			$aData['inbox'] = 'default';
		}
		
		if($bConfirmed === true) {
			$aData['confirmed'] = $aData['created'];
		}

		// Zahlungsmethode setzen
		if(empty($aData['payment_method'])) {
			if(empty($aData['agency_id'])) {
				$aData['payment_method'] = 1;
			}
		}
		
		if($this->bSave) {
			$iInquiryId = self::addEntry('ts_inquiries', null, $aData, $iOriginalId, $this->sImportKey);
		} else {
			__out($aData);
		}

		/**
		 * Flexible Felder
		 */
		if($this->bSave) {
			$this->saveFlexValues($this->oMatching->getFlexMatching(), $this->aItem, $iInquiryId);
		}

		// Indexeintrag anlegen
		if($this->bSave === true) {
			Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 1);
		}

		$this->oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

		$this->resetState();
		
		return $iInquiryId;
	}

	public function completeInquiry($bDisableValidate = false) {
		
//		if($this->sTransferType !== null) {
//			$this->oInquiry->tsp_transfer = $this->sTransferType;
//		}

		if($bDisableValidate) {
			$this->oInquiry->disableValidate();
		}

		$this->oInquiry->calculatePayedAmount(false);
		$this->oInquiry->resetAmount(false);
		$this->oInquiry->getAmount(false, true, null, false);

		// Experimentell auskommentiert, damit nicht die ganzen Childs usw. nochmal gespeichert werden
		//$this->oInquiry->save();

		// Muss wiederum manuell gemacht werden, da das immer in save() passiert
		$this->oInquiry->refreshServicePeriod();

		DB::updateData('ts_inquiries', [
			#'tsp_transfer' => $this->oInquiry->tsp_transfer,
			'has_proforma' => $this->oInquiry->has_proforma,
			'has_invoice' => $this->oInquiry->has_invoice,
			'amount' => $this->oInquiry->amount,
			'amount_initial' => $this->oInquiry->amount_initial,
			'amount_payed' => $this->oInquiry->amount_payed,
			'amount_payed_prior_to_arrival' => $this->oInquiry->amount_payed_prior_to_arrival,
			'amount_payed_at_school' => $this->oInquiry->amount_payed_at_school,
			'amount_payed_refund' => $this->oInquiry->amount_payed_refund,
			'canceled_amount' => 0,
			'service_from' => $this->oInquiry->service_from,
			'service_until' => $this->oInquiry->service_until,
			'payment_method' => $this->oInquiry->payment_method,
			'canceled' => date('Y-m-d H:i:s', $this->oInquiry->canceled),
		], ['id' => $this->oInquiry->id]);

		$transferMode = 0;
		if($this->sTransferType == 'arr_dep') {
			$transferMode = 3;
		} elseif($this->sTransferType == 'arrival') {
			$transferMode = 1;
		} elseif($this->sTransferType == 'departure') {
			$transferMode = 2;
		}
		
		DB::updateData('ts_inquiries_journeys', [
			'transfer_mode' => $transferMode
		], ['id' => $this->oInquiry->id]);
		
		$this->oStackRepository->writeToStack('ts/tuition-index', ['inquiry_id' => $this->oInquiry->id], 1);

	}

	public function saveInquiryGroup($sIdentifier, $iCurrencyId, $iInboxId) {

		$aData = [
			'active' => 1,
			'school_id' => $this->iSchoolId,
			'inbox_id' => $iInboxId,
			'currency_id' => $iCurrencyId,
			'accommodation_data' => 'no',
			'course_data' => 'no',
			'transfer_data' => 'no'
		];

		if(isset($this->aData['inquiry_group'][$sIdentifier])) {
			return $this->aData['inquiry_group'][$sIdentifier];
		}

		self::processItems($this->oMatching->getMatching('inquiry_group'), $this->aItem, $aData);

		if(empty($aData['created'])) {
			$aData['created'] = date('Y-m-d H:i:s');
		}

		$iGroupId = 0;
		if($this->bSave) {
			$iGroupId = self::addEntry('kolumbus_groups', null, $aData, null, $this->sImportKey);
			$this->aData['inquiry_group'][$sIdentifier] = $iGroupId;
		}

		return $iGroupId;

	}
	
	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return $this->oInquiry;
	}

	/**
	 * @param \Ext_TS_Inquiry $oInquiry
	 */
	public function setInquiry(\Ext_TS_Inquiry $oInquiry) {
		
		$this->oInquiry = $oInquiry;

		$this->resetState();
		
	}
	
	public function saveAgency($aItem) {

		$aData = array();
		$aData['active'] = 1;
		$aData['type'] = 2;
		$aData['idClient'] = 1;
		$aData['created'] = date('Y-m-d H:i:s');

		self::processItems($this->oMatching->getAgencyMatching(), $aItem, $aData);

		if(!empty($aData['ext_1'])) {
		
			// Wenn Agentur noch nicht da, neuen Eintrag anlegen
			$aAgencyCheck = array(
				'ext_1'=>$aData['ext_1']
			);
			
			if($this->bSave) {
				$iAgencyId = Ext_Thebing_Import::addEntry('ts_companies', $aAgencyCheck, $aData, null, $this->sImportKey);

				if(!$iAgencyId) {
					__out('Agentur konnte nicht angelegt werden!');
					__out($aItem);
				}
				
				return $iAgencyId;
			}
			
		}

	}
	
	public function saveJourney($iInquiryId) {

		$aData = array();
		$aData['active'] = 1;
		$aData['created'] = date('Y-m-d H:i:s');
		$aData['inquiry_id'] = $iInquiryId;
		$aData['productline_id'] = 1;
		$aData['school_id'] = $this->iSchoolId;
		$aData['type'] = 2;

		if($this->bSave) {
			$iJourneyId = self::addEntry('ts_inquiries_journeys', null, $aData, null, $this->sImportKey);
		}
		
		return $iJourneyId;
	}
	
	public function finalizeInquiriesImport() {
		
		// Price-Index der Versionen neu berechnen, da ansonsten dort noch Müll drin stehen könnte
//		$oCheck = new Ext_Thebing_System_Checks_AmountCache();
//		$oCheck->executeCheck();

		if($this->bSave) {
			Ext_Gui2_Index_Stack::save();
		}

		#$_SESSION['sid'] = 0;

	}

	public function resetIndexes() {

		foreach((new Ext_TS_System_Tools())->getIndexes() as $sResetCheck) {
			$oCheck = new $sResetCheck();
			if($oCheck instanceof GlobalChecks) {
				__out($sResetCheck.': '.$oCheck->executeCheck());
			}
		}

	}
	
	public function saveCustomerNumber($iContactId, $iNumberrangeId=0, array $matching=null) {
		
		$aCustomerNumberData = array(
			'contact_id' => $iContactId, 
			'numberrange_id' => $iNumberrangeId
		);
		
		if($matching === null) {
			$matching = $this->oMatching->getCustomerNumberMatching();
		}
		
		self::processItems($matching, $this->aItem, $aCustomerNumberData);

		if($this->bSave) {
			Ext_TC_Import::addEntry('tc_contacts_numbers', null, $aCustomerNumberData, null, $this->sImportKey);
		}
		
		return $aCustomerNumberData['number'];
	}
	
	public function saveMatchingDetails($iInquiryId) {
		
		$aMatchingDetailsData = array();
		$aMatchingDetailsData['inquiry_id'] = $iInquiryId;
		self::processItems($this->oMatching->getMatchingDetailsMatching(), $this->aItem, $aMatchingDetailsData);
		
		if($this->bSave) {
			self::addEntry('ts_inquiries_matching_data', null, $aMatchingDetailsData, null, $this->sImportKey);
		}

	}
	
	public function saveCourse($iJourneyId, $iContactId, $courseLanguageId=1) {

		$aCourseData = array();
		$aCourseData['active'] = 1;
		$aCourseData['visible'] = 1;
		$aCourseData['created'] = date('Y-m-d H:i:s');
		$aCourseData['journey_id'] = $iJourneyId;
		$aCourseData['calculate'] = 1;
		$aCourseData['for_tuition'] = 1;
		$aCourseData['courselanguage_id'] = $courseLanguageId;

		self::processItems($this->oMatching->getCourseMatching(), $this->aItem, $aCourseData);

		if(
			empty($aCourseData['until']) &&
			!empty($aCourseData['weeks'])
		) {
			$oFrom = new DateTime($aCourseData['from']);
			$oUntil = clone $oFrom;
			$oUntil->modify('+'.(int)$aCourseData['weeks'].' weeks');
			$aCourseData['until'] = $oUntil->format('Y-m-d');
		}

		if(
			empty($aCourseData['from']) ||
			empty($aCourseData['until']) ||
			$aCourseData['from'] == '0000-00-00' ||
			$aCourseData['until'] == '0000-00-00'
		) {
			return false;
		}
		
		if(empty($aCourseData['weeks'])) {

			$oFrom = new \Carbon\Carbon($aCourseData['from']);
			$oUntil = new \Carbon\Carbon($aCourseData['until']);
			try {
				$aCourseData['weeks'] = ceil($oUntil->floatDiffInWeeks($oFrom));
			} catch (Exception $e) {
				$aCourseData['weeks'] = 0;
			}

			if($aCourseData['weeks'] <= 0) {
				$aCourseData['weeks'] = 1;
			}
			
		}

		// Kurs anlegen falls nicht vorhanden
		if(empty($aCourseData['course_id'])) {

			$aMatching = $this->oMatching->getCourseMatching();
			foreach($aMatching as $iKey=>$aMatchingField) {
				if($aMatchingField['target'] == 'course_id') {
					$iCourseNameField = $iKey;
					break;
				}
			}

			if(is_numeric($iCourseNameField)) {
				$sCourseName = &$this->aItem['field_'.$iCourseNameField];
			} else {
				$sCourseName = &$this->aItem[$iCourseNameField];
			}

			// Spezialfall, Level mit | getrennt
			if(strpos($sCourseName, '|') !== false) {

				list($sCourseName, $sLevel) = explode('|', $sCourseName);

				$sCourseName = trim($sCourseName);
				$sLevel = trim($sLevel);

				$aLevels = $this->getLevels('name_en');

				if(isset($aLevels[$sLevel])) {
					$aCourseData['level_id'] = $aLevels[$sLevel];
				}

			}
			
		}
		
		$this->getOrCreateEntity($aCourseData, 'course_id', 'course', 'courses');

		// Das erste Programm des Kurses verknüpfen
		$aCourseData['program_id'] = (int)\DB::getQueryOne("SELECT `id` FROM `ts_tuition_courses_programs` WHERE `active` = 1 AND `course_id` = :course_id LIMIT 1", ['course_id' => $aCourseData['course_id']]);

		if($this->bSave) {
			$iInquiryCourseId = self::addEntry('ts_inquiries_journeys_courses', null, $aCourseData, null, $this->sImportKey);
		}

		$aToTraveller = array(
			'journey_course_id' => $iInquiryCourseId,
			'contact_id' => $iContactId
		);

		if($this->bSave) {
			self::addEntry('ts_inquiries_journeys_courses_to_travellers', null, $aToTraveller, null, $this->sImportKey);
		}

		return $iInquiryCourseId;
	}

	public function saveInsurance($iJourneyId, $iContactId) {
		
		$aInsuranceData = array();
		$aInsuranceData['active'] = 1;
		$aInsuranceData['visible'] = 1;
		$aInsuranceData['created'] = date('Y-m-d H:i:s');
		$aInsuranceData['journey_id'] = $iJourneyId;

		self::processItems($this->oMatching->getInsuranceMatching(), $this->aItem, $aInsuranceData);

		if(
			empty($aInsuranceData['from']) ||
			empty($aInsuranceData['until']) ||
			$aInsuranceData['from'] == '0000-00-00' ||
			$aInsuranceData['until'] == '0000-00-00'
		) {
			return false;
		}
		
		if(empty($aInsuranceData['weeks'])) {

			$oFrom = new WDDate($aInsuranceData['from'], WDDate::DB_DATE);
			$oUntil = new WDDate($aInsuranceData['until'], WDDate::DB_DATE);
			try {
				$aInsuranceData['weeks'] = $oUntil->getDiff(WDDate::WEEK, $oFrom)+1;
			} catch (Exception $e) {
				$aInsuranceData['weeks'] = 0;
			}

			if($aInsuranceData['weeks'] <= 0) {
				$aInsuranceData['weeks'] = 1;
			}
			
		}
			
		$this->getOrCreateEntity($aInsuranceData, 'insurance_id', 'insurance', 'insurances');

		if($this->bSave) {
			$iInquiryInsuranceId = self::addEntry('ts_inquiries_journeys_insurances', null, $aInsuranceData, null, $this->sImportKey);
		}

		$aToTraveller = array(
			'journey_insurance_id' => $iInquiryInsuranceId,
			'contact_id' => $iContactId
		);

		if($this->bSave) {
			self::addEntry('ts_inquiries_journeys_insurances_to_travellers', null, $aToTraveller, null, $this->sImportKey);
		}

		return $iInquiryInsuranceId;
	}
	
	public function saveAccommodation($iJourneyId, $iContactId) {

		$aAccommodationData = array();
		$aAccommodationData['active'] = 1;
		$aAccommodationData['visible'] = 1;
		$aAccommodationData['created'] = date('Y-m-d H:i:s');
		$aAccommodationData['journey_id'] = $iJourneyId;
		$aAccommodationData['calculate'] = 1;
		$aAccommodationData['for_matching'] = 1;

		self::processItems($this->oMatching->getAccommodationMatching(), $this->aItem, $aAccommodationData);

		if(
			empty($aAccommodationData['from']) ||
			empty($aAccommodationData['until']) ||
			$aAccommodationData['from'] == '0000-00-00' ||
			$aAccommodationData['until'] == '0000-00-00'
		) {
			return false;
		}
		if(empty($aAccommodationData['weeks'])) {
			
			if(
				\Core\Helper\DateTime::isDate($aAccommodationData['from'], 'Y-m-d') &&
				\Core\Helper\DateTime::isDate($aAccommodationData['until'], 'Y-m-d')
			) {
				$dFrom = new DateTime($aAccommodationData['from']);
				$dUntil = new DateTime($aAccommodationData['until']);
				$oDiff = $dFrom->diff($dUntil);
				
				$aAccommodationData['weeks'] = ceil($oDiff->days / 7);
				if($aAccommodationData['weeks'] === 0) {
					// Starttag = Endtag
					$aAccommodationData['weeks'] = 1;
				}
				
			} else {
				$aAccommodationData['weeks'] = 1;
			}
			
		}

		if($aAccommodationData['weeks'] <= 0) {
			$aAccommodationData['weeks'] = 1;
		}

		// Unterkunftskategorie
		$this->getOrCreateEntity($aAccommodationData, 'accommodation_id', 'accommodation', 'accommdation_categories');

		// Raumtyp
		$this->getOrCreateEntity($aAccommodationData, 'roomtype_id', 'accommodation', 'accommdation_roomtypes');

		// Verpflegung
		$this->getOrCreateEntity($aAccommodationData, 'meal_id', 'accommodation', 'accommdation_meals');

		if(
			empty($aAccommodationData['accommodation_id']) ||
			$aAccommodationData['accommodation_id'] == 0
		) {
//			__out($this->aItem);
//			__out($aAccommodationData,1);
		}

		if($this->bSave) {
			$iInquiryAccommodationId = self::addEntry('ts_inquiries_journeys_accommodations', null, $aAccommodationData, null, $this->sImportKey);
		}

		$aToTraveller = array(
			'journey_accommodation_id' => $iInquiryAccommodationId,
			'contact_id' => $iContactId
		);

		if($this->bSave) {
			self::addEntry('ts_inquiries_journeys_accommodations_to_travellers', null, $aToTraveller, null, $this->sImportKey);
		}

		return $iInquiryAccommodationId;
	}

	public function saveTransfer($iJourneyId, $iContactId) {
				
		$aTransferData = array();
		$aTransferData['booked'] = 1;
		$aTransferData['journey_id'] = $iJourneyId;
		$aTransferData['import_key'] = $this->sImportKey;

		self::processItems($this->oMatching->getMatching('transfer'), $this->aItem, $aTransferData);

		// Wenn kein Transfertyp angegeben ist, es aber schon eine Anreise gibt, dann ist der zweite Transfer Abreise
		if($aTransferData['transfer_type'] === null) {
			if($this->sTransferType == 'arrival') {
				$aTransferData['transfer_type'] = 2;
			} else {
				$aTransferData['transfer_type'] = 1;
			}
		}

		if(
			(
				$aTransferData['transfer_type'] == 1 &&
				$this->sTransferType == 'departure'
			) ||
			(
				$aTransferData['transfer_type'] == 2 &&
				$this->sTransferType == 'arrival'		
			)
		) {
			$this->sTransferType = 'arr_dep';
		} elseif($aTransferData['transfer_type'] == 1) {
			$this->sTransferType = 'arrival';
		} elseif($aTransferData['transfer_type'] == 2) {
			$this->sTransferType = 'departure';
		} else {
			$this->sTransferType = 'no';
		}

		if(
			!empty($aTransferData['from']) ||
			!empty($aTransferData['until'])
		) {
			if($aTransferData['transfer_type'] == 1) {
				$aTransferData['transfer_date'] = $aTransferData['from'];
			} else {
				$aTransferData['transfer_date'] = $aTransferData['until'];
			}
			unset($aTransferData['from']);
			unset($aTransferData['until']);
		}

		if($this->bSave) {
			$iInquiryTransferId = self::addEntry('ts_inquiries_journeys_transfers', null, $aTransferData, null, $this->sImportKey);
		}

		$aToTraveller = array(
			'journey_transfer_id' => $iInquiryTransferId,
			'contact_id' => $iContactId
		);

		self::addEntry('ts_inquiries_journeys_transfers_to_travellers', null, $aToTraveller, null, $this->sImportKey);

		return $iInquiryTransferId;
	}
	
	public function getOrCreateEntity(array &$aData, $sField, $sMatchingType, $sDataType) {

		if(empty($aData[$sField])) {

			$aMatchingFieldData = [];
			$mMatchingField = 0;
			$aMatching = $this->oMatching->getMatching($sMatchingType);

			foreach($aMatching as $iKey=>$aMatchingField) {
				if($aMatchingField['target'] == $sField) {
					$aMatchingFieldData = $aMatchingField;
					$mMatchingField = $iKey;
					break;
				}
			}

			if(!empty($mMatchingField)) {

				if(is_numeric($mMatchingField)) {
					$sName = $this->aItem['field_'.$mMatchingField];
				} else {
					$sName = $this->aItem[$mMatchingField];
				}

				// Wenn Mapping angegeben und Feld ist 0: Einfach nichts machen
				// Hier wurde dann für ID 0 plötzlich ID 1 zurückgeliefert?
				if(
					isset($aMatchingFieldData['special']) &&
					$aMatchingFieldData['special'] === 'array' &&
					empty($sName)
				) {
					return;
				}

				if(isset($this->aData[$sDataType][$sName])) {
					$aData[$sField] = $this->aData[$sDataType][$sName];
				}

				if(
					empty($aData[$sField]) &&
					!empty($sName)
				) {
					
					$iEntityId = $this->saveEntity($sField, $sName);

					$this->aData[$sDataType][$sName] = $iEntityId;

					$aData[$sField] = $iEntityId;

				}

			}
			
		}

		//return $aData[$sField];
	}

	public function saveEntity($sField, $sName, $sLanguage='en') {

		$iEntityId = null;

		if(!$this->bCreateResources) {
			return $iEntityId;
		}

		switch($sField) {
			case 'accommodation_id':

				// Kurs anlegen
				$aAccommodationCategoryCheck = array(
					'active' => 1,
					'name_'.$sLanguage => $sName
				);
				// Kurs anlegen
				$aAccommodationCategory = array(
					'active' => 1,
					'short_'.$sLanguage => $sName,
					'name_'.$sLanguage => $sName
				);
				if($this->bSave) {
					$iEntityId = self::addEntry('kolumbus_accommodations_categories', $aAccommodationCategoryCheck, $aAccommodationCategory, null, $this->sImportKey);
				}

				$aAccommodationCategorySetting = array(
					'category_id' => $iEntityId
				);

				if($this->bSave) {
					$iSettingId = self::addEntry('ts_accommodation_categories_settings', $aAccommodationCategorySetting, $aAccommodationCategorySetting, null, $this->sImportKey);
				}

				$aSchoolToAccommodationCategory = array(
					'setting_id' => $iSettingId,
					'school_id' => $this->iSchoolId
				);

				if($this->bSave) {
					self::addEntry('ts_accommodation_categories_settings_schools', $aSchoolToAccommodationCategory, $aSchoolToAccommodationCategory, null, $this->sImportKey);
				}

				break;
			case 'roomtype_id':

				// Kurs anlegen
				$aRoomType = array(
					'active' => 1,
					'short_'.$sLanguage => $sName,
					'name_'.$sLanguage => $sName
				);
				$iEntityId = self::addEntry('kolumbus_accommodations_roomtypes', $aRoomType, $aRoomType, null, $this->sImportKey);

				$aSchoolToAccommodationRoomTypes = array(
					'accommodation_roomtype_id' => $iEntityId,
					'school_id' => $this->iSchoolId
				);

				if($this->bSave) {
					self::addEntry('ts_accommodation_roomtypes_schools', $aSchoolToAccommodationRoomTypes, $aSchoolToAccommodationRoomTypes, null, $this->sImportKey);
				}

				break;
			case 'meal_id':

				// Kurs anlegen
				$aMealCheck = array(
					'active' => 1,
					'name_'.$sLanguage => $sName
				);
				$aMeal = array(
					'active' => 1,
					'short_'.$sLanguage => $sName,
					'name_'.$sLanguage => $sName
				);
				$iEntityId = self::addEntry('kolumbus_accommodations_meals', $aMealCheck, $aMeal, null, $this->sImportKey);

				$aSchoolToAccommodationMeals = array(
					'accommodation_meal_id' => $iEntityId,
					'school_id' => $this->iSchoolId
				);

				if($this->bSave) {
					self::addEntry('ts_accommodation_meals_schools', $aSchoolToAccommodationMeals, $aSchoolToAccommodationMeals, null, $this->sImportKey);
				}

				break;
			case 'course_id':

				// Kurs anlegen
				$aCourse = array(
					'active' => 1,
					'idClient' => 1,
					'school_id' => $this->iSchoolId,
					'name_short' => $sName,
					'name_'.$sLanguage => $sName
				);

				if($this->bSave) {
					$iEntityId = self::addEntry('kolumbus_tuition_courses', null, $aCourse, null, $this->sImportKey);
					// Jeder Kurs braucht immer ein Programm, da nicht mehr bekannt ist eins das
					$iProgramId = self::addEntry('ts_tuition_courses_programs', null, ['active' => 1, 'course_id' => $iEntityId], null, $this->sImportKey);
					$iProgramServiceId = self::addEntry('ts_tuition_courses_programs_services', null, ['active' => 1, 'program_id' => $iProgramId, 'type' => \TsTuition\Entity\Course\Program\Service::TYPE_COURSE, 'type_id' => $iEntityId], null, $this->sImportKey);
				}
				
				break;
			case 'insurance_id':

				// Kurs anlegen
				$aInsurance = array(
					'active' => 1,
					'name_'.$sLanguage => $sName
				);

				$aInsuranceCheck = [
					'name_'.$sLanguage => $sName
				];

				if($this->bSave) {
					$iEntityId = self::addEntry('kolumbus_insurances', $aInsuranceCheck, $aInsurance, null, $this->sImportKey);
				}

				break;
			// Visa type
			case 'status':
				
				// Kurs anlegen
				$aVisaType = array(
					'active' => 1,
					'name' => $sName,
					'client_id' => 1,
					'school_id' => $this->iSchoolId,
					'numberformat' => '%d/%M/%Y'
				);
				$aCheckVisaType = array(
					'active' => 1,
					'name' => $sName,
					'client_id' => 1,
					'school_id' => $this->iSchoolId
				);
				
				if($this->bSave) {
					$iEntityId = self::addEntry('kolumbus_visum_status', $aCheckVisaType, $aVisaType, null, $this->sImportKey);
				}

				break;
		}
		
		return $iEntityId;
	}
	
	public function getAccommodationRoomtypes($sLabel='short_en') {

		$sCacheKey = __METHOD__.'_'.$sLabel;
		
		if(!isset(self::$aCache[$sCacheKey])) {

			$sSql = " SELECT * FROM `kolumbus_accommodations_roomtypes` WHERE active = 1";
			$aTemp = DB::getPreparedQueryData($sSql, []);
			$aRoomTypes = array();
			foreach((array)$aTemp as $aData) {
				$aRoomTypes[$aData[$sLabel]] = $aData['id'];
			}

			$this->setData('accommdation_roomtypes', $aRoomTypes);

			self::$aCache[$sCacheKey] = $aRoomTypes;
			
		}
		
		return self::$aCache[$sCacheKey];
	}

	public function getAccommodationMeals($sLabel='short_en') {

		$sCacheKey = __METHOD__.'_'.$sLabel;
		
		if(!isset(self::$aCache[$sCacheKey])) {

			$sSql = " SELECT * FROM `kolumbus_accommodations_meals` WHERE active = 1";
			$aTemp = DB::getPreparedQueryData($sSql, []);
			$aMeals = array();
			foreach((array)$aTemp as $aData) {
				$aMeals[$aData[$sLabel]] = $aData['id'];
			}

			$this->setData('accommdation_meals', $aMeals);

			self::$aCache[$sCacheKey] = $aMeals;
			
		}
		
		return self::$aCache[$sCacheKey];
	}

	public function getAccommodationCategories($sLabel='short_en') {

		$sCacheKey = __METHOD__.'_'.$sLabel;
		
		if(!isset(self::$aCache[$sCacheKey])) {

			$sSql = " SELECT * FROM `kolumbus_accommodations_categories` WHERE active = 1";
			$aSql = [];
			$aTemp = (array)DB::getPreparedQueryData($sSql, $aSql);

			$aAccommodationCategories = array();
			foreach($aTemp as $aData) {
				$aAccommodationCategories[$aData[$sLabel]] = $aData['id'];
			}

			$this->setData('accommdation_categories', $aAccommodationCategories);
			
			self::$aCache[$sCacheKey] = $aAccommodationCategories;
			
		}
		
		return self::$aCache[$sCacheKey];
	}
	
	public function getVisaTypes() {

		$sSql = "
			SELECT 
				* 
			FROM 
				`kolumbus_visum_status` 
			WHERE 
				`school_id` = :school_id AND 
				`active` = 1
		";

		$aSql = array(
			'school_id' => $this->iSchoolId
		);
		
		
		$aTemp = (array)DB::getQueryRows($sSql, $aSql);

		$aVisaTypes = array();
		foreach($aTemp as $aData) {
			$aVisaTypes[$aData['name']] = $aData['id'];
		}

		$this->setData('visa_types', $aVisaTypes);
		
		return $aVisaTypes;
	}
	
	public function getCourses($sLabel = 'name_en') {
		
		$sCacheKey = __METHOD__.'_'.$this->iSchoolId.'_'.$sLabel;
		
		if(!isset(self::$aCache[$sCacheKey])) {
		
			$sSql = "
				SELECT 
					* 
				FROM 
					`kolumbus_tuition_courses` 
				WHERE 
					`school_id` = :school_id AND 
					active = 1
			";

			$aSql = array(
				'school_id'=>$this->iSchoolId
			);
			$aTemp = DB::getPreparedQueryData($sSql, $aSql);

			$aCourses = array();
			foreach((array)$aTemp as $aData) {
				$aCourses[$aData[$sLabel]] = $aData['id'];
			}

			$this->setData('courses', $aCourses);
			
			self::$aCache[$sCacheKey] = $aCourses;
			
		}
		
		return self::$aCache[$sCacheKey];
	}
	
	public function getLevels($sLabel = 'name_en', $sType='normal') {
		
		$sSql = "
			SELECT 
				`ts_tl`.*
			FROM 
				`ts_tuition_levels` `ts_tl` JOIN
				`ts_tuition_levels_to_schools` `ts_tlts` ON
					`ts_tl`.`id` = `ts_tlts`.`level_id`
			WHERE 
				`ts_tlts`.`school_id` = :school_id AND 
				`ts_tl`.`type` = :type AND
				`ts_tl`.`active` = 1
		";

		$aSql = array(
			'school_id' => (int)$this->iSchoolId,
			'type'=>$sType
		);
		$aTemp = DB::getPreparedQueryData($sSql, $aSql);

		$aLevels = array();
		foreach((array)$aTemp as $aData) {
			$aLevels[$aData[$sLabel]] = $aData['id'];
		}
		
		return $aLevels;		
	}

	public function saveDocument($iInquiryId, $sType = 'additional_document', $iInvoiceNumberrange = 0) {

		$sImportMatching = 'document';
		if(
			strpos($sType, 'brutto') !== false ||
			strpos($sType, 'net') !== false ||
			strpos($sType, 'credit') !== false ||
			strpos($sType, 'storno') !== false
		) {
			$sImportMatching = 'invoice';
		}

		// Document anlegen
		$aDocumentData = array(
			'active' => 1,
			'import_key' => $this->sImportKey,
			'entity' => 'Ext_TS_Inquiry',
			'entity_id' => $iInquiryId,
			'numberrange_id' => $iInvoiceNumberrange,
			'type' => $sType
		);

		self::processItems($this->oMatching->getMatching($sImportMatching), $this->aItem, $aDocumentData);

		if(empty($aDocumentData['created'])) {
			$aDocumentData['created'] = date('Y-m-d H:i:s');
		}

		$iDocumentId = self::addEntry('kolumbus_inquiries_documents', null, $aDocumentData, null, $this->sImportKey, true, true);

		// Version anlegen
		$aVersionData = array(
			'active' => 1,
			'import_key' => $this->sImportKey,
			'document_id' => $iDocumentId,
			'template_language' => 'en',
			'created' => $aDocumentData['created'],
			'version' => 1
		);

		self::processItems($this->oMatching->getMatching($sImportMatching.'_version'), $this->aItem, $aVersionData);

		$iVersionId = self::addEntry('kolumbus_inquiries_documents_versions', null, $aVersionData, null, $this->sImportKey, true, true);

		DB::executePreparedQuery("UPDATE `kolumbus_inquiries_documents` SET `latest_version` = :version_id WHERE `id` = :id", ['id' => $iDocumentId, 'version_id' => $iVersionId]);

		if($this->bSave === true) {
			Ext_Gui2_Index_Stack::add('ts_document', $iDocumentId, 1);
		}

		return [$iDocumentId, $iVersionId];

	}
	
	public function saveInvoice(
		$iInquiryId,
		$iContactId,
		$iInvoiceNumberrange,
		$aItems = null,
		$bProforma = false,
		$sFinalPayDate = null,
		$fTotalAmount = null,
		$iMergeItemsToDocumentId = null, // Items in anderes Dokument mergen (z.B. Gruppenrechnungen)
		$sAddressType='address', 
		$iAddressTypeId=0,
		$sInvoiceType=null,
		$iLinkToDocument=null,
		$sLinkToDocumentType='creditnote'
	) {

		$this->aCurrentInvoiceItems = [];

		if($sInvoiceType === null) {
			if(
				empty($this->oInquiry->agency_id) || 
				$sAddressType != 'agency'
			) {
				$sInvoiceType = 'brutto';
			} else {
				$sInvoiceType = 'netto';
			}

			if($bProforma) {
				$sInvoiceType = 'proforma_'.$sInvoiceType;
			}

			if(
				!$bProforma &&
				$this->bFirstInvoice !== true
			) {
				$sInvoiceType .= '_diff';
			}
		}

		if($iMergeItemsToDocumentId === null) {
			
			list($iInvoiceId, $iInvoiceVersionId) = $this->saveDocument($iInquiryId, $sInvoiceType, $iInvoiceNumberrange);
			
			$aInvoiceAddressData = [
				'version_id' => $iInvoiceVersionId,
				'type' => $sAddressType,
				'type_id' => $iAddressTypeId
			];
			
			Ext_Thebing_Import::addEntry('ts_inquiries_documents_versions_addresses', null, $aInvoiceAddressData, null, $this->sImportKey, true, true);
			
		} else {
			// Items anderer Rechnung zuweisen (z.B. Gruppe)
			$iInvoiceId = $iMergeItemsToDocumentId;
			$iInvoiceVersionId = DB::getQueryOne("SELECT latest_version FROM kolumbus_inquiries_documents WHERE id = :id", ['id' => $iInvoiceId]);
			if(empty($iInvoiceVersionId)) {
				throw new RuntimeException('Could not find a version id for merge document '.$iInvoiceId);
			}
		}

		if($aItems === null) {

			// TODO: Das funktioniert nicht universal. £770.00 ergibt 77000!
			//$fAmount = self::processSpecial('float', $this->aItem['field_3']);

			// TODO Konfigurierbar machen (oder was anderes überlegen)
			$fAmount = $fTotalAmount;

			$aItemData = array(
				'active' => 1,
				'import_key' => $this->sImportKey,
				'version_id' => $iInvoiceVersionId,
				'created' => date('Y-m-d H:i:s'),
				'description' => 'Invoice total',
				'type' => 'extraPosition',
				'amount' => $fAmount,
				'amount_net' => $fAmount,
				'onPdf' => 1,
				'calculate' => 1,
				'contact_id' => $iContactId
			);

			if(strpos($sInvoiceType, 'creditnote') !== false) {
				$aItemData['amount_provision'] = $fAmount;
			}
			
			$aItems = [
				$aItemData
			];

		} else {
			
			foreach($aItems as $iKey=>$aItem) {
				
				$this->setItem($aItem);
				
				$aItemData = array(
					'active' => 1,
					'version_id' => $iInvoiceVersionId,
					'created' => date('Y-m-d H:i:s'),
					'type' => 'extraPosition',
					'onPdf' => 1,
					'calculate' => 1,
					'contact_id' => $iContactId
				);
				self::processItems($this->oMatching->getMatching('invoice_item'), $this->aItem, $aItemData);

				$aItems[$iKey] = $aItemData;
				
			}
		}

		$aItemIds = [];
		if($this->bSave === true) {
			foreach($aItems as $aItemData) {
				$aItemIds[] = Ext_Thebing_Import::addEntry('kolumbus_inquiries_documents_versions_items', null, $aItemData, null, $this->sImportKey, true, true);
			}
		}

		if(!$bProforma) {
			$this->oInquiry->has_invoice = 1;
		} else {
			$this->oInquiry->has_proforma = 1;
		}

		// Price-Index
		$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iInvoiceVersionId);
		$oVersion->refreshPriceIndex();

		$aPriceIndexList = Ext_Thebing_Inquiry_Document_Version_Price::getPriceIndexList($oVersion, true, true);
		foreach($aPriceIndexList as $oPriceIndex) {
			// import_key hinzufügen, damit in der Datenbank keine Rückstände bleiben
			DB::updateData('kolumbus_inquiries_documents_versions_priceindex', ['import_key' => $this->sImportKey], array('id' => $oPriceIndex->id));
		}

		// Payment Term
		$aPaymentTerm = [
			'version_id' => $iInvoiceVersionId,
			'setting_id' => 0,
			'type' => 'final',
			'date' => $sFinalPayDate ?? $oVersion->date,
			'amount' => $oVersion->getAmount()
		];

		Ext_Thebing_Import::addEntry('ts_documents_versions_paymentterms', null, $aPaymentTerm, null, $this->sImportKey, true, true);
		
		$this->bFirstInvoice = false;

		$this->aInvoices[] = $oVersion->getDocument();

		$aDbItems = DB::getQueryRows("SELECT * FROM `kolumbus_inquiries_documents_versions_items` WHERE id IN(:ids)", ['ids' => $aItemIds]);
		foreach($aDbItems as $aDbItem) {
			$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getObjectFromArray($aDbItem);
			$this->aInvoiceItems[] = $oItem;
			$this->aInvoicesInvoiceItems[$iInvoiceId][] = $oItem;
			$this->aCurrentInvoiceItems[] = $oItem;
		}

		if(
			$iLinkToDocument !== null ||
			strpos($sInvoiceType, 'creditnote') !== false
		) {
			
			if($iLinkToDocument === null) {
				$oMainInvoice = reset($this->aInvoices);
				$iLinkToDocument = $oMainInvoice->id;
			}
			
			$aDocument2Document = [
				'parent_document_id' => $iLinkToDocument,
				'child_document_id' => $iInvoiceId,
				'type'=>$sLinkToDocumentType
			];
			Ext_Thebing_Import::addEntry('ts_documents_to_documents', null, $aDocument2Document, null, $this->sImportKey, true, true);
			
		}
		
		return $iInvoiceId;
	}
	
	public function savePayment($iInquiryId, $iCurrencyId, $iPaymentGroupingNumberrangeId = 0, Ext_TS_Payment_Item_AllocateAmount $oAllocateAmountService = null, $iInvoiceId=null) {
		
		$aPaymentGroupingData = array(
			'active' => 1,
			'import_key' => $this->sImportKey
		);

		self::processItems($this->oMatching->getMatching('payment_grouping'), $this->aItem, $aPaymentGroupingData);

		// Bezahlvorgang (Payment Grouping)
		$iPaymentGroupingId = null;
		if($this->bSave === true) {

			// Optional: Nummer wird übergeben, dann Transaktionen übernehmen
			if(
				$iPaymentGroupingNumberrangeId !== null &&
				!empty($aPaymentGroupingData['number'])
			) {
				$aPaymentGroupingData['numberrange_id'] = $iPaymentGroupingNumberrangeId;
				$iPaymentGroupingId = $this->aData['payment_grouping'][$iPaymentGroupingNumberrangeId][$aPaymentGroupingData['number']];
			}

			if($iPaymentGroupingId === null) {
				$iPaymentGroupingId = Ext_Thebing_Import::addEntry('ts_inquiries_payments_groupings', null, $aPaymentGroupingData, null, $this->sImportKey, true, true);
			}

			$this->aData['payment_grouping'][$iPaymentGroupingNumberrangeId][$aPaymentGroupingData['number']] = $iPaymentGroupingId;

		}

		$sSender = 'customer';
		if($this->oInquiry->agency_id > 0) {
			$sSender = 'agency';	
		}

		$aPaymentData = array(
			'active' => 1,
			'import_key' => $this->sImportKey,
			'inquiry_id' => $iInquiryId,
			'type_id' => 1,
			'sender' => $sSender,
			'receiver' => 'school',
			'grouping_id' => $iPaymentGroupingId,
			'currency_inquiry' => $iCurrencyId,
			'currency_school' => $iCurrencyId
		);

		self::processItems($this->oMatching->getMatching('payment'), $this->aItem, $aPaymentData);

		if(empty($aPaymentData['created'])) {
			$aPaymentData['created'] = date('Y-m-d H:i:s');
		}

		$iPaymentId = null;
		if($this->bSave === true) {
			$iPaymentId = Ext_Thebing_Import::addEntry('kolumbus_inquiries_payments', null, $aPaymentData, null, $this->sImportKey, true, true);
		}

		$aInvoiceItems = $this->aInvoiceItems;
		
		if($iInvoiceId !== null) {
			$aInvoiceItems = $this->aInvoicesInvoiceItems[$iInvoiceId];
		}
		
		if(
			$iInvoiceId === null && 
			!empty($this->aInvoices)
		) {
			$oInvoice = reset($this->aInvoices);
			$iInvoiceId = $oInvoice->id;
		}

		if($oAllocateAmountService === null) {
			$oAllocateAmountService = new Ext_TS_Payment_Item_AllocateAmount($aInvoiceItems, $aPaymentData['amount_school']);
		}

		$aAllocatedAmounts = $oAllocateAmountService->allocateAmounts();

		// Payment Items
		foreach($aAllocatedAmounts as $iItemId => $fItemAmount) {

			if(round(abs($fItemAmount), 2) === 0.0) {
				continue;
			}

			$aPaymentItemData = array(
				'active' => 1,
				'import_key' => $this->sImportKey,
				'payment_id' => $iPaymentId,
				'created' => date('Y-m-d H:i:s'),
				'item_id' => $iItemId,
				'amount_inquiry' => sprintf('%F', $fItemAmount),
				'amount_school' => sprintf('%F', $fItemAmount),
				'currency_inquiry' => $iCurrencyId,
				'currency_school' => $iCurrencyId
			);

			if($this->bSave === true) {
				Ext_Thebing_Import::addEntry('kolumbus_inquiries_payments_items', null, $aPaymentItemData, null, $this->sImportKey, true);
			}

		}

		// Overpayment
		if($oAllocateAmountService->hasOverPayment()) {

			$aOverpaymentData = [
				'payment_id' => $iPaymentId,
				'inquiry_document_id' => $iInvoiceId,
				'amount_inquiry' => sprintf('%F', $oAllocateAmountService->getOverPayment()),
				'amount_school' => sprintf('%F', $oAllocateAmountService->getOverPayment()),
				'currency_inquiry' => $iCurrencyId,
				'currency_school' => $iCurrencyId
			];

			if($this->bSave === true) {
				Ext_Thebing_Import::addEntry('kolumbus_inquiries_payments_overpayment', null, $aOverpaymentData, null, $this->sImportKey, true);
			}

		}

		if($iInvoiceId) {
			$aDocumentToInquiryData = [
				'document_id' => $iInvoiceId,
				'payment_id' => $iPaymentId
			];

			if($this->bSave === true) {
				Ext_Thebing_Import::addEntry('ts_documents_to_inquiries_payments', null, $aDocumentToInquiryData, null, $this->sImportKey, true);
			}
		}
		
		return $iPaymentId;
	}
	
}
