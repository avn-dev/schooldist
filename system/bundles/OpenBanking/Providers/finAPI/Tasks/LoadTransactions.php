<?php

namespace OpenBanking\Providers\finAPI\Tasks;

use OpenBanking\Enums\Transaction\Direction;
use OpenBanking\Interfaces\Process;
use OpenBanking\Interfaces\Processes\LoadTransactionsTask as Task;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\DefaultApi;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LoadTransactions implements Task
{
	public function __construct(
		private readonly User $user,
		private readonly Collection $accountIds,
		private readonly array $filters = []
	) {}

	public function getHumanReadableText($l10n): string
	{
		return OpenBanking::l10n()->translate('finAPI: Transaktionen synchronisieren');
	}

	public function get(Process $process, Carbon $from, Carbon $until, Direction $direction = null): Collection
	{
		if ($direction) {
			$direction = ($direction->isIncoming()) ? 'income' : 'sending';
		} else {
			$direction = 'all';
		}

		return DefaultApi::default()
			->getTransactions($this->user, $this->accountIds, $from, $until, [
				...$this->filters,
				...['direction' => $direction]
			]);
	}

}