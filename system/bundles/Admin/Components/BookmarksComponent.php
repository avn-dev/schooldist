<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\Parameters;
use Admin\Dto\Component\VueComponentDto;
use Admin\Exceptions\TooManyBookmarks;
use Admin\Facades\Router;
use Admin\Facades\Router as RouterFacade;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BookmarksComponent implements Component\VueComponent, Component\RouterActionSource
{
	const KEY = 'bookmarks';

	const MAX_ENTRIES = 12;

	private \User $user;

	public function __construct(
		protected \Access_Backend $access,
		protected Instance $admin
	) {
		$this->user = $this->access->getUser();
	}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Bookmarks', '@Admin/layouts/admin/Bookmarks.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$nodes = $this->getMainNodes();

		// Die normalen Bookmarks werden über den User-Init-Request geladen
		return (new InitialData(['nodes' => array_values($nodes)]))
			->l10n([
				'bookmarks.my' => $admin->translate('Meine Schnellauswahl'),
				'bookmarks.my.empty' => $admin->translate('Sie haben noch keine Schnellauswahl hinzugefügt'),
			]);
	}

	public function delete(Request $request)
	{
		$this->deleteBookmark($request->input('id'));

		return response()->json([
			'success' => true
		]);
	}

	protected function getMainNodes(): array
	{
		$nodes = [
			'user_board' => ['text' => [$this->admin->translate('Benutzer')], 'icon' => 'fa fa-user-circle', 'action' => RouterFacade::openUserBoard(false)]
		];

		if ($this->admin->hasSupport()) {
			$nodes['support'] = ['text' => [$this->admin->translate('Support')], 'icon' => 'fa fa-life-ring', 'action' => RouterFacade::openSupport(false)];
		}

		return $nodes;
	}

	public function deleteBookmark(StorableRouterAction|string $payload): bool
	{
		if ($payload instanceof StorableRouterAction) {
			$payload = $payload->getStorableKey();
		}

		$bookmarks = $this->getBookmarks();
		$newBookmarks = array_filter($bookmarks, fn ($loop) => $loop['key'] !== $payload);

		$changed = count($bookmarks) !== count($newBookmarks);

		if ($changed) {
			$this->saveBookmarks($newBookmarks, false);
		}

		return $changed;
	}

	public function addBookmark(StorableRouterAction $routerAction): bool
	{
		$bookmarks = (array)$this->user->getMeta('admin.bookmarks');

		$newBookmarks = $bookmarks;
		if (!empty($payload = Router::toStoreData($this->admin, $routerAction))) {
			$newBookmarks[] = ['key' => $routerAction->getStorableKey(), 'payload' => $payload];
		}

		$changed = count($bookmarks) !== count($newBookmarks);

		if ($changed) {
			$this->saveBookmarks($newBookmarks);
		}

		return $changed;
	}

	public function toggleBookmark(StorableRouterAction $routerAction): bool
	{
		$bookmarks = $this->getBookmarks();

		$existing = Arr::first($bookmarks, fn ($loop) => $loop['key'] === $routerAction->getStorableKey());

		$changed = true;
		$added = false;

		if ($existing) {
			$bookmarks = array_filter($bookmarks, fn ($loop) => $loop['key'] !== $routerAction->getStorableKey());
		} else if (!empty($payload =  Router::toStoreData($this->admin, $routerAction))) {
			$bookmarks[] = ['key' => $routerAction->getStorableKey(), 'payload' => $payload];
			$added = true;
		} else {
			$changed = false;
		}

		if ($changed) {
			$this->saveBookmarks($bookmarks);
		}

		return $added;
	}

	/**
	 * @param bool $resolve
	 * @return StorableRouterAction[]
	 */
	public function getBookmarks(bool $resolve = false): array
	{
		$bookmarks = (array)$this->user->getMeta('admin.bookmarks');

		if ($resolve) {
			$bookmarks = array_map(fn ($bookmark) => Router::fromStoreData($this->admin, $bookmark['payload'], false), $bookmarks);
			return array_filter($bookmarks, fn ($bookmark) => $bookmark instanceof StorableRouterAction);
		}

		return $bookmarks;
	}

	private function saveBookmarks(array $bookmarks, bool $checkLimit = true)
	{
		if (!empty($bookmarks)) {
			$bookmarks = array_values($bookmarks);

			if ($checkLimit && count($bookmarks) > self::MAX_ENTRIES) {
				throw new TooManyBookmarks('Too many bookmarks');
			}
		} else {
			$bookmarks = null;
		}

		$this->user->setMeta('admin.bookmarks', $bookmarks)->save();
	}

	public static function getRouterActionByKey(Instance $admin, string $key, Parameters $parameters = null, bool $initialize = true): ?RouterAction
	{
		/* @var BookmarksComponent $bookmarks */
		$bookmarks = $admin->getComponent(self::KEY);
		$mainNodes = $bookmarks->getMainNodes();

		if (isset($mainNodes[$key])) {
			return $mainNodes[$key]['action'];
		}

		return null;
	}
}