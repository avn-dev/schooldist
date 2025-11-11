<?php

namespace Gui2\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class Registry extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {
		
		$oEntity = \Factory::getInstance($aData['class'], $aData['id']);

		if($oEntity instanceof \WDBasic) {

			\Ext_Gui2_Index_Registry::updateStack($oEntity);

			$bSuccess = \Ext_Gui2_Index_Stack::executeCache();

			if($bSuccess) {
				$bSuccess = \Ext_Gui2_Index_Stack::save();
			}

		}
		
		return true;
	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Aktualisierung verknüpfter Entitäten', 'Framework');
	}

}