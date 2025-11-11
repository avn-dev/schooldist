<?php

namespace Admin\Factory;

use Admin\Components;
use Admin\Dto\Component\Parameters;
use Admin\Enums\Size;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\Component\VueComponent;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Admin\Router;
use Admin\Traits\SystemButtons;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RouterAction
{
	use SystemButtons;

	public function __construct(
		private Container $app,
		private Instance $admin,
		private \Access_Backend $access
	) {}

	public function openUserBoard(bool $initialize = true): \Admin\Interfaces\RouterAction
	{
		return $this->slideOver(Components\UserBoardComponent::class, initialize: $initialize)
			->size(Size::MEDIUM);
	}

	public function openBookmarks(bool $initialize = true): \Admin\Interfaces\RouterAction
	{
		return $this->modal($this->admin->translate('Schnellauswahl'), Components\BookmarksComponent::KEY, initialize: $initialize)
			->size(Size::MEDIUM);
	}

	public function openSupport(bool $initialize = true): \Admin\Interfaces\RouterAction
	{
		return $this->slideOver(Components\SupportComponent::class, initialize: $initialize)
			->size(Size::MEDIUM);
	}

	public function openDashboard(): \Admin\Interfaces\RouterAction
	{
		return $this->openNavigationNode('admin.dashboard');
	}

	public function openSystemUpdate(): ?\Admin\Interfaces\RouterAction
	{
		return $this->openNavigationNode('admin.update');
	}

	public function openNavigationNode(string $key): ?\Admin\Interfaces\RouterAction
	{
		/* @var Components\NavigationComponent $navigation */
		$navigation = $this->admin->getComponent(Components\NavigationComponent::KEY);
		$node = $navigation->findNodeByKey($key, false);

		if (!$node) {
			throw new \RuntimeException(sprintf('Navigation node not found [%s]', $key));
		}

		return $node['action'];
	}

	public function openGui2Dialog(string $ymlName, string $action, array $selectedIds, array $vars = [], bool $initialize = true): ?\Admin\Interfaces\RouterAction
	{
		$content = $this->resolveContent(
			Components\Gui2DialogComponent::class,
			[
				'yml_file' => $ymlName,
				'action' => $action,
				'selected_ids' => $selectedIds,
				'vars' => $vars,
			],
			$initialize
		);

		return new Router\Action\OpenGui2Dialog($content);
	}

	public function openCommunication(
		Collection $models = null,
		string|array $application = null,
		array|string|null $access = ['core_communication', 'list'],
		array $additional = [],
		bool $initialize = true
	): \Admin\Interfaces\RouterAction
	{
		$parameters = [];

		if ($models && $models->isNotEmpty()) {
			$parameters['models'] = $models->map(fn ($model) => $model::class)->toArray();

			// Payload verringern falls es sich immer um dieselbe Modelklasse handelt
			if (count($unique = array_unique($parameters['models'])) === 1) {
				$parameters['models'] = $unique;
			}

			$parameters['ids'] = $models->map(fn ($model) => $model->id)->toArray();
		}

		if (!empty($application)) {
			$parameters['application'] = $application;
		}

		if (!empty($access)) {
			$parameters['access'] = $access;
		}

		if (!empty($additional)) {
			$parameters['additional'] = $additional;
		}

		// TODO eigentlich gehört das nach Core, aber generell gehört die komplette Kommunikation eher ins Framework
		return $this->modal($this->admin->translate('Kommunikation'), \Communication\Admin\Components\CommunicationComponent::KEY, $parameters, $initialize)
			->size(Size::EXTRA_LARGE)
			->outerClosable(false);
	}

	public function tab(string $id, string $icon, string|array $text, Router\Content|string $payload, array $parameters = []): Router\Action\OpenTab
	{
		return new Router\Action\OpenTab($id, $icon, $text, $this->resolveContent($payload, $parameters, false));
	}

	public function modal(string $text, Router\Content|string $payload, array $parameters = [], bool $initialize = true): Router\Action\OpenModal
	{
		return new Router\Action\OpenModal($text, $this->resolveContent($payload, $parameters, $initialize));
	}

	public function slideOver(Router\Content|string $payload, array $parameters = [], bool $initialize = true): Router\Action\OpenSlideOver
	{
		return new Router\Action\OpenSlideOver($this->resolveContent($payload, $parameters, $initialize));
	}

	public function visit(string $url): Router\Action\VisitPage
	{
		return new Router\Action\VisitPage($url);
	}

	public function resolveContent(Router\Content|string $payload, array $parameters = [], bool $initialize = true): Router\Content
	{
		if ($payload instanceof Router\Content) {
			return $payload;
		}

		$component = $this->admin->getComponent($payload, $parameters);

		if (!$component instanceof VueComponent) {
			throw new \RuntimeException('Component is no vue component [%s]', $component::class);
		}

		if (!$component->isAccessible($this->access)) {
			$component = $this->admin->getComponent(Components\AccessDeniedComponent::class);
			$apikey = $this->admin->getComponentBindingKey($component);
		} else {
			$apikey = $this->admin->getComponentBindingKey($payload, $parameters);
		}

		$vue = $component::getVueComponent($this->admin);

		$initialData = ($initialize) ? $component->init($this->app->make(Request::class), $this->admin) : null;

		$parameters = ($component instanceof Component\HasParameters)
			? $component->getParameterValues()
			: null;

		return Content::component($apikey, $vue->getName(), (array)$initialData?->getData(), $parameters, $initialize)
			->dateAsOf($initialData?->getDateAsOf())
			->l10n((array)$initialData?->getTranslations());
	}

	public function toStoreData(Instance $admin, \Admin\Interfaces\RouterAction $routerAction): ?array
	{
		if (!$routerAction instanceof StorableRouterAction) {
			return null;
		}

		if ($routerAction->hasSource()) {
			/* @var Component $component */
			[$source, $payload] = $routerAction->getSource();
		} else {
			$source = $routerAction::class;
			$payload = $routerAction->getPayload($admin);
		}

		return [
			'key' => $routerAction->getStorableKey(),
			'source' => $source,
			'payload' => $payload,
			'parameters' => Arr::wrap($routerAction->getStorableParameters($admin)?->toArray()),
		];
	}

	public function fromStoreData(Instance $admin, array $storeData, bool $initialize = true): ?\Admin\Interfaces\RouterAction
	{
		$routerAction = null;

		if (is_a($storeData['source'], \Admin\Interfaces\RouterAction::class, true)) {

			$routerAction = call_user_func_array([$storeData['source'], 'fromPayload'], [$admin, $storeData['payload']]);

		} else if (
			is_string($storeData['payload']) &&
			is_a($storeData['source'], Component\RouterActionSource::class, true)
		) {
			$parameters = (!empty($storeData['parameters'])) ? new Parameters($storeData['parameters']) : null;

			$routerAction = $storeData['source']::getRouterActionByKey($admin, $storeData['payload'], $parameters, $initialize);

			if ($routerAction instanceof StorableRouterAction) {
				$routerAction->source($storeData['source'], $storeData['payload']);
			}
		}

		if (!$routerAction) {
			$admin->getLogger('Store')->error('Cannot restore router action', ['payload' => $storeData]);
		}

		return $routerAction;
	}
}