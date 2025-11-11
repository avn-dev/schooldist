<?php

namespace Core\Handler\ParallelProcessing;

class CheckHandler extends TypeHandler {

	public function execute(array $aData, $bDebug = false) {

		$sCheck = $aData['check'];

		if(!class_exists($sCheck)) {
			throw new \RuntimeException('Check "'.$sCheck.'" does not exist');
		}

		$oCheck = new $sCheck();
		return $oCheck->executeProcess($aData);

	}

	public function getLabel() {
		return \L10N::t('Check');
	}

}