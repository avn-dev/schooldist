<?php

namespace TsTuition\Exception;

class SaveBlocksForWeekErrorException extends \RuntimeException
{
	public function __construct(private readonly int $week, private readonly array $errors)
	{
		parent::__construct('Save blocks for week '.$this->week.' failed with errors.');
	}

	public function getWeek(): int
	{
		return $this->week;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}
}