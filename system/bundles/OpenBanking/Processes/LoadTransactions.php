<?php

namespace OpenBanking\Processes;

use OpenBanking\Enums\Transaction\Direction;
use OpenBanking\Events\ProcessFailed;
use OpenBanking\Exception\ProcessException;
use OpenBanking\Interfaces\Process;
use OpenBanking\Interfaces\Processes\LoadTransactionsTask;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LoadTransactions implements Process
{
	/**
	 * @var LoadTransactionsTask[]
	 */
	private array $tasks = [];

	public function getHumanReadableText($l10n): string
	{
		return OpenBanking::l10n()->translate('Transaktionen synchronisieren');
	}

	public function finApi(finAPI\Api\Models\User $user, Collection $accountIds, array $filters = []): static
	{
		return $this->task(new finAPI\Tasks\LoadTransactions($user, $accountIds, $filters));
	}

	public function task(LoadTransactionsTask $task): static
	{
		$this->tasks[] = $task;
		return $this;
	}

	public function get(Carbon $from, Carbon $until, Direction $direction = null): Collection
	{
		$collection = new Collection();

		$logger = OpenBanking::logger();

		$logger->info('Load transactions', ['from' => $from->toDateTimeString(), 'until' => $until->toDateTimeString(), 'tasks' => count($this->tasks)]);

		try {

			if ($until < $from) {
				throw (new ProcessException('Please define a valid date period for loading transactions'))->process($this);
			}

			foreach ($this->tasks as $task) {
				try {

					$transactions = $task->get($this, $from, $until, $direction);

					$logger->info('Task executed', ['task' => $task::class, 'found' => $transactions->count()]);

					$collection = $collection->merge($transactions);

				} catch (\Throwable $e) {
					throw (new ProcessException('Load transaction task failed', 0, $e))->process($this, $task);
				}
			}

		} catch (\Throwable $e) {

			if (!$e instanceof ProcessException) {
				$e = (new ProcessException('Process failed', 0, $e))->process($this);
			}

			ProcessFailed::dispatch($e);

			$logger->error('Load transaction failed', ['message' => $e->getPrevious()->getMessage()]);

			throw $e;
		}

		$logger->info('Transaction loaded', ['found' => $collection->count()]);

		return $collection;
	}

}