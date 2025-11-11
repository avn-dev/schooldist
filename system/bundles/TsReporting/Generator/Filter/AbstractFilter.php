<?php

namespace TsReporting\Generator\Filter;

use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\TranslateTrait;

abstract class AbstractFilter
{
	use TranslateTrait;

	protected mixed $value;

	abstract public function getTitle(): string;

	abstract public function getType(): string;

	abstract public function build(QueryBuilder $builder);

	public function __construct()
	{
		$this->value = $this->getDefault();
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [];
	}

	public function getDefault(): mixed
	{
		return null;
	}

	public function isRequired(): bool
	{
		return false;
	}

	public function getDependencies(): array
	{
		return [];
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function setValue(mixed $value): void
	{
		$this->value = $value;
	}

	public function hasValue(): bool
	{
		return $this->getValue() !== null;
	}
}