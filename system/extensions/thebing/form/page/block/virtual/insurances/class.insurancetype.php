<?php

/**
 * Virtueller Block: Versicherung > Versicherung
 */
class Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype extends Ext_Thebing_Form_Page_Block_Virtual_Insurances_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'insurances_insurancetype';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-insurances';

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {
		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_SELECT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		$oForm = $this->getPage()->getForm();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$aOptions = [];
		$aInsurances = $oForm->oCombination->getServiceHelper()->getInsurances();
		foreach($aInsurances as $oInsurance) {
			$aOptions[$oInsurance->id] = $oInsurance->getName($sLanguage);
		}

		asort($aOptions);
		$aOptions = Ext_TC_Util::addEmptyItem($aOptions, $oForm->getTranslation('defaultdd', $sLanguage));
		$aOptions = $this->convertSelectOptions($aOptions);

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $aOptions
			)
		);

		$aAttributes[] = array(
			'type' => 'TriggerAjaxRequest',
			'data' => array(
				'task' => 'prices'
			)
		);

		return $aAttributes;

	}

}
