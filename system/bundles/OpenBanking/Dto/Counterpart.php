<?php

namespace OpenBanking\Dto;

use OpenBanking\Interfaces\Counterpart as BaseCounterpart;

class Counterpart implements BaseCounterpart
{
	public function __construct(
		public readonly string $name,
		public readonly array $data
	) {}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function toArray()
	{
		return [
			'name' => $this->name,
			'data' => $this->data
		];
	}

	public static function fromArray(array $payload): static
	{
		return new self(
			$payload['name'],
			$payload['data']
		);
	}
}