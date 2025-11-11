<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class UpdateTransactions extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		if(isset($aData['document_id'])) {
			$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($aData['document_id']);
			$oDocument->updateTransactions();
		} elseif(isset($aData['payment_id'])) {
			$oPayment = \Ext_Thebing_Inquiry_Payment::getInstance($aData['payment_id']);
			$oPayment->updateTransaction();
		}

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Buchung von Rechnungen oder Zahlungen', 'School');
	}

}