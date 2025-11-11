<?php

/**
 * Virtueller Block: Unterkunft > Startdatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'accommodations_startdate';

	const TRANSLATION_TITLE = 'start';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-startdate';

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

		$sCourseStartDateField = null;
		$sCourseDurationField = null;

		// Block muss nicht vorhanden sein, wenn keine Kursabhängigkeit besteht
		if($oForm->acc_depending_on_course) {
			$oCourseBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES);
			$aCourseBlockChilds = reset($oCourseBlock->getChildBlocks(true))->getChildBlocks(true); // Container überspringen
			foreach($aCourseBlockChilds as $oBlock) {
				if($oBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate) {
					$sCourseStartDateField = $oBlock->getInputDataIdentifier();
				} elseif($oBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration) {
					$sCourseDurationField = $oBlock->getInputDataIdentifier();
				}
			}
		}

		if(
			$oForm->acc_depending_on_course && (
				$sCourseStartDateField === null ||
				$sCourseDurationField === null
			)
		) {
			return $aAttributes;
		}

		$aValueMap = array(
			$sAccommodationTypeField => array(),
			$sCourseStartDateField => array(),
			$sCourseDurationField => array()
		);
		$aResultMap = array();

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		foreach($aAccommodations as $oDto) {

			$aValueMap[$sAccommodationTypeField]['v'.$oDto->oCategory->id] = $oDto->oCategory->id;

			$sIndex = 'ns:'.$oDto->oCategory->id;
			$aResultMap[$sIndex] = [];
			$aAccommodationOptions = []; // Relevant bei !$oForm->acc_depending_on_course
			$dFirstStartDate1Y = null; // Relevant bei !$oForm->acc_depending_on_course

			foreach($oDto->aBookableDates as $iWeek => $oBookableDate) {

				$aOptions = [];

				if($oForm->acc_depending_on_course) {
					$aValueMap[$sCourseStartDateField]['v'.$oBookableDate->dCourseStart->format('Ymd')] = $iWeek;
					$aValueMap[$sCourseDurationField]['v'.$iWeek] = ($iWeek - 1);
				}

				foreach($oBookableDate->aStartDates as $dStartDate) {
					$sCssClass = 'select-date-extra';
					if($dStartDate === end($oBookableDate->aStartDates)) {
						// Letzter Eintrag im Array ist das eigentliche Startdatum und keine Extranacht
						$sCssClass = 'select-date-non-extra';
					}

					$aOption = [$dStartDate->format('Ymd'), $oDateFormat->format($dStartDate), $sCssClass];

					// Bei Kursabhängigkeit sind die Daten pro $iWeek (für Kursdauer), ansonsten müssen alle Startdaten gesammelt werden
					if($oForm->acc_depending_on_course) {
						$aOptions[] = $aOption;
					} else {
						if($dFirstStartDate1Y === null) {
							$dFirstStartDate1Y = clone $dStartDate;
							$dFirstStartDate1Y->add(new DateInterval('P52W'));
						}

						// Nur Startdaten innerhalb eines Jahres, sonst fehlen die Enddaten (nur zwei Jahre werden generiert)
						if($dStartDate <= $dFirstStartDate1Y) {
							$aAccommodationOptions[] = $aOption;
						}
					}

				}

				// Nur bei Kursabhängigkeit wird $iWeek für SelectOptionsLookup benötigt
				if($oForm->acc_depending_on_course) {
					$aResultMap[$sIndex][$iWeek] = array(
						'value' => $iWeek,
						'select_options' => $aOptions
					);
				}

			}

			// »Einfache« $aResultMap für SelectOptionsInRange (keine Kursabhängigkeit)
			if(!$oForm->acc_depending_on_course) {
				$aResultMap[$oDto->oCategory->id] = array(
					'value' => $oDto->oCategory->id,
					'select_options' => $aAccommodationOptions
				);
			}

		}

		if($oForm->acc_depending_on_course) {
			foreach($aResultMap as $sNamespace => $aMap) {
				$aResultMap[$sNamespace] = array_values($aMap);
			}
		}

		$aPrependSelectOptions = array();
		$aPrependSelectOptions = Ext_TC_Util::addEmptyItem($aPrependSelectOptions);
		$aPrependSelectOptions = $this->convertSelectOptions($aPrependSelectOptions);
		$aDefaultSelectOptions = array();

		if($oForm->acc_depending_on_course) {

			$aNamespaceDefinition = array(
				'field' => 'single:'.$sAccommodationTypeField,
				'options' => array(
					'type' => 'namespace'
				),
				'childs' => array()
			);
			$aMinDefinition = array(
				'field' => 'all:'.$sCourseStartDateField,
				'options' => array(
					'type' => 'min'
				),
				'childs' => array()
			);
			$aMaxDefinition = array(
				'field' => 'all:'.$sCourseStartDateField,
				'options' => array(
					'type' => 'startrange'
				),
				'childs' => array(
					array(
						'field' => 'single:'.$sCourseDurationField,
						'options' => array(
							'type' => 'range'
						),
						'childs' => array()
					)
				)
			);

			$aAttributes[] = array(
				'type' => 'SelectOptionsLookup',
				'data' => array(
					'definitions' => array($aNamespaceDefinition, $aMinDefinition, $aMaxDefinition),
					'require_results_from_all_definitions' => true,
					'value_map' => $aValueMap,
					'result_map' => $aResultMap,
					'default_select_options' => array_values($aDefaultSelectOptions),
					'prepend_select_options' => array_values($aPrependSelectOptions),
					'preselect' => ['order' => 'first', 'class' => 'select-date-non-extra']
				)
			);

		} else {

			$aDefinition = array(
				'field' => 'single:'.$sAccommodationTypeField,
				'options' => array(),
				'childs' => array()
			);

			$aAttributes[] = array(
				'type' => 'SelectOptionsInRange',
				'data' => array(
					'definitions' => array($aDefinition),
					'value_map' => $aValueMap,
					'result_map' => array_values($aResultMap),
					'default_select_options' => array_values($aDefaultSelectOptions),
					'prepend_select_options' => array_values($aPrependSelectOptions)
				)
			);

		}

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
