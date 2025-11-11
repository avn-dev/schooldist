<?php

namespace OpenBanking\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface Counterpart extends Arrayable
{
	public function getName(): string;
	public function getData(): array;
	public static function fromArray(array $payload): static;
}