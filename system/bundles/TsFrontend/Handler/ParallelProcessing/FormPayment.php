<?php

namespace TsFrontend\Handler\ParallelProcessing;

use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;
use Ts\Events\Inquiry\PaymentAllocationFailed;

/**
 * Eingehende Zahlung vom Formular (Zahlungsformular und V2): Dokument suchen und Zahlung zuweisen
 *
 * Da das Dokument ebenso durchs PP generiert wird, wird die Zuweisung der Zahlung mehrfach probiert.
 */
class FormPayment extends TypeHandler {

	/**
	 * $data = [
	 *    'object' => 'Ext_TS_Inquiry',
	 *    'object_id' => 1,
	 *    'unallocated_payment_id' => 1,
	 *    'payment_process_id' => 0
	 * ];
	 */
	public function execute(array $aData, $bDebug = false) {

		$oInquiry = \Ext_TS_Inquiry::getInstance($aData['object_id']);
		if (!$oInquiry->exist()) {
			throw new \RuntimeException('No inquiry!');
		}

		$oUnallocatedPayment = \Ext_TS_Inquiry_Payment_Unallocated::getInstance($aData['unallocated_payment_id']);
		if (!$oUnallocatedPayment->exist()) {
			// Vielleicht wurde die Zahlung auch manuell zugewiesen
			throw new \RuntimeException('Unallocated payment does not exist!');
		}

		// Lediglich registrierte Payments nicht zuweisen
		if ($oUnallocatedPayment->status === \Ext_TS_Inquiry_Payment_Unallocated::STATUS_REGISTERED) {
			return true;
		}

		try {

			$oDocument = $oInquiry->getLastDocument('invoice');
			if ($oDocument === null) {
				// Ein Dokument ist zwingend notwendig, das wird aber ebenso durchs PP generiert
				// Daher mehrfach probieren, denn eventuell fehlt das Dokument nur wegen einer Nummernkreissperre
				throw new RewriteException('No document available for payment allocation!');
			}

			// Proforma umwandeln, da nur eine Rechnung Zahlungen haben kann
			// Der Sinn hiervon ist, dass bei einer fehlerhaften Zahlung nur die Proforma zurückbleibt und gelöscht werden kann
			if (
				$oDocument->isProforma() &&
				!\System::d('ts_payments_without_invoice')
			) {
				$this->convertProforma($oInquiry, $oDocument);
			}

			$oPayment = $oUnallocatedPayment->createInquiryPayment($oInquiry, $oUnallocatedPayment->payment_method_id, 'frontend_payment_form');

			$oInquiry->setInquiryStatus();

		} catch (\Throwable $e) {
			if (!$e instanceof RewriteException) {
				// Nur auslösen wenn es keine RewriteException ist
				PaymentAllocationFailed::dispatch($oUnallocatedPayment);
			}
			throw $e;
		}

		if (
			!empty($aData['payment_process_id']) &&
			($oPaymentProcess = \Ts\Entity\Payment\PaymentProcess::getInstance($aData['payment_process_id'])) !== null
		) {
			$oPaymentProcess->payment_id = $oPayment->id;
			$oPaymentProcess->save();
		}

		\Ext_Gui2_Index_Stack::save(true);

		return true;

	}

	private function convertProforma(\Ext_TS_Inquiry $oInquiry, \Ext_Thebing_Inquiry_Document $oDocument) {

		$oSchool = $oInquiry->getSchool();

		$aNumberranges = \Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType('brutto', false, false, $oSchool->id);
		if (empty($aNumberranges)) {
			throw new \RuntimeException('$aNumberranges is empty');
		}

		$sComment = \L10N::t('Proforma-Rechnung in Rechnung umwandeln aufgrund von Zahlung', \Ext_Thebing_Document::$sL10NDescription);
		$oDocument->overrideCreationAsDraft = true;
		$oDocument = $oDocument->convertProformat2InquiryDocument($sComment, key($aNumberranges), '', false);
		if (!$oDocument instanceof \Ext_Thebing_Inquiry_Document) {
			throw new \RuntimeException('convertProformat2InquiryDocument did not return an inquiry document');
		}

	}

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return \L10N::t('Zahlung über Frontend', 'School');
	}

	/**
	 * @inheritdoc
	 */
	public function getRewriteAttempts() {
		return 10;
	}

}
