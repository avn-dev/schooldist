<?php

/**
 * Virtueller Block: Unterkunft > Raumart
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Roomtype extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @var string
	 */
	const SUBTYPE = 'accommodations_roomtype';

	const TRANSLATION_TITLE = 'roomtype';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-roomtype';

	protected $bRequired = true;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {
		parent::__construct($oVirtualParent);
		$this->block_id = self::TYPE_SELECT;
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

		$sAccommodationTypeField = $this->getTypeBlockInputDataIdentifier();

		$aVisibilityOptions = [
			'default' => 'show',
			'dependencies' => [
				[
					'type' => 'Field',
					'name' => $sAccommodationTypeField,
					'data' => [
						'v0' => [
							[
								'type' => 'Visibility',
								'action' => 'hide',
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

		$oForm = $this->getPage()->getForm();
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

		$sAccommodationTypeField = $this->getTypeBlockInputDataIdentifier();

		$aValueMap = [
			$sAccommodationTypeField => [],
		];
		$aResultMap = [];

		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		foreach($aAccommodations as $oDto) {
			$aValueMap[$sAccommodationTypeField]['v'.$oDto->oCategory->id] = $oDto->oCategory->id;
			$sIndex = ':'.$oDto->oCategory->id;
			if(!isset($aResultMap[$sIndex])) {
				$aResultMap[$sIndex] = [
					'value' => $sIndex,
					'select_options' => [],
				];
			}
			$aResultMap[$sIndex]['select_options'][$oDto->oRoomtype->id] = $oDto->oRoomtype;
		}

		foreach(array_keys($aResultMap) as $sIndex) {
			// Sortierung nach "position"-Feld
			uasort(
				$aResultMap[$sIndex]['select_options'],
				function(Ext_Thebing_Accommodation_Roomtype $oRoomtype1, Ext_Thebing_Accommodation_Roomtype $oRoomtype2) {
					return strcmp($oRoomtype1->position, $oRoomtype2->position);
				}
			);
			// Umwandeln von Objekten in String (Name)
			$aResultMap[$sIndex]['select_options'] = array_map(
				function(Ext_Thebing_Accommodation_Roomtype $oRoomtype) use($sLanguage) {
					return $oRoomtype->getName($sLanguage);
				},
				$aResultMap[$sIndex]['select_options']
			);
			// Leere Select-Option und Umwandeln in passendes Format für JS
			$aResultMap[$sIndex]['select_options'] = Ext_TC_Util::addEmptyItem(
				$aResultMap[$sIndex]['select_options']
			);
			$aResultMap[$sIndex]['select_options'] = $this->convertSelectOptions(
				$aResultMap[$sIndex]['select_options']
			);
		}

		$aDefinition = [
			'field' => 'single:'.$sAccommodationTypeField,
			'options' => [],
			'childs' => [],
		];

		$aDefaultSelectOptions = [];
		$aAttributes[] = [
			'type' => 'SelectOptionsMap',
			'data' => [
				'definitions' => [$aDefinition],
				'value_map' => $aValueMap,
				'result_map' => array_values($aResultMap),
				'default_select_options' => array_values($aDefaultSelectOptions),
				'preselect' => ['order' => 'first']
			],
		];

		$aAttributes[] = $this->getDependencyRequirementAttribute();

		$aAttributes[] = [
			'type' => 'TriggerAjaxRequest',
			'data' => [
				'task' => 'prices',
			],
		];

		return $aAttributes;

	}

}
