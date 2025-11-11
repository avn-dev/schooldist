<?php

/**
 * Virtueller Block: Kurse > Dauer
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_duration';

	const TRANSLATION_TITLE = 'duration';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-duration';

	protected $bRequired = true;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {
		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_SELECT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		$oForm = $this->getPage()->getForm();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getParentBlock();
		$oNonVirtualParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getBlockDataAttributesArray($mSchool, $sLanguage);

		if(
			$oParent === null ||
			$oNonVirtualParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$sCourseTypeField = $this->getTypeBlockInputDataIdentifier();

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();
		$aVisibleCourses = [];
		foreach($aCourses as $oDto) {
			// bei jedem Krus sichtbar
			$aVisibleCourses['v'.$oDto->oCourse->id] = [
				[
					'type' => 'Visibility',
					'action' => 'show',
				],
			];
		}

		$aVisibilityOptions = [
			'default' => 'hide',
			'dependencies' => [
				[
					'type' => 'Field',
					'name' => $sCourseTypeField,
					'data' => $aVisibleCourses,
				],
			],
		];

		$aAttributes[] = [
			'type' => 'DependencyVisibility',
			'data' => $aVisibilityOptions,
		];

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
		$sStartDateField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::class);

		$aValueMap = [
			$sCourseTypeField => [],
			$sStartDateField => [],
		];
		$aResultMap = [];

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();

		foreach($aCourses as $oDto) {

			if(!empty($oDto->aStartDates)) {
				$aValueMap[$sCourseTypeField]['v'.$oDto->oCourse->id] = $oDto->oCourse->id;
				foreach($oDto->aStartDates as $aData) {
					$sDateIndex = $aData->start->format('Ymd');
					$aValueMap[$sStartDateField]['v'.$sDateIndex] = $sDateIndex;
					$aOptions = [];
					for($i = $aData->minDuration; $i <= $aData->maxDuration; $i++) {
						$aOptions[$i] = $i;
					}
					$aOptions = Ext_TC_Util::addEmptyItem($aOptions); // TODO: prepend_select_options?
					$aOptions = $this->convertSelectOptions($aOptions);
					$aResultMap[] = [
						'value' => ':'.$oDto->oCourse->id.':'.$sDateIndex,
						'select_options' => $aOptions,
					];
				}
			}

		}

		$aDefinition = [
			'field' => 'single:'.$sCourseTypeField,
			'options' => [],
			'childs' => [
				[
					'field' => 'single:'.$sStartDateField,
					'options' => [],
					'childs' => [],
				],
			],
		];

		$aDefaultSelectOptions = [];
		$aAttributes[] = [
			'type' => 'SelectOptionsMap',
			'data' => [
				'definitions' => [$aDefinition],
				'value_map' => $aValueMap,
				'result_map' => array_values($aResultMap),
				'default_select_options' => array_values($aDefaultSelectOptions),
			],
		];

		$aAttributes[] = $this->getDependencyRequirementAttribute();

		$aAttributes[] = [
			'type' => 'TriggerAjaxRequest',
			'data' => [
				'task' => 'prices',
				'additional' => ['check_services' => 1]
			],
		];

		return $aAttributes;

	}

}
