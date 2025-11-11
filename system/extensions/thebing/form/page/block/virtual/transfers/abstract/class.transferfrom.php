<?php

/**
 * Virtueller Block: Transfer > * > Anreiseort
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Transferfrom extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract {

	const TRANSLATION_TITLE = 'origin';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-from';

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
		$oParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);


		if(
			$oParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$aTransfers = $this->getCorrespondingTransferDTOs();

		$aOptions = [];
		foreach($aTransfers as $oDto) {
			$aOptions[$oDto->sFromKey] = $oDto->sFromLabel;
		}

		$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
		$aOptions = $this->convertSelectOptions($aOptions);

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $aOptions
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
