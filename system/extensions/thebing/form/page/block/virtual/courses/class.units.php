<?php

/**
 * Virtueller Block: Kurse > Lektionen
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Units extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_units';

	const TRANSLATION_TITLE = 'units';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-lessons';

	/**
	 * Minimale Anzahl an Lektionen (wenn es sich um einen Lektionskurs handelt)
	 *
	 * @var integer
	 */
	const UNITS_MIN = 1;

	/**
	 * Maximale Anzahl an Lektionen (wenn es sich um einen Lektionskurs handelt)
	 *
	 * @var integer
	 */
	const UNITS_MAX = 999;

	protected $bRequired = true;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {
		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_INPUT;
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
			if($oDto->oCourse->getType() !== 'unit') {
				// nur bei Lektionskursen sichtbar (#9118)
				continue;
			}
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

		$aAttributes[] = [
			'type' => 'ValidateInput',
			'data' => [
				'message' => $this->getTranslation('error', $sLanguage),
				'algorithm' => 'IntegerRange',
				'min' => self::UNITS_MIN,
				'max' => self::UNITS_MAX,
			],
		];

		// Versteckte Elemente werden ignoriert, daher kann das immer gesetzt werden
		$aAttributes[] = $this->getDependencyRequirementAttribute();

		$aAttributes[] = [
			'type' => 'TriggerAjaxRequest',
			'data' => [
				'task' => 'prices',
			],
		];

		return $aAttributes;

	}

}
