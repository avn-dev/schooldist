<?php

namespace OpenBanking\Interfaces;

use OpenBanking\Dto\Amount;
use OpenBanking\Enums\Transaction\Direction;
use Illuminate\Contracts\Support\Arrayable;
use Carbon\Carbon;

interface Transaction extends Arrayable
{
	public function getProviderKey(): string;
	public function getId(): mixed;
	public function getDirection(): Direction;
	public function getDate(): Carbon;
	public function getAmount(): Amount;
	public function getPurpose(): string;
	public function getCounterPart(): ?Counterpart;
	public static function fromArray(array $payload): static;
}