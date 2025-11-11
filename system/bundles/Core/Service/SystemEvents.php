<?php

namespace Core\Service;

use Core\Helper\SystemUpdate;
use Illuminate\Support\Str;

class SystemEvents
{
	public static function dispatchSystemUpdates(bool $force = true): void
	{
		[, $availableUpdates] = SystemUpdate::getAvailableUpdates($force);

		if (empty($availableUpdates)) {
			return;
		}

		$buildNotificationKey = function ($update) {
			$extension = ($update['extension'] !== null) ? Str::slug($update['extension']) : 'framework';
			return sprintf('update_%s_last_dispatched', $extension);
		};

		$notify = false;
		foreach ($availableUpdates as $availableUpdate) {

			$alreadyNotifiedVersion = \System::d($buildNotificationKey($availableUpdate), null);

			if (
				$alreadyNotifiedVersion === null ||
				version_compare($availableUpdate['version'], $alreadyNotifiedVersion) > 0
			) {
				$notify = true;
			}

			\System::s($buildNotificationKey($availableUpdate), $availableUpdate['version']);
		}

		if ($notify) {
			\Core\Events\NewSystemUpdates::dispatch($availableUpdates);
		}
	}
}