<?php

/**
 * Virtueller Block: Unterkunft > Unterkunft
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Accommodationtype extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'accommodations_accommodationtype';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-accommodations';

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

		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		$aOptions = [];
		foreach($aAccommodations as $oDto) {
			$aOptions[$oDto->oCategory->id] = $oDto->oCategory->getName($sLanguage);
		}

		$aOptions = Ext_TC_Util::addEmptyItem($aOptions, $oForm->getTranslation('defaultdd', $sLanguage));

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $this->convertSelectOptions($aOptions)
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
