<?php

namespace Communication\Services;

use Communication\Exceptions\Mail\AccountConnectionLocked;
use Core\Facade\Cache;
use Core\Service\NotificationService;

class ConnectionLock
{
	const MAX_CONCURRENT_CONNECTIONS = 3;

	const LOCK_DURATION = 60;

	public static function lock(\Ext_TC_Communication_EmailAccount $account): string
	{
		$connectionId = \Util::generateRandomString(6);

		$cacheKey = self::buildCacheKey($account);

		$connections = (array)Cache::get($cacheKey);
		$connections[] = $connectionId;

		if (count($connections) > self::MAX_CONCURRENT_CONNECTIONS) {
			NotificationService::getLogger('MailAccount')->error('Account connections limit exceeded', ['account' => $account->email, 'limit' => self::MAX_CONCURRENT_CONNECTIONS]);
			throw new AccountConnectionLocked($account);
		}

		Cache::put($cacheKey, self::LOCK_DURATION, $connections);

		return $connectionId;
	}

	public static function unlock(\Ext_TC_Communication_EmailAccount $account, string $connectionId): void
	{
		$cacheKey = self::buildCacheKey($account);

		$connections = (array)Cache::get($cacheKey);
		$connections = array_filter($connections, fn ($loop) => $loop !== $connectionId);

		if (empty($connections)) {
			Cache::forget($cacheKey);
		} else {
			Cache::put($cacheKey, self::LOCK_DURATION, $connections);
		}
	}

	private static function buildCacheKey(\Ext_TC_Communication_EmailAccount $account): string
	{
		return sprintf('tc_email_account_%d_connections', $account->id);
	}
}