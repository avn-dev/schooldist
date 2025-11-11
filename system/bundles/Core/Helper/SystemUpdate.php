<?php

namespace Core\Helper;

use Carbon\Carbon;

class SystemUpdate
{
	const CACHE_KEY = 'system.updates';

	public static function getAvailableUpdates(bool $force = true): array
	{
		if (!$force && !empty($cache = \Core\Facade\Cache::get(self::CACHE_KEY, true))) {
			return $cache;
		}

		$oUpdate = new \Update();
		$oUpdate->flushUpdatesCache();
		$aUpdate = $oUpdate->getUpdates();

		$aAvailableUpdates = [];

		if(!empty($aUpdate)) {
			end($aUpdate);
			$fLatestUpdate = key($aUpdate);
			$aAvailableUpdates[] = [
				'label' => \L10N::t('Framework'),
				'version' => $fLatestUpdate,
				'extension' => null
			];
		}

		$aUpdateExtensions = $oUpdate->getExtensions();
		$aExtensions = \DB::getQueryRows("SELECT * FROM system_elements WHERE element = 'modul' AND active = 1 ORDER BY title");

		foreach($aExtensions as $aExtension) {
			if(
				isset($aUpdateExtensions[$aExtension['file']]) &&
				version_compare($aUpdateExtensions[$aExtension['file']], $aExtension['version']) > 0
			) {
				$aAvailableUpdates[] = [
					'label' => $aExtension['title'],
					'version' => $aUpdateExtensions[$aExtension['file']],
					'extension' => $aExtension['file']
				];
			}
		}

		$payload = [Carbon::now()->getTimestamp(), $aAvailableUpdates];

		\Core\Facade\Cache::forever(self::CACHE_KEY, $payload);

		return $payload;
	}
}