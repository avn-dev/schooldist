<?php

namespace OpenBanking\Providers\finAPI\Api\Models;
use Illuminate\Contracts\Support\Arrayable;

class Account implements Arrayable
{
	private string $iban = '';

	public function __construct(
		private readonly int $bankConnectionId,
		private readonly int $id,
		string $iban = '',
		private readonly string $accountName = '',
	) {
		$this->iban = $this->formatIban($iban);
	}

	public function getBankConnectionId(): int
	{
		return $this->bankConnectionId;
	}

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
	public function getIban(): string
	{
		return $this->iban;
	}

	/**
	 * @return string
	 */
	public function getAccountName(): string
	{
		return $this->accountName;
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'iban' => $this->iban,
			'account_name' => $this->accountName,
		];
	}

	private function formatIban(string $iban)
	{
		$iban = str_replace(' ', '', $iban);
		return wordwrap($iban, 4, ' ', true);
	}
}