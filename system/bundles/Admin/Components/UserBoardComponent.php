<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\Parameters;
use Admin\Dto\Component\VueComponentDto;
use Admin\Enums\Size;
use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router as RouterFacade;
use Admin\Factory\Content;
use Admin\Http\Resources\SystemButtonResource;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\RouterAction;
use Admin\Traits\SystemButtons;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

class UserBoardComponent implements Component\VueComponent, Component\RouterActionSource
{
	use SystemButtons;

	const KEY = 'userBoard';

	public function __construct(
		private Container $container,
		private \Access_Backend $access,
		private Instance $admin
	) {}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('UserBoard', '@Admin/layouts/admin/UserBoard.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$tabs = [
			['key' => 'notifications', 'icon' => 'fa fa-bell', 'text' => $admin->translate('Nachrichten'),  'component' => 'UserNotifications', 'active' => true],
			// TODO konzipieren und implementieren
			//['key' => 'tasks', 'icon' => 'fa fa-clipboard-check', 'title' => 'Aufgaben',  'component' => 'TaskListTab', 'active' => false],
		];

		// Buttons auf max. 12 begrenzen damit es nicht zu viele werden
		$buttons = $this->getButtons($this->access, $this->container)->slice(0, 12);

		$profileAction = static::getRouterActionByKey($admin, 'profile', initialize: false);

		return (new InitialData([
				'profile' => $profileAction,
				'tabs' => $tabs,
				'buttons' => SystemButtonResource::collection($buttons)
			]))
			->l10n([
				'userboard.btn.my' => $admin->translate('Mein Profil'),
				'userboard.notification.empty' => $admin->translate('Keine Nachrichten vorhanden'),
				'userboard.notification.count' => $admin->translate('%d/%d Nachrichten'),
				'userboard.notification.delete_all' => $admin->translate('Alle Nachrichten löschen'),
				'userboard.notification.delete_all.confirm.title' => $admin->translate('Möchten Sie wirklich alle Nachrichten löschen?'),
				'userboard.notification.delete_all.confirm.text' => $admin->translate('Durch diese Aktion werden die Nachrichten unwiderruflich gelöscht. Möchten Sie wirklich fortfahren?'),
			]);
	}

	public function button(Request $request, Container $container, \Access_Backend $access)
	{
		$key = $request->input('button');

		$button = $this->getButton($access, $container, $key);

		if (!$button) {
			return response('Bad request', 400);
		}

		$response = $button->handle($request);

		if (is_bool($response)) {
			$message = ($response === true)
				? ['type' => 'success', 'message' => $this->admin->translate('Die Aktion wurde erfolgreich durchgeführt.')]
				: ['type' => 'error', 'message' => $this->admin->translate('Die Aktion konnte nicht durchgeführt werden.')];

			$response = InterfaceResponse::json(['message' => $message]);
		}

		$response->json([
			'button' => new SystemButtonResource($button)
		]);

		return $response;
	}

	public static function getRouterActionByKey(Instance $admin, string $key, Parameters $parameters = null, bool $initialize = true): ?RouterAction
	{
		return match ($key) {
			/*'profile' => RouterFacade::tab(md5('/admin/user'), 'fa fa-user-circle', $admin->translate('Meine Daten'), MyProfileComponent::class)
				->source(static::class, 'profile')
				->active(),*/
			'profile' => RouterFacade::modal($admin->translate('Meine Daten'), MyProfileComponent::class, initialize: $initialize)
				->source(static::class, 'profile')
				->size(Size::EXTRA_LARGE),
			default => null
		};
	}
}