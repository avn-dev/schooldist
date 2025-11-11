<?php

class Ext_Thebing_Cancellation_Amount
{

	/**
	 * @var Ext_TS_Inquiry_Abstract
	 */
	protected $_oInquiry;
	protected $_iCurrencyId					= 0;
	protected $_aDocuments;
	protected $_aCancellationDates			= array();
	protected $_iCancellationAmount			= 0;
	protected $_iCancellationAmountDynamic	= 0;
	protected $_aCancellationItems			= array();
	protected $_sLanguage					= '';
	
	/**
	 * @var \Tc\Service\Language\Frontend 
	 */
	protected $oLanguage = null;

	protected $_aCancellationTypes			= array();
	protected $_aItems						= array();
	private static $_oInstance				= null;
	protected $_iCourseCount				= 0;
	protected $_iAccommodationCount			= 0;

	public function __construct($oInquiry, $aDocuments = null, $sLanguage='', $bPrepareDocs=false)
	{
		$this->_oInquiry	= $oInquiry;
		$this->_iCurrencyId = (int)$oInquiry->getCurrency();
		
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof Ext_TS_Inquiry
		)
		{
			$oSchool			= $oInquiry->getSchool();
			
			if(empty($sLanguage))
			{
				$sLanguage	= $oSchool->getInterfaceLanguage();
			}
		}
		else
		{
			$oSchool = Ext_Thebing_Client::getFirstSchool();
		}

		$this->_aDocuments	= $aDocuments;
		
		$this->_sLanguage = $sLanguage;

		$this->oLanguage = new \Tc\Service\Language\Frontend($sLanguage);
		
		$this->_aCancellationTypes	= (array)$oSchool->getStornoTypeFromOptions($this->oLanguage);

		$this->_buildInvoiceItems($bPrepareDocs);
		
		$this->_buildCancellationDates();
	}

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param array $aDocuments
	 * @param string $sLanguage
	 * @param bool $bPrepareDocs
	 * @return self
	 */
	public static function getInstance($oInquiry, $aDocuments = null, $sLanguage='', $bPrepareDocs=false)
	{
		if(
			self::$_oInstance === null
		)
		{
			self::$_oInstance = new self($oInquiry, $aDocuments, $sLanguage, $bPrepareDocs);
		}

		return self::$_oInstance;
	}

	//items aufbauen
	public function initItems()
	{
		$this->_buildCancellationData();
	}

	protected function _buildCancellationData()
	{
		$oInquiry			= $this->_oInquiry;
		$oSchool			= $oInquiry->getSchool();
		$iSchoolId			= (int)$oSchool->id;

		$aCancellationItems = array();

		if($oInquiry->agency_id > 0){
			$sCustomerType	= 'agency_customer';
			$iItemId		= $oInquiry->agency_id;
		}else{
			$sCustomerType	= 'customer';
			$iItemId		= $oSchool->id;
		}

		$aStornoArray		= $this->getCancellationDates();

		if(empty($aStornoArray['firstAll']))
		{
			$aStornoArray['firstAll'] = time();
		}

		$oWdDate = new WDDate();
		$iNow = $oWdDate->get(WDDate::TIMESTAMP);
		$oWdDate->set($aStornoArray['firstAll'], WDDate::TIMESTAMP);
		$iDaysToStart = $oWdDate->getDiff(WDDate::DAY, $iNow, WDDate::TIMESTAMP);

		$iCurrencyId = $this->_iCurrencyId;

		$oCancellationFee	= new Ext_Thebing_Cancellation_Fee();
		$aCancellationFees	= $oCancellationFee->getMatchingFee($iDaysToStart, $sCustomerType, $iItemId, $iCurrencyId);

		// Dynamic Storno -----------------------------------------------------
		$aResult = array();

		foreach($aCancellationFees as $oCancellationFee)
		{
			$oDynamic = $oCancellationFee->getDynamicAmount();

			$aResult += array_merge($aResult, $oDynamic->toArray());
		}

		$iDynamicStorno			= 0;

		$fAmountAll				= 0;
		$fAmountNetAll			= 0;
		$fAmountProvisionAll	= 0;
		$fAmountDiscountAll		= 0;
		$fAmountWithoutTaxAll	= 0;
		$fAmountWithTaxAll		= 0;

		if(!empty($aResult))
		{
			$aItems = (array)$this->_aItems;

			foreach($aItems as $iKey => $aItem)
			{
				$fAmountAll				+= $aItem['amount'];
				#$fAmountNetAll			+= $aItem['amount_net'];
				$fAmountProvisionAll	+= $aItem['amount_provision'];

				$fAmountNet				= $aItem['amount'] - $aItem['amount_provision'];
				$fAmountNetAll			+= $fAmountNet;

				$fAmountDiscount		= ($fAmountNet * $aItem['amount_discount']) / 100;
				$fAmountDiscountAll		+= $fAmountDiscount;

				$fAmountWithoutTax		= $fAmountNet - $fAmountDiscount;
				$fAmountWithoutTaxAll	+= $fAmountWithoutTax;

				if($oSchool->tax==2) {
					$fAmountWithTaxAll		+= $fAmountWithoutTax + ($fAmountWithoutTax * $aItem['tax'] / 100);
				} elseif($oSchool->tax==1) {
					$fAmountWithTaxAll		+= $fAmountWithoutTax;# / (1 + $aItem['tax'] / 100); # Preis ist inklusive, also müssen Steuern nicht noch addiert werden.
				} else {
					$fAmountWithTaxAll		+= $fAmountWithoutTax;
				}
				
			}

			$aResult						= $this->_bindCancellationItemsWithInvoiceItems($aResult);

			// Extra Storno Amount Type = "all"
			foreach ($aResult as $aStorno) {

				if ($aStorno['type'] === 'all') {

					$iCancellationTaxCategoryId		= $aStorno['tax_category_id'];
					$iCancellationTax				= Ext_TS_Vat::getTaxRate($aStorno['tax_category_id'],$iSchoolId);

					if($oSchool->tax==2)
					{
						$fCancellationAllWithoutTax		= $fAmountWithTaxAll / (1+$iCancellationTax/100);
					}
					elseif($oSchool->tax==1)
					{
						$fCancellationAllWithoutTax		= $fAmountWithTaxAll * (1+$iCancellationTax/100);
					}
					else
					{
						$fCancellationAllWithoutTax		= $fAmountWithoutTaxAll;
					}

					$fFactorDiscount = 0;
					if($fAmountWithoutTaxAll != 0) {
						$fFactorDiscount = $fAmountDiscountAll / $fAmountWithoutTaxAll;
					}
					$fCancellationDiscountAll		= $fCancellationAllWithoutTax * $fFactorDiscount;
					$fCancellationNetAll			= $fCancellationDiscountAll + $fCancellationAllWithoutTax;
					
					$fCancellationDiscountFactor = 0;
					if($fCancellationNetAll != 0) {
						$fCancellationDiscountFactor = $fCancellationDiscountAll / $fCancellationNetAll * 100;
					}
					
					$fFactorProvision = 0;
					if($fAmountNetAll != 0) {
						$fFactorProvision = $fAmountProvisionAll / $fAmountNetAll;
					}
					
					$fCancellationProvisionAll		= $fCancellationNetAll * $fFactorProvision;
					$fCancellationAmountAll			= $fCancellationProvisionAll + $fCancellationNetAll;

					$fCurrentAmount = 0;
					$fCurrentAmountNet = 0;
					$fCurrentAmountProvision = 0;
					$fCurrentFactorDiscount = 0;

					if($aStorno['kind'] == 1){
						$fCurrentAmount = ($fCancellationAmountAll/100) * $aStorno['amount'];
						$fCurrentAmountNet = ($fCancellationNetAll/100) * $aStorno['amount'];
						$fCurrentAmountProvision = ($fCancellationProvisionAll/100) * $aStorno['amount'];
						$fCurrentFactorDiscount = $fCancellationDiscountFactor;

						$this->_iCancellationAmountDynamic += ($fAmountWithTaxAll/100) * $aStorno['amount'];

					}elseif($aStorno['kind'] == 2){
						$fCurrentAmount = $aStorno['amount'];
						$fCurrentAmountNet = $aStorno['amount'];

						$this->_iCancellationAmountDynamic += $fCurrentAmountNet;
					}

					$sDescription = $this->getDescription($aStorno['amount'], $aStorno['kind'], 'all');

					$oDateRange = $oInquiry->getCompleteServiceTimeframe();

					$aCancellationItem = array(
						'description'		=> $sDescription,
						'item_type'			=> 'storno',
						'amount'			=> $fCurrentAmount,
						'amount_net'		=> $fCurrentAmountNet,
						'amount_provision'	=> $fCurrentAmountProvision,
						'amount_discount'	=> $fCurrentFactorDiscount,
						'tax_category'		=> $iCancellationTaxCategoryId,
						'additional_info'	=> array(
							'cancellation_fee_id' => $aStorno['cancellation_fee_id'],
							'cancellation_type'	=> 'all',
							'fee_type'			=> $aStorno['kind'],
							'fee_value'			=> $aStorno['amount']
						)
					);
					
					// Wenn die Buchung keine Leistungen mehr hat, gibt es keinen Zeitraum
					if ($oDateRange !== null) {
						$aCancellationItem['index_from'] = $oDateRange->start->format('Y-m-d');
						$aCancellationItem['index_until'] = $oDateRange->end->format('Y-m-d');
					}
					
					$aCancellationItems[] = $aCancellationItem;
					
				} else {

					$aRelationItems = (array)$aStorno['items'];

					foreach($aRelationItems as $iItemKey => $aIntersectKeys)
					{
						if(isset($this->_aItems[$iItemKey]))
						{
							$sItemOldType = $aStorno['type'];

							$aStorno['type'] = \Illuminate\Support\Arr::first($aIntersectKeys);
							$aCancellationItem = $this->_buildCancellationItem($aStorno, $this->_aItems[$iItemKey]);
							$aStorno['type'] = $sItemOldType;

							if(!empty($aCancellationItem))
							{
								$aCancellationItems[] = $aCancellationItem;
							}
						}
					
					}
				}

			}
		}

		$this->checkMinimumAmount($aCancellationItems);

		//Cache Storno Positionen
		$this->_aCancellationItems = $aCancellationItems;
	}

	public function getCancellationAmount() {
		return $this->_iCancellationAmount;
	}

	public function getCancellationAmountDynamic()
	{
		return $this->_iCancellationAmountDynamic;
	}

	public function getCancellationDates()
	{
		return $this->_aCancellationDates;
	}

	public function getCancellationItems()
	{
		return $this->_aCancellationItems;
	}

	public function getDescription($mFeeValue, $iFeeType, $sCancellationType, $sItemType='',$iParentBookingId=0, $iTypeId=0) {

		$aCancellationTypes = $this->_aCancellationTypes;
		$sLanguage			= $this->_sLanguage;

		$sTitle				= '%fee_value%fee_type auf %cancellation_type';
		$sTitle				= $this->oLanguage->translate($sTitle);

		$iBookingId			= $iTypeId;
		if($iParentBookingId>0){
			$iBookingId		= $iParentBookingId;
		}

		if(
			$iBookingId > 0 && 
			strlen($sItemType) > 0
		) {
			if(
				(
					$sItemType == 'course' ||
					$sItemType == 'additional_course'
				) &&
				$this->_iCourseCount > 1
			) {
				$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iBookingId);
				$sTitle .= ' ('.$oInquiryCourse->getInfo(null,$sLanguage).')';
			}
			if(
				(
					$sItemType == 'accommodation' ||
					$sItemType == 'additional_accommodation'
				) &&
				$this->_iAccommodationCount > 1
			) {
				$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iBookingId);
				$sTitle .= ' ('.$oInquiryAccommodation->getInfo(false, $sLanguage, false).')';
			}
		}

		if($iFeeType == 1)
		{
			$sFeeType = '%';
		}
		else
		{
			$aCurrencyData	= Ext_Thebing_Currency_Util::getCurrencyDataById($this->_iCurrencyId);
			$sSign			= $aCurrencyData['sign'];

			$sFeeType = ' '.$sSign;
		}


		$sTitle = str_replace('%fee_value', $mFeeValue, $sTitle);
		$sTitle = str_replace('%fee_type', $sFeeType, $sTitle);
		$sTitle = str_replace('%cancellation_type', $aCancellationTypes[$sCancellationType], $sTitle);

		return $sTitle;

	}

	protected function _buildCancellationDates()
	{
		$aStornoArray		= array();
		$oInquiry			= $this->_oInquiry;

		if(is_array($this->_aDocuments))
		{
			$aItems = (array)$this->_aItems;

			$aCourses = $aAccommodations = array();

			foreach($aItems as $aItem)
			{
				if($aItem['type'] == 'course')
				{
					$aCourses[] = Ext_TS_Inquiry_Journey_Course::getInstance($aItem['type_id']);
				}
				else if($aItem['type'] == 'accommodation')
				{
					$aAccommodations[] = Ext_TS_Inquiry_Journey_Accommodation::getInstance($aItem['type_id']);
				}
			}
		}
		else
		{
			// All Courses in Inquiry
			$aCourses = $oInquiry->getCourses();
			// All Accommodations in Inquiry
			$aAccommodations = $oInquiry->getAccommodations();
		}

		$aStornoArray['firstCourse']	= 0;
		$aStornoArray['lastCourse']		= 0;
		$aStornoArray['allWeekCourse']	= 0;

		foreach($aCourses as $oCourse){
			// check whitch Course is first
			if(Ext_Thebing_Util::convertDateToTimestamp($oCourse->from) < $aStornoArray['firstCourse'] && $aStornoArray['firstCourse'] != 0){
			 	$aStornoArray['firstCourse'] = Ext_Thebing_Util::convertDateToTimestamp($oCourse->from);
			}elseif($aStornoArray['firstCourse'] == 0){
				$aStornoArray['firstCourse'] = Ext_Thebing_Util::convertDateToTimestamp($oCourse->from);
			}
			// check whitch Course is last ending
			if(Ext_Thebing_Util::convertDateToTimestamp($oCourse->until) > $aStornoArray['lastCourse'] && $aStornoArray['lastCourse'] != 0){
			 	$aStornoArray['lastCourse'] = Ext_Thebing_Util::convertDateToTimestamp($oCourse->until);
			}elseif($aStornoArray['lastCourse'] == 0){
				$aStornoArray['lastCourse'] = Ext_Thebing_Util::convertDateToTimestamp($oCourse->until);
			}
			// All Courses together
			$aStornoArray['allWeekCourse'] += $oCourse->weeks;
			$this->_iCourseCount += 1;
		}

		$aStornoArray['firstAccommodation']		= 0;
		$aStornoArray['lastAccommodation']		= 0;
		$aStornoArray['allWeekAccommodation']	= 0;

		foreach($aAccommodations as $oAccom){
			// check whitch Accommodation is first
			if(Ext_Thebing_Util::convertDateToTimestamp($oAccom->from) < $aStornoArray['firstAccommodation'] && $aStornoArray['firstAccommodation'] != 0){
			 	$aStornoArray['firstAccommodation'] = Ext_Thebing_Util::convertDateToTimestamp($oAccom->from);
			}elseif($aStornoArray['firstAccommodation'] == 0){
				$aStornoArray['firstAccommodation'] = Ext_Thebing_Util::convertDateToTimestamp($oAccom->from);
			}
			// check whitch Accommodation is last ending
			if(Ext_Thebing_Util::convertDateToTimestamp($oAccom->until) > $aStornoArray['lastAccommodation'] && $aStornoArray['lastAccommodation'] != 0){
			 	$aStornoArray['lastAccommodation'] = Ext_Thebing_Util::convertDateToTimestamp($oAccom->until);
			}elseif($aStornoArray['lastAccommodation'] == 0){
				$aStornoArray['lastAccommodation'] = Ext_Thebing_Util::convertDateToTimestamp($oAccom->until);
			}
			$aStornoArray['allWeekAccommodation'] += $oAccom->weeks;
			$this->_iAccommodationCount += 1;
		}

		// general time customer stays at school
		if($aStornoArray['firstCourse'] > 0 && $aStornoArray['firstAccommodation'] > 0){
			// course AND Accommodation in Inquiry
			$aStornoArray['firstAll'] = ($aStornoArray['firstCourse'] < $aStornoArray['firstAccommodation']) ? $aStornoArray['firstCourse']: $aStornoArray['firstAccommodation'];
			$aStornoArray['lastAll'] = ($aStornoArray['lastCourse'] > $aStornoArray['lastAccommodation']) ? $aStornoArray['lastCourse']: $aStornoArray['lastAccommodation'];
		}elseif($aStornoArray['firstCourse'] > 0 && $aStornoArray['firstAccommodation'] == 0){
			// Only Course
			$aStornoArray['firstAll'] = $aStornoArray['firstCourse'];
			$aStornoArray['lastAll'] = $aStornoArray['lastCourse'];
		}elseif($aStornoArray['firstCourse'] == 0 && $aStornoArray['firstAccommodation'] > 0){
			// Only Accommodation
			$aStornoArray['firstAll'] = $aStornoArray['firstAccommodation'];
			$aStornoArray['lastAll'] = $aStornoArray['lastAccommodation'];
		}

		$aStornoArray['allWeek']	= $aStornoArray['allWeekCourse'] + $aStornoArray['allWeekAccommodation'];

		//Cache
		$this->_aCancellationDates	= $aStornoArray;
	}

	public static function prepareCancellationDocs($aDocs)
	{
		$aDocsNew = array();

		foreach($aDocs as $iKey => $oDoc)
		{

			if(strpos($oDoc->type, 'storno') !== false)
			{
				continue;
			}

			//Gutgeschriebene Rechnungen + Gutschriften ausblenden
			$iNextKey	= $iKey+1;
			$iPrevKey	= $iKey-1;
			if(
				(
					$oDoc->type == 'netto' &&
					$oDoc->is_credit == 1 &&
					isset($aDocs[$iNextKey]) &&
					$aDocs[$iNextKey]->type == 'netto' &&
					$aDocs[$iNextKey]->is_credit == 0
				) ||
				(
					$oDoc->type == 'brutto' &&
					$oDoc->is_credit == 1 &&
					isset($aDocs[$iNextKey]) &&
					$aDocs[$iNextKey]->type == 'brutto' &&
					$aDocs[$iNextKey]->is_credit == 0
				)
			){
				continue;
			}
			if(
				(
					$oDoc->type == 'netto' &&
					$oDoc->is_credit == 0 &&
					isset($aDocs[$iPrevKey]) &&
					$aDocs[$iPrevKey]->type == 'netto' &&
					$aDocs[$iPrevKey]->is_credit == 1
				) ||
				(
					$oDoc->type == 'brutto' &&
					$oDoc->is_credit == 0 &&
					isset($aDocs[$iPrevKey]) &&
					$aDocs[$iPrevKey]->type == 'brutto' &&
					$aDocs[$iPrevKey]->is_credit == 1
				)
			){
				continue;
			}

			$aDocsNew[] = $oDoc;

		}

		return $aDocsNew;
	}

	protected function _buildInvoiceItems($bPrepareDocs)
	{
		$oInquiry	= $this->_oInquiry;
		$aItems		= array();

		if(is_array($this->_aDocuments))
		{
			$aDocs = $this->_aDocuments;
		}
		else
		{
            $oTypeSearch = new Ext_Thebing_Inquiry_Document_Type_Search();
            $oTypeSearch->addSection('invoice_without_proforma');
            $oTypeSearch->remove('brutto_diff_special');
			$aDocs = (array)Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, $oTypeSearch, true, true);
		}

		if($bPrepareDocs)
		{
			$aDocs = self::prepareCancellationDocs($aDocs);
		}

		foreach($aDocs as $iKey => $oDoc)
		{
			/* @var Ext_Thebing_Inquiry_Document_Version $oVersion*/
			$oVersion	= $oDoc->getLastVersion();
			$aDocItems	= $oVersion->getItems(null,false,true,false);

			if(is_array($aDocItems)){
                $aItems		= array_merge($aItems,$aDocItems);
            }
		}

		$this->_aItems	= $aItems;
	}

	protected function _buildCancellationItem($aStorno,$aItem)
	{
		$aCancellationItem = array();

		$sType		= $aStorno['type'];
		$sItemType	= $aItem['type'];
		$sArrayKey	= false;

		if (str_starts_with($sType, 'course')) {
			$sArrayKey = 'allWeekCourse';
		} else if (str_starts_with($sType, 'accommodation')) {
			$sArrayKey = 'allWeekAccommodation';
		} else if (str_starts_with($sType, 'additional')) {
			if ($sItemType === 'additional_course') {
				$sArrayKey = 'allWeekCourse';
			} else if ($sItemType === 'additional_accommodation'){
				$sArrayKey = 'allWeekAccommodation';
			} else if ($sItemType === 'additional_general'){
				$sArrayKey = 'allWeek';
			}
		}

		if(!$sArrayKey){
			return array();
		}

		$iCurrentAmount = 0;

		if($aStorno['kind'] == 1){
			$iCurrentAmount = ($aItem['amount']/100) * $aStorno['amount'];
		}elseif($aStorno['kind'] == 2){
			$iCurrentAmount = $aStorno['amount'];
		}elseif($aStorno['kind'] == 3){
			if(isset($this->_aCancellationDates[$sArrayKey])){
				$iCurrentAmount = $this->_aCancellationDates[$sArrayKey] * $aStorno['amount'];
			}
		}
		
		$iProvisionPercent = 0;
		
		if($aItem['amount'] > 0){
			$iProvisionPercent = $aItem['amount_provision'] * 100 / $aItem['amount'];
		}

		if($iCurrentAmount>0) {

			$iAmountProvision					= $iCurrentAmount * $iProvisionPercent / 100;
			$iAmountNet							= $iCurrentAmount - $iAmountProvision;

			$sDescription = $this->getDescription($aStorno['amount'], $aStorno['kind'], $sType, $sItemType, $aItem['parent_booking_id'], $aItem['type_id']);

			$aCancellationItem = array(
				'description'		=> $sDescription,
				'item_type'			=> $aItem['type'],
				'tax_category'		=> $aItem['tax_category'],
				'amount'			=> $iCurrentAmount,
				'amount_provision'	=> $iAmountProvision,
				'amount_discount'	=> $aItem['amount_discount'],
				'amount_net'		=> $iAmountNet,
				'type_id'			=> (int)$aItem['type_id'],
				'parent_booking_id'	=> (int)$aItem['parent_booking_id'],
				'type_object_id' => (int)$aItem['type_object_id'],
				'type_parent_object_id' => (int)$aItem['type_parent_object_id'],
				'index_from' => $aItem['index_from'],
				'index_until' => $aItem['index_until'],
				'additional_info'	=> array(
					'cancellation_fee_id' => $aStorno['cancellation_fee_id'],
					'cancellation_type'	=> $sType,
					'fee_type'			=> $aStorno['kind'],
					'fee_value'			=> $aStorno['amount']
				)
			);

			$iCurrentAmountWithoutTax	= $iAmountNet - ($iAmountNet * $aItem['amount_discount'] / 100);
			$iCurrentAmountWithTax		= $iCurrentAmountWithoutTax + ($iCurrentAmountWithoutTax * $aItem['tax'] / 100);

			$this->_iCancellationAmountDynamic += $iCurrentAmountWithTax;
		}

		return $aCancellationItem;
	}

	protected function _bindCancellationItemsWithInvoiceItems(array $aCancellationsDynamic){
		$aCancellationBounded = array(); 
		$aItems = (array) $this->_aItems;

		foreach($aCancellationsDynamic as $aCancellationDynamic){
			$aKeys = array();
			$sType = $aCancellationDynamic['type'];

			foreach($aItems as $iItemKey => $aItem) {
				if ($sType === 'all_split') {
					$selection = array_keys($this->_aCancellationTypes);
				} else {
					$selection = $aCancellationDynamic['selection'];
				}

				if($selection != null) {
					// siehe Ext_Thebing_Inquiry_Document_Version_Item::getServiceTags()
					if (!empty($intersect = array_intersect($selection, $aItem['tags']))) {
						$aKeys[$iItemKey] = array_values($intersect);
					}
				}
			}

			$aCancellationDynamic['items'] = $aKeys;
			$aCancellationBounded[] = $aCancellationDynamic;
		}

		return $aCancellationBounded;
	}

	private function checkMinimumAmount(&$aCancellationItems) {

		if(empty($aCancellationItems)) {
			return;
		}

		$fTotalCancellationAmount = 0.00;

		$iCancellationFeeId = (int)$aCancellationItems[0]['additional_info']['cancellation_fee_id'];
		$fMinimumValue = Ext_Thebing_Cancellation_Fee::getInstance($iCancellationFeeId)->minimum_value;

		foreach($aCancellationItems as $aCancellationItem) {
			$fTotalCancellationAmount += (float)$aCancellationItem['amount'];
		}

		if($fTotalCancellationAmount < $fMinimumValue) {

			$aMinimumValueItem = reset($aCancellationItems);

			$aMinimumValueItem['description'] = \L10N::t('Stornierungsgebühr');
			$aMinimumValueItem['amount'] = $fMinimumValue;
			$aMinimumValueItem['amount_net'] = $fMinimumValue;
			$aMinimumValueItem['amount_provision'] = 0;
			$aMinimumValueItem['amount_discount'] = 0;
			$aMinimumValueItem['additional_info']['fee_value'] = $fMinimumValue;

			$aMinimumValueItem['deleted_cancellation_item'] = [];

			foreach($aCancellationItems as $aDeletedCancellationItem) {
				$aMinimumValueItem['deleted_cancellation_item'][] = $aDeletedCancellationItem['additional_info'];
			}

			$aCancellationItems = [$aMinimumValueItem];

		}

	}
}