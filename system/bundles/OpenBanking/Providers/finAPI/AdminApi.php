<?php

namespace OpenBanking\Providers\finAPI;

use OpenBanking\Providers\finAPI\Api\AbstractApi;
use OpenBanking\Providers\finAPI\Api\Operations;
use Illuminate\Support\Collection;

/**
 * Nur für administrative Aufgaben
 * https://docs.finapi.io/#tag--Mandator-Administration
 */
class AdminApi extends AbstractApi
{
	const ADMIN_CLIENT_ID_SETTING_KEY = 'finapi_admin_client_id';
	const ADMIN_CLIENT_SECRET_SETTING_KEY = 'finapi_admin_client_secret';

	public static function default(): static
	{
		return new self(
			\System::d(self::ADMIN_CLIENT_ID_SETTING_KEY, ''),
			\System::d(self::ADMIN_CLIENT_SECRET_SETTING_KEY, '')
		);
	}

	public function getUserList(): Collection
	{
		return $this->request(new Operations\Admin\GetUserList());
	}

	public function deleteUsers(Collection $usernames): bool
	{
		return $this->request(new Operations\Admin\DeleteUsers($usernames))
			->successful();
	}
}