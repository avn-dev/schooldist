<?php

/**
 * Virtueller Block: Versicherung > Dauer
 */
class Ext_Thebing_Form_Page_Block_Virtual_Insurances_Duration extends Ext_Thebing_Form_Page_Block_Virtual_Insurances_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'insurances_duration';

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

		$sInsuranceTypeField = $this->getTypeBlockInputDataIdentifier();

		$aHidden = array();
		$aHidden[] = 0;

		$aInsurances = $oForm->oCombination->getServiceHelper()->getInsurances();
		foreach($aInsurances as $oInsurance) {
			if(!$oInsurance->isWeekInsurance()) {
				$aHidden[] = $oInsurance->id;
			}
		}

		$aVisibilityData = array();
		foreach($aHidden as $iHiddenOnId) {
			$aVisibilityData['v'.$iHiddenOnId] = array(
				array(
					'type' => 'Visibility',
					'action' => 'hide'
				)
			);
		}

		$aVisibilityOptions = array(
			'default' => 'show',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $sInsuranceTypeField,
					'data' => $aVisibilityData
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

		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		$aOptions = array();
		for($i = 1; $i <= 52; $i++) {
			$aOptions[$i] = $i;
		}
		$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
		$aOptions = $this->convertSelectOptions($aOptions);

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $aOptions
			)
		);

		$aUpdateToValueAttribute = $this->getUpdateToValueAttribute(
			Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration::class,
			Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration::class,
			'max'
		);
		if($aUpdateToValueAttribute !== null) {
			$aAttributes[] = $aUpdateToValueAttribute;
		}

		// Versteckte Elemente werden ignoriert, daher kann das immer gesetzt werden
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
