<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\Search\SearchResult;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Http\Resources\StoredRouterActionResource;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Illuminate\Http\Request;

class SearchComponent implements Component
{
	const KEY = 'search';
	const MAX_RESULTS = 30;

	public function __construct(
		private readonly \Access_Backend $access,
		private readonly Instance $admin
	) {}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(): InitialData
	{
		$quickActions = [
			['text' => [$this->admin->translate('Schnellauswahl öffnen')], 'icon' => 'fa fa-bookmark', 'action' => Router::openBookmarks(false)],
		];

		if ($this->admin->hasSupport()) {
			$quickActions[] = ['text' => [$this->admin->translate('Brauchen Sie Hilfe? Schauen Sie sich im Supportbereich um.')], 'icon' => 'fa fa-life-ring', 'action' => Router::openSupport(false)];
		}

		$recentSearch = collect((array)$this->access->getUser()->getMeta('admin.recent_search'))
			->map(fn ($search) => Router::fromStoreData($this->admin, $search['payload'], false))
			->filter(fn ($search) => $search instanceof StorableRouterAction)
			->map(fn ($routerAction) => new StoredRouterActionResource($routerAction, $this->admin))
			->reverse()
			->values();

		$search = $this->admin->getSearch()
			->filter(fn (Component\InteractsWithSearch $search) => $search->isAccessible($this->access))
			->map(fn (Component\InteractsWithSearch $search, $key) => ['key' => $key, 'text' => $search->getLabel()])
			->values();

		return (new InitialData([
				'recent' => $recentSearch,
				'quick_actions' => $quickActions,
				'instances' => $search,
				'selected_instance' => $search->first()['key']
			]))
			->l10n([
				'search.placeholder' => \L10N::t('Suche', 'Framework'),
				'search.recent' => \L10N::t('Suchverlauf', 'Framework'),
				'search.recent.empty' => \L10N::t('Noch keine Suche durchgeführt', 'Framework'),
				'search.result.empty' => \L10N::t('Leider gibt es keine Treffer für Ihre Suche', 'Framework'),
			]);
	}

	public function search(Request $request, \Access_Backend $access)
	{
		$query = $request->input('query');
		$instance = $request->input('instance');

		$result = collect();

		if (!empty($query)) {
			$search = $this->admin->getSearchInstance($instance);

			if ($search->isAccessible($access)) {
				/* @var SearchResult $searchResult */
				$searchResult = $search->search($query, \System::d('admin.max_search_results', self::MAX_RESULTS));

				if (!$searchResult->isEmpty()) {
					$result = collect(array_slice($searchResult->getRows(), 0, self::MAX_RESULTS));
				}
			}
		}

		return InterfaceResponse::json([
			'hits' => $result->count(),
			'rows' => $result
				->map(fn ($row) => [
					'action' => new StoredRouterActionResource($row[0], $this->admin),
					'matches' => $row[1]
				])
				->values()
		]);
	}

	public function store(Request $request)
	{
		if (empty($action = $request->input('action', ''))) {
			return response()
				->json(['success' => false], 400);
		}

		$routerAction = Router::fromStoreData($this->admin, $action);

		if (
			$routerAction instanceof StorableRouterAction &&
			!empty($payload =  Router::toStoreData($this->admin, $routerAction))
		) {
			/* @var \User $user */
			$user = $this->access->getUser();

			$recentSearch = collect($user->getMeta('admin.recent_search', []))
				->mapWithKeys(fn ($data) => [$data['key'] => $data])
				->forget($routerAction->getStorableKey())
				->push(['key' => $routerAction->getStorableKey(), 'payload' => $payload]);

			$user->setMeta('admin.recent_search', $recentSearch->slice(-4)->values()->toArray());
			$user->save();

			return response()
				->json(['success' => true]);
		}

		return response()->json(['success' => false]);
	}
}