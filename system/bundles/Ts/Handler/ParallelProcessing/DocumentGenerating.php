<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class DocumentGenerating extends TypeHandler {

    /**
     * @param array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		$bSuccess = $this->executeType($aData);

		// Manuell ausführen, da das bisher nicht im SequentialProcessing drin ist
		\Ext_Gui2_Index_Stack::executeCache();
		\Ext_Gui2_Index_Stack::save();

		return $bSuccess;

	}

	/**
	 * @param array $aData
	 * @return bool
	 */
	private function executeType(array $aData) {

		$oObject = $this->getObject($aData);

		switch($aData['type']) {
			case 'additional_document':
			case 'attached_additional':
				$oService = new \Ext_TS_Document_AdditionalServiceDocuments($oObject);
				return $oService->generateDocumentByTask($aData);
			case 'document_payment_overview': // Übersicht der Zahlungen je Dokument
				return $oObject->createPaymentDocument($aData);
			case 'inquiry_payment_overview': // Übersicht der Zahlungen je Buchung
				return $oObject->createInquiryDocumentOverview($aData);
			case 'payment_receipt': // Übersicht der Zahlung je Zahlung
				return $oObject->createPaymentPdf($aData);
			default:
				throw new \InvalidArgumentException('Unknown type "'.$aData['type'].'"');
		}

	}

	/**
	 * @param $aData
	 * @return \Ext_Thebing_Inquiry_Document|\Ext_Thebing_Inquiry_Payment|\Ext_TS_Inquiry|null
	 * @throws \Exception
	 */
	private function getObject($aData) {

		switch($aData['type']) {
			case 'document_payment_overview':
				return \Ext_Thebing_Inquiry_Document::getInstance($aData['document_id']);
			case 'additional_document':
			case 'attached_additional':
			case 'inquiry_payment_overview':
				return \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);
			case 'payment_receipt':
				return \Ext_Thebing_Inquiry_Payment::getInstance($aData['payment_id']);
		}

		return null;
	}

	/**
	* @param array $aData
	* @param bool $bExecuted
	*/
	public function afterAction(array $aData, $bExecuted) {

		if(!$bExecuted) {
			switch($aData['type']) {
				case 'document_payment_overview':

					$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($aData['payment_document_id']);
					$oDocument->status = 'fail';
					$oDocument->save();

					break;
				case 'inquiry_payment_overview':
				case 'payment_receipt':

					$aDocuments = (array)$aData['document_ids'];
					$aDocuments[] = $aData['main_document_id'];

					foreach($aDocuments as $iDocumentId) {
						$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
						$oDocument->status = 'fail';
						$oDocument->save();
					}

					break;
			}
		}

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Dokumente', 'School');
	}

}