<?php

abstract class Ext_Thebing_Form_Page_Block_Virtual_Courses_Abstract extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	/**
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype
	 */
	protected function getTypeBlock() {

		/** @var Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype $oBlock */
		$oBlock = $this->getChildBlock(Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype::class);
		return $oBlock;

	}

	/**
	 * @return string
	 */
	protected function getTypeBlockInputDataIdentifier() {

		$oBlock = $this->getTypeBlock();
		return $oBlock->getInputDataIdentifier();

	}

	/**
	 * @return array
	 */
	protected function getDependencyRequirementAttribute() {

		$oForm = $this->getPage()->getForm();
		$sCourseTypeField = $this->getTypeBlockInputDataIdentifier();

		$aCourses = $oForm->oCombination->getServiceHelper()->getCourses();
		$aDependencyRequirements = [];
		foreach($aCourses as $oDto) {
			$aDependencyRequirements[] = (string)$oDto->oCourse->id;
		}

		return $this->getDependencyRequirementAttributeArray($sCourseTypeField, $aDependencyRequirements);

	}

}
