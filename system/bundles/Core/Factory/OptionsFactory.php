<?php

namespace Core\Factory;

use Core\Interfaces\Optionable;
use Illuminate\Support\Collection;

class OptionsFactory
{
	public static function build(Collection $options): Collection
	{
		return $options
			->mapWithKeys(fn (Optionable $option) => [$option->getOptionValue() => $option->getOptionText()]);
	}

	public static function buildJs(Collection $options): Collection
	{
		return $options
			->map(fn (Optionable $option) => ['value' => $option->getOptionValue(), 'text' => $option->getOptionText()])
			->values();
	}
}