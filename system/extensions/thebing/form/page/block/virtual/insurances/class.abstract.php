<?php

abstract class Ext_Thebing_Form_Page_Block_Virtual_Insurances_Abstract extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	/**
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype
	 */
	protected function getTypeBlock() {

		/** @var Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype $oBlock */
		$oBlock = $this->getChildBlock(Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype::class);
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

		$aInsurances = $oForm->oCombination->getServiceHelper()->getInsurances();
		$aDependencyRequirements = [];
		foreach($aInsurances as $oInsurance) {
			$aDependencyRequirements[] = (string)$oInsurance->id;
		}

		return $this->getDependencyRequirementAttributeArray($sCourseTypeField, $aDependencyRequirements);

	}

	/**
	 * Definition für: Felder vorbefüllen mit entsprechenden Feldern aus Kurs und/oder Unterkunft
	 *
	 * @param string $sCourseClass1
	 * @param string $sAccommodationClassDate2
	 * @param string $sType
	 * @return array|null
	 */
	protected function getUpdateToValueAttribute($sCourseClass1, $sAccommodationClassDate2, $sType) {

		$aUpdateToValueDefinitions = [];
		$oForm = $this->getPage()->getForm();

		$oCourseBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES);
		if($oCourseBlock !== null) {
			/** @var Ext_Thebing_Form_Page_Block_Virtual_Container $oContainer */
			$oContainer = reset($oCourseBlock->getChildBlocks());
			$aUpdateToValueDefinitions[] = 'all:'.$oContainer->getChildBlock($sCourseClass1)->getInputDataIdentifier();
		}

		$oAccommodationBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);
		if($oAccommodationBlock !== null) {
			/** @var Ext_Thebing_Form_Page_Block_Virtual_Container $oContainer */
			$oContainer = reset($oAccommodationBlock->getChildBlocks());
			$aUpdateToValueDefinitions[] = 'all:'.$oContainer->getChildBlock($sAccommodationClassDate2)->getInputDataIdentifier();
		}

		if(empty($aUpdateToValueDefinitions)) {
			return null;
		}

		return [
			'type' => 'UpdateToValue',
			'data' => [
				'definitions' => $aUpdateToValueDefinitions,
				'type' => $sType,
				'only_if_unmodified' => true,
				'ignore_values' => ['', '0']
			]
		];

	}

}
