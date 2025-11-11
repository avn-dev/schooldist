<?php

namespace Communication\Dto\Message;

use Core\Traits\WithIcon;
use Illuminate\Support\Arr;

class Flag
{
	use WithIcon;

	private array $recipientKeys = [];

	public function __construct(
		private string $key,
		private string $name,
	) {}

	public function recipients(array|string $keys): static
	{
		$this->recipientKeys = array_unique(Arr::wrap($keys));
		return $this;
	}

	public function getKey() : string
	{
		return $this->key;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRecipientKeys(): array
	{
		return $this->recipientKeys;
	}

	public function toArray(): array
	{
		return [
			'key' => $this->getKey(),
			'name' => $this->getName(),
			'icon' => $this->getIcon() ?? 'fa fa-flag',
		];
	}
}