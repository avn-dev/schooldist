<?php

/**
 * Virtueller Block: Kurse > Niveau
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Level extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_level';

	const TRANSLATION_TITLE = 'level';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-level';

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

		$sCourseTypeField = $this->getTypeBlockInputDataIdentifier();

		$aVisibilityOptions = array(
			'default' => 'show',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $sCourseTypeField,
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

		$sCourseTypeField = $this->getTypeBlockInputDataIdentifier();

		// Select-Optionen sind immer alle vorhandenen Niveau-Optionen
		$aOptions = $oSchool->getCourseLevelList();
		$aOptions = Ext_TC_Util::addEmptyItem($aOptions);

		$iResultMapCounter = 1;
		$aResultMap = [
			// Alle externen Level
			[
				'value' => $iResultMapCounter++,
				'select_options' => $this->convertSelectOptions($aOptions)
			]
		];

		// Den verfügbaren Kursen die passenden Werte in $aResultMap zuweisen
		$aValueMap = array(
			$sCourseTypeField => array()
		);

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();
		foreach($aCourses as $oDto) {
			if(empty($oDto->oCourse->start_level_id)) {
				// Alle externen Level
				$aValueMap[$sCourseTypeField]['v'.$oDto->oCourse->id] = 1;
			} else {
				$aNewOptions = Ext_TC_Util::addEmptyItem($oDto->aLevels);
				$aResultMap[] = ['value' => $iResultMapCounter, 'select_options' => $this->convertSelectOptions($aNewOptions)];
				$aValueMap[$sCourseTypeField]['v'.$oDto->oCourse->id] = $iResultMapCounter++;
			}
		}

		// Die verfügbaren Optionen beziehen sich auf die aktuelle Auswahl im Kurs-Select
		$aDefinition = array(
			'field' => 'single:'.$sCourseTypeField,
			'options' => array(),
			'childs' => array()
		);

		$aDefaultSelectOptions = array();
		$aAttributes[] = array(
			'type' => 'SelectOptionsInRange',
			'data' => array(
				'definitions' => array($aDefinition),
				'value_map' => $aValueMap,
				'result_map' => array_values($aResultMap),
				'default_select_options' => array_values($aDefaultSelectOptions)
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
