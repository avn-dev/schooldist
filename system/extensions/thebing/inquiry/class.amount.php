<?php

use \Carbon\Carbon;

class Ext_Thebing_Inquiry_Amount {

	// Fehler
	public $aErrors = array();

	// Beträge die durch Special zu Stande gekommen sind
	protected $_aSpecialAmount = array();

	// Gefundene SpecialBlöcke
	protected $_aSpecialBlocks = array();

	protected $_idInquiry = 0;

	/**
	 * @var Ext_TS_Inquiry_Abstract
	 */
	protected $_oInquiry;
	
	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool;

	protected $_oCurrency;

	protected $_iFrom = 0;
	protected $_iTo = 0;
	protected $_iWeeks = 0;

	protected $_iSaisonStart = 0;

	protected $_iInquiryAccommodationId = 0;

	public $aCalculationDescription;

	public $aCurrencExtraNight = null;

	/**
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 */
	public function __construct(Ext_TS_Inquiry_Abstract $oInquiry) {

		$this->_oInquiry = $oInquiry;
		$this->_idInquiry = $oInquiry->id;
		$this->_oSchool = $oInquiry->getSchool();

		$oCurrency = new Ext_Thebing_Currency_Util($this->_oSchool->getId());
		$oCurrency->setCurrencyById($this->_oInquiry->getCurrency());
		$this->_oCurrency = $oCurrency;

	}

	/**
	 * Liefert den Spacial Betrag zu einer Buchungsposition
	 */
	public function getSpecialAmount($sType , $iTypeId){
		if(!in_array($sType.'_'.$iTypeId, $this->_aSpecialAmount)) {
			return false;
		}
		return $this->_aSpecialAmount[$sType.'_'.$iTypeId];
	}

	/**
	 * Liefert alle Specialbeträge
	 */
	public function getSpecialAmounts(){
		return $this->_aSpecialAmount;
	}

	/**
	 * Setzt den Special Betrag für eine Bestimmte Rechnungsposition (transfer, generelle Kosten)
	 * 
	 * @param string $sType
	 * @param int $iTypeId
	 * @param array $aSpecialBlocks
	 * @param float $fAmount
	 * @param boolean $bNetto
	 * @param array $aAdditionalIDs
	 */
	public function setSpecialAmount($sType, $iTypeId, \Ext_Thebing_Basic $object, $priceObject, $bNetto = false, $aAdditionalIDs = array()) {

		$aKey = array();
		$aKey[] = $sType;
		$aKey[] = $iTypeId;

		// Muss vorab passieren, da je nach Klasse die Special-Blocks hier erst ermittelt werden
		$fAmount = $priceObject->getSpecialAmount($bNetto);
		$aSpecialBlocks = $priceObject->getSpecialBlocks();

		if(count($aSpecialBlocks) > 0) {

			$specialCodes = $priceObject->getSpecialCodes();
			
			if(!empty($specialCodes)) {
				$aAdditionalIDs['special']['code_ids'] = array_column($specialCodes, 'id');
			}

			$this->_aSpecialAmount[implode('_', $aKey)]['object'] = $object;
			
			if($bNetto) {
				$this->_aSpecialAmount[implode('_', $aKey)]['net'] = $fAmount;
			} else {
				$this->_aSpecialAmount[implode('_', $aKey)]['gross'] = $fAmount;
			}

			// kann erst nach vollständiger Special-Preisberechnung ausgelesen werden
			$aBlocks = (array)$this->_aSpecialAmount[implode('_', $aKey)]['block'];
			$aBlocks += $aSpecialBlocks;
			$this->_aSpecialAmount[implode('_', $aKey)]['block'] = array_unique($aBlocks);
			
			// Zusatzinfos mit speichern
			if(!empty($aAdditionalIDs)) {
				$this->_aSpecialAmount[implode('_', $aKey)]['additional_info'] = $aAdditionalIDs;
			}

		}

	}

	/**
	 * @param mixed $mFrom
	 * @param mixed $mTo
	 * @param null|int $iWeeks
	 * @param null|int $iUnits
	 */
	public function setTimeData($mFrom, $mTo, $iWeeks = null, $iUnits = null) {
		
		if($mFrom instanceof \DateTime) {
			$this->_iFrom = $mFrom->getTimestamp();
		} else {
			$this->_iFrom = $mFrom;	
		}
		
		if($mTo instanceof \DateTime) {
			$this->_iTo = $mTo->getTimestamp();
		} else {
			$this->_iTo = $mTo;	
		}
		
		$this->_iWeeks = $iWeeks;
		$this->_iUnits = $iUnits;
		$this->_iSaisonStart = $this->_iFrom;
	}

	/**
	 * @param string $sDicountFor
	 * @param bool $bPriceSaisoin
	 * @param bool $bTeacherSaison
	 * @param bool $bTransferSaison
	 * @param bool $bAccommodationSaison
	 * @param bool $bFixcostSaison
	 * @param bool $bDiscountCheck
	 * @return \Ext_Thebing_Saison
	 */
	private function getCurrentSaison(
		$sDicountFor = 'course', $bPriceSaisoin = true, $bTeacherSaison = false, $bTransferSaison = false,
		$bAccommodationSaison = false, $bFixcostSaison = false, $bDiscountCheck = true
	) {

		$oInquiry = $this->_oInquiry;
		$oSchool = $this->_oSchool;
		$oSaison = new Ext_Thebing_Saison($oSchool);

		$aSaisonData = Ext_Thebing_Saison_Search::bySchoolAndTimestamp(
			$oSchool->id,
			$this->_iSaisonStart,
			$oInquiry->getCreatedForDiscount(),
			$sDicountFor,
			$bDiscountCheck,
			$bPriceSaisoin,
			$bTeacherSaison,
			$bTransferSaison,
			$bAccommodationSaison,
			$bFixcostSaison
		);

		$iSaisonId = (int)$aSaisonData[0]['id'];

		if($iSaisonId > 0) {
			$oSaison->setSaisonById($iSaisonId);
		}

		return $oSaison;
	}

	public function getCalculationDescription() {
		return $this->aCalculationDescription;
	}

	public function calculateCourseAmount($mInquiryCourse, $bNetto = false, $iStartWeek = 0, $sTmpItemKey = '') {

		$oInquiryCourse = null;
		$iInquiryCourseId = 0;

		if(
			is_object($mInquiryCourse) &&
			$mInquiryCourse instanceof Ext_TS_Service_Interface_Course
		) {
			$oInquiryCourse = $mInquiryCourse;
			$iInquiryCourseId = (int)$oInquiryCourse->id;
		} elseif(is_numeric($mInquiryCourse)) {
			$iInquiryCourseId = $mInquiryCourse;
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
		}

		$oCourseAmount = new Ext_Thebing_Course_Amount(); 
		$oCourseAmount->setInquiryCourse($oInquiryCourse);
		$fAmount = $oCourseAmount->calculate($bNetto, $iStartWeek);

		$this->aCalculationDescription = $oCourseAmount->getCalculationDescription();

		if(!empty($oCourseAmount->aWeekPrices)) {
			$this->aPeriods = [];
			foreach($oCourseAmount->aWeekPrices as $iTimeStamp=>$fPrice) {
				$this->aPeriods[Carbon::createFromTimestamp($iTimeStamp)->toDateString()] = $fPrice;
			}
		}

		// Hinweise/Fehler innerhalb der Preisberechnung
		$this->aErrors = array_merge_recursive($this->aErrors, $oCourseAmount->aErrors);

		foreach((array)$this->aErrors as $sKey => $aError) {
			$this->aErrors[$sKey] = array_unique((array)$aError);
		}

		// nicht verändern, ansonsten alle verwendeten Stellen anpassen (provisionen neu laden,..?)
		//$sSpecialHash = md5('course'.$iInquiryCourseId);

		$aSpecialAdditionalInfo = array(
			'parent_item_key' => $sTmpItemKey,
			'type' => 'course',
			'type_id' => (int)$iInquiryCourseId,
			'inquiry_journey_course_id' => (int)$iInquiryCourseId,
			'calculation' => $oCourseAmount->aSpecialCalculationDescription, // Mitschleifen zum Tooltip
			'periods' => $oCourseAmount->aSpecialPeriods
		);

		if(
			$oCourseAmount->oSpecialPeriod instanceof \Core\DTO\DateRange &&
			$oCourseAmount->oSpecialPeriod->from !== null &&
			$oCourseAmount->oSpecialPeriod->until !== null
		) {
			$aSpecialAdditionalInfo['from'] = $oCourseAmount->oSpecialPeriod->from->toDateString();
			$aSpecialAdditionalInfo['until'] = $oCourseAmount->oSpecialPeriod->until->toDateString();
		}
		
		// Objekt noch nicht gespeichert
		if(empty($iInquiryCourseId)) {
			$iInquiryCourseId = spl_object_hash($oInquiryCourse);
		}
		
		// Special
		if($bNetto) {
			$this->setSpecialAmount('course', $iInquiryCourseId, $oInquiryCourse, $oCourseAmount, true, $aSpecialAdditionalInfo);
		} else {
			$this->setSpecialAmount('course', $iInquiryCourseId, $oInquiryCourse, $oCourseAmount, false, $aSpecialAdditionalInfo);
		}

		return $fAmount;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Activity $oJourneyActivity
	 * @return \Ts\Model\Price
	 */
	public function calculateActivityAmount(Ext_TS_Inquiry_Journey_Activity $oJourneyActivity) {

		$oActivityAmount = new \TsActivities\Service\Amount();
		$oPrice = $oActivityAmount->calculate($oJourneyActivity);

		$this->aErrors = array_merge_recursive($this->aErrors, $oActivityAmount->aErrors);

		return $oPrice;
	}

	public function calculateAccommodationCost($iInquiryAccommodationId, $iFrom, $iUntil) {

		$fAmount = 0;

		if($iInquiryAccommodationId <= 0){
			return $fAmount;
		}

		$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);

		$this->_iFrom = $iFrom;
		$this->_iTo = $iUntil;
		$this->_iSaisonStart = $iFrom;
		$this->_iWeeks = $oInquiryAccommodation->weeks;
		$this->_iInquiryAccommodationId = $oInquiryAccommodation->id;

		$fAmount = $this->calculateAccommodationAmount($oInquiryAccommodation->accommodation_id, $oInquiryAccommodation->roomtype_id, $oInquiryAccommodation->meal_id);

		return $fAmount;

	}

	public function calculateAccommodationAmount($mInquirAccommodation, $bNetto = false, $iStartWeek = 0, $sTmpItemKey = '') {

		$oInquiryAccommodation = null;

		if(
			is_object($mInquirAccommodation) &&
			$mInquirAccommodation instanceof Ext_TS_Service_Interface_Accommodation
		) {
			$oInquiryAccommodation = $mInquirAccommodation;
			$iInquiryAccoId = (int)$oInquiryAccommodation->id;
		} elseif(is_numeric($mInquirAccommodation)) {
			$iInquiryAccoId = $mInquirAccommodation;
			$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccoId);
		}

		$oAccommodationAmount = new Ext_Thebing_Accommodation_Amount();
		$oAccommodationAmount->setInquiryAccommodation($oInquiryAccommodation);
		$fAmount = $oAccommodationAmount->calculate($bNetto, $iStartWeek);

		$this->aCalculationDescription = $oAccommodationAmount->getCalculationDescription();

		#$this->aSeasonErrors = $oAccommodationAmount->aSeasonErrors;

		// Hinweise/Fehler innerhalb der Preisberechnung
		$this->aErrors = array_merge_recursive($this->aErrors, $oAccommodationAmount->aErrors);

		foreach((array)$this->aErrors as $sKey => $aError){
			$this->aErrors[$sKey] = array_unique((array)$aError);
		}

		// nicht verändern, ansonsten alle verwendeten Stellen anpassen (provisionen neu laden,..?)
		#$sSpecialHash = md5('accommodation'.$iInquiryAccoId);

		$aSpecialAdditionalInfos = array(
			'parent_item_key' => $sTmpItemKey,
			'type' => 'accommodation',
			'type_id' => (int)$iInquiryAccoId,
			'inquiry_journey_accommodation_id' => (int)$iInquiryAccoId,
			'calculation' => $oAccommodationAmount->aSpecialCalculationDescription // Mitschleifen zum Tooltip
		);

		if($bNetto) {
			$this->setSpecialAmount('accommodation', $iInquiryAccoId, $oInquiryAccommodation, $oAccommodationAmount, true, $aSpecialAdditionalInfos);
		} else {
			$this->setSpecialAmount('accommodation', $iInquiryAccoId, $oInquiryAccommodation, $oAccommodationAmount, false, $aSpecialAdditionalInfos);
		}

		return $fAmount;

	}

	public function getExtraNightAmount($bNetto = false, $bCost = false, $mInquiryAccommodation = 0) {

		$oInquiryAccommodation = null;

		if(
			is_object($mInquiryAccommodation) &&
			$mInquiryAccommodation instanceof Ext_TS_Service_Interface_Accommodation
		) {
			$oInquiryAccommodation = $mInquiryAccommodation;
			$iInquiryAccommodationId = (int)$oInquiryAccommodation->id;
		} elseif(is_numeric($mInquiryAccommodation)) {
			$iInquiryAccommodationId = $mInquiryAccommodation;
			$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);
		}

//		if($iInquiryAccommodationId <= 0) {
//			return 0;
//		}

		$oAccommodationAmount = new Ext_Thebing_Accommodation_Amount();
		$oAccommodationAmount->setInquiryAccommodation($oInquiryAccommodation);
		$oAccommodationAmount->aCurrencExtraNight = $this->aCurrencExtraNight; 

		$fAmount = $oAccommodationAmount->calculateExtraNight($bNetto);

		$this->aSeasonErrors = $oAccommodationAmount->aSeasonErrors;

		return $fAmount;

	}

	public function getExtraWeekAmount($bNetto = false, $mInquirAccommodation = 0) {

		$oAccommodationAmount = new Ext_Thebing_Accommodation_Amount();
		$oAccommodationAmount->setInquiryAccommodation($mInquirAccommodation);
		return $oAccommodationAmount->calculateExtraWeek($bNetto);

	}

	/**
	 * @param Ext_TS_Service_Interface_Insurance|Ext_TS_Inquiry_Journey_Insurance|Ext_TS_Enquiry_Combination_Insurance $oJourneyInsurance
	 * @return float
	 */
	public function calculateInsuranceAmount(Ext_TS_Service_Interface_Insurance $oJourneyInsurance) {

		$oInquiry = $oJourneyInsurance->getInquiry();
		$oInsurance = $oJourneyInsurance->getInsurance();

		$aData = [
			'id' => $oJourneyInsurance->id,
			'insurance_id' => $oInsurance->id,
			//'insurance' => $oJourneyInsurance->getInsuranceName($sDisplayLanguage),
			'from' => (new DateTime($oJourneyInsurance->from))->getTimestamp(),
			'until' => (new DateTime($oJourneyInsurance->getUntil()))->getTimestamp(),
			'weeks' => $oJourneyInsurance->weeks,
			'payment' => $oInsurance->payment,
			'price' => 0,
			'currency_id' => $oInquiry->currency_id,
			'school_id' => $oJourneyInsurance->getSchool()->id,
			'inquiry_created' => $oInquiry->getCreatedForDiscount()
		];

		$oWhatever = new Ext_Thebing_Insurances_Gui2_Customer($aResult);
		$aData = $oWhatever->format([$aData]);

		$this->aCalculationDescription = $oWhatever->getCalculationDescription();

		return $aData[0]['price'];
	}

	public function calculateAdditionalAccommodationCost(Ext_Thebing_School_Additionalcost $additionalCost, \DateTime $from, \DateTime $until, bool $net=false, int $parentId=0) {
		
		$weeks = ceil($until->diff($from)->days / 7);

		$amount = 0;
		
		// Pro Nacht
		if($additionalCost->calculate == 3) {

			$period = new DatePeriod($from, new DateInterval('P1D'), $until);
			foreach($period as $dDate) {
				/** @var DateTime $dDate */
				$this->setTimeData($dDate->getTimestamp(), $dDate->getTimestamp());
				$amount += $this->calculateAdditionalCost($additionalCost, $net, 'accommodation', $parentId);
			}

		}
		// Pro Woche
		elseif((int)$additionalCost->calculate == 2){

			$period = new DatePeriod($from, new DateInterval('P1W'), $until);
			foreach($period as $date) {
				$this->setTimeData($date, $date, 1);
				$amount += $this->calculateAdditionalCost($additionalCost, $net, 'accommodation', $parentId);
			}
			
		} else {
			$this->setTimeData($from, $until, $weeks);
			$amount = $this->calculateAdditionalCost($additionalCost, $net, 'accommodation', $parentId);
		}

		return $amount;
	}
	
	public function calculateAdditionalCost(Ext_Thebing_School_Additionalcost $additionalCost, $bNetto = false,$sFor = 'course', $iParentid = 0, $bGeneralCost = false) {

		$iId = $additionalCost->id;
		
		$oInquiry = $this->_oInquiry; 
		$oSchool = $this->_oSchool;
		$oSaison = $this->getCurrentSaison($sFor);
		$oCurrency = $this->_oCurrency;
		$oPrice = new Ext_Thebing_Price($oSchool, $oSaison, $oCurrency);

		$nationality = $oInquiry->getCustomer()->nationality;
		$agencyCategory = null;

		$oAgency = null;
		if($bNetto == true) {
			$oAgency = Ext_Thebing_Agency::getInstance($oInquiry->agency_id);
			$oAgency->setSchool($oSchool);
			$oAgency->setSaison($oSaison);
			//$oSchoolProvision = $oAgency->getSchoolProvisions($oSaison->getSaisonId());

			$agencyCategory = $oAgency->getCategory();
		}

		$iPrice = null;
		if($sFor == 'accommodation' && $iParentid > 0) {

			if ($agencyCategory) {
				$oFee = new Ext_Thebing_Accommodation_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), null, $agencyCategory, null);
				$iPrice = $oFee->getFeeAmount();
			}

			// Fallback to nationality and country group
			if (
				$iPrice === null &&
				$nationality
			) {

				$oFee = new Ext_Thebing_Accommodation_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), $nationality, null, null);
				$iPrice = $oFee->getFeeAmount();
			}

			// Fallback to all null
			if($iPrice === null) {
				$oFee = new Ext_Thebing_Accommodation_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), null, null, null);
				$iPrice = $oFee->getFeeAmount();
			}
			
		} elseif($sFor == 'course' && $iParentid > 0) {

			if ($agencyCategory) {
				$oFee = new Ext_Thebing_Course_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), null, $agencyCategory, null);
				$iPrice = $oFee->getFeeAmount();
			}

			// Fallback to nationality and country group
			if (
				$iPrice === null &&
				$nationality
			) {

				$oFee = new Ext_Thebing_Course_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), $nationality, null, null);
				$iPrice = $oFee->getFeeAmount();
			}

			// Fallback to all null
			if($iPrice === null) {
				$oFee = new Ext_Thebing_Course_Fee($iParentid, $iId, $oSaison->id, $oCurrency->getCurrencyId(), null, null, null);
				$iPrice = $oFee->getFeeAmount();
			}
			
		} else {
			$iPrice = $oPrice->getAdditionalCost($iId, false);
		}

		// Special Beträge merken //////////////////////////////////////////////////////////////////////
		/*
		$iPrice = $oInquiry->calculateSpecialAmount($iPrice, $this->_iFrom, 'additional', $iId);
		if($bNetto == false){
			// Beträge für die einzelnen Special merken um später den einmaligen betrag errechnen zu können
			$_SESSION['special_amounts'][$oInquiry->id][$oInquiry->iLastSpecialId]['brutto'][] = $iPrice;	
		}
		*/
		////////////////////////////////////////////////////////////////////////////////////////////////

		if($bNetto == true){ 

			// Provision
			$oSchoolProvision = $oAgency->getSchoolProvisions($oSaison->getSaisonId());

			if($bGeneralCost) {
				$oProvision = $oSchoolProvision->getGeneralProvision($iId, $iParentid, $sFor);
			} else {
				$oProvision = $oSchoolProvision->getAdditionalProvision($iId, $iParentid, $sFor);
			}

			if ($oProvision) {
				$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
			}

			// Beträge für die einzelnen Special merken um später den einmaligen betrag errechnen zu können
			//$_SESSION['special_amounts'][$oInquiry->id][$oInquiry->iLastSpecialId]['netto'][] = $iPrice;

		}

		return $iPrice;
	}

	public function getTransferProvision($oTransfer, $bTwoWay = false): ?\Ts\Dto\Commission {

		$oInquiry = $this->_oInquiry;
		$oSaison = $this->getCurrentSaison('course');

		if($oInquiry->agency_id > 0) {
			$oAgency = $oInquiry->getAgency();
			$oAgency->setSaison($oSaison);
			$oSchoolProvision = $oAgency->getSchoolProvisions($oSaison->getSaisonId());
			return $oSchoolProvision->getTransferProvision($oTransfer, $bTwoWay);
		}

		return null;

	}

	public function getCancellationAmount() {

		$iAmountStorno = 0;
		$iDynamicStorno = 0;
		$oInquiry = $this->_oInquiry;
		$iCanceledAmount = $oInquiry->canceled_amount;
		//$aStornoArray = array();

		// return storno calculated before in out of DB
		if($iCanceledAmount > 0) {
			return $iCanceledAmount;
		}

		$oCancellationAmount = new Ext_Thebing_Cancellation_Amount($oInquiry);
		// items aufbauen damit das rechnen funktioniert
		$oCancellationAmount->initItems();

		$iAmountStorno = $oCancellationAmount->getCancellationAmount();
		$iDynamicStorno = $oCancellationAmount->getCancellationAmountDynamic();

		// return new calculated storno amount
		return $iAmountStorno + $iDynamicStorno;

	}

	public static function getPaymentMethods($oLanguage=null) {
		return Ext_Thebing_Agency::getAgencyPaymentMethods($oLanguage);
	}

	/**
	 * Die Funktion liefert den Rabatt Prozentsatz zu einer Buchungsposition eines PC
	 */
	public static function getAmountDiscount($iItemId, $sTyp = 'course', $iPCId = 0){
		$aReturn = array();

		switch($sTyp){
			case 'course':
				if($iPCId > 0){
					// Es gibt schon einen PC und es wird geguckt ob beim vorherigen PC ein Rabatt zu der Position existiert
					$sSql = "SELECT 
								`kipcp`.`amount_discount`,
								`kipcp`.`description_discount`,
								`kipcp`.`tax_category`
							FROM
								`kolumbus_inquiries_program_change_positions` AS `kipcp` INNER JOIN
								`kolumbus_inquiries_program_change_courses` AS `kipcc`
									ON `kipcc`.`id_program_change` = `kipcp`.`program_change_id`
							WHERE
								`kipcp`.`program_change_id` = :pc_id AND
								`kipcc`.`id_course_new` = :id";
					$aSql['pc_id'] = (int)$iPCId;
					$aSql['id'] = (int)$iItemId;
					$aResult = DB::getPreparedQueryData($sSql,$aSql);
				}else{
					// Es wurde noch kein PC gemacht daher suche Rabatt der ursprünglichen Buchung
					$sSql = "SELECT
						`kidp`.`amount_discount`,
						`kidp`.`description_discount`,
						`kidp`.`tax_category`
					FROM
						`kolumbus_inquiries_documents_positions` AS `kidp` INNER JOIN
						`ts_inquiries_journeys_courses` AS `kic`
							ON `kic`.`id` = `kidp`.`type_id`
					WHERE
						`kic`.`id` = :id";
					$aSql['id'] = (int)$iItemId;
					$aResult = DB::getPreparedQueryData($sSql,$aSql);
				}
				break;
				
			case 'accommodation':
				if($iPCId > 0){
					// Es gibt schon einen PC und es wird geguckt ob beim vorherigen PC ein Rabatt zu der Position existiert
					$sSql = "SELECT 
								`kipcp`.`amount_discount`,
								`kipcp`.`description_discount`,
								`kipcp`.`tax_category`
							FROM
								`kolumbus_inquiries_program_change_positions` AS `kipcp` INNER JOIN
								`kolumbus_inquiries_program_change_accommodations` AS `kipca`
									ON `kipca`.`id_program_change` = `kipcp`.`program_change_id`
							WHERE
								`kipcp`.`program_change_id` = :pc_id AND
								`kipcc`.`id_course_new` = :id";
					$aSql['pc_id'] = (int)$iPCId;
					$aSql['id'] = (int)$iItemId;
					$aResult = DB::getPreparedQueryData($sSql,$aSql);
				}else{
					// Es wurde noch kein PC gemacht daher suche Rabatt der ursprünglichen Buchung
					$sSql = "SELECT
						`kidp`.`amount_discount`,
						`kidp`.`description_discount`,
						`kidp`.`tax_category`
					FROM
						`kolumbus_inquiries_documents_positions` AS `kidp` INNER JOIN
						`ts_inquiries_journeys_accommodations` AS `kia`
							ON `kia`.`id` = `kidp`.`type_id`
					WHERE
						`kia`.`id` = :id";
					$aSql['id'] = (int)$iItemId;
					$aResult = DB::getPreparedQueryData($sSql,$aSql);
				}
				break;
		}

		if(!empty($aResult)){
			$aReturn['amount_discount'] 		= $aResult[0]['amount_discount'];
			$aReturn['description_discount'] 	= $aResult[0]['description_discount'];
			$aReturn['tax_category'] 			= $aResult[0]['tax_category'];
		}

		return $aReturn;
	}

}
