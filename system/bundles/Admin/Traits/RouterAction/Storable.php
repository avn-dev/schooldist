<?php

namespace Admin\Traits\RouterAction;

use Admin\Dto\Component\Parameters;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Component;
use Illuminate\Support\Arr;

trait Storable
{
	protected ?array $source = null;

	protected ?string $storableKey = null;

	private ?string $icon = null;

	private ?array $text = null;

	public function storable(string $key, string $icon, string|array $text): static
	{
		$this->storableKey = $key;
		$this->icon = $icon;
		$this->text = Arr::wrap($text);
		return $this;
	}

	public function getStorableKey(): ?string
	{
		return $this->storableKey;
	}

	public function isStorable(): bool
	{
		return $this->storableKey !== null;
	}

	public function getStorablePayload(Instance $admin): array
	{
		return Router::toStoreData($admin, $this);
	}

	public function source(string $class, string $key): static
	{
		if (!is_a($class, Component\RouterActionSource::class, true)) {
			throw new \RuntimeException(sprintf('Please only use classes of "%s" as router action source.', Component\RouterActionSource::class));
		}

		$this->source = [$class, $key];
		/*if (empty($this->storableKey)) {
			$this->storable($class.'{|}'.$key, $this->icon, $this->text);
		}*/

		return $this;
	}

	public function getSource(): ?array
	{
		return $this->source;
	}

	public function hasSource(): bool
	{
		return $this->source !== null;
	}

	public function getStorableIcon(): ?string
	{
		return $this->icon;
	}

	public function getStorableText(): string|array
	{
		return Arr::wrap($this->text);
	}

	public function getStorableParameters(Instance $admin): ?Parameters
	{
		return null;
	}
}