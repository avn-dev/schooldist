<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class PartialInvoice extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		$oInquiry = \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);

		$oInquiry->generatePartialInvoices();

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Teilrechnungen', 'School');
	}

}