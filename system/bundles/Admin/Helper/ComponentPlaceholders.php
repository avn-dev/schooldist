<?php

namespace Admin\Helper;

use Illuminate\Support\Arr;

class ComponentPlaceholders
{
	public static function getPlaceholders(string $string): array
	{
		if (!str_contains($string, '{')) {
			return [];
		}

		preg_match_all('/\{(.*?)\}/', $string, $matches);

		if ($matches && $matches[1]) {
			return $matches[1];
		}

		return [];
	}

	public static function matchAgainst(string $string, array $values): ?array
	{
		if (in_array($string, $values)) {
			return [$string, []];
		}

		foreach ($values as $value) {

			$placeholders = self::getPlaceholders($value);

			if (!empty($placeholders)) {
				$tmp = str_replace(array_map(fn ($placeholder) => '{'.$placeholder.'}', $placeholders), '{||}', $value);
				$nonePlaceholderParts = array_filter(explode('{||}', $tmp), fn ($part) => !empty($part));

				$regex = '';
				foreach ($nonePlaceholderParts as $index => $part) {
					$regex .= preg_quote($part, '#').'(\S+)';
				}

				preg_match_all('/^'.$regex.'$/', $string, $matches);

				if (!empty($matches) && !empty($matches[1])) {
					$placeholderValues = Arr::flatten(Arr::except($matches, 0));
					return [$value, array_combine($placeholders, $placeholderValues)];
				}
			}
		}

		return null;
	}
}