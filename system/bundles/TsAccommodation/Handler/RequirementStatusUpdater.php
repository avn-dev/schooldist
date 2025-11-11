<?php

namespace TsAccommodation\Handler;

use Core\Handler\ParallelProcessing\TypeHandler;

class RequirementStatusUpdater extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		$oAccommodation = \Ext_Thebing_Accommodation::getInstance($aData['accommodation_id']);

		if($oAccommodation->exist()) {
			$oAccommodation->updateRequirementStatus();
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return \L10N::t('Unterkunfts-Voraussetzungen');
	}

}
