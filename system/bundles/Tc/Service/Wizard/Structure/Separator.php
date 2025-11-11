<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Support\Arr;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure;

class Separator extends AbstractElement
{
	public function isVisitable(bool $checkAgain = false): bool
	{
		return false;
	}

	/**
	 * Generiert ein Step-Objekt anhand eines Arrays
	 *
	 * @param array $config
	 * @param string $key
	 * @return Step
	 */
	public static function fromArray(Wizard $wizard, array $config, string $key = ''): static
	{
		/* @var Separator $separator */
		$separator = app()->make(self::class)
			->key($key)
			->config(Arr::except($config, ['class','elements']));

		Structure::runConditions($wizard, $separator);

		return $separator;
	}
}