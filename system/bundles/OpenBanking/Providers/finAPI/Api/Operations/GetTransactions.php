<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use OpenBanking\Dto\Amount;
use OpenBanking\Dto\Counterpart;
use OpenBanking\Enums\Transaction\Direction;
use OpenBanking\Providers\finAPI\Api\Models\Transaction;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * https://docs.finapi.io/#get-/api/v2/transactions
 */
class GetTransactions implements UserOperation
{
	public function __construct(
		private readonly User $user,
		private readonly Collection $accountIds,
		private readonly Carbon $minImportDate,
		private readonly Carbon $maxImportDate,
		private readonly array $filters = [],
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Collection|Response
	{
		if ($this->accountIds->isEmpty()) {
			return new Collection();
		}

		return $this->request($request);
	}

	private function request(PendingRequest $request, int $page = 1, Collection $collection = null): Collection
	{
		if ($collection === null) {
			$collection = new Collection();
		}

		$response = $request
			->asJson()
			->get('/api/v2/transactions', [
				...$this->filters,
				...[
					'view' => 'bankView',
					'page' => $page,
					'perPage' => 500,
					'accountIds' => $this->accountIds->implode(','),
					'minImportDate' => $this->minImportDate->toDateString(),
					'maxImportDate' => $this->maxImportDate->toDateString()
				]
			]);

		if (!$response->successful()) {
			$response->throw();
		}

		$json = $response->json();

		$transactions = collect($json['transactions'])
			->map(function (array $transaction) {

				$counterPart = null;
				if (isset($transaction['counterpartName'])) {
					$keys = array_filter(array_flip($transaction), fn ($key) => str_starts_with($key, 'counterpart'));
					$counterPart = new Counterpart($transaction['counterpartName'], array_intersect_key($transaction, array_flip($keys)));
				}

				return new Transaction(
					$transaction['id'],
					$transaction['accountId'],
					($transaction['amount'] < 0) ? Direction::OUTGOING : Direction::INCOMING,
					Carbon::parse($transaction['bankBookingDate']),
					new Amount($transaction['amount'], \Ext_TC_Currency::getInstance($transaction['currency'])),
					$transaction['purpose'] ?? '',
					$counterPart
				);
			});

		$collection = $collection->merge($transactions);

		if ($json['paging']['pageCount'] >= $page) {
			$collection = $collection->merge($this->request($request, ($page + 1)));
		}

		return $collection;
	}

}