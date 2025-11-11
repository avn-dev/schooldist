<?php

use \Core\Helper\DateTime;

/**
 * Virtueller Block: Transfer > * > Anreiseuhrzeit / Abreiseuhrzeit
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Time extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract {

	const TRANSLATION_TITLE = 'time';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-time';

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct($oVirtualParent);
		$this->block_id = static::TYPE_INPUT;
		$this->set_type = static::SUBTYPE;
		$this->parent_area = 0;

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

		$aVisibilityOptions = [
			'default' => 'hide',
			'dependencies' => [
				[
					'type' => 'Field',
					'name' => $sTransferTypeField,
					'data' => [
						'v'.static::TRANSFER_TYPE => [
							[
								'type' => 'Visibility',
								'action' => 'show',
							],
						],
						'varr_dep' => [
							[
								'type' => 'Visibility',
								'action' => 'show',
							],
						],
					],
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
		$oNonVirtualParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oNonVirtualParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$aAttributes[] = [
			'type' => 'ValidateInput',
			'data' => [
				'message' => $this->getTranslation('error', $sLanguage),
				'algorithm' => 'TimeOrEmpty',
			],
		];

		return $aAttributes;

	}

	/**
	 * {@inheritdoc}
	 */
	public function validateFormInput(MVC_Request $oRequest, $mSchool, $sLanguage = null) {

		$aResult = parent::validateFormInput($oRequest, $mSchool, $sLanguage);
		if(!empty($aResult)) {
			return $aResult;
		}

		$sInputName = $this->getInputBlockName();
		if(strlen($sInputName) < 1) {
			return [];
		}

		$sValue = (string)$this->getFormInputValue($oRequest);

		if(strlen($sValue) < 1) {
			return [];
		}

		if(
			!DateTime::isDate($sValue.':00', 'H:i:s') &&
			!DateTime::isDate($sValue, 'H:i:s')
		) {
			return [
				'block_errors' => [
					$sInputName => [
						'value' => $sValue,
						'message' => $this->getErrorMessage($sLanguage),
						'algorithm' => 'InputBlacklist'
					],
				],
			];
		}

		return [];

	}

	/**
	 * @inheritdoc
	 */
	public function canValidate() {
		return true;
	}

}
