<?php

namespace AdminTools\Dto;

use Carbon\Carbon;

class Log
{
	private ?string $key = null;

	public function __construct(
		private Carbon $date,
		private string $logger,
		private string $level,
		private string $message,
		private ?string $context = null,
	) {}

	public function key(string $key): self
	{
		$this->key = $key;
		return $this;
	}

	public function getKey(): ?string
	{
		return $this->key;
	}

	public function getDate(): Carbon {
		return $this->date;
	}

	public function getLogger(): string {
		return $this->logger;
	}

	public function getLevel(): string {
		return $this->level;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function getContext(): ?string {
		return $this->context;
	}

	public function getParsedContext(): array {
		if (!empty($this->context)) {
			return (array)json_decode($this->context, true);
		}
		return [];
	}

	public function isInfo(): bool {
		return $this->level === 'INFO';
	}

	public static function fromString(string $line): self
	{
		// https://github.com/ddtraceweb/monolog-parser/pull/6/commits/2d74e556b921fedc152fab83f2adf2de7f8ae630#diff-324d39f9965bb48cbf803e2def70b900d984279bc89ee208786058d7237e92a4R24
		preg_match('/\[(?P<date>.*)\]\s(?P<logger>[\w-]+)\.(?P<level>\w+):\s(?P<message>[^\[\{]+)\s(?P<context>[\[\{].*[\]\}])\s(?P<extra>[\[\{].*[\]\}])/', $line, $matches);

		// [ in message funktioniert nicht
		if (empty($matches)) {
			$matches['message'] = 'COULD NOT PARSE LINE: '.$line;
		}

		return new self(
			Carbon::parse($matches['date']),
			$matches['logger'],
			$matches['level'],
			$matches['message'],
			$matches['context'] !== '[]' ? $matches['context'] : null
		);
	}
}