<?php

namespace Ts\Entity\Payment;

use Illuminate\Support\Str;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $parent_id
 * @property $inquiry_id
 * @property $payment_id
 * @property $hash
 * @property $capture
 * @property $seen
 * @property $payed
 */
class PaymentProcess extends \WDBasic {

	protected $_sTable = 'ts_inquiries_payments_processes';

	public function getInquiry(): \Ext_TS_Inquiry {
		return \Ext_TS_Inquiry::getInstance($this->inquiry_id);
	}

	/**
	 * Prozess (nur) erzeugen, wenn es keinen offenen Prozess gibt und es einen zu bezahlenden Betrag gibt
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return static
	 */
	public static function createPaymentProcess(\Ext_TS_Inquiry $inquiry, self $exclude = null): ?self {

		$query = self::query()
			->where('inquiry_id', $inquiry->id)
			->where('capture', 'next')
			->whereNull('payed');

		if ($exclude) {
			$query->where('id', '!=', $exclude->id);
		}

		/** @var self $process */
		$process = $query->first();

		// Offenen Prozess gefunden, diesen verwenden
		if ($process) {
			return $process;
		}

		// Buchung komplett bezahlt oder es gibt keinen zu bezahlenden Betrag: Nichts generieren
		// Diese Prüfung darf nicht da sein, da man sonst den Platzhalter nicht auf der Rechnung selber verwenden kann, da der offene Betrag dann noch nicht abgespeichert ist.
//		$amountOpen = $inquiry->getOpenPaymentAmount();
//		if (bccomp($amountOpen, 0) === 0) {
//			return null;
//		}

		$process = new self();
		$process->inquiry_id = $inquiry->id;
		$process->hash = strtolower(Str::random(8));
		$process->capture = 'next';

		if ($exclude) {
			// Wird nicht aktiv verwendet, aber so lässt sich das besser nachvollziehen
			$process->parent_id = $exclude->id;
		}

		return $process;

	}

}