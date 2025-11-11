<?php

namespace Admin\Http\Controller;

use Admin\Components\BookmarksComponent;
use Admin\Enums\ColorScheme;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Http\Resources\StoredRouterActionResource;
use Admin\Http\Resources\UserNotificationResource;
use Admin\Instance;
use Admin\Interfaces\Notification\AdminButton;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Core\Entity\System\UserNotification;
use Core\Enums\AlertLevel;
use Core\Factory\ValidatorFactory;
use Core\Notifications\ToastrNotification;
use Core\Service\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class UserController extends Controller
{
	public function load(\Access_Backend $access, Instance $admin)
	{
		$user = $access->getUser();

		$notifications = $user->notifications()->limit(100)->get();

		$numberOfUnseen = $notifications->filter(fn(UserNotification $notification) => !$notification->isRead())->count();

		$max = 30;
		if ($numberOfUnseen > $max) {
			$cut = $numberOfUnseen;
		} else {
			$cut = $max;
		}

		$bookmarks = collect((array)$user->getMeta('admin.bookmarks'))
			->map(fn ($bookmark) => Router::fromStoreData($admin, $bookmark['payload'], false))
			->filter(fn ($bookmark) => $bookmark instanceof StorableRouterAction)
			->values();

		return InterfaceResponse::json([
			'notifications_total' => $notifications->count(),
			'notifications' => $notifications->slice(0, $cut)->map(fn ($notification) => new UserNotificationResource($notification, $access, $admin)),
			'bookmarks' => $bookmarks->map(fn ($bookmark) => new StoredRouterActionResource($bookmark, $admin))->toArray(),
			'tasks' => []
		]);
	}

	public function readNotification(\Access_Backend $access, $id)
	{
		$user = $access->getUser();

		// TODO ->update(['read_at' => date('Y-m-d H:i:s')])
		$notification = UserNotification::query()
			->where('notifiable', $user->id)
			->where('id', (int)$id)
			->whereNull('read_at')
			->first();

		if (!$notification) {
			return response()
				->json(['success' => false]);
		}

		$notification->read_at = date('Y-m-d H:i:s');
		$notification->save();

		NotificationService::getLogger('DatabaseChannel')->info('Notification read', ['user' => $user->id, 'notification' => $notification->id, 'data' => $notification->getDataArray()]);

		return response()
			->json(['success' => true]);
	}

	public function deleteNotifications(\Access_Backend $access, Request $request)
	{
		$user = $access->getUser();

		// TODO ->delete()
		$query = UserNotification::query()
			->where('notifiable', $user->id);

		if ($request->has('id')) {
			$query->whereIn('id', Arr::wrap($request->input('id', [])));
		}

		$query->get()->each(function ($notification) use ($user) {
			NotificationService::getLogger('DatabaseChannel')->info('Notification deleted', ['user' => $user->id, 'notification' => $notification->id, 'created' => $notification->created, 'read_at' => $notification->read_at, 'data' => $notification->getDataArray()]);
			$notification->delete();
		});

		return response()
			->json(['success' => true]);
	}

	public function notificationAction(Instance $admin, \Access_Backend $access, Request $request, int $id, string $button)
	{
		$notification = UserNotification::query()
			->where('notifiable', $access->getUser()->id)
			->where('id', (int)$id)
			->first();

		if (!$notification) {
			return response()->json(['success' => false]);
		}

		$data = $notification->getDataArray();

		$button = Arr::first((array)$data['buttons'], fn ($payload) => $payload['key'] === $button);

		if (!$button || !is_a($button['class'], AdminButton::class, true)) {
			return response()->json(['success' => false]);
		}

		/* @var AdminButton $button */
		$button = call_user_func_array([$button['class'], 'fromArray'], [$admin, $button['payload']]);

		try {

			if (!$button || !$button->isAccessible($access)) {
				return InterfaceResponse::json(['success' => false])
					->notification(
						new ToastrNotification($admin->translate('Sie haben keine Berechtigung'), AlertLevel::DANGER)
					);
			}

			$action = $button->action();

			if ($action) {
				return InterfaceResponse::json(['success' => true])
					->action($action);
			}

		} catch (\Throwable $e) {
			$admin->getLogger('Notifications')->error('Notification button failed', ['button' => $button::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
		}

		return InterfaceResponse::json(['success' => false])
			->notification(
				new ToastrNotification($admin->translate('Beim Ausführen dieser Aktion is etwas schiefgelaufen.'), AlertLevel::DANGER)
			);
	}

	public function saveColorScheme(\Access_Backend $access, Request $request)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
				'scheme' => ['required', new Enum(ColorScheme::class)]
			]);

		if ($validator->fails()) {
			return response()
				->json(['errors' => $validator->messages()->all()]);
		}

		$user = $access->getUser();
		$user->setMeta('admin.color_scheme', $request->input('scheme'));
		$user->save();

		return response()
			->json(['success' => true]);
	}

	public function addBookmark(\Access_Backend $access, Request $request, Instance $admin)
	{
		$action = $request->input('action');

		$routerAction = Router::fromStoreData($admin, $action, false);

		if (!$routerAction instanceof StorableRouterAction) {
			return response()
				->json(['success' => false]);
		}

		/* @var BookmarksComponent $bookmarks */
		$bookmarks = $admin->getComponent(BookmarksComponent::KEY);
		$success = $bookmarks->addBookmark($routerAction);

		$response = ['success' => $success];
		if ($success) {
			$response['bookmark'] = new StoredRouterActionResource($routerAction, $admin);
		}

		return response()->json($response);
	}

	public function toggleBookmark(\Access_Backend $access, Request $request, Instance $admin)
	{
		$action = $request->input('action');

		$routerAction = Router::fromStoreData($admin, $action, false);

		if (!$routerAction instanceof StorableRouterAction) {
			return response()
				->json(['success' => false]);
		}

		/* @var BookmarksComponent $bookmarks */
		$bookmarks = $admin->getComponent(BookmarksComponent::KEY);
		$added = $bookmarks->toggleBookmark($routerAction);

		return response()
			->json(['success' => true, 'active' => $added, 'bookmark' => new StoredRouterActionResource($routerAction, $admin)]);
	}

	public function deleteBookmark(Request $request, Instance $admin)
	{
		if (empty($key = $request->input('key'))) {
			return response()
				->json(['success' => false]);
		}

		/* @var BookmarksComponent $bookmarks */
		$bookmarks = $admin->getComponent(BookmarksComponent::KEY);

		$success = $bookmarks->deleteBookmark($key);

		return response()
			->json(['success' => $success]);
	}
}