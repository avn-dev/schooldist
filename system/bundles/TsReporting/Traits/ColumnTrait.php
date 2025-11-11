<?php

namespace TsReporting\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TsReporting\Generator\Bases\AbstractBase;
use TsReporting\Generator\ValueHandler;

trait ColumnTrait
{
	protected string $id;

	protected AbstractBase $base;

	public function setId(string $id): void {
		$this->id = $id;
	}

	public function getId(): string {
		return $this->id;
	}

	public function setBase(AbstractBase $base)
	{
		$this->base = $base;
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		return $result;
	}

	public function getFormat(ValueHandler $values): array
	{
		return [];
	}

	public function setConfig(array|string $config = null): void
	{
		if ($config === null) {
			return;
		}

		if (is_string($config)) {
			$config = json_decode($config, true);
		}

		foreach ($config as $attribute => $value) {
			$attribute = Str::camel($attribute);
			if (property_exists($this, $attribute)) {
				$this->{$attribute} = $value;
			}
		}
	}

	public function getConfigOptions(): array
	{
		return [];
	}
}