<?php

/**
 * Virtueller Block: Unterkunft > Dauer
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'accommodations_duration';

	/**
	 * @inheritdoc
	 */
	const TRANSLATION_TITLE = 'duration';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-duration';

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
		$sAccommodationStartDateField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::class);

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
			$sAccommodationStartDateField => array(),
			$sCourseStartDateField => array(),
			$sCourseDurationField => array()
		);
		$aResultMap = array();

		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		// Bei fehlender Abhängigkeit werden die Options unten mit StaticSelectOptions generiert
		if($oForm->acc_depending_on_course) {

			foreach($aAccommodations as $oDto) {

				$sIndex = 'ns:'.$oDto->oCategory->id;
				$aValueMap[$sAccommodationTypeField]['v'.$oDto->oCategory->id] = $oDto->oCategory->id;

				foreach($oDto->aBookableDates as $iWeek => $oBookableDate) {

					$aValueMap[$sCourseStartDateField]['v'.$oBookableDate->dCourseStart->format('Ymd')] = $iWeek;
					$aValueMap[$sCourseDurationField]['v'.$iWeek] = $iWeek;

					foreach($oBookableDate->aStartDates as $dDate) {
						$aValueMap[$sAccommodationStartDateField]['v'.$dDate->format('Ymd')] = $iWeek;
					}

					$aResultMap[$sIndex][$iWeek] = array(
						'value' => $iWeek,
						'select_options' => array(
							array($iWeek, $iWeek)
						)
					);

				}

			}

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
			$aStartDefinition = array(
				'field' => 'single:'.$sAccommodationStartDateField,
				'options' => array(
					// Event setzen, aber range ignorieren, da nicht korrekt
					'type' => 'ignore'
				),
				'childs' => array()
			);
			$aEndDefinition = array(
				'field' => 'all:'.$sCourseStartDateField,
				'options' => array(
					// Event setzen und Kind durchlaufen(!), aber range ignorieren, da nicht korrekt
					'type' => 'ignore'
				),
				'childs' => array(
					array(
						// Hier wird das EINE Feld mit dem GRÖẞTEN Wert benötigt
						// all: in Kombination mit max et al. funktioniert alles nicht (zu viele Wochen)
						'field' => 'single:'.$sCourseDurationField,
						'options' => array(
							'type' => 'max'
						),
						'childs' => array()
					)
				)
			);

			$aAttributes[] = array(
				'type' => 'SelectOptionsLookup',
				'data' => array(
					'definitions' => array($aNamespaceDefinition, $aStartDefinition, $aEndDefinition),
					'require_results_from_all_definitions' => true,
					'value_map' => $aValueMap,
					'result_map' => $aResultMap,
					'default_select_options' => array_values($aDefaultSelectOptions),
					'prepend_select_options' => array_values($aPrependSelectOptions),
					// Ohne das hier würde nur eine Woche angezeigt werden (siehe JS)
					'scale_min_to' => 1,
					'preselect' => ['order' => 'last']
				)
			);

		} else {

			// Pauschal 52 Wochen generieren
			$aOptions = [[0, '']];
			for($i = 1; $i < 53; $i++) {
				$aOptions[] = [$i, $i];
			}

			$aAttributes[] = array(
				'type' => 'StaticSelectOptions',
				'data' => array(
					'select_options' => $aOptions
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
