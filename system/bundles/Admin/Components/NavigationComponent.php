<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\Parameters;
use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Factory\Content;
use Admin\Http\Resources\RouterActionResource;
use Admin\Instance;
use Admin\Interfaces;
use Admin\Interfaces\RouterAction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NavigationComponent implements Interfaces\Component, Interfaces\Component\RouterActionSource
{
	const KEY = 'navigation';

	const NODE_KEY_SEPARATOR = '-';

	const DEFAULT_ICON = 'fa fa-angle-right';

	private \Admin\Helper\Navigation $navigation;

	private static array $cache = [];

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function __construct(
		private Instance $admin,
		\Access_Backend|null $access,
		private readonly Request $request
	) {
		$this->navigation = \Factory::getObject(\Admin\Helper\Navigation::class, [$access]);
	}

	public function init(): InitialData
	{
		return (new InitialData([
				//'layout' => 'extended',
				'layout' => 'basic',
				'nodes' => RouterActionResource::resolvePayloadInstances(
					// Ebenen > 0 werden bei nachgeladen
					$this->buildJsStructure(0),
					$this->admin,
					$this->request
				)
			]))
			->l10n([
				'navigation.node.loading_failed' => $this->admin->translate('Laden der Navigation fehlgeschlagen.')
			]);
	}

	public function load(Request $request)
	{
		$id = $request->input('id');

		if (empty($id)) {
			throw new \RuntimeException('Missing node id parameter');
		}

		$nodes = $this->buildJsStructure();

		$childs = array_filter($nodes, fn (array $node) => $node['parent'] && $node['parent'] === $id);

		return InterfaceResponse::json(['nodes' => array_values($childs)]);
	}

	public function findNodeByKey(string $key, bool $encoded = true, bool $withOriginalNodes = false): ?array
	{
		if (!$encoded) {
			$key = md5($key);
		}

		if (!empty($cache = $this->getFromCache($key, $withOriginalNodes))) {
			return $cache;
		}

		$structure = $this->buildJsStructure(withOriginalNodes: $withOriginalNodes);

		$node = Arr::first($structure, fn ($node) => $node['id'] === $key || str_ends_with($node['id'], self::NODE_KEY_SEPARATOR.$key));

		return $node;
	}

	public function findNodeUrlByKey(string $key, bool $encoded = true)
	{
		$node = $this->findNodeByKey($key, $encoded, true);

		if ($node) {
			$url = $node['original']['url'] ?? $node['original'][1];
			return $url;
		}

		return null;
	}

	public static function getRouterActionByKey(Instance $admin, string $key, Parameters $parameters = null, bool $initialize = true): ?RouterAction
	{
		/* @var NavigationComponent $navigation */
		$navigation = $admin->getComponent(self::KEY);

		$node = $navigation->findNodeByKey($key, true);

		if ($node) {
			return $node['action'];
		}

		return null;
	}

	/**
	 * @param int|null $depth
	 * @return array
	 */
	public function buildJsStructure(int $depth = null, bool $withOriginalNodes = false): array
	{
		$structure = $this->getBaseNodes();

		$nodes = [];
		foreach ($structure as $legacyNode) {
			$nodes = array_merge($nodes, $this->buildJsNodes(legacyNode: $legacyNode, withOriginalNodes: $withOriginalNodes, maxDepth: $depth));
		}

		return array_values($nodes);
	}

	/**
	 * TODO jede Node sollte eine eindeutig ID bekommen
	 * @param array $legacyNode
	 * @param array $parentNodes
	 * @param $level
	 * @return array[]
	 */
	private function buildJsNodes(array $legacyNode, array $parentNodes = [], $level = 0, bool $withOriginalNodes = false, int $maxDepth = null): array
	{
		$text = strip_tags($legacyNode['title'] ?? $legacyNode[0]);

		$buildId = function (string $nodeValue) use ($legacyNode, $parentNodes) {
			$nodeHash = md5($nodeValue);
			return (!empty($parentNodes)) ? Arr::last($parentNodes)['id'].self::NODE_KEY_SEPARATOR.$nodeHash : $nodeHash;
		};

		$type = $legacyNode['type'] ?? $legacyNode[6];
		$url = $legacyNode['url'] ?? $legacyNode[1];
		if (!$type && empty($legacyNode['childs']) && !empty($url)) {
			$type = 'url';
		}

		$name = $legacyNode['key'] ?? $legacyNode[5];
		if (empty($name)) {
			throw new \RuntimeException(sprintf('Missing unique node key [%s]', $text));
			// TODO es ist hier schlecht den Titel oder die URL zu nehmen
			$name = (!empty($url)) ? $url : $text;
		}

		$id = $buildId($name);

		if (empty($node = $this->getFromCache($id, $withOriginalNodes))) {

			$tabIcon = ($legacyNode['icon'] ? 'fa '. $legacyNode['icon'] : self::DEFAULT_ICON);

			$node = [];
			$node['id'] = $buildId($name);
			//$node['depth'] = $level;
			$node['text'] = $text;
			$node['icon'] = $tabIcon;
			$node['parent'] = (!empty($parentNodes)) ? Arr::last($parentNodes)['id'] : null;
			$node['active'] = false;

			if ($withOriginalNodes) {
				$node['original'] = $legacyNode;
			}

			if (!empty($url) && empty($legacyNode['childs'])) {

				$tabText = (!empty($parentNodes))
					? [...array_column($parentNodes, 'text'), $text]
					: $text;

				$content = match ($type) {
					'url', 'iframe' => Content::iframe($url),
					'html', 'view' => Content::html($url),
					// $url ist in dem Fall die Component-Klasse (siehe Dashboard)
					// initialize = false es macht keinen Sinn in der Navigation den Payload der Components mitzuliefern
					'component' => Router::resolveContent(payload: $url, initialize: false)
				};

				if (
					str_contains($tabIcon, self::DEFAULT_ICON) &&
					// Schauen ob es in dem Baum ein Icon gibt welches von dem Default-Icon abweicht
					!empty($tabChildIcon = Arr::first(Arr::pluck($parentNodes, 'icon'), fn ($icon) => !str_contains($icon, self::DEFAULT_ICON)))
				) {
					$tabIcon = $tabChildIcon;
				}

				$action = Router::tab($buildId($url), $tabIcon, $tabText, $content)
					->source(static::class, $node['id'])
					->active();

				//$action = Router::modal($text, $content)->size(Size::EXTRALARGE);
				//$action = Router::slideOver($content)->size(Size::EXTRALARGE);

				$node['action'] = $action;
			}

			$this->addToCache($node['id'], $node, $withOriginalNodes);
		}

		$nodes = [$node];
		if (
			!empty($legacyNode['childs']) &&
			($maxDepth === null || ($level + 1) <= $maxDepth)
		) {
			$parentNodes[] = $node;
			foreach ($legacyNode['childs'] as $legacyChildNode) {
				$nodes = array_merge($nodes, $this->buildJsNodes($legacyChildNode, $parentNodes, ($level + 1), $withOriginalNodes, $maxDepth));
			}
		}

		return $nodes;
	}

	private function addToCache(string $key, array $node, bool $withOriginalNodes = false): static
	{
		if ($withOriginalNodes) {
			$key = $key.'_original';
		}

		self::$cache[$key] = $node;

		return $this;
	}

	private function getFromCache(string $key, bool $withOriginalNodes = false): ?array
	{
		if ($withOriginalNodes) {
			$key = $key.'_original';
		}

		if (isset(self::$cache[$key])) {
			return self::$cache[$key];
		}

		return null;
	}

	private function getBaseNodes(): array
	{
		if (isset(self::$cache['helper_nodes'])) {
			return self::$cache['helper_nodes'];
		}

		self::$cache['helper_nodes'] = $this->navigation->get();

		return self::$cache['helper_nodes'];
	}
}