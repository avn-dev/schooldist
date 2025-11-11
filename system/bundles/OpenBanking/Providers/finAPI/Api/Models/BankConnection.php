<?php

namespace OpenBanking\Providers\finAPI\Api\Models;

class BankConnection
{
	public function __construct(
		private readonly int $id,
		private readonly string $status,
		private readonly array $accountIds,
	) { }

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	public function isReady(): bool
	{
		return strtoupper($this->getStatus()) === 'READY';
	}

	/**
	 * @return array
	 */
	public function getAccountIds(): array
	{
		return $this->accountIds;
	}

}