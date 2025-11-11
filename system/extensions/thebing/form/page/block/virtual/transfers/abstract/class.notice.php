<?php

/**
 * Virtueller Block: Transfer > * > Kommentar
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Notice extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract {

	const TRANSLATION_TITLE = 'comment';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-comment';

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_TEXTAREA;
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

}
