<?php

namespace Ts\Service\OpenBanking;

use Illuminate\Support\Collection;
use OpenBanking\Enums\Transaction\Direction;
use OpenBanking\Interfaces\Transaction;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\ExternalApp as finAPIApp;
use TcExternalApps\Service\AppService;
use Core\Entity\ParallelProcessing\Stack;
use Carbon\Carbon;

class IncomingPayments
{
	public static function run(): Collection
	{
		$lastCallTimestamp = \System::d('ts_incoming_payments_sync', Carbon::now()->subMinute()->getTimestamp());
		$lastCall = Carbon::now()->setTimestamp($lastCallTimestamp);

		$process = OpenBanking::transactions();

		if (AppService::hasApp(finAPIApp::APP_KEY)) {
			// Nur Accounts laden die ausgewählt sind und eine Bezahlmethode haben
			$accounts = array_intersect_key(finAPIApp::getPaymentMethodsIds(), array_flip(finAPIApp::getAccountIds()));

			if (!empty($accounts)) {
				// isNew wird per PP auf false gesetzt beim Abarbeiten der Transaktion
				// minBankBookingDate da es sein kann dass Transaktionen von heute erst morgen eingelesen werden.
				$process->finApi(finAPIApp::getUser(), collect($accounts)->keys(), ['isNew' => true, 'minBankBookingDate' => $lastCall->clone()->subDay()->toDateString()]);
			}
		}

		$now = Carbon::now();

		$transactions = $process->get($lastCall, $now, Direction::INCOMING);

		// Zeitpunkt der letzten Abfrage speichern damit beim nächsten Aufruf die Transaktionen ab diesem Zeitpunkt geladen werden
		\System::s('ts_incoming_payments_sync', $now->getTimestamp());

		foreach ($transactions as $transaction) {
			/* @var Transaction $transaction */

			$paymentMethodId = null;
			if ($transaction instanceof \OpenBanking\Providers\finAPI\Api\Models\Transaction) {
				// Bezahlmethode aus den externen App-Einstellungen für das Konto der Transaktion auslesen
				$paymentMethodId = finAPIApp::getPaymentMethodsIds()[$transaction->getAccountId()] ?? null;
			}

			// Transaktionen einzeln über das PP, weil pro Transaktion geschaut werden muss ob diese
			// schon im System existiert und ggf. mit der API kommuniziert werden muss
			Stack::getRepository()->writeToStack('ts/open-banking-transaction', [
				'class' => $transaction::class,
				'payload' => $transaction->toArray(),
				'paymentmethod_id' => $paymentMethodId,
			], 10);
		}

		return $transactions;
	}
}