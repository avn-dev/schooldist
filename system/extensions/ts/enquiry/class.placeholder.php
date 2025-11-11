<?php

// TODO 16002 Entfernen
class Ext_TS_Enquiry_Placeholder extends Ext_TS_Inquiry_Placeholder_Abstract {

	/**
	 * @var null|Ext_TS_Enquiry 
	 */
	protected $_oEnquiry = null;

	/**
	 * @var Ext_TS_Enquiry_Contact_Traveller
	 */
	protected $_oCustomer = null;

	/**
	 * @var Ext_Thebing_Agency
	 */
	protected $_oAgency = null;

	/**
	 * @var null|Ext_TS_Enquiry_Combination_Transfer
	 */
	protected $_oTransferArrival = null;

	/**
	 * @var null|Ext_TS_Enquiry_Combination_Transfer
	 */
	protected $_oTransferDeparture = null;

	/**
	 * Wird beim Verarbeiten von Loops gesetzt
	 *
	 * @see Ext_TS_Enquiry_Placeholder::_helperReplaceCourseLoop()
	 * @see Ext_TS_Enquiry_Placeholder::_searchCourseLoopPlaceholderValue()
	 * @var null|Ext_TS_Enquiry_Combination_Course
	 */
	private $_oLoopCourse = null;

	/**
	 * Wird beim Verarbeiten von Loops gesetzt
	 *
	 * @see Ext_TS_Enquiry_Placeholder::_helperReplaceAccommodationLoop()
	 * @see Ext_TS_Enquiry_Placeholder::_searchAccommodationLoopPlaceholderValue()
	 * @var null|Ext_TS_Enquiry_Combination_Accommodation
	 */
	private $_oLoopAccommodation = null;

	/**
	 * Wird beim Verarbeiten von Loops gesetzt
	 *
	 * @see Ext_TS_Enquiry_Placeholder::_helperReplaceInsuranceLoop()
	 * @see Ext_TS_Enquiry_Placeholder::_searchInsuranceLoopPlaceholderValue()
	 * @var null|Ext_TS_Enquiry_Combination_Insurance
	 */
	private $_oLoopInsurance = null;

	/**
	 * @param Ext_TS_Enquiry $oEnquiry
	 */
	public function __construct(Ext_TS_Enquiry $oEnquiry = null) {

		parent::__construct();

		$aCategories = [
			'schools',
//			'tuition_courses',
//			'tuition_course_categories',
			'agencies',
//			'accommodations',
//			'meals',
//			'roomtypes',
			'enquiries',
			'groups'
		];

		$aFlexPlaceholders = Ext_Thebing_Inquiry_Placeholder::getAllFlexTags($aCategories);
		$aFlexPlaceholders = Ext_Thebing_Inquiry_Placeholder::clearPlaceholders($aFlexPlaceholders);
		$aFlexPlaceholders = (array)$aFlexPlaceholders[0]['placeholders'];
		foreach($aFlexPlaceholders as $sPlaceholder => $sLabel) {
			$this->_aFlexFieldLabels[$sPlaceholder] = $sLabel;
		}

		if($oEnquiry === null) {
			return;
		}

		$this->_oEnquiry = $oEnquiry;
		$this->_oCustomer = $oEnquiry->getFirstTraveller();
		$this->_oAgency = $oEnquiry->getAgency();
		$this->_iSchoolId = $oEnquiry->getSchool()->id;

		$mTransfersArrival = $this->_oEnquiry->getTransfers('arrival', true);
		if(is_array($mTransfersArrival)) {
			$mTransfersArrival = reset($mTransfersArrival);
		}
		if($mTransfersArrival instanceof Ext_TS_Enquiry_Combination_Transfer) {
			$this->_oTransferArrival = $mTransfersArrival;
		}

		$mTransfersDeparture = $this->_oEnquiry->getTransfers('departure', true);
		if(is_array($mTransfersDeparture)) {
			$mTransfersDeparture = reset($mTransfersDeparture);
		}
		if($mTransfersDeparture instanceof Ext_TS_Enquiry_Combination_Transfer) {
			$this->_oTransferDeparture = $mTransfersDeparture;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPlaceholders($sType = '') {

		$this->buildPlaceholderTableData();

		return [
			$this->_getPlaceholders('general'),
			$this->_getPlaceholders('customer'),
			$this->_getPlaceholders('agency'),
			$this->_getPlaceholders('numbers'),
			$this->_getPlaceholders('document'),
			$this->_getPlaceholders('enquiry'),
			$this->_getPlaceholders('group'),
		];

	}

	/**
	 * @todo $oDocument und $oVersion werden nicht verwendet, warum sind die da?
	 * @param mixed $oDocument
	 * @param mixed $oVersion
	 * @param string $sText
	 * @return string
	 */
	public function replaceForPdf($oDocument, $oVersion, $sText) {

		$aPlaceholderList = $this->_getAllPlaceholders($sText);

		if(
			is_array($aPlaceholderList) &&
			array_key_exists('document_number', $aPlaceholderList)
		) {
			$mDocument = (array)$this->getMainObject()->getDocuments();
			if(
				is_array($mDocument) &&
				count($mDocument) > 0
			) {
				$mDocument = end($mDocument);
			}
			if($mDocument instanceof Ext_Thebing_Inquiry_Document) {
				$sNumber = (string)$mDocument->document_number;
				$sText = str_replace('{document_number}', $sNumber, $sText);
			}
		}

		return $sText;

	}

	/**
	 * {@inheritdoc}
	 */
	public function searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder = []) {

		if($this->_oLoopCourse !== null) {
			$mValue = $this->_searchCourseLoopPlaceholderValue($sField);
			if($mValue !== null) {
				return $mValue;
			}
		}

		if($this->_oLoopAccommodation !== null) {
			$mValue = $this->_searchAccommodationLoopPlaceholderValue($sField);
			if($mValue !== null) {
				return $mValue;
			}
		}

		if($this->_oLoopInsurance !== null) {
			$mValue = $this->_searchInsuranceLoopPlaceholderValue($sField);
			if($mValue !== null) {
				return $mValue;
			}
		}

		switch($sField) {
			case 'sales_person':
				$oSalesPerson = $this->getMainObject()->getSalesPerson();
				if($oSalesPerson) {
					return $oSalesPerson->getName();
				}
				return '';
			case 'date_entry':
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('U', $this->getMainObject()->created);
				return (string)$oDateFormat->format($dDate);

			case 'date_first_course_start':
				$iLastCourse = null;
				foreach($this->getMainObject()->getCourses() as $oCourse) {
					if(
						$iLastCourse === null ||
						$iLastCourse > $oCourse->from
					) {
						$iLastCourse = $oCourse->from;
					}
				}
				if($iLastCourse === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $iLastCourse);
				return (string)$oDateFormat->format($dDate);

			case 'date_last_course_end':
				$iLastCourse = null;
				foreach($this->getMainObject()->getCourses() as $oCourse) {
					if(
						$iLastCourse === null ||
						$iLastCourse < $oCourse->until
					) {
						$iLastCourse = $oCourse->until;
					}
				}
				if($iLastCourse === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $iLastCourse);
				return (string)$oDateFormat->format($dDate);

			case 'total_course_weeks':
			case 'total_course_weeks_absolute':
				$iTotalWeeks = 0;
				foreach($this->getMainObject()->getCourses() as $oCourse) {
					$iTotalWeeks += $oCourse->weeks;
				}
				return (string)$iTotalWeeks;

			case 'booked_transfer':
				$aTransferModes = Ext_Thebing_Data::getTransferList($this->getLanguageObject());
				return (string)$aTransferModes[$this->getMainObject()->getTransferMode()];

			case 'transfer_comment':
				return (string)$this->getMainObject()->getTransferComment();

			case 'arrival_date':
				if($this->_oTransferArrival === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oTransferArrival->transfer_date);
				return (string)$oDateFormat->format($dDate);

			case 'arrival_time':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->transfer_time;

			case 'arrival_airline':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->airline;

			case 'arrival_flightnumber':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->flightnumber;

			case 'arrival_pick_up':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->getStartLocation($this->getLanguageObject()); 

			case 'arrival_drop_off':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->getEndLocation($this->getLanguageObject());

			case 'arrival_comment':
				if($this->_oTransferArrival === null) {
					return '';
				}
				return (string)$this->_oTransferArrival->comment;

			case 'departure_date':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oTransferDeparture->transfer_date);
				return (string)$oDateFormat->format($dDate);

			case 'departure_time':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->transfer_time;

			case 'departurel_airline':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->airline;

			case 'departure_flightnumber':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->flightnumber;

			case 'departure_pick_up':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->getStartLocation($this->getLanguageObject());

			case 'departure_drop_off':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->getEndLocation($this->getLanguageObject());

			case 'departure_comment':
				if($this->_oTransferDeparture === null) {
					return '';
				}
				return (string)$this->_oTransferDeparture->comment;

			case 'enquiry_comment_course_category':
				return (string)$this->getMainObject()->course_category;

			case 'enquiry_comment_course_intensity':
				return (string)$this->getMainObject()->course_intensity;

			case 'enquiry_comment_accommodation_category':
				return (string)$this->getMainObject()->accommodation_category;

			case 'enquiry_comment_accommodation_room':
				return (string)$this->getMainObject()->accommodation_room;

			case 'enquiry_comment_accommodation_meal':
				return (string)$this->getMainObject()->accommodation_meal;

			case 'enquiry_comment_transfer_category':
				return (string)$this->getMainObject()->transfer_category;

			case 'enquiry_comment_transfer_location':
				return (string)$this->getMainObject()->transfer_location;

		}

		$mValue = $this->searchPlaceholderInFirstPossibleLoopElement($sField);
		if($mValue !== null) {
			return $mValue;
		}

		$sFlexCategory = Ext_TC_Flexibility::getPlaceholderCategory($sField);

		if(!empty($sFlexCategory)) {
			switch($sFlexCategory) {
				case 'groups':
					return Ext_TC_Flexibility::getPlaceholderValue($sField, $this->_oEnquiry->id, false, $this->getLanguage(), 'enquiry');
				case 'enquiries':
					return Ext_TC_Flexibility::getPlaceholderValue($sField, $this->_oEnquiry->id, false, $this->getLanguage());
				case 'schools':
					return Ext_TC_Flexibility::getPlaceholderValue($sField, $this->_oEnquiry->school_id, false, $this->getLanguage());
				case 'agencies':
					return Ext_TC_Flexibility::getPlaceholderValue($sField, $this->_oEnquiry->agency_id, false, $this->getLanguage());
			}
		}

		return parent::searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);

	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Ext_TS_Enquiry_Contact_Traveller
	 */
	public function getCustomer() {
		return $this->_oCustomer;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Ext_Thebing_Agency
	 */
	public function getAgency() {
		return $this->_oAgency;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSchool() {
		return $this->getMainObject()->getSchool();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|Ext_Thebing_Agency_Contact
	 */
	public function getAgencyMasterContact() {
		return $this->getMainObject()->getAgencyContact();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|Ext_TS_Enquiry
	 */
	public function getMainObject() {
		return $this->_oEnquiry;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _helperReplaceVars($sText, $iOptionalId = 0) {

		$aReplacements = [
			[
				'@\{start_loop_courses\}(.*?)\{end_loop_courses\}@ims',
				function(array $aText) { return $this->_helperReplaceCourseLoop($aText); }
			],
			[
				'@\{start_loop_accommodations\}(.*?)\{end_loop_accommodations\}@ims',
				function(array $aText) { return $this->_helperReplaceAccommodationLoop($aText); }
			],
			[
				'@\{start_loop_insurances\}(.*?)\{end_loop_insurances\}@ims',
				function(array $aText) { return $this->_helperReplaceInsuranceLoop($aText); }
			],
		];

		foreach($aReplacements as $aReplacement) {
			$sText = preg_replace_callback($aReplacement[0], $aReplacement[1], $sText);
		}

		return parent::_helperReplaceVars($sText, $iOptionalId);

	}

	/**
	 * @param mixed[] $aText
	 * @return string
	 */
	private function _helperReplaceCourseLoop(array $aText) {

		$aCombinationCourses = $this->getMainObject()->getCourses();
		$sText = '';

		$mOriginalLoopCourse = $this->_oLoopCourse;
		foreach((array)$aCombinationCourses as $oCombinationCourse) {
			$this->_oLoopCourse = $oCombinationCourse;
			$sText .= $this->_helperReplaceVars($aText[1], $this->_oLoopCourse->id);
		}
		$this->_oLoopCourse = $mOriginalLoopCourse;

		return $sText;

	}

	/**
	 * @param string $sField
	 * @return null|string
	 */
	private function _searchCourseLoopPlaceholderValue($sField) {

		switch($sField) {

			case 'course':
				if($this->_oLoopCourse === null) {
					return '';
				}
				return (string)$this->_oLoopCourse->getCourseName($this->getLanguage());

			case 'course_weeks':
				if($this->_oLoopCourse === null) {
					return '';
				}
				return (string)$this->_oLoopCourse->weeks;

			case 'course_category':
				if($this->_oLoopCourse === null) {
					return '';
				}
				return (string)$this->_oLoopCourse->getCourse()->getCategory()->getName();

			case 'course_max_students':
				if($this->_oLoopCourse === null) {
					return '';
				}
				return (string)$this->_oLoopCourse->getCourse()->maximum_students;

			case 'date_course_start':
				if($this->_oLoopCourse === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oLoopCourse->from);
				return (string)$oDateFormat->format($dDate);

			case 'date_course_end':
				if($this->_oLoopCourse === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oLoopCourse->until);
				return (string)$oDateFormat->format($dDate);

			case 'lessons_per_week':
				if($this->_oLoopCourse === null) {
					return '';
				}

				// TODO korrekt?
				$lessons = $this->_oLoopCourse->getCourse()->getLessons();
				return (string)\Illuminate\Support\Arr::first($lessons->getLessons());

			default:

				$sFlexCategory = Ext_TC_Flexibility::getPlaceholderCategory($sField);
				if(
					$sFlexCategory === 'tuition_courses' ||
					$sFlexCategory === 'tuition_course_categories'
				) {
					if($this->_oLoopCourse === null) {
						return '';
					}

					if($sFlexCategory === 'tuition_courses') {
						$iId = $this->_oLoopCourse->course_id;
					} elseif($sFlexCategory === 'tuition_course_categories') {
						$oCourse = $this->_oLoopCourse->getCourse();
						$iId = $oCourse->category_id;
					}

					return Ext_TC_Flexibility::getPlaceholderValue($sField, $iId, false, $this->getLanguage());

				}

		}

	}

	/**
	 * @param mixed[] $aText
	 * @return string
	 */
	private function _helperReplaceAccommodationLoop(array $aText) {

		$aCombinationAccommodations = $this->getMainObject()->getAccommodations();
		$sText = '';

		$mOriginalLoopAccommodation = $this->_oLoopAccommodation;
		foreach((array)$aCombinationAccommodations as $oCombinationAccommodation) {
			$this->_oLoopAccommodation = $oCombinationAccommodation;
			$sText .= $this->_helperReplaceVars($aText[1], $this->_oLoopAccommodation->id);
		}
		$this->_oLoopAccommodation = $mOriginalLoopAccommodation;

		return $sText;

	}

	/**
	 * @param string $sField
	 * @return null|string
	 */
	private function _searchAccommodationLoopPlaceholderValue($sField) {

		switch($sField) {

			case 'accommodation_category':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->getCategory()->getName($this->getLanguage());

			case 'accommodation_weeks':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->weeks;

			case 'date_accommodation_start':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oLoopAccommodation->from);
				return (string)$oDateFormat->format($dDate);

			case 'date_accommodation_end':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('Y-m-d', $this->_oLoopAccommodation->until);
				return (string)$oDateFormat->format($dDate);

			case 'roomtype':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->getRoomType()->getShortName($this->getLanguage());

			case 'roomtype_full':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->getRoomType()->getName($this->getLanguage());

			case 'accommodation_meal':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->getMeal()->getShortName($this->getLanguage());

			case 'accommodation_meal_full':
				if($this->_oLoopAccommodation === null) {
					return '';
				}
				return (string)$this->_oLoopAccommodation->getMeal()->getName($this->getLanguage());

			default:

				$sFlexCategory = Ext_TC_Flexibility::getPlaceholderCategory($sField);
				if(
					$sFlexCategory === 'accommodations' ||
					$sFlexCategory === 'meals' ||
					$sFlexCategory === 'roomtypes'
				) {
					if($this->_oLoopAccommodation === null) {
						return '';
					}

					if($sFlexCategory === 'accommodations') {
						$iId = $this->_oLoopAccommodation->accommodation_id;
					} elseif($sFlexCategory === 'meals') {
						$iId = $this->_oLoopAccommodation->meal_id;
					} elseif($sFlexCategory === 'roomtypes') {
						$iId = $this->_oLoopAccommodation->roomtype_id;
					}

					return Ext_TC_Flexibility::getPlaceholderValue($sField, $iId, false, $this->getLanguage());

				}

		}

	}

	/**
	 * @param mixed[] $aText
	 * @return string
	 */
	private function _helperReplaceInsuranceLoop(array $aText) {

		$aCombinationInsurances = $this->getMainObject()->getInsurances();
		$sText = '';

		$mOriginalLoopInsurance = $this->_oLoopInsurance;
		foreach((array)$aCombinationInsurances as $oCombinationInsurance) {
			$this->_oLoopInsurance = $oCombinationInsurance;
			$sText .= $this->_helperReplaceVars($aText[1], $this->_oLoopInsurance->id);
		}
		$this->_oLoopInsurance = $mOriginalLoopInsurance;

		return $sText;

	}

	/**
	 * @param string $sField
	 * @return null|string
	 */
	private function _searchInsuranceLoopPlaceholderValue($sField) {

		switch($sField) {

			case 'insurance':
				if($this->_oLoopInsurance === null) {
					return '';
				}
				return (string)$this->_oLoopInsurance->getInsuranceName($this->getLanguage());

			case 'insurance_provider':
				if($this->_oLoopInsurance === null) {
					return '';
				}
				return (string)$this->_oLoopInsurance->getInsuranceProvider()->company;

			case 'date_insurance_start':
				if($this->_oLoopInsurance === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('U', $this->_oLoopInsurance->getInsuranceStart());
				return (string)$oDateFormat->format($dDate);

			case 'date_insurance_end':
				if($this->_oLoopInsurance === null) {
					return '';
				}
				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->getSchool()->id);
				$dDate = DateTime::createFromFormat('U', $this->_oLoopInsurance->getInsuranceEnd());
				return (string)$oDateFormat->format($dDate);

			case 'insurance_price':
				if($this->_oLoopInsurance === null) {
					return '';
				}
				$oSchool = $this->getSchool();
				$oCurrency = Ext_Thebing_Currency::getInstance($this->getMainObject()->getCurrency());
				$dStartDate = DateTime::createFromFormat('U', $this->_oLoopInsurance->getInsuranceStart());
				$dEndDate = DateTime::createFromFormat('U', $this->_oLoopInsurance->getInsuranceEnd());
				$fPrice = $this->_oLoopInsurance->getInsurance()->getInsurancePriceForPeriod(
					$oSchool,
					$oCurrency,
					$dStartDate,
					$dEndDate,
					$this->_oLoopInsurance->weeks
				);
				return (string)Ext_Thebing_Format::Number($fPrice, $oCurrency->id, $oSchool->id, true, 2);

		}

	}

	/**
	 * @param string $sField
	 * @return null|string
	 */
	private function searchPlaceholderInFirstPossibleLoopElement($sField) {

		$aCombinationCourses = $this->getMainObject()->getCourses();
		$mOriginalLoopCourse = $this->_oLoopCourse;
		if(!empty($aCombinationCourses)) {
			$this->_oLoopCourse = reset($aCombinationCourses);
		}
		$sText = $this->_searchCourseLoopPlaceholderValue($sField);
		$this->_oLoopCourse = $mOriginalLoopCourse;
		if($sText !== null) {
			return $sText;
		}

		$aCombinationAccommodations = $this->getMainObject()->getAccommodations();
		$mOriginalLoopAccommodation = $this->_oLoopAccommodation;
		if(!empty($aCombinationAccommodations)) {
			$this->_oLoopAccommodation = reset($aCombinationAccommodations);
		}
		$sText = $this->_searchAccommodationLoopPlaceholderValue($sField);
		$this->_oLoopAccommodation = $mOriginalLoopAccommodation;
		if($sText !== null) {
			return $sText;
		}

		$aCombinationInsurances = $this->getMainObject()->getInsurances();
		$mOriginalLoopInsurance = $this->_oLoopInsurance;
		if(!empty($aCombinationInsurances)) {
			$this->_oLoopInsurance = reset($aCombinationInsurances);
		}
		$sText = $this->_searchInsuranceLoopPlaceholderValue($sField);
		$this->_oLoopInsurance = $mOriginalLoopInsurance;
		if($sText !== null) {
			return $sText;
		}

	}

}
