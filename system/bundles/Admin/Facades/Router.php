<?php

namespace Admin\Facades;

use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Admin\Router\Content;
use Admin\Router\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RouterAction openDashboard()
 * @method static RouterAction openUserBoard(bool $initialize = true)
 * @method static RouterAction openBookmarks(bool $initialize = true)
 * @method static RouterAction openSupport(bool $initialize = true)
 * @method static RouterAction openSystemUpdate()
 * @method static RouterAction openCommunication(Collection $models = null, string $application = null, string|array|null $access = null, array $additional = [], bool $initialize = true)
 * @method static Action\OpenGui2Dialog openGui2Dialog(string $ymlName, string $action, array $selectedIds, array $vars = [])
 * @method static Action\OpenTab tab(string $id, string|array $icon, string|array $text, Content|string $content, array $placeholders = [], bool $initialize = true)
 * @method static Action\OpenModal modal(string $text, Content|string $content, array $placeholders = [], bool $initialize = true)
 * @method static Action\OpenSlideOver slideOver(Content|string $content, array $placeholders = [], bool $initialize = true)
 * @method static Content resolveContent(Content|string $payload, array $placeholders = [], boolean $initialize = true)
 * @method static array toStoreData(Instance $admin, RouterAction $routerAction)
 * @method static RouterAction fromStoreData(Instance $admin, string $storeKey, bool $initialize = true)
 */
class Router extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Admin\Factory\RouterAction::class;
	}
}