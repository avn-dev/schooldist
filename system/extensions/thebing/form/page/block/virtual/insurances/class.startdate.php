<?php

/**
 * Virtueller Block: Versicherung > Startdatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Insurances_Startdate extends Ext_Thebing_Form_Page_Block_Virtual_Insurances_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'insurances_startdate';

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
		$this->block_id = self::TYPE_DATE;
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

		$sInsuranceTypeField = $this->getTypeBlockInputDataIdentifier();

		$aVisibilityOptions = array(
			'default' => 'show',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $sInsuranceTypeField,
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

		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		$aUpdateToValueAttribute = $this->getUpdateToValueAttribute(
			Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::class,
			Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::class,
			'min'
		);
		if($aUpdateToValueAttribute !== null) {
			$aAttributes[] = $aUpdateToValueAttribute;
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
