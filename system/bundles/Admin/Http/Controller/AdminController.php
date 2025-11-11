<?php

namespace Admin\Http\Controller;

use Admin\Components\UserBoardComponent;
use Admin\Dto\Component\InitialData;
use Admin\Dto\TenantDto;
use Admin\Facades\Admin;
use Admin\Facades\Router;
use Admin\Facades\InterfaceResponse;
use Admin\Http\Resources\UserNotificationResource;
use Admin\Instance;
use Admin\Traits\SystemButtons;
use Admin\Interfaces\Component;
use Core\Enums\AlertLevel;
use Core\Facade\Cache;
use Core\Notifications\Channels\DatabaseChannel;
use Core\Notifications\ToastrNotification;
use Core\Service\RoutingService;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class AdminController extends Controller
{
	use SystemButtons;

	public function index(Instance $admin)
	{
		return InterfaceResponse::render('Admin', [
				'title' => sprintf('%s - %s', \System::d('project_name'), $admin->translate('Administration'))
			])
			//->action(\Ts\Admin\Router::openTraveller(3683, 3232, initialize: false))
			//->action(Router::openCommunication(collect([\Ext_TS_Inquiry::getInstance(2996), \Ext_TS_Inquiry::getInstance(3232)]), application: 'booking', initialize: false))
			//->action(Router::openCommunication(collect([\Ext_TS_Inquiry::getInstance(2996)]), initialize: false))
			//->action(Router::openCommunication(initialize: false))
			//->gui2Dialog('Ts_tuition_colors', 'edit', [1], initialize: false)
			//->action(Router::openUserBoard())
			//->action(Router::openSupport())
			//->action(UserBoardComponent::getRouterActionByKey($admin, 'profile'))
			//->tab('fidelo', 'fa fa-star', 'Fidelo Software GmbH', \Admin\Factory\Content::iframe('https://fidelo.com'))
			->l10n([
				'interface' => [
					'search' => $admin->translate('Suche'),
					'bookmarks' => $admin->translate('Schnellauswahl'),
				]
			]);
	}

	public function switchTenant(Request $request, Instance $admin, RoutingService $router)
	{
		$selection = $request->input('tenant');

		$tenants = $admin->getTenants();

		if (!$tenants) {
			return response('Bad request', 400);
		}

		$tenantDto = $tenants->getOptions()->filter(fn (TenantDto $dto) => $dto->getKey() === $selection)->first();

		if (!$tenantDto) {
			return response('Bad request', 400);
		}

		$response = $tenants->switchTenant($tenantDto);

		if (is_bool($response)) {
			if ($response) {
				$response = \Admin\Facades\InterfaceResponse::visit($router->generateUrl('Admin.index'));
			} else {
				$notification = (new ToastrNotification($admin->translate('Die Aktion konnte nicht durchgeführt werden.')))->persist();
				$response = \Admin\Facades\InterfaceResponse::notification($notification);
			}
		}

		return $response;
	}

	public function componentAction(Container $container, \Access_Backend $access, Component $component, string $action)
	{
		if (!method_exists($component, $action)) {
			throw new \RuntimeException(sprintf('Component action does not exist [%s::%s]', $component::class, $action));
		}

		if (!$component->isAccessible($access)) {
			return response('Access denied', 403);
		}

		$response = $container->call([$component, $action]);

		if (!$response) {
			return response('', 204);
		}

		if (\Ext_Gui2_Index_Stack::executeCache()) {
			\Ext_Gui2_Index_Stack::save();
		}

		if ($response instanceof InitialData) {
			return InterfaceResponse::json($response->getData())
				->dateAsOf($response->getDateAsOf())
				->l10n($response->getTranslations());
		}

		return $response;
	}

	public function openBookmarks()
	{
		return InterfaceResponse::action(Router::openBookmarks());
	}

	public function openUserBoard()
	{
		return InterfaceResponse::action(Router::openUserBoard());
	}

	public function openSupport()
	{
		return InterfaceResponse::action(Router::openSupport());
	}

	public function ping(Request $request, \Access_Backend $access, Instance $admin)
	{
		// Recht manuell prüfen, da der Controller auch ohne Login funktionieren muss
		if(
			$access->checkValidAccess() &&
			$access->hasRight('control')
		) {
			return $this->pingLoggedIn($request, $access);
		}

		return $this->pingLoggedOut($request, $access, $admin, );
	}

	private function pingLoggedIn(Request $request, \Access_Backend $access)
	{
		// Session-Kram skippen, damit es asynchron laufen kann
		if($request->exists('skip_session')) {
			return response('No content', 204);
		}

		// Session anfassen, damit sie nicht stirbt
		\Core\Handler\SessionHandler::getInstance();

		// TODO Middleware?
		\Factory::executeStatic('Util', 'getAndSetTimezone');
		\Factory::executeStatic('System', 'setLocale');

		$notifications = $access->getUser()->unreadNotifications()->limit(500)->get()
			->map(fn ($notification) => new UserNotificationResource($notification, $access, Admin::instance()));

		// Globale checks
		$globalChecks = (new \GlobalChecks())->checkForCheck();

		if(class_exists('\Gui2\Controller\Middleware\GuiPing')) {
			(new \Gui2\Controller\Middleware\GuiPing())->handle($request);
		}

		return response()
			->json([
				'messages' => $notifications,
				'global_checks' => (bool)$globalChecks
			]);
	}

	private function pingLoggedOut(Request $request, \Access_Backend $access, Instance $admin)
	{
		// TODO das sollte irgendwann auch mal zurückgesetzt werden
		$notificationSeen = Cache::get('access_logout_seen_'.$access->getAccessUser(), true);

		if ($notificationSeen !== null) {
			return response('No content', 204);
		}

		$logoutReason = Cache::get('access_logout_'.$access->getAccessUser());

		if($logoutReason !== null) {
			if($logoutReason['reason'] === 'update') {
				$updateUser = \User::getInstance($logoutReason['current_user']);
				$message = \L10n::t('Benutzer "%s" hat ein Systemupdate gestartet. Sie wurden automatisch ausgeloggt. Wenn das Systemupdate komplett ausgeführt ist, können Sie sich wieder einloggen.', 'System » Update');
				$message = sprintf($message, $updateUser->getName());
			} else {
				$message = $admin->translate('Sie wurden von einem anderen Benutzer ausgeloggt. Bitte starten Sie das System neu!').'<br>';
				$message .= '<br>'.$admin->translate('IP').': '.$logoutReason['ip'];
				$message .= '<br>'.$admin->translate('Browser').': '.$logoutReason['browser']['agent'].' '.$logoutReason['browser']['version'];
				$message .= '<br>'.$admin->translate('OS').': '.$logoutReason['browser']['os'];
			}
		} else {
			$message = $admin->translate('Ihre Sitzung ist abgelaufen. Bitte starten Sie das System neu!');
		}

		$notificationObject = (new ToastrNotification($message, AlertLevel::DANGER))->persist();

		if (
			$access instanceof \Access_Backend &&
			$access->getUser()->exist()
		) {
			$access->getUser()->notifyNow($notificationObject, ['database']);
			$notifications = $access->getUser()->notifications()->latest()->limit(1)->get();
		} else {
			$notification = DatabaseChannel::toEntity($notificationObject);
			$notifications = collect([$notification]);
		}

		Cache::forever('access_logout_seen_'.$access->getAccessUser(), 1);

		return response()
			->json([
				'message_reset' => true,
				'messages' => $notifications->map(fn ($notification) => new UserNotificationResource($notification, $access, Admin::instance())),
			]);
	}

	public function legacyRedirect(RoutingService $router)
	{
		return redirect($router->generateUrl('Admin.index'), 301);
	}
}
