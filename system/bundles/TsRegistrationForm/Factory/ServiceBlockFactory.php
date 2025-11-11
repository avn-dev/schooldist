<?php

namespace TsRegistrationForm\Factory;

use Illuminate\Support\Collection;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Generator\ServiceBlock;

class ServiceBlockFactory
{
	const CLASSES = [
		'courses' => ServiceBlock\CourseBlock::class
	];

	public function make(string $type, CombinationGenerator $combination, Collection $data): ServiceBlock\ServiceBlockInterface
	{
		if (!isset(self::CLASSES[$type])) {
			throw new \InvalidArgumentException('Unknown type '.$type);
		}

		$class = self::CLASSES[$type];
		return new $class($combination, $data);
	}

	/**
	 * @return ServiceBlock\ServiceBlockInterface[]
	 */
	public function makeAll(CombinationGenerator $combination, Collection $data): array
	{
		return array_map(fn(string $class) => new $class($combination, $data), self::CLASSES);
	}
}