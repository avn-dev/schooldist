<?php

namespace Tc\Handler\ParallelProcessing\Combination;

use Core\Exception\ParallelProcessing\TaskException;
use Core\Handler\ParallelProcessing\TypeHandler;

class Initialize extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

        /** @var \Ext_TC_Frontend_Combination $oCombination */
		$oCombination = \Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $aData['combination_id']);

		// Kombination wurde zwischenzeitlich gelöscht
		if(!$oCombination->isActive()) {
			return true;
		}

		$oFrontendCombination = $oCombination->getObjectForUsage(new \SmartyWrapper());
		$oFrontendCombination->initializeData();		

		return true;
	}

    /**
     * @param array $aData
     * @param bool $bExecuted
     */
    public function afterAction(array $aData, $bExecuted) {

        if(!$bExecuted) {
			/** @var \Ext_TC_Frontend_Combination $oCombination */
			$oCombination = \Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $aData['combination_id']);
            $oCombination->updateState('fail');
        }
		
    }

	/**
	 * @param array $aData
	 * @param TaskException $oException
	 */
	public function handleException(array $aData, TaskException $oException) {
		/** @var \Ext_TC_Frontend_Combination $oCombination */
		$oCombination = \Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $aData['combination_id']);
		
		$oException->bindErrorData(['combination' => $oCombination->getName()]);
		
		$oCombination->updateState('fail');
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return \L10N::t('Frontend-Kombinationen', 'Core');
	}

}