<?php

namespace TsRegistrationForm\Handler\ParallelProcessing;

use Core\Exception\ParallelProcessing\RewriteException;
use Core\Exception\ParallelProcessing\TaskException;
use TsFrontend\Handler\ParallelProcessing\FormPayment;
use TsRegistrationForm\Events\FormSaved;
use TsRegistrationForm\Events\PdfCreationFailed;
use TsRegistrationForm\Helper\BuildInquiryHelper;

class InquiryTask extends AbstractTask {

	/**
	 * $data = [
	 *    'combination_id' => 1,
	 *    'object' => 'Ext_TS_Inquiry',
	 *    'object_id' => 1,
	 *    'document_type' => 'brutto',
	 *    'document_items' => [],
	 *    'document_date' => 'Y-m-d',
	 *    'numberrange_id' => 1,
	 *    'unallocated_payment_id' => 1
	 * ];
	 */
	public function execute(array $data, $debug = false) {

		$combination = $this->createCombination($data);
		$inquiry = $this->createObject($data);

		// Inquiry manuell gelöscht
		if ($inquiry->isActive() === false) {
			return true;
		}

		// Nummernkreis sperren
		$numberrange = null;
		if ($data['document_type']) {
			$numberrange = $this->lockNumberrange($data);
		}

		\DB::begin(__CLASS__);

		try {
			// Tuition-Index generieren
			// Das muss vor dem Dokument passieren, da dort möglicherweise Platzhalter, die aus dem Tuition-Index kommen, verwendet werden
			if ($inquiry instanceof \Ext_TS_Inquiry) {
				$tuitionIndex = new \Ext_TS_Inquiry_TuitionIndex($inquiry);
				$tuitionIndex->update();
			}

			$document = null;
			if ($data['document_type']) {
				$helper = new BuildInquiryHelper($combination);
				$document = $helper->createDocument($inquiry, $numberrange, $data);
			}

			if ($inquiry instanceof \Ext_TS_Inquiry) {
				$inquiry->status = 'ready';
				$inquiry->save();
			}
		} catch (\Throwable $e) {
			\DB::rollback(__CLASS__);

			if ($data['document_type']) {
				$numberrange->removeLock();
			}

			throw $e;
		}

		\DB::commit(__CLASS__);

		if ($data['document_type']) {
			$numberrange->removeLock();
		}

		// Zahlung zuweisen (Dokument muss dafür erst da sein)
		// Alles in einer Transaktion ausführen, da die ganzen PDF-Sachen sehr fragil sind und dann z.B. has_invoice fehlen kann
		try {

			if (!empty($data['unallocated_payment_id'])) {
				\DB::begin(__CLASS__);

				$handler = new FormPayment(); // ts-frontend/form-payment
				$handler->execute($data, $debug);

				\DB::commit(__CLASS__);
			}

		} catch (\Throwable $e) {

			\DB::rollback(__CLASS__);

			// Zahlung existiert weiter als unzugewiesene Zahlung
			/** @var \TsRegistrationForm\Generator\CombinationGenerator|\Ext_TS_Frontend_Combination_Inquiry_Abstract $combination */
			$combination->log('allocatePayment Error', ['message' => $e->getMessage(), 'data' => $data, 'file' => $e->getFile(), 'line' => $e->getLine(),'trace' => $e->getTraceAsString()]);

		}

		// Inquiry muss aktualisiert werden, da ansonsten ggf. Rechnungsdaten fehlen (je nach Laune der Registry)
		$inquiry->updateIndexStack(false, true);

		\Ext_Gui2_Index_Stack::save(true);

		// Event abschicken
		FormSaved::dispatch($combination, $inquiry, $document);

		if ($document && $document->getLastVersion()->hasFlag('status', \Ext_Thebing_Inquiry_Document_Version::STATUS_PDF_CREATION_FAILED)) {
			PdfCreationFailed::dispatch($combination, $inquiry, $document);
		}

		// Durch Ereignissteuerung ersetzt

		// E-Mail schicken, Dokument muss ggf. angehangen werden
		//$stackData = collect($data)->only(['combination_id', 'object', 'object_id', 'document_type'])->toArray();
		//\Core\Entity\ParallelProcessing\Stack::getRepository()
		//	->writeToStack('ts-registration-form/mail-task', $stackData, 2);

		return true;

	}

	public function handleException(array $data, TaskException $exception) {

		\DB::rollback(__CLASS__);

		$inquiry = $this->createObject($data);
		if ($inquiry instanceof \Ext_TS_Inquiry) {

			$combination = $this->createCombination($data);
			$combination->log(sprintf('%s::handleException(): %s', __CLASS__, $exception->getMessage()), $exception->getErrorData());

			$inquiry->status = 'fail';
			$inquiry->save();

			\Ext_Gui2_Index_Stack::update('ts_inquiry', $inquiry->id, ['status']);

		}

	}

	private function lockNumberrange(array $data): \Ext_Thebing_Inquiry_Document_Numberrange {

		$numberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getInstance($data['numberrange_id']);

		if ($numberrange->acquireLock()) {
			return $numberrange;
		}

		sleep(\Ext_TC_NumberRange::LOCK_DURATION * 0.5);
		throw new RewriteException('RewriteException: Numberrange already locked');

	}

	public function getLabel() {
		return \L10N::t('Anmeldeformular', 'School');
	}

	public function getRewriteAttempts() {
		return 2;
	}

}