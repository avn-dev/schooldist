<?php

namespace Admin\Http;

use Admin\Enums\Size;
use Admin\Http\Resources\RouterActionResource;
use Admin\Http\Resources\UserNotificationResource;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\HasTranslations;
use Admin\Router;
use Admin\Router\Content;
use Admin\Traits\WithDateAsOf;
use Admin\Traits\WithTranslations;
use Admin\Factory\RouterAction as RouterActionFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class InterfaceResponse implements Responsable
{
	use WithTranslations,
		WithDateAsOf;

	protected Response|InertiaResponse|null $response = null;

	protected int $status = 200;

	protected array $actions = [];

	protected array $data = [];

	protected array $notifications = [];

	public function __construct(
		private readonly Container $container,
		private readonly Instance $admin,
		private readonly \Access_Backend $access,
		private readonly RouterActionFactory $router
	) {}

	public function status(int $status): static
	{
		$this->status = $status;
		return $this;
	}

	public function notification(Notification $notification, $notifiable = null): static
	{
		if (!$notifiable) {
			$notifiable = $this->access->getUser();
		}

		$this->notifications[] = [$notifiable, $notification];

		return $this;
	}

	public function modal(string $text, Content|VueComponent|string $content, array $parameters = [], Size $size = Size::MEDIUM): static
	{
		$action = $this->router->modal($text, $content, $parameters)
			->size($size);

		return $this->action($action);
	}

	public function tab(string $id, string $icon, string|array $text, Content|VueComponent|string $content, array $parameters = []): static
	{
		$action = $this->router->tab($id, $icon, $text, $content, $parameters)
			->active();

		return $this->action($action);
	}

	public function slideOver(Content|VueComponent|string $content, array $parameters = [], Size $size = Size::MEDIUM, bool $initialize = true): static
	{
		$existing = Arr::first($this->actions, fn ($action) => $action instanceof Router\Action\OpenSlideOver);

		if ($existing) {
			throw new \RuntimeException('Please use the slide-over menu only once');
		}

		$action = $this->router->slideOver($content, $parameters, $initialize)
			->size($size);

		return $this->action($action);
	}

	public function gui2Dialog(string $ymlName, string $action, array $selectedIds, array $vars = [], bool $initialize = true): static
	{
		$action = $this->router->openGui2Dialog($ymlName, $action, $selectedIds, $vars, $initialize);
		return $this->action($action);
	}

	public function action(RouterAction|array $payload): static
	{
		$this->actions += Arr::wrap($payload);
		return $this;
	}

	public function visit(string $url)
	{
		$action = $this->router->visit($url);

		return $this->action($action);
	}

	public function render(string $component, array|Arrayable $props = []): static
	{
		$this->response = Inertia::render($component, $props);
		return $this;
	}

	public function json(array|Arrayable $data, $status = 200): static
	{
		if ($data instanceof Arrayable) {
			$data = $data->toArray();
		}

		$this->data = array_merge($this->data, $data);

		if (!$this->response) {
			$this->response = new JsonResponse([], ($this->status !== 200) ? $this->status : $status);
		}

		return $this;
	}

	public function toResponse($request)
	{
		if ($this->response instanceof InertiaResponse) {

			InertiaResponse::macro('mergeProps', function (string $key, array $value) {
				$existing = Arr::get($this->props, $key);
				if ($existing) {
					$value = array_merge($value, $existing);
				}
				Arr::set($this->props, $key, $value);
			});

			$internal = $this->withInternalData($request);

			$this->response->mergeProps('interface._l10n', $internal['_l10n']);
			$this->response->mergeProps('interface._actions', $internal['_actions']);

			foreach (Arr::except($internal, ['_l10n', '_actions']) as $key => $value) {
				$this->response->with('interface.'.$key, $value);
			}

			return $this->response->toResponse($request);

		} else if (!$this->response) {
			$this->response = new JsonResponse(null, $this->status);
		}

		$this->response->withHeaders(['X-Admin-Response' => 1]);

		$data = $this->withInternalData($request, $this->data, 'data');

		$this->response->setData($data);

		return $this->response;
	}

	private function withInternalData(Request $request, array|Arrayable $props = [], string $wrap = null): array
	{
		if ($props instanceof Arrayable) {
			$props = $props->toArray();
		}

		$props = RouterActionResource::resolvePayloadInstances($props, $this->admin, $request);

		if ($wrap) {
			$props = [$wrap => $props];
		}

		$l10n = $this->getTranslations();
		$actions = [];
		foreach ($this->actions as $action) {
			if ($action instanceof HasTranslations) {
				$l10n = array_merge($l10n, $action->getTranslations());
			}
			$actions[] = (new RouterActionResource($action, $this->admin))->toArray($request);
		}

		$notifications = [];
		if (!empty($this->notifications)) {
			array_walk($this->notifications, fn ($payload) => $payload[0]->notify($payload[1]));

			$notifications = $this->access->getUser()->unreadNotifications()->get()
				->map(fn ($notification) => new UserNotificationResource($notification, $this->access, $this->admin))
				->toArray();
		}

		$dateAsOf = [];
		if ($this->dateAsOf) {
			$format = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);
			$dateAsOf['_date_as_of'] = $format->formatByValue($this->dateAsOf);
		}

		return [
			...$props,
			...['_l10n' => $l10n, '_actions' => $actions, '_notifications' => $notifications],
			...$dateAsOf,
		];
	}

}