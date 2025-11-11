<?php

namespace Gui2\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class Index extends TypeHandler {

    /**
	 *  
     * @param  array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {
		
		$oGenerator = new \Ext_Gui2_Index_Generator($aData['index_name']);
		if($bDebug === true) {
			$oGenerator->enableDebugmode();
		}
		$oGenerator->updateIndex(null, null, array($aData));
		
		return true;
	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Index', 'Framework');
	}

}