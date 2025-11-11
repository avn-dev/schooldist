<?php

namespace Admin\Search;

use Admin\Dto\Component\Search;
use Admin\Dto\Component\Search\SearchResult;
use Admin\Instance;
use Admin\Interfaces\Component\InteractsWithSearch;

class Navigation implements InteractsWithSearch
{
	const KEY = 'navigation';

	private ?\Admin\Components\NavigationComponent $component;

	public function __construct(
		private Instance $admin
	) {
		$this->component = $this->admin->getComponent(\Admin\Components\NavigationComponent::KEY);
	}
	public function getLabel(): string
	{
		return $this->admin->translate('Navigation');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function search(string $query, int $limit): Search\SearchResult
	{
		$nodes = $this->component->buildJsStructure();

		$matches = collect($nodes)
			->map(fn ($node) => [$node, $this->match($node['text'], $query)])
			->filter(fn ($matching) => is_array($matching[1]));

		//dd($query, $matches);

		$result = new SearchResult();
		foreach ($matches as [$node, $matchingParts]) {
			if (!$node['action']) {
				$childNodes = array_filter($nodes, fn (array $loop) => $loop['action'] && str_starts_with($loop['id'], $node['id']));
				foreach ($childNodes as $childNode) {
					$result->addRow($childNode['id'], $childNode['action'], $matchingParts);
				}
			} else {
				$result->addRow($node['id'], $node['action'], $matchingParts);
			}
		}

		return $result;
	}

	private function match(string $text, string $query): ?array
	{
		$originalText = explode(' ', $text);
		$textNormalized = explode(' ', $this->normalize($text));
		$queryNormalized = explode(' ', $this->normalize($query));

		$matches = [];
		foreach ($textNormalized as $position => $textPart) {
			foreach ($queryNormalized as $queryPart) {
				if (($queryPos = mb_strpos($textPart, $queryPart)) !== false) {
					$hightlight = substr($originalText[$position], $queryPos, strlen($queryPart));
					$unnormalizedChars = $this->getUnnormalizedChars($hightlight);
					if (!empty($unnormalizedChars)) {
						// Falls Sonderzeichen enthalten sind die bei der Suche nicht berücksichtigt wurde, müssen diese
						// auch hier berücksichtigt werden
						$hightlight = substr($originalText[$position], $queryPos, strlen($queryPart) + count($unnormalizedChars));
					}
					$matches[$position] = $hightlight;
				} else if ($this->levenshtein($textPart, $queryPart)) {
					$matches[$position] = $originalText[$position];
				}
			}
		}

		if (count($matches) >= count($queryNormalized)) {
			return $this->mergeSequentialKeys($matches);
		}

		return null;
	}

	private function levenshtein(string $text, string $query, float $threshold = 0.7): bool
	{
		$levenshtein = new \Oefenweb\DamerauLevenshtein\DamerauLevenshtein($text, $query);
		$similarity = $levenshtein->getSimilarity();

		if ($similarity > 0) {
			$maxlen = max(strlen($text), strlen($query));
			if ($maxlen > 0) {
				$similarity = 1 - ($similarity / $maxlen);
				return $similarity >= $threshold;
			}
		}

		return false;
	}

	private function normalize(string $text): string
	{
		$normalized = preg_replace('/[^a-zA-ZäöüÄÖÜß\s]/u', '', $text);
		return mb_strtolower($normalized);
	}

	private function getUnnormalizedChars(string $text)
	{
		preg_match_all('/[^a-zA-ZäöüÄÖÜß\s]/u', $text, $matches);
		return $matches[0] ?? [];
	}

	private function mergeSequentialKeys(array $arr): array {
		if (empty($arr)) {
			return [];
		}

		ksort($arr);

		$result = [];
		$prevKey = null;
		foreach ($arr as $key => $value) {
			if ($prevKey !== null && $key === $prevKey + 1) {
				// fortlaufend → an bestehende Gruppe anhängen
				$result[$prevKey] .= ' ' . $value;
			} else {
				// neue Gruppe beginnen
				$result[] = $value;
			}
			$prevKey = $key;
		}

		return array_values($result);
	}

}