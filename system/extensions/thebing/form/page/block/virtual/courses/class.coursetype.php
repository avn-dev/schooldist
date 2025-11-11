<?php

/**
 * Virtueller Block: Kurse > Kurs
 */
class Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype extends Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'courses_coursetype';

	/**
	 * Siehe Elternklasse
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = 'infotext-courses';

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

		$bLevelGroups = (bool)$oParent->getSetting('show_levelgroups');

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();

		$aOptions = [];

		if(!$bLevelGroups) {

			foreach($aCourses as $oDto) {
				$aOptions[$oDto->oCourse->id] = $oDto->oCourse->getName($sLanguage);
			}

			$aOptions = Ext_TC_Util::addEmptyItem($aOptions, $oForm->getTranslation('defaultdd', $sLanguage));
			$aOptions = $this->convertSelectOptions($aOptions);

		} else {

			$aTmpOptions = [];
			foreach($aCourses as $oDto) {
				foreach((array)$oDto->oCourse->course_languages as $courseLanguageId) {
					$aTmpOptions[$courseLanguageId][$oDto->oCourse->id] = $oDto->oCourse->getName($sLanguage);
				}
			}

			$aLevelGroups = Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
			$aLevelGroups = array_intersect_key($aLevelGroups, $aTmpOptions);

			$aOptions[] = [0, $oForm->getTranslation('defaultdd', $sLanguage)];

			foreach($aLevelGroups as $iLevelGroupId => $sLabelGroupName) {
				$aOptions[] = [
					'type' => 'optgroup',
					'label' => $sLabelGroupName,
					'select_options' => $this->convertSelectOptions($aTmpOptions[$iLevelGroupId])
				];
			}

		}

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
