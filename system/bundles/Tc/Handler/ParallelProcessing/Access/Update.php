<?php

namespace Tc\Handler\ParallelProcessing\Access;

use Core\Handler\ParallelProcessing\TypeHandler;

class Update extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		// Datenbank aktuallisieren um die Rechte Daten für die anzeige zu haebn
		\Ext_TC_Factory::executeStatic('Ext_TC_Update', 'updateAccessDatabase');

		return true;
	}

	public function afterAction(array $aData, $bExecuted) {

		if($bExecuted === true) {
			\System::s('latest_access_update', date('Y-m-d'));
		}

	}
	
	public function getLabel() {
		return \L10N::t('Aktualisierung der Rechtedatenbank', 'Core');
	}
	
}