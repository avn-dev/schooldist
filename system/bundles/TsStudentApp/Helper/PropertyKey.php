<?php

namespace TsStudentApp\Helper;

use Core\Helper\BundleConfig;

readonly class PropertyKey
{
	public function __construct(private BundleConfig $bundleConfig) {}
	public function match(string $key): ?array
	{
		$allPropertyKeys = collect($this->bundleConfig->get('properties'))->keys();

		if ($allPropertyKeys->contains($key)) {
			return [$key, []];
		}

		$withPlaceholders = $allPropertyKeys
			->filter(fn ($key) => str_contains($key, '{'));

		foreach ($withPlaceholders as $rawKey) {

			[$regex, $placeholders] = $this->buildRegex($rawKey);

			$matches = [];
			preg_match_all("/".$regex."/", $key, $matches);

			if (!empty($matches[1])) {
				return [$rawKey, array_combine($placeholders, $matches[1])];
			}
		}

		return [$key, []];
	}

	public function generate(string $key, array $placeholderValues = []): string
	{
		if (empty($config = $this->bundleConfig->get('properties.'.$key))) {
			throw new \RuntimeException(sprintf('Unknown property "%s"', $key));
		}

		if (!str_contains($key, '{')) {
			return $key;
		}

		$placeholderValues = array_merge($config['default'] ?? [], $placeholderValues);
		$placeholders = $this->getPlaceholders($key);

		if (!empty($missing = array_diff($placeholders, array_keys($placeholderValues)))) {
			throw new \RuntimeException(sprintf('Missing placeholder values for key %s [missing: %s]', $key, implode(', ', $missing)));
		}

		foreach ($placeholderValues as $placeholder => $value) {
			$key = str_replace('{'.$placeholder.'}', $value, $key);
		}

		return $key;
	}

	public function getPlaceholders(string $key): array {

		$matches = [];
		preg_match_all("/\{([^}]+)\}/", $key, $matches);

		if (!empty($matches[1])) {
			return $matches[1];
		}

		return [];
	}

	private function buildRegex(string $key): array
	{
		$placeholders = $this->getPlaceholders($key);

		$regex = preg_quote($key);

		foreach ($placeholders as $placeholder) {
			$regex = str_replace('\{'.$placeholder.'\}', '([^}]+)', $regex);
		}

		return [$regex, $placeholders];
	}

}