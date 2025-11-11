<?php

/**
 * Virtueller Block: Versicherung > Enddatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Insurances_Enddate extends Ext_Thebing_Form_Page_Block_Virtual_Insurances_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'insurances_enddate';

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
		$this->block_id = self::TYPE_DATE;
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
			if($oInsurance->isWeekInsurance()) {
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

		$aUpdateToValueAttribute = $this->getUpdateToValueAttribute(
			Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate::class,
			Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate::class,
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
