<?php

/**
 * Virtueller Block: Transfer > * > Datum
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Date extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract {

	const TRANSLATION_TITLE = 'date';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-date';

	protected $bRequired = true;

	protected $sCourseDateBlock;

	protected $sAccommodationDateBlock;

	protected $sAttributeUpdateToValueType;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_DATE;
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
		$oNonVirtualParent = $this->getNonVirtualParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oNonVirtualParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		// TODO Geht jetzt auch einfacher, siehe Insurances
		$sCourseDateField = null;
		$sAccommodationDateField = null;
		$oCallbackFunction = function(Ext_Thebing_Form_Page_Block $oBlock) use (
			&$oCallbackFunction, &$sCourseDateField, &$sAccommodationDateField
		) {
			if($oBlock instanceof $this->sCourseDateBlock) {
				$sCourseDateField = $oBlock->getInputDataIdentifier();
			} elseif($oBlock instanceof $this->sAccommodationDateBlock) {
				$sAccommodationDateField = $oBlock->getInputDataIdentifier();
			}
			$aChildBlocks = $oBlock->getChildBlocks();
			array_walk($aChildBlocks, $oCallbackFunction);
		};
		foreach($this->getPage()->getForm()->getPages() as $oPage) {
			$aBlocks = $oPage->getBlocks();
			array_walk($aBlocks, $oCallbackFunction);
		}

		// TODO Wenn es keine Unterkunft gibt, wird das Feld dann generell nicht befüllt?
		if(
			$sCourseDateField === null ||
			$sAccommodationDateField === null
		) {
			// TODO Muss TriggerAjaxRequest: prices nicht weiterhin gesetzt werden, aber als letztes Attribut?
			return $aAttributes;
		}

		$aAttributes[] = array(
			'type' => 'UpdateToValue',
			'data' => array(
				'definitions' => array('all:'.$sCourseDateField, 'all:'.$sAccommodationDateField),
				'type' => $this->sAttributeUpdateToValueType,
				'only_if_unmodified' => true,
				'ignore_values' => array('', '0')
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
