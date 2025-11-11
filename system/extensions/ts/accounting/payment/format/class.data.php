<?php

class Ext_TS_Accounting_Payment_Format_Data extends Ext_Gui2_View_Format_Abstract {
	
	/**
	 * Betragsspalten
	 * @var array 
	 */
	protected $_aAmountColumns = array(
		'course_amount',
		'accommodation_amount',
		'transfer_amount',
		'insurance_amount',
		'additional_course_amount',
		'additional_accommodation_amount',
		'additional_general_amount',
		'extraPosition_amount' 
	);
	
	protected static $_aExpectedAccommodationAmountCache = array();
	
	protected $_bFormat = true;
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		
		$aData = explode('{||}', $mValue);
		
		if(
			in_array($oColumn->select_column, $this->_aAmountColumns) ||
			strpos($oColumn->select_column, 'additional_cost_amount_') !== false
		) {
			
			$sService = str_replace('_amount', '', $oColumn->select_column);
			$iCostId = 0;
			
			if(strpos($oColumn->select_column, 'additional_cost_amount_') !== false) {
				$iCostId = (int) str_replace('additional_cost_amount_', '', $oColumn->select_column);
				$sService = null;
			}
			
			$fAmount = $this->getServicePrice($sService, $aResultData, $iCostId);
			// Extrawochen/-nächte hinzuaddieren
			if($oColumn->select_column == 'accommodation_amount') {
				$fAmount += $this->getServicePrice('extra_nights', $aResultData);
				$fAmount += $this->getServicePrice('extra_weeks', $aResultData);
			}
			
			if($this->_bFormat) {
				$aReturn[] = $this->formatSum($fAmount, $oColumn, $aResultData);
			} else {
				$aReturn[] = $fAmount;
			}
			
		} elseif(
			$oColumn->select_column == 'course_dates_from' ||
			$oColumn->select_column == 'course_dates_until' ||
			$oColumn->select_column == 'accommodation_dates_from' ||
			$oColumn->select_column == 'accommodation_dates_until'
		) {
			
			$oFormat = new Ext_Thebing_Gui2_Format_Date();
			foreach($aData as $iKey => $mData) {
				$aReturn[$iKey] = $oFormat->format($mData);
			}
			
		} elseif(
			$oColumn->db_column == 'total_amount'
		) {

			$fAmount = $this->_sumServiceAmounts($aResultData);

			if($this->_bFormat) {
				$aReturn[] = $this->formatSum($fAmount, $oColumn, $aResultData);
			} else {
				$aReturn[] = $fAmount;
			}

		} else {
			$aReturn = $aData;
		}
		
		$aHookData = array(
			'data' => $aReturn,
			'column' => $oColumn,
			'resultdata' => $aResultData,
			'format_class' => $this,
			'format' => $this->_bFormat
		);
		System::wd()->executeHook('ts_accounting_payment_format', $aHookData);
	
		if(is_array($aHookData['data'])) {
			$mValue = implode('<br/>', $aHookData['data']);
		} else {
			$mValue = $aHookData['data'];
		}
		
		return $mValue;
	}
	
	/**
	 * Betrag für die Summierungsspalte
	 * 
	 * @param mixed $mValue
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return mixed
	 */
	public function getSumValue($mValue, &$oColumn = null, &$aResultData = null){

		$this->_bFormat = false;
		
		$mAmount = $this->format($mValue, $oColumn, $aResultData);

		$this->_bFormat = true;
		
		return $mAmount;
	}
	
	/**
	 * liefert den Betrag für einen bestimmten Service (course, accommodation, ...) oder für Zusatzgebühren
	 * 
	 * @param string $sService
	 * @param array $aResultData
	 * @param int $iTypeId
	 * @return type
	 */
	public function getServicePrice($sService, $aResultData, $iTypeId = 0) {

		$fReturn = 0;
		if(isset($aResultData['service_amount_data'])) {			
			$fReturn = $this->getAmountFromArray($aResultData['service_amount_data'], $sService, $iTypeId);
		}
		
		return $fReturn;		
	}
	
	/**
	 * liefert den Betrag für einen bestimmten Service (course, accommodation, ...) oder für Zusatzgebühren
	 * 
	 * @param array $aAmountData
	 * @param string $sService
	 * @param int $iTypeId
	 * @return int
	 */
	public function getAmountFromArray($aAmountData, $sService, $iTypeId = 0, $bNetAmount = false, $bCalcDiscount = true) {
		
		if(empty($aAmountData)) {
			return 0;
		}
		
		$aRawItems = explode('{||}', $aAmountData);
		$aItems = array();
		$aUsedSpecials = array();
		$fAmount = 0;

		foreach($aRawItems as $sItemString) {
			$aItems[] = explode('{|}', $sItemString);
		}

		foreach($aItems as $aItemData) {

			$sItemService = (string)$aItemData[1];
			$iItemTypeId = (int)$aItemData[3];

			if($bNetAmount) {
				$fItemAmount = (float)$aItemData[4];
				if($bCalcDiscount) {
					$fItemAmount = (float)$this->_calcDiscountAmount($fItemAmount, $aItemData[5]);
				}
			} else {
				$fItemAmount = (float)$aItemData[2];
				if($bCalcDiscount) {
					$fItemAmount = (float)$this->_calcDiscountAmount($aItemData[2], $aItemData[5]);
				}
			}

			$bSubtractSpecialAmount = false;

			if($iTypeId > 0) {
				// bei einer Zusatzgebühr muss die ID geprüft werden 
				if($iTypeId == $iItemTypeId) {
//					if(
//						$sService &&
//						$sItemService != $sService
//					) {
//						continue;
//					}						
					$fAmount += $fItemAmount;
					$bSubtractSpecialAmount = true;
				}
			} else if($sItemService == $sService) {
				$fAmount += $fItemAmount;
				$bSubtractSpecialAmount = true;
			}

			// Nach Specials suchen, die abgezogen werden müssen
			if($bSubtractSpecialAmount) {
				foreach($aItems as $aItemData2) {
					if(
						$aItemData2[1] === 'special' &&
						$aItemData2[6] == $aItemData[0] && // parent_id == id
						!in_array($aItemData2[0], $aUsedSpecials)
					) {
						// Ist bereits Minus-Betrag, daher addieren
						if($bNetAmount) {
							$fAmount += (float)$aItemData2[4];
						} else {
							$fAmount += (float)$aItemData2[2];
						}

						// Ein Special darf nicht mehrfach abgezogen werden!
						// Da die Payments alle gesamt betrachtet werden, kann das Version Item mehrfach vorkommen
						$aUsedSpecials[] = $aItemData2[0];
					}
				}
			}
		}

		// Fall: Bezahltes Special, aber Parent-Item wurde nicht in dieser Bezahlung bezahlt
		foreach($aItems as $aItemData) {
			if(
				$aItemData[1] === 'special' &&
				!in_array($aItemData[0], $aUsedSpecials)
			) {
				// Parent-Item holen zum Ermitteln des Typs
				$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aItemData[6]);
				if($oItem->type === $sService) {
					if($bNetAmount) {
						$fAmount += (float)$this->_calcDiscountAmount($aItemData[4], $aItemData[5]);
					} else {
						$fAmount += (float)$this->_calcDiscountAmount($aItemData[2], $aItemData[5]);
					}
				}
			}
		}

		return $fAmount;
	}
	
	/**
	 * setzt die Textausrichtung für Betragsspalten
	 * 
	 * @param Ext_Gui2_Head $oColumn
	 * @return string
	 */
	public function align(&$oColumn = null) {
		if(
			in_array($oColumn->select_column, $this->_aAmountColumns) ||
			strpos($oColumn->select_column, 'additional_cost_amount_') !== false ||
			substr($oColumn->select_column, -7) === '_amount' ||
			$oColumn->db_column === 'total_amount'
		) {
			return 'right';			
		}
		
		return 'left';
	}
	
	/**
	 * formatiert einen Betrag
	 * 
	 * @param float $fAmount
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function formatSum($fAmount, &$oColumn = null, &$aResultData = null) {
		$oFormat = new Ext_Thebing_Gui2_Format_Amount();		
		return $oFormat->format($fAmount, $oColumn, $aResultData);
	}	
	
	/**
	 * Tooltip
	 * 
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		
		if($oColumn->select_column == 'accommodation_names_short') {
			$aReturn['content'] = (string)$this->format($aResultData['accommodation_names'], $oColumn, $aResultData);
			$aReturn['tooltip'] = true;
		} else if($oColumn->select_column == 'course_names_short') {
			$aReturn['content'] = (string)$this->format($aResultData['course_names'], $oColumn, $aResultData);
			$aReturn['tooltip'] = true;
		}
		
		return $aReturn;

	}

    private static $aSumServiceCache;

	/**
	 * Summiert die Beträge aller aufkommender Items
	 * 
	 * @param array $aResultData
     * @param bool $bNet
	 * @return float
	 */
	protected function _sumServiceAmounts($aResultData, $bNet = false) {

		$aAmountData = $aResultData['service_amount_data'];
        $fOverpayment = (float)$aResultData['overpayment_amount'];
		$fAmount = 0;
		
		$aItems = explode('{||}', $aAmountData);
		foreach($aItems as $sItemString) {
			$aItemData = explode('{|}', $sItemString);
			if($bNet) {
				$fItemAmount = (float)$this->_calcDiscountAmount($aItemData[4], $aItemData[5]);
			} else {
				$fItemAmount = (float)$this->_calcDiscountAmount($aItemData[2], $aItemData[5]);
			}
			$fAmount += $fItemAmount;
		}

        // Die Überbezahlung darf nur pro Payment einmal berechnet werden
        if(!isset(self::$aSumServiceCache[$aResultData['id']][$this->_bFormat])) {
            self::$aSumServiceCache[$aResultData['id']][$this->_bFormat] = true;
            $fAmount += $fOverpayment;
        }

		return $fAmount;
	}

	protected function _calcDiscountAmount($fAmount, $fDiscount) {
		
		if($fDiscount > 0) {
			$fAmount -= (float) ($fDiscount / 100 * $fAmount);
		}		
		
		return (float) $fAmount;
	}
	
	
	public function calculateExpectedAccommodationAmount(Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation, $aResultData) {
			
		if(!isset(self::$_aExpectedAccommodationAmountCache[$oJourneyAccommodation->id])) {		
		
			$bNetto = false;
			if(strpos($aResultData['type'], 'netto') !== false) {
				$bNetto = true;
			}

			$fAmount = 0;

			$oAccommodationAmount = new Ext_Thebing_Accommodation_Amount();
			$oAccommodationAmount->setInquiryAccommodation($oJourneyAccommodation->id);
			$oAccommodationAmount->setCalculateType('cost');

			$aProviders = array();

			$aAllocations = $oJourneyAccommodation->getAllocations();
			if(!empty($aAllocations)) {
				foreach($aAllocations as $oAllocation) {
					$oAccommodationProvider = $oAllocation->getRoom()->getProvider();
					if($oAccommodationProvider) {
						$aProviders[] = $oAccommodationProvider->id;
					}
				}

			} else {

				$oInquiry = $oJourneyAccommodation->getInquiry();
				$oCategory = $oJourneyAccommodation->getCategory();

				$oMatching = new Ext_Thebing_Matching();
				$oMatching->oAccommodation = $oJourneyAccommodation;

				$oDateFrom = new DateTime($oJourneyAccommodation->from);
				$oMatching->setFrom($oDateFrom);
				$oDateUntil = new DateTime($oJourneyAccommodation->until);
				$oMatching->setUntil($oDateUntil);			

				if($oCategory->type_id == 1) {
					$aMatching = (array) $oMatching->getMatchedFamilie($oInquiry);
				} else {
					$aMatching = (array) $oMatching->getOtherMatched($oInquiry);
				}

				if(!empty($aMatching)) {
					$aFirstMatchingProvider = reset($aMatching);
					$aProviders[] = $aFirstMatchingProvider['id'];
				}

			}

			foreach($aProviders as $iAccommodationProvider) {
				$oAccommodationAmount->setAccommodationProvider($iAccommodationProvider);
				$oAccommodationAmount->setCalculateModel();
				$fAmount += $oAccommodationAmount->calculate($bNetto, 0);

				$sCostType = $oAccommodationAmount->getCalculateModel();

			}
		
			self::$_aExpectedAccommodationAmountCache[$oJourneyAccommodation->id] = (float) $fAmount;			
		}

		return self::$_aExpectedAccommodationAmountCache[$oJourneyAccommodation->id];
	}	
	
}
