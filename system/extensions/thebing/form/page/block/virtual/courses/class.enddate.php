<?php

/**
 * Virtueller Block: Kurse > Enddatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_enddate';

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
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);

		if(
			$oParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$sStartDateField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::class);
		$sDurationDateField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration::class);

		$aValueMap = array(
			$sStartDateField => array(),
			$sDurationDateField => array()
		);
		$aResultMap = array();

		$aCourseDurations = $oForm->oCombination->getServiceHelper()->getCourseDurations();
		foreach($aCourseDurations as $iKey => $oDateRange) {

			// $iWeek ist vielmehr als Key für JS/SelectOptionsInRange zu verstehen
			$iWeek = $iKey + 1;

			// Startdatum mit Wert aus $aResultMap ($iWeek) verknüpfen
			$aValueMap[$sStartDateField]['v'.$oDateRange->from->format('Ymd')] = $iWeek;

			// Duration immer um 1 verschieben (aber bei Duration 1 keine Woche draufrechnen usw.)
			$aValueMap[$sDurationDateField]['v'.$iWeek] = $iWeek - 1;

			$aOptions = [$oDateRange->until->format('Ymd') => $oDateFormat->format($oDateRange->until)];

			$aResultMap[$iWeek] = array(
				'value' => $iWeek,
				'select_options' => $this->convertSelectOptions($aOptions)
			);

		}

		$aDefinition = array(
			// Hiermit werden Start- und Enddatum verknüpft
			'field' => 'single:'.$sStartDateField,
			'options' => array(),
			// Hiermit werden Duration und Enddatum verknüpft, damit Enddatum je nach Duration-Value verschoben wird
			'childs' => array(
				array(
					'field' => 'single:'.$sDurationDateField,
					'options' => array(),
					'childs' => array()
				)
			)
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
				'task' => 'prices',
				'additional' => ['check_services' => 1]
			)
		);

		return $aAttributes;

	}

}
