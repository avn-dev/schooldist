<?php

namespace Core\Interfaces\Events;

interface ReportErrorEvent
{
	public function getErrorTitle(): string;
	public function getErrorData(): array;
}