<?php

namespace Admin\Traits\RouterAction;

use Admin\Facades\Router;
use Admin\Http\Resources\RouterActionResource;
use Admin\Instance;
use Admin\Interfaces\Component;
use Illuminate\Support\Arr;

trait Storeable
{
	protected ?array $source = null;

	protected ?string $storeableKey = null;

	private ?string $icon = null;

	private ?array $text = null;

	public function storeable(string $key, string $icon, string|array $text): static
	{
		$this->storeableKey = $key;
		$this->icon = $icon;
		$this->text = Arr::wrap($text);
		return $this;
	}

	public function getStoreableKey(): ?string
	{
		return $this->storeableKey;
	}

	public function isStoreable(): bool
	{
		return $this->storeableKey !== null;
	}

	public function getStoreablePayload(Instance $admin): array
	{
		return Router::toStoreData($admin, $this);
	}

	public function source(string $class, string $key): static
	{
		if (!is_a($class, Component\RouterActionSource::class, true)) {
			throw new \RuntimeException(sprintf('Please only use classes of "%s" as router action source.', Component\RouterActionSource::class));
		}

		$this->source = [$class, $key];
		#if (empty($this->storeableKey)) {
		#	$this->storeable($class.'{|}'.$key, $this->icon, $this->text);
		#}

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

	public function getStoreableText(): string|array
	{
		return Arr::wrap($this->text);
	}

}