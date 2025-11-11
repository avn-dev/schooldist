<?php

/**
 * Virtueller Block: Unterkunft > Enddatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'accommodations_enddate';

	const TRANSLATION_TITLE = 'end';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-enddate';

	protected $bRequired = true;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {
		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_SELECT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getParentBlock();
		$aAttributes = parent::getBlockDataAttributesArray($mSchool, $sLanguage);

		if(
			$oParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$sAccommodationTypeField = $this->getTypeBlockInputDataIdentifier();

		$aVisibilityOptions = array(
			'default' => 'show',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $sAccommodationTypeField,
					'data' => array(
						'v0' => array(
							array(
								'type' => 'Visibility',
								'action' => 'hide'
							)
						)
					)
				)
			)
		);

		$aAttributes[] = array(
			'type' => 'DependencyVisibility',
			'data' => $aVisibilityOptions
		);

		return $aAttributes;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		$oForm = $this->getPage()->getForm();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getParentBlock();
		$oNonVirtualParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oParent === null ||
			$oNonVirtualParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$sAccommodationTypeField = $this->getTypeBlockInputDataIdentifier();
		$sStartDateField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::class);
		$sDurationField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration::class);

		$aValueMap = array(
			$sAccommodationTypeField => array(),
			$sStartDateField => array(),
			$sDurationField => array()
		);
		$aResultMap = array();

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		foreach($aAccommodations as $oDto) {

			$sIndex = 'ns:'.$oDto->oCategory->id;
			$aValueMap[$sAccommodationTypeField]['v'.$oDto->oCategory->id] = $oDto->oCategory->id;
			$aResultMap[$sIndex] = [];

			foreach($oDto->aBookableDates as $iWeek => $oBookableDate) {

				$aOptions = [];
				$aValueMap[$sDurationField]['v'.$iWeek] = ($iWeek - 1);
				foreach($oBookableDate->aStartDates as $dStartDate) {
					$aValueMap[$sStartDateField]['v'.$dStartDate->format('Ymd')] = $iWeek;
				}

				foreach($oBookableDate->aEndDates as $dEndDate) {
					$sCssClass = 'select-date-extra';
					if($dEndDate === reset($oBookableDate->aEndDates)) {
						$sCssClass = 'select-date-non-extra';
					}
					$aOptions[] = [$dEndDate->format('Ymd'), $oDateFormat->format($dEndDate), $sCssClass];
				}

				$aResultMap[$sIndex][$iWeek] = array(
					'value' => $iWeek,
					'select_options' => $aOptions
				);

			}

		}

		foreach($aResultMap as $sNamespace => $aMap) {
			$aResultMap[$sNamespace] = array_values($aMap);
		}

		$aNamespaceDefinition = array(
			'field' => 'single:'.$sAccommodationTypeField,
			'options' => array(
				'type' => 'namespace'
			),
			'childs' => array()
		);
		$aRangeDefinition = array(
			'field' => 'single:'.$sStartDateField,
			'options' => array(
				'type' => 'range'
			),
			'childs' => array(
				array(
					'field' => 'single:'.$sDurationField,
					'options' => array(
						'type' => 'range'
					),
					'childs' => array()
				)
			)
		);

		$aPrependSelectOptions = array();
		$aPrependSelectOptions = Ext_TC_Util::addEmptyItem($aPrependSelectOptions);
		$aPrependSelectOptions = $this->convertSelectOptions($aPrependSelectOptions);
		$aDefaultSelectOptions = array();
		$aAttributes[] = array(
			'type' => 'SelectOptionsLookup',
			'data' => array(
				'definitions' => array($aNamespaceDefinition, $aRangeDefinition),
				'require_results_from_all_definitions' => true,
				'value_map' => $aValueMap,
				'result_map' => $aResultMap,
				'default_select_options' => array_values($aDefaultSelectOptions),
				'prepend_select_options' => array_values($aPrependSelectOptions),
				'preselect' => ['order' => 'last', 'class' => 'select-date-non-extra']
			)
		);

		$aAttributes[] = $this->getDependencyRequirementAttribute();

		$aAttributes[] = array(
			'type' => 'TriggerAjaxRequest',
			'data' => array(
				'task' => 'prices'
			)
		);

		return $aAttributes;

	}

}
