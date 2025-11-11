<?php

namespace Communication\Dto;

use Core\Interfaces\HasIcon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class ChannelConfig implements HasIcon, Arrayable
{
	const CONTENT_TEXT = 'text';
	const CONTENT_HTML = 'html';

	const ACTION_REPLY = 'reply';
	const ACTION_REPLY_ALL = 'reply_all';
	const ACTION_FORWARD = 'forward';
	const ACTION_RESEND = 'resend';
	const ACTION_ASSIGN = 'assign';
	const ACTION_DELETE = 'delete';
	const ACTION_OBSERVE = 'observe';

	const FIELD_TO = 'to';
	const FIELD_CC = 'cc';
	const FIELD_BCC = 'bcc';
	const FIELD_SUBJECT = 'subject';
	const FIELD_TEMPLATE = 'template';
	const FIELD_FLAGS = 'flags';
	const FIELD_ATTACHMENTS = 'attachments';

	private array $config = [
		'icon' => 'fas fa-envelope',
		'content_types' => [self::CONTENT_HTML, self::CONTENT_TEXT],
		'fields' => [
			self::FIELD_TO => ['allow_custom' => true, 'routes_selection' => true],
			self::FIELD_CC => ['allow_custom' => true, 'routes_selection' => true],
			self::FIELD_BCC => ['allow_custom' => true, 'routes_selection' => true],
			self::FIELD_TEMPLATE => [],
			self::FIELD_SUBJECT => ['reaches_recipient' => true],
			self::FIELD_FLAGS => [],
			self::FIELD_ATTACHMENTS => [],
		],
		'actions' => [
			self::ACTION_REPLY => ['history' => true],
			self::ACTION_REPLY_ALL => ['history' => true],
			self::ACTION_FORWARD => [],
			self::ACTION_RESEND => ['direction' => 'out'],
			self::ACTION_ASSIGN => ['direction' => 'in'],
			self::ACTION_DELETE => [],
			self::ACTION_OBSERVE => [],
		]
	];

	public function __construct(array $overwrite) {
		$this->config = [...$this->config , ...$overwrite];
	}

	public function icon(string $icon): static
	{
		$this->config['icon'] = $icon;
		return $this;
	}

	public function get(string $key, $default = null)
	{
		return Arr::get($this->config, $key, $default);
	}

	public function set(string $key, $value): static
	{
		Arr::set($this->config, $key, $value);
		return $this;
	}

	public function getIcon(): ?string
	{
		return $this->config['icon'];
	}

	public function getContentTypes(): array
	{
		return $this->config['content_types'];
	}

	public function getActions(): array
	{
		return $this->config['actions'] ?? [];
	}

	public function hasAttachments(): bool
	{
		return isset($this->config['fields'][self::FIELD_ATTACHMENTS]);
	}

	public function toArray()
	{
		return $this->config;
	}
}