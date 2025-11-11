<?php

/**
 * Virtueller Block: Transfer > * > Ankunftsort
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Transferto extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract {

	const TRANSLATION_TITLE = 'destination';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-to';

	protected $bRequired = true;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_SELECT;
		$this->set_type = self::SUBTYPE;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oNonVirtualParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getBlockDataAttributesArray($mSchool, $sLanguage);

		if(
			$oNonVirtualParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$aSettings = $oNonVirtualParent->getSettings();

		if($aSettings['always_show_inputs_'.$oSchool->id]) {
			return $aAttributes;
		}

		$sTransferTypeField = $this->getTypeBlockInputDataIdentifier();

		$aVisibilityOptions = array(
			'default' => 'hide',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $sTransferTypeField,
					'data' => array(
						'v'.static::TRANSFER_TYPE => array(
							array(
								'type' => 'Visibility',
								'action' => 'show'
							)
						),
						'varr_dep' => array(
							array(
								'type' => 'Visibility',
								'action' => 'show'
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

		// Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Transferfrom, je nach Typ
		$oChildBlock = $this->getTransferFromChildBlock();
		if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract) {
			$sTransferFromField = $oChildBlock->getInputDataIdentifier();
		} else {
			return $aAttributes;
		}

		$aValueMap = array(
			$sTransferFromField => array()
		);
		$aResultMap = array();

		$aTransfers = $this->getCorrespondingTransferDTOs();
		foreach($aTransfers as $oDto) {

			$aValueMap[$sTransferFromField]['v'.$oDto->sFromKey] = $oDto->sFromKey;

			if(!isset($aResultMap[':'.$oDto->sFromKey])) {
				$aResultMap[':'.$oDto->sFromKey] = [];
			}

			$aResultMap[':'.$oDto->sFromKey][$oDto->sToKey] = $oDto->sToLabel;

		}

		foreach(array_keys($aResultMap) as $sIndex) {
			$aResultMap[$sIndex] = Ext_TC_Util::addEmptyItem($aResultMap[$sIndex]);
			$aResultMap[$sIndex] = $this->convertSelectOptions($aResultMap[$sIndex]);
			$aResultMap[$sIndex] = array(
				'value' => $sIndex,
				'select_options' => $aResultMap[$sIndex]
			);
		}

		$aDefinition = array(
			'field' => 'single:'.$sTransferFromField,
			'options' => array(),
			'childs' => array()
		);

		$aDefaultSelectOptions = array();
		$aAttributes[] = array(
			'type' => 'SelectOptionsMap',
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
