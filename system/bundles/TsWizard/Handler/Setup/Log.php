<?php

namespace TsWizard\Handler\Setup;

use Tc\Service\Wizard\Iteration;
use Tc\Service\Wizard\Structure\Step;

class Log implements \Tc\Interfaces\Wizard\Log
{
	public function __construct(private array $logData) {}

	public function getId(): mixed
	{
		return $this->logData['id'];
	}

	public function getIteration(): int
	{
		return (int)$this->logData['iteration'];
	}

	public function getStepKey(): string
	{
		return $this->logData['step'];
	}

	public function getUser(): ?\User
	{
		return \Ext_Thebing_User::getInstance($this->logData['user_id']);
	}

	public function getQueryParameters(): array
	{
		return $this->logData['query_parameters'];
	}

	public function setQueryParameter(string $key, $value): static
	{
		$this->logData['query_parameters'][$key] = $value;
		return $this;
	}

	public function finish(): static
	{
		$this->logData['finish'] = true;
		return $this;
	}

	public function isFinished(): bool
	{
		return $this->logData['finish'] ?? false;
	}

	public function toArray(): array
	{
		return $this->logData;
	}

	public static function generate(Iteration $iteration, Step $step): static
	{
		$logData = [
			'id' => \Util::generateRandomString(10),
			'iteration' => $iteration->getIterationCount(),
			'step' => $step->getKey(),
			'user_id' => $iteration->getUser()->getId(),
			'query_parameters' => $step->getQueryParameters()
		];
		return new static($logData);
	}

}