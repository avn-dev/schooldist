<?php

namespace Core\Gui2\Format\ParallelProcessing;

use Core\Service\ParallelProcessingService;
use Core\Handler\ParallelProcessing\TypeHandler;

class Label extends \Ext_Gui2_View_Format_Abstract {
	
	/**
	 * @param string $mValue
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
				
		try {

			$oParallelProcessingService = new ParallelProcessingService();
			$oTypeHandler = $oParallelProcessingService->getTypeHandler($aResultData['type']);

			$sLabel = $oTypeHandler->getLabel();

		} catch(\Throwable $e) {
			$sLabel = \L10N::t('Unbekannt', 'Framework').' ('.$aResultData['type'].')';
		}
		
		return $sLabel;
	}
	
}

