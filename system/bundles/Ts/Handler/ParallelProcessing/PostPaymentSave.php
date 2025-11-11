<?php

namespace Ts\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use Ts\Events\Inquiry\NewPayment;

/**
 * @see \Ext_Thebing_Inquiry_Payment::writePostSaveTask()
 */
class PostPaymentSave extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Nachbehandlung von Zahlungen', 'School');
	}

	/**
	 * TODO siehe \Ext_Thebing_Inquiry_Payment::writePostSaveTask() -> $payment->inquiry_id
	 *
	 * @param array $data
	 * @param $debug
	 * @return bool
	 */
	public function execute(array $data, $debug = false)
	{
		/* @var \Ext_Thebing_Inquiry_Payment $payment */
		$payment = \Ext_Thebing_Inquiry_Payment::query()->find($data['payment_id']);
		/* @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Ext_TS_Inquiry::query()->find($data['inquiry_id']);

		if (!$inquiry || !$payment) {
			return true;
		}

		if (isset($data['receipts'])) {
			foreach ($data['receipts'] as $payload) {
				$payment->createPaymentPdf($payload);
			}
		}

		NewPayment::dispatch($inquiry, $payment);

		return true;
	}
}