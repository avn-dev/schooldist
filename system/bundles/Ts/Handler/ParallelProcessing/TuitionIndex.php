<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class TuitionIndex extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		$oInquiry = \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);

		$oTuitionIndex = new \Ext_TS_Inquiry_TuitionIndex($oInquiry);
		$oTuitionIndex->update();

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Klassenplanung', 'School');
	}

}