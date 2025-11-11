<?php

namespace Admin\Components\Dashboard;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Carbon\Carbon;
use Core\Facade\Cache;
use Core\Helper\SystemUpdate;
use Core\Service\SystemEvents;
use Illuminate\Http\Request;

class SystemUpdateWidgetComponent implements VueComponent
{
	const KEY = 'update.widget';

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('SystemUpdateWidget', '@Admin/components/dashboard/SystemUpdateWidget.vue');
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$force = $request->boolean('force');

		[$dateAsOf, $availableUpdates] = SystemUpdate::getAvailableUpdates($force);

		SystemEvents::dispatchSystemUpdates($force);

		return (new InitialData([
				'updates' => $availableUpdates,
				'action' => Router::openSystemUpdate()
			]))
			->dateAsOf(Carbon::createFromTimestamp($dateAsOf, date_default_timezone_get()))
			->l10n([
				'dashboard.system_updates.empty' => $admin->translate('Ihr System ist auf dem aktuellsten Stand', 'Dashboard')
			]);
	}

	public function isAccessible(\Access $access): bool
	{
		return $access->hasRight('update');
	}
}