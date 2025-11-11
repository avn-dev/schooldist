<?php

namespace OpenBanking\Providers\finAPI\Api\Models;

use Carbon\Carbon;
use OpenBanking\Dto\Amount;
use OpenBanking\Dto\Counterpart;
use OpenBanking\Enums\Transaction\Direction;
use OpenBanking\Interfaces\Transaction as BaseTransaction;

class Transaction implements BaseTransaction
{
	public function __construct(
		private readonly int $id,
		private readonly string $accountId,
		private readonly Direction $direction,
		private readonly Carbon $date,
		private readonly Amount $amount,
		private readonly string $purpose,
		private readonly ?Counterpart $counterpart = null,
	) {}

	public function getProviderKey(): string
	{
		return 'finAPI';
	}

	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getAccountId(): string
	{
		return $this->accountId;
	}

	public function getDirection(): Direction
	{
		return $this->direction;
	}

	public function getDate(): Carbon
	{
		return $this->date;
	}

	public function getAmount(): Amount
	{
		return $this->amount;
	}

	public function getPurpose(): string
	{
		return $this->purpose;
	}

	public function getCounterPart(): ?Counterpart
	{
		return $this->counterpart;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'account_id' => $this->accountId,
			'direction' => $this->direction->value,
			'date' => $this->date->getTimestamp(),
			'amount' => $this->amount->getAmount(),
			'currency' => $this->amount->getCurrency()->iso4217,
			'purpose' => $this->purpose,
			'counterpart' => $this->counterpart?->toArray()
		];
	}

	public static function fromArray(array $payload): static
	{
		return new self(
			$payload['id'],
			$payload['account_id'],
			Direction::from($payload['direction']),
			Carbon::now()->setTimestamp($payload['date']),
			new Amount($payload['amount'], \Ext_TC_Currency::getInstance($payload['currency'])),
			$payload['purpose'],
			(!empty($payload['counterpart'])) ? Counterpart::fromArray($payload['counterpart']) : null
		);
	}
}