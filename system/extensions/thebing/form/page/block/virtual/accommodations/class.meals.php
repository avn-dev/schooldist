<?php

/**
 * Virtueller Block: Unterkunft > Verpflegung
 */
class Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Meals extends Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'accommodations_meals';

	const TRANSLATION_TITLE = 'meal';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-meal';

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
		$sRoomtypeField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Roomtype::class);

		$aAccommodations = $oForm->oCombination->getServiceHelper()->getAccommodations();

		$aValueMap = [
			$sAccommodationTypeField => [],
			$sRoomtypeField => [],
		];
		$aResultMap = [];

		foreach($aAccommodations as $oDto) {
			$aValueMap[$sAccommodationTypeField]['v'.$oDto->oCategory->id] = $oDto->oCategory->id;
			$aValueMap[$sRoomtypeField]['v'.$oDto->oRoomtype->id] = $oDto->oRoomtype->id;
			$sIndex = ':'.$oDto->oCategory->id.':'.$oDto->oRoomtype->id;
			if(!isset($aResultMap[$sIndex])) {
				$aResultMap[$sIndex] = [
					'value' => $sIndex,
					'select_options' => [],
				];
			}
			$aResultMap[$sIndex]['select_options'][$oDto->oMeal->id] = $oDto->oMeal;
		}

		foreach(array_keys($aResultMap) as $sIndex) {
			// Sortierung nach "position"-Feld
			uasort(
				$aResultMap[$sIndex]['select_options'],
				function(Ext_Thebing_Accommodation_Meal $oMeal1, Ext_Thebing_Accommodation_Meal $oMeal2) {
					return strcmp($oMeal1->position, $oMeal2->position);
				}
			);
			// Umwandeln von Objekten in String (Name)
			$aResultMap[$sIndex]['select_options'] = array_map(
				function(Ext_Thebing_Accommodation_Meal $oMeal) use($sLanguage) {
					return $oMeal->getName($sLanguage);
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
			'childs' => [
				[
					'field' => 'single:'.$sRoomtypeField,
					'options' => [],
					'childs' => [],
				],
			],
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
