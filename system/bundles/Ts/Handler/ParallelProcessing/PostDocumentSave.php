<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class PostDocumentSave extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		$version = \Ext_Thebing_Inquiry_Document_Version::getInstance($aData['version_id']);

		// Kann passieren, wenn die Version nicht mehr da ist, eventuell durch Rollback einer DB Transaktion
		if($version->exist()) {
			$version->updateHasCommissionableItems();
			$version->save();
		}

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Nachbehandlung von Rechnungen', 'School');
	}

}