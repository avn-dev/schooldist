<?php

/**
 * V2
 * @deprecated
 *
 * Helper-Klasse zum Holen der im Formular verfügbaren Leistungen und Zeiträume inkl. Instanz-Cache
 *
 * Versicherungspreise gehen nicht über die buildItems(), da das momentan nur mit ID funktioniert!
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Prices {

	/**
	 * @var Ext_TS_Inquiry
	 */
	private $oInquiry;

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Abstract
	 */
	private $oCombination;

	/**
	 * @var string
	 */
	private $sLanguage;

	/**
	 * @var Ext_Thebing_Gui2_Format_Date
	 */
	private $oDateFormat;

	/**
	 * @var Ext_Thebing_Currency|null
	 */
	private $oCurrency;

	/**
	 * @var Ext_TS_Inquiry_Journey_Course[]
	 */
	private $aCourses = [];

	/**
	 * @var Ext_TS_Inquiry_Journey_Accommodation[]
	 */
	private $aAccommodations = [];

	/**
	 * @var Ext_TS_Inquiry_Journey_Transfer[]
	 */
	private $aTransfers = [];

	/**
	 * @var Ext_TS_Inquiry_Journey_Insurance[]
	 */
	private $aInsurances = [];

	/**
	 * @var Ext_Thebing_School_Additionalcost[]
	 */
	private $aAdditionalFees = [];

	/**
	 * @var int
	 */
	private $fTotalAmount = 0;

	/**
	 * @var bool
	 */
	private $bAddTotalAmount = true;

	public function __construct(Ext_TS_Frontend_Combination_Inquiry_Abstract $oCombination, Ext_Thebing_Currency $oCurrency) {

		$this->oCombination = $oCombination;
		$this->oCurrency = $oCurrency;
		$this->oCurrency->bThinspaceSign = true;
		$this->sLanguage = $oCombination->getLanguage()->getLanguage();
		$this->oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->oCombination->getSchool()->id);

	}

	/**
	 * @TODO Eigentlich würde das nicht benötigt werden, aber da Versicherungen nicht ohne IDs funktionieren und
	 *   - generelle Zusatzgebühren nicht zur Buchung gehören, sondern irgendwo rumschwirren, bleibt das natürlich
	 *   - eine Utopie.
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param array $aAdditionalFees
	 */
	public function setInquiry(Ext_TS_Inquiry $oInquiry, array $aAdditionalFees) {

		$this->oInquiry = $oInquiry;
		$oJourney = $oInquiry->getJourney();
		$this->aCourses = $oJourney->getCoursesAsObjects();
		$this->aAccommodations = $oJourney->getAccommodationsAsObjects();
		$this->aTransfers = $oJourney->getTransfersAsObjects();
		$this->aInsurances = $oJourney->getInsurancesAsObjects();
		$this->aAdditionalFees = $aAdditionalFees;

	}

	/**
	 * JSON-Array für Preisblöcke (AJAX-Request)
	 *
	 * @return array
	 */
	public function getPriceBlockData() {

		$aPriceBlocks = $this->getPriceBlocks();

		if(
			empty($aPriceBlocks) ||
			$this->oCurrency === null
		) {
			return [];
		}

		$aItemsGrouped = $this->getGroupedItems();

		$aResult = [];
		foreach($aPriceBlocks as $oPriceBlock) {

			$aResult['group_'.$oPriceBlock->getInputDataIdentifier()] = [
				'css' => 'prices',
				'rows' => [],
			];

			$this->setRows($oPriceBlock, $aResult, $this->getCourseRows($oPriceBlock, $aItemsGrouped));
			$this->setRows($oPriceBlock, $aResult, $this->getAccommodationRows($oPriceBlock, $aItemsGrouped));
			$this->setRows($oPriceBlock, $aResult, $this->getTransferRows($oPriceBlock, $aItemsGrouped));
			$this->setRows($oPriceBlock, $aResult, $this->getInsuranceRows($oPriceBlock, $aItemsGrouped));
			$this->setRows($oPriceBlock, $aResult, $this->getAdditionalFeeRows($oPriceBlock, $aItemsGrouped));

			$aResult['group_'.$oPriceBlock->getInputDataIdentifier()]['rows'][] = [
				'css' => 'total primary',
				'title' => $oPriceBlock->getTranslation('priceTotal', $this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($this->fTotalAmount, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'type' => 'total'
			];

			$this->bAddTotalAmount = false;

		}

		return $aResult;

	}

	/**
	 * @return array|Ext_Thebing_Form_Page_Block[]
	 */
	private function getPriceBlocks() {

		$aPriceBlocks = $this->oCombination->getForm()->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Prices_Display);
		});

		return $aPriceBlocks;

	}

	private function getGroupedItems() {

		$aItems = $this->oCombination->buildDocumentVersionItems($this->oInquiry);

		$aItemsGrouped = [];
		foreach($aItems as $aItem) {
			$aItemsGrouped[$aItem['type']][] = $aItem;
		}

		return $aItemsGrouped;

	}

	/**
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aResult
	 * @param array $aRows
	 */
	private function setRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array &$aResult, array $aRows) {

		$sIdentifier = 'group_'.$oPriceBlock->getInputDataIdentifier();
		$aResult[$sIdentifier]['rows'] = array_merge($aResult[$sIdentifier]['rows'] , $aRows);

	}

	/**
	 * Preis kalkulieren: Ggf. Steuer addieren
	 *
	 * @param array $aItem
	 * @return float
	 */
	private function getPrice(array $aItem) {

		$fPrice = (float)$aItem['amount'];
		$iTaxCategory = (int)$aItem['tax_category'];

		if(
			$iTaxCategory > 0 &&
			$this->oCombination->getSchool()->getTaxStatus() == Ext_Thebing_School::TAX_EXCLUSIVE
		) {
			$iTaxRate = Ext_TS_Vat::getTaxRate($iTaxCategory, $this->oCombination->getSchool()->id);
			$aTax = Ext_TS_Vat::calculateExclusiveTaxes($fPrice, $iTaxRate);
			$fPrice += $aTax['amount'];
		}

		return round($fPrice, 2);

	}

	/**
	 * @param $fAmount
	 */
	private function addToTotalSum($fAmount) {

		if($this->bAddTotalAmount) {
			$this->fTotalAmount += $fAmount;
		}

	}

	/**
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aItems
	 * @return array
	 */
	private function getCourseRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array $aItems) {

		if(empty($this->aCourses)) {
			return [];
		}

		$aRows = [];

		$aRows[] = [
			'css' => 'courses primary',
			'title' => $oPriceBlock->getTranslation('priceCourse', $this->sLanguage),
			'title_css' => 'title',
		];

		foreach((array)$aItems['additional_course'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aRows[] = [
				'css' => 'courses secondary',
				'title' => (string)$aItem['description'],
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'additional_course'
			];
		}

		foreach((array)$aItems['course'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$oCourse = Ext_Thebing_Tuition_Course::getInstance($aItem['type_object_id']);

			$aRow = [
				'css' => 'courses secondary main-item',
				'title' => $oCourse->getName($this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'course'
			];

			$sUnits = '';
			if($aItem['additional_info']['course_units'] > 0) {
				$sUnits = ' / '.$aItem['additional_info']['course_units'].' '.$oPriceBlock->getTranslationChoice('priceUnit', $aItem['additional_info']['course_units'], $this->sLanguage);
			}

			$aRow['notes'] = [
				$aItem['additional_info']['course_weeks'].' '.$oPriceBlock->getTranslationChoice('priceWeek', $aItem['additional_info']['course_weeks'], $this->sLanguage).$sUnits,
				$this->oDateFormat->format($aItem['additional_info']['from']).' - '.$this->oDateFormat->format($aItem['additional_info']['until']),
			];

			$aRows[] = $aRow;

		}

		return $aRows;

	}

	/**
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aItems
	 * @return array
	 */
	private function getAccommodationRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array $aItems) {

		if(empty($this->aAccommodations)) {
			return [];
		}

		$aRows = [];
		$oBlock = $this->oCombination->getForm()->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);

		$aRows[] = [
			'css' => 'accommodations primary',
			'title' => $oPriceBlock->getTranslation('priceAccommodation', $this->sLanguage),
			'title_css' => 'title',
		];

		foreach((array)$aItems['additional_accommodation'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aRows[] = [
				'css' => 'accommodations secondary',
				'title' => (string)$aItem['description'],
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'additional_accommodation'
			];
		}

		foreach((array)$aItems['extra_nights'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aRow = [
				'css' => 'accommodations secondary',
				'title' => $aItem['additional_info']['nights'].' '.$oBlock->getTranslationChoice('extra', $aItem['nights'], $this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'extra_nights'
			];

			$aRow['notes'] = [
				$this->oDateFormat->format($aItem['from']).' - '.$this->oDateFormat->format($aItem['until']),
			];

			$aRows[] = $aRow;
		}

		foreach((array)$aItems['extra_weeks'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aRows[] = [
				'css' => 'accommodations secondary',
				'title' => $aItem['additional_info']['extra_weeks'].' '.$oBlock->getTranslationChoice('extraWeek', $aItem['additional_info']['extra_weeks'], $this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'extra_weeks'
			];
		}

		foreach((array)$aItems['accommodation'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($aItem['additional_info']['accommodation_category_id']);
			$oRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance($aItem['additional_info']['accommodation_roomtype_id']);
			$oMealType = Ext_Thebing_Accommodation_Meal::getInstance($aItem['additional_info']['accommodation_meal_id']);

			$aRow = [
				'css' => 'accommodations secondary main-item',
				'title' => $oAccommodationCategory->getName($this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'price' => $fPrice,
				'type' => 'accommodation'
			];

			$aRow['notes'] = [
				$aItem['additional_info']['accommodation_weeks'].' '.$oPriceBlock->getTranslationChoice('priceWeek', $aItem['additional_info']['accommodation_weeks'], $this->sLanguage),
				$oRoomType->getName($this->sLanguage).' / '.$oMealType->getName($this->sLanguage),
				//$this->oDateFormat->format($aItem['additional_info']['from']).' - '.$this->oDateFormat->format($aItem['additional_info']['until']),
				$this->oDateFormat->format($aItem['from']).' - '.$this->oDateFormat->format($aItem['until']),
			];

			$aRows[] = $aRow;

		}

		return $aRows;

	}

	/**
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aItems
	 * @return array
	 */
	private function getTransferRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array $aItems) {

		if(empty($this->aTransfers)) {
			return [];
		}

		$aRows = [];
		$oBlock = $this->oCombination->getForm()->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);

		$aRows[] = [
			'css' => 'transfers primary',
			'title' => $oPriceBlock->getTranslation('priceTransfer', $this->sLanguage),
			'title_css' => 'title',
		];

		foreach((array)$aItems['transfer'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aTransferTypes = [];
			if(
				// Anreise oder Abreise (einzelne Positionen)
				!isset($aItem['additional_info']['transfer_arrival_id']) &&
				!isset($aItem['additional_info']['transfer_departure_id'])
			) {
				if($aItem['additional_info']['transfer_type'] === 'arrival') {
					$sTitle = $oBlock->getTranslation('arrival', $this->sLanguage);
					$aTransferTypes = [Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL];
				} elseif($aItem['additional_info']['transfer_type'] === 'departure') {
					$sTitle = $oBlock->getTranslation('departure', $this->sLanguage);
					$aTransferTypes = [Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE];
				} else {
					// Sollte eigentlich niemals vorkommen
					$sTitle = 'none';
				}
			} else {
				// Zwei-Wege-Transfer (nur eine Position)
				$sTitle = $oBlock->getTranslation('arrival_departure', $this->sLanguage);
				$aTransferTypes = [Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL, Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE];
			}

			// Zugehörige Objekte suchen, da der ganze Item-Spaß ansonsten nur mit IDs funktioniert
			// Zudem stehen die Infos ja nicht im Item, da aus zwei Items eins wird, aber an diversen Stellen wieder zwei Items benötigt werden
			/** @var Ext_TS_Inquiry_Journey_Transfer[] $aJourneyTransfers */
			$aJourneyTransfers = array_filter($this->aTransfers, function(Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer) use ($aTransferTypes) {
				return in_array($oJourneyTransfer->transfer_type, $aTransferTypes);
			});

			$aNotes = [];
			foreach($aJourneyTransfers as $oJourneyTransfer) {
				$dDate = $oJourneyTransfer->getTransferDate();
				$sNote = $oJourneyTransfer->getLocationName('start', true, $this->sLanguage).' - ';
				$sNote .= $oJourneyTransfer->getLocationName('end', true, $this->sLanguage).' ';
				$sNote .= '('.$this->oDateFormat->format($dDate).')';
				$aNotes[] = $sNote;
			}

			$aRows[] = [
				'css' => 'transfers secondary main-item',
				'title' => $sTitle,
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => $aNotes,
				'notes_css' => 'note',
				'type' => 'transfer'
			];

		}

		return $aRows;

	}

	/**
	 * Geht nicht über die buildItems()!
	 *
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aItems
	 * @return array
	 * @throws Exception
	 */
	private function getInsuranceRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array $aItems) {

		if(empty($this->aInsurances)) {
			return [];
		}

		$aRows = [];

		$aRows[] = [
			'css' => 'insurances primary',
			'title' => $oPriceBlock->getTranslation('priceInsurance', $this->sLanguage),
			'title_css' => 'title',
		];

		foreach((array)$aItems['insurance'] as $aItem) {

			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);
			$oInsurance = Ext_Thebing_Insurance::getInstance($aItem['type_object_id']);

			$aRows[] = [
				'css' => 'insurances secondary main-item',
				'title' => $oInsurance->getName($this->sLanguage),
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [
					$this->oDateFormat->format($aItem['from']).' - '.$this->oDateFormat->format($aItem['until']),
				],
				'notes_css' => 'note',
				'type' => 'insurance'
			];

		}

		return $aRows;

	}

	/**
	 * Zusatzgebühren kommen nicht über die buildItems()
	 *
	 * @param Ext_Thebing_Form_Page_Block $oPriceBlock
	 * @param array $aItems
	 * @return array
	 */
	private function getAdditionalFeeRows(Ext_Thebing_Form_Page_Block $oPriceBlock, array $aItems) {

		if(empty($aItems['additional_general'])) {
			return [];
		}

		$aRows = [];

		$aRows[] = [
			'css' => 'fees_general primary',
			'title' => $oPriceBlock->getTranslation('priceCostsGeneral', $this->sLanguage),
			'title_css' => 'title',
		];

		foreach((array)$aItems['additional_general'] as $aItem) {
			$fPrice = $this->getPrice($aItem);
			$this->addToTotalSum($fPrice);

			$aRows[] = [
				'css' => 'fees_general secondary main-item',
				'title' => $aItem['description'],
				'title_css' => 'title',
				'text' => Ext_Thebing_Format::Number($fPrice, $this->oCurrency, $this->oCombination->getSchool(), true, 2),
				'text_css' => 'text',
				'notes' => [],
				'notes_css' => 'note',
				'type' => 'additional_general'
			];

		}

		return $aRows;

	}

	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return $this->oInquiry;
	}

}
