<?php

/**
 * Virtueller Block: Kurse > Startdatum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_startdate';

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

		$aVisibilityOptions = array(
			'default' => 'show',
			'dependencies' => array(
				array(
					'type' => 'Field',
					'name' => $this->getTypeBlockInputDataIdentifier(),
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

		$sCourseTypeField = $this->getTypeBlockInputDataIdentifier();
		$sLevelField = $this->getChildBlockInputDataIdentifier(Ext_Thebing_Form_Page_Block_Virtual_Courses_Level::class);

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$aValueMap = array(
			$sCourseTypeField => array()
		);
		$aResultMap = array();

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();

		// Startdatum abhängig von Kurs (SelectOptionsInRange)
		if(!$oNonVirtualParent->getSetting('startdates_depending_on_level')) {

			foreach($aCourses as $oDto) {

				if(!empty($oDto->aStartDates)) {

					// TOOD Das könnte man bei AVAILABILITY_ALWAYS eigentlich optimieren, aber da hängen alle anderen Felder auch noch dran
					$aValueMap[$sCourseTypeField]['v' . $oDto->oCourse->id] = $oDto->oCourse->id; // v, damit das immer ein Objekt mit json_encode() ergibt
					$aResultMap[$oDto->oCourse->id] = array();
					foreach ($oDto->aStartDates as $oStartDateDto) {
						$aResultMap[$oDto->oCourse->id][$oStartDateDto->start->format('Ymd')] = $oDateFormat->format($oStartDateDto->start);
					}
					$aResultMap[$oDto->oCourse->id] = Ext_TC_Util::addEmptyItem($aResultMap[$oDto->oCourse->id]);
					$aResultMap[$oDto->oCourse->id] = $this->convertSelectOptions($aResultMap[$oDto->oCourse->id]);
					$aResultMap[$oDto->oCourse->id] = array(
						'value' => $oDto->oCourse->id,
						'select_options' => $aResultMap[$oDto->oCourse->id]
					);
				}

			}

			$aDefinition = array(
				'field' => 'single:'.$sCourseTypeField,
				'options' => array(),
				'childs' => array()
			);

			$aDefaultSelectOptions = array();
			$aAttributes[] = array(
				'type' => 'SelectOptionsInRange',
				'data' => array(
					'definitions' => array($aDefinition),
					'value_map' => $aValueMap,
					'result_map' => array_values($aResultMap),
					'default_select_options' => array_values($aDefaultSelectOptions)
				)
			);

		// Startdatum abhängig von Kurs und Level (SelectOptionsMap)
		// Das erzeugt sehr viele Daten und bringt einen Browser ggf. zum Absturz!
		} else {

			foreach($aCourses as $oDto) {
				$aValueMap[$sCourseTypeField]['v'.$oDto->oCourse->id] = $oDto->oCourse->id;
				foreach(array_keys($oDto->aLevels) as $iLevelId) {
					$aValueMap[$sLevelField]['v'.$iLevelId] = $iLevelId;
					$sIndex = ':'.$oDto->oCourse->id.':'.$iLevelId;
					foreach($oDto->aStartDates as $oStartDateDto) {
						if(
							empty($oStartDateDto->levels) ||
							in_array($iLevelId, $oStartDateDto->levels)
						) {
							$aResultMap[$sIndex]['value'] = $sIndex;
							$aResultMap[$sIndex]['select_options'][$oStartDateDto->start->format('Ymd')] = $oDateFormat->format($oStartDateDto->start);
						}
					}
				}
			}

			foreach(array_keys($aResultMap) as $sIndex) {
				$aResultMap[$sIndex]['select_options'] = Ext_TC_Util::addEmptyItem(
					$aResultMap[$sIndex]['select_options']
				);
				$aResultMap[$sIndex]['select_options'] = $this->convertSelectOptions(
					$aResultMap[$sIndex]['select_options']
				);
			}

			$aDefinition = [
				'field' => 'single:'.$sCourseTypeField,
				'options' => [],
				'childs' => [
					[
						'field' => 'single:'.$sLevelField,
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
					//'preselect' => ['order' => 'first']
				],
			];

		}

		$aAttributes[] = $this->getDependencyRequirementAttribute();

		$aAttributes[] = array(
			'type' => 'TriggerAjaxRequest',
			'data' => array(
				'task' => 'prices',
				'additional' => ['check_services' => 1]
			)
		);

		return $aAttributes;

	}

}
