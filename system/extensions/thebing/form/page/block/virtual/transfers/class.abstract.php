<?php

abstract class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	const SUBTYPE = '';

	const TRANSFER_TYPE = '';

	/**
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype
	 */
	protected function getTypeBlock() {

		/** @var Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype $oBlock */
		$oBlock = $this->getChildBlock(Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype::class);
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

		$sTransferTypeField = $this->getTypeBlockInputDataIdentifier();
		$aDependencyRequirements = [static::TRANSFER_TYPE, 'arr_dep'];

		return $this->getDependencyRequirementAttributeArray($sTransferTypeField, $aDependencyRequirements);

	}

	/**
	 * Transfer-DTOs zurückliefern, welche dieser KIND-Klasse gehören (Anreise/Abreise)
	 *
	 * @return Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer[]
	 */
	protected function getCorrespondingTransferDTOs() {

		$oForm = $this->getPage()->getForm();
		$aTransfers = $oForm->oCombination->getServiceHelper()->getTransfers();

		return array_filter($aTransfers, function(Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer $oDto) {
			return $oDto->sType === static::TRANSFER_TYPE;
		});

	}

	/**
	 * Child-Block vom Typ »Transfer von« entsprechend der KIND-Klasse zurückliefern
	 *
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferfrom|Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferfrom
	 */
	protected function getTransferFromChildBlock() {

		if(static::TRANSFER_TYPE === 'arrival') {
			$sClass = Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferfrom::class;
		} elseif(static::TRANSFER_TYPE === 'departure') {
			$sClass = Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferfrom::class;
		} else {
			throw new RuntimeException('Use case unknown');
		}

		/** @var Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferfrom|Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferfrom $oBlock */
		$oBlock = $this->getChildBlock($sClass);
		return $oBlock;

	}



}
