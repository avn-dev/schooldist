<?php

namespace AdminTools\Http\Middleware;

use Admin\Http\Resources\UserResource;
use Admin\Traits\WithColorScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
	use WithColorScheme;

	/**
	 * The root template that's loaded on the first page visit.
	 *
	 * @see https://inertiajs.com/server-side-setup#root-template
	 * @var string
	 */
	protected $rootView = 'app';

	/**
	 * Determines the current asset version.
	 *
	 * @see https://inertiajs.com/asset-versioning
	 * @param  \Illuminate\Http\Request  $request
	 * @return string|null
	 */
	public function version(Request $request): ?string
	{
		return parent::version($request);
	}

	/**
	 * Defines the props that are shared by default.
	 *
	 * @see https://inertiajs.com/shared-data
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function share(Request $request): array
	{
		$user = \Access_Backend::getInstance()->getUser();

		$config = (new \Core\Helper\Bundle())->readBundleFile('AdminTools');

		foreach ($config['menu'] as &$item) {
			$item = $this->prepareItem($request, $item);
		}

		$title = Arr::first($config['menu'], fn ($node) => $node['active'])['text'];

		return array_merge(parent::share($request), [
			'interface' => [
				'color_scheme' => $this->getUserColorScheme($user), // Systemeinstellung ignorieren
				'title' => sprintf('%s - %s', $title, \Util::getHost()),
				'navigation' => ['nodes' => $config['menu']],
				'user' => (new UserResource($user))->toArray($request),
			]
		]);
	}

	private function prepareItem(Request $request, array $item)
	{
		if ($item['submenu']) {
			foreach ($item['submenu'] as &$subItem) {
				$subItem = $this->prepareItem($request, $subItem);
			}
			return $item;
		}

		$item['active'] = $request->is(ltrim($item['url'], '/'));

		return $item;
	}
}