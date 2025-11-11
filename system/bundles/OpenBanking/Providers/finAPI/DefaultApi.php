<?php

namespace OpenBanking\Providers\finAPI;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Uri;
use OpenBanking\Providers\finAPI\Api\AbstractApi;
use OpenBanking\Providers\finAPI\Api\Models\Account;
use OpenBanking\Providers\finAPI\Api\Models\BankConnection;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Api\Models\Webform;
use OpenBanking\Providers\finAPI\Api\Operations;
use Illuminate\Support\Collection;

class DefaultApi extends AbstractApi
{
	const CLIENT_ID_SETTING_KEY = 'finapi_client_id';
	const CLIENT_SECRET_SETTING_KEY = 'finapi_client_secret';
	const SANDBOX_KEY = 'finapi_sandboxed';

	public static function default(): static
	{
		$sandboxed = (bool)\System::d(self::SANDBOX_KEY, 0);

		$api = new self(
			\System::d(self::CLIENT_ID_SETTING_KEY, ''),
			\System::d(self::CLIENT_SECRET_SETTING_KEY, '')
		);

		return $api->sandboxed($sandboxed);
	}

	public function createUser(string $username, string $password): User
	{
		$operation = new Operations\CreateUser($username, $password);
		return $this->request($operation);
	}

	public function deleteUser(User $user): bool
	{
		return $this->request(new Operations\DeleteUser($user))->successful();
	}

	public function requestWebForm(User $user, Uri $callbackUrl): Webform
	{
		return $this->request(new Operations\RequestWebform($user, $callbackUrl));
	}

	public function getAllBankConnections(User $user): Collection
	{
		return $this->request(new Operations\GetAllBankConnections($user));
	}

	public function getAccounts(User $user): Collection
	{
		return $this->request(new Operations\GetAccounts($user));
	}

	public function getAccount(User $user, int $id): ?Account
	{
		try {
			return $this->request(new Operations\GetAccount($user, $id));
		} catch (\Throwable $e) {}

		return null;
	}

	public function deleteAccount(User $user, Account $account): bool
	{
		return $this->request(new Operations\DeleteAccount($user, $account))->successful();
	}

	public function deleteAllAccounts(User $user): bool
	{
		return $this->request(new Operations\DeleteAllAccounts($user))->successful();
	}

	public function getTransactions(User $user, Collection $accountIds, Carbon $minImportDate, Carbon $maxImportDate, array $filters = []): Collection
	{
		return $this->request(new Operations\GetTransactions($user, $accountIds, $minImportDate, $maxImportDate, $filters));
	}

	public function editTransaction(User $user, int $transactionId, array $payload): bool
	{
		return $this->request(new Operations\EditTransaction($user, $transactionId, $payload))->successful();
	}

	public function backgroundUpdate(User $user, BankConnection $bankConnection): bool
	{
		return $this->request(new Operations\BackgroundUpdate($user, $bankConnection))->successful();
	}

}