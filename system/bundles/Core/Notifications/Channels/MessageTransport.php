<?php

namespace Core\Notifications\Channels;

use Core\Interfaces\HumanReadable;

class MessageTransport
{
	private ?\WDBasic $log = null;
	private bool $draft = false;

	private ?int $queueId = null;

	public function __construct(
		private bool $success,
		private array $errors = []
	) {}

	public function success(bool $success, array $errors = []): static
	{
		$this->success = $success;
		$this->errors = $errors;
		return $this;
	}

	public function errors(array $errors): static
	{
		$this->errors = [...$this->errors, ...$errors];
		return $this;
	}

	public function log(\WDBasic $log, bool $draft = false): static
	{
		$this->log = $log;
		$this->draft = $draft;
		return $this;
	}

	public function queue(int $queueId): static
	{
		$this->queueId = $queueId;
		return $this;
	}

	public function successfully(): bool
	{
		return $this->success;
	}

	public function isDraft(): bool
	{
		return $this->draft;
	}

	public function isQueued(): bool
	{
		return $this->queueId !== null;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function getErrorMessages($l10n): array
	{
		return array_map(function ($error) use ($l10n) {
			if ($error instanceof HumanReadable) {
				return $error->getHumanReadableText($l10n);
			} else if ($error instanceof \Throwable) {
				return $error->getMessage();
			}
			return $error;
		}, $this->errors);
	}

	public function getLog(): ?\WDBasic
	{
		return $this->log;
	}

	public function getQueueId(): ?int
	{
		return $this->queueId;
	}
}