<?php

namespace Tc\Interfaces\Wizard;

interface Log
{
	public function getId(): mixed;

	public function getIteration(): int;

	public function getUser(): ?\User;

	public function getStepKey(): string;

	public function getQueryParameters(): array;

	public function setQueryParameter(string $key, $value): static;

	public function finish(): static;

	public function isFinished(): bool;
}