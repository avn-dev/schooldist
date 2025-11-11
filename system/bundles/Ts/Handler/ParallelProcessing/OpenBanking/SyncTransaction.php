<?php

namespace Ts\Handler\ParallelProcessing\OpenBanking;

use Carbon\Carbon;
use OpenBanking\Interfaces\Transaction;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\DefaultApi;
use Core\Handler\ParallelProcessing\TypeHandler;
use Psr\Log\LoggerInterface;

class SyncTransaction extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Open Banking: Transaktion synchronisieren');
	}

	private function logger(): LoggerInterface
	{
		return OpenBanking::logger('Transaction');
	}

	public function execute(array $data, $debug = false)
	{
		/* @var Transaction $transaction */
		$transaction = call_user_func_array([$data['class'], 'fromArray'], [$data['payload']]);

		if (empty($paymentMethodId = $data['paymentmethod_id'])) {
			throw new \RuntimeException(sprintf('Missing payment method for banking account [%s, %s]', $transaction->getProviderKey(), $transaction->getId()));
		}

		$this->logger()->info('Sync transaction', ['provider' => $transaction->getProviderKey(), 'payload' => $transaction->toArray()]);

		$identifier = sprintf('%s_%s', $transaction->getProviderKey(), $transaction->getId());

		// Prüfen, ob die Transaktion in unserem System bereits existiert
		if (!empty($found = $this->checkExisting($identifier))) {
			$this->logger()->info('Transaction already exist', ['provider' => $transaction->getProviderKey(), 'transaction' => $identifier, 'found' => $found]);
			return true;
		}

		\DB::begin(__METHOD__);

		try {

			$unallocatedPayment = $this->buildUnallocatedPayment($identifier, $transaction);

			// Eine Buchung oder Rechnung für die Transaktion suchen
			$inquiryOrInvoice = $this->findInquiryOrInvoice($transaction);

			if ($inquiryOrInvoice) {

				$this->logger()->info('Found invoice', ['provider' => $transaction->getProviderKey(), 'transaction' => $transaction->getId(), 'entity' => $inquiryOrInvoice::class, 'entity_id' => $inquiryOrInvoice->id, 'purpose' => $transaction->getPurpose(), 'amount' => (string)$transaction->getAmount()]);

				$inquiry = $inquiryOrInvoice;
				if ($inquiryOrInvoice instanceof \Ext_Thebing_Inquiry_Document) {
					$inquiry = $inquiryOrInvoice->getInquiry();
				}

				$payment = $unallocatedPayment->createInquiryPayment($inquiry, $paymentMethodId, 'open_banking');
				$payment->setMeta('open_banking_transaction', $identifier);
				$payment->save();

				$this->logger()->info('New inquiry payment', ['provider' => $transaction->getProviderKey(), 'transaction' => $transaction->getId(), 'inquiry_id' => $inquiryOrInvoice->id, 'amount' => (string)$transaction->getAmount()]);

			} else {

				// Transaktion als nicht zugewiesene Zahlung speichern damit diese manuell nachbearbeitet werden kann
				$unallocatedPayment->save();

				\Ts\Events\Inquiry\PaymentAllocationFailed::dispatch($unallocatedPayment);

				$this->logger()->info('No invoice found', ['provider' => $transaction->getProviderKey(), 'transaction' => $transaction->getId(), 'purpose' => $transaction->getPurpose(), 'unallocated_payment_id' => $unallocatedPayment->id]);

			}

		} catch (\Throwable $e) {
			$this->logger()->error('Failed', ['provider' => $transaction->getProviderKey(), 'transaction' => $transaction->getId(), 'purpose' => $transaction->getPurpose(), 'error' => $e->getMessage()]);
			\DB::rollback(__METHOD__);

			throw $e;
		}

		if ($transaction instanceof \OpenBanking\Providers\finAPI\Api\Models\Transaction) {
			// Transaktion in finAPI als isNew=false speichern damit diese nicht erneut abgerufen wird
			DefaultApi::default()->editTransaction(\OpenBanking\Providers\finAPI\ExternalApp::getUser(), $transaction->getId(), ['isNew' => false]);
		}

		\DB::commit(__METHOD__);

		return true;
	}

	/**
	 * Prüft ob eine Transaktion bereits im System existiert
	 *
	 * @param string $identifier
	 * @return array|null
	 */
	private function checkExisting(string $identifier): ?array
	{
		$attribute = \DB::getQueryRow("SELECT `id`, `entity`, `entity_id` FROM `wdbasic_attributes` WHERE `key` = :key AND `value` = :value LIMIT 1", [
			'key' => 'open_banking_transaction',
			'value' => $identifier
		]);

		if (!empty($attribute)) {
			return $attribute;
		}

		$unallocatedPayment = \DB::getQueryCol("SELECT `id` FROM `ts_inquires_payments_unallocated` WHERE `transaction_code` = :key LIMIT 1", [
			'key' => $identifier
		]);

		if (!empty($unallocatedPayment)) {
			return ['unallocated_Payment', $unallocatedPayment];
		}

		return null;
	}

	private function buildUnallocatedPayment(string $identifier, Transaction $transaction): \Ext_TS_Inquiry_Payment_Unallocated
	{
		$amount = $transaction->getAmount();

		// TODO - Schule benutzt (noch) Ids
		$currency = \Ext_Thebing_Currency::getCurrencyByIso($amount->getCurrency()->iso4217);

		$payment = new \Ext_TS_Inquiry_Payment_Unallocated();
		$payment->transaction_code = $identifier;
		$payment->comment = $transaction->getPurpose();
		$payment->firstname = $transaction->getCounterPart()?->getName();
		$payment->lastname = '';
		$payment->amount = $amount->amount;
		$payment->amount_currency = (int)$currency->id;
		$payment->payment_date = $transaction->getDate()->toDateString();
		$payment->additional_info = json_encode(['type' => $transaction->getProviderKey(), 'payload' => $transaction->toArray(), 'counterpart' => $transaction->getCounterPart()?->getData()]);

		return $payment;
	}

	/**
	 * Buchung oder Rechnung anhand des Verwendungszwecks der Transaktion suchen
	 *
	 * TODO evtl. verfeinern indem mit dem Muster der Nummernkreise gearbeitet wird? Nachname?
	 *
	 * @param Transaction $transaction
	 * @return \Ext_Thebing_Inquiry_Document|\Ext_TS_Inquiry|null
	 */
	private function findInquiryOrInvoice(Transaction $transaction):\Ext_Thebing_Inquiry_Document|\Ext_TS_Inquiry|null
	{
		if (empty($transaction->getCounterPart())) {
			return null;
		}

		$applyNotOlderThan = function ($query) {
			if (
				!empty($months = \System::d('openbanking.automatic.inquiries_not_older_than')) &&
				is_numeric($months)
			) {
				$query->whereDate('created', '>=', Carbon::today()->subMonths((int)$months));
			}
		};

		// Aufsplitten und gezielt nach den einzelnen Bestandteilen suchen
		$splittedPurpose = collect(explode(' ', $transaction->getPurpose()))
			->filter(fn (string $part) => preg_match('~[0-9]+~', $part))
			->values();

		$invoiceQuery = \Ext_Thebing_Inquiry_Document::query()
			->where('entity', \Ext_TS_Inquiry::class)
			->whereIn('document_number', $splittedPurpose)
			->whereIn('type', \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice'))
			->orderByDesc('created');

		$applyNotOlderThan($invoiceQuery);

		$invoice = $invoiceQuery->first();

		if ($invoice) {
			/* @var \Ext_Thebing_Inquiry_Document $invoice */
			return $invoice;
		}

		$inquiryQuery = \Ext_TS_Inquiry::query()
			->whereIn('number', $splittedPurpose)
			->orderByDesc('created');

		$applyNotOlderThan($inquiryQuery);

		/* @var \Ext_TS_Inquiry $inquiry */
		$inquiry = $inquiryQuery->first();

		return $inquiry;
	}

}