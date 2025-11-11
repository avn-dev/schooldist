<?php

namespace TsActivities\Exceptions;

class AlreadyAllocatedException extends \RuntimeException {

	private bool $skipable = false;

	public function __construct(private string $type, private string $label, private string $errorCode = 'already_allocated') {
		parent::__construct('AlreadyAllocatedException: '.$type.' / '.$label);
	}

	public function getType(): string {
		return $this->type;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function getErrorCode(): string {
		return $this->errorCode;
	}

	public function skipable(): static {
		$this->skipable = true;
		return $this;
	}

	public function isSkipable(): bool {
		return $this->skipable;
	}

}
