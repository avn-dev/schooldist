<?php

namespace Admin\Facades;

use Admin\Enums\Size;
use Admin\Http\InterfaceResponse as Response;
use Admin\Interfaces\Component\VueComponent;
use Admin\Interfaces\RouterAction;
use Admin\Router\Content;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Response status(int $status)
 * @method static Response l10n(string|array $payload, string $translation = null)
 * @method static Response modal(string $text, Content $content, array $placeholder = [], Size $size = Size::MEDIUM)
 * @method static Response tab(string $id, string|array $text, Content $content)
 * @method static Response slideOver(Content|VueComponent|string $content, array $placeholders = [], Size $size = Size::MEDIUM, bool $initialize = true)
 * @method static Response gui2Dialog(string $ymlName, string $action, array $selectedIds, array $vars = [])
 * @method static Response action(RouterAction $action)
 * @method static Response notification(Notification $notification, $notifiable = null)
 * @method static Response visit(string $url)
 * @method static Response json(array|Arrayable $data, $status = 200)
 * @method static Response render(string $component, array|Arrayable $props = [])
 */
class InterfaceResponse extends Facade
{
	protected static function getFacadeAccessor()
	{
		return Response::class;
	}
}