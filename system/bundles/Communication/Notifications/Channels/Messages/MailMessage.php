<?php

namespace Communication\Notifications\Channels\Messages;

use Communication\Interfaces\Notifications\LoggableMessage;
use Core\Notifications\Attachment;
use Core\Notifications\Recipient;
use Core\Traits\Notification;
use Core\Traits\WithAdditionalData;

class MailMessage implements LoggableMessage
{
	use Notification\WithModelsRelations,
		Notification\WithAttachments,
		WithAdditionalData;

	private array $from = [];

	private array $recipients = [
		'to' => [],
		'cc' => [],
		'bcc' => [],
	];

	private ?string $subject = null;
	private array $content = [];
	private string $sendMode = \Ext_TC_Communication::SEND_MODE_AUTOMATIC;

	private ?\Ext_TC_Communication_Message $log = null;

	public function sendMode(string $sendMode): static
	{
		$this->sendMode = $sendMode;
		return $this;
	}

	public function from(\Ext_TC_Communication_EmailAccount $account, \User $user = null, string $senderName = null): static
	{
		$this->from = [$account, $user, $senderName];
		return $this;
	}

	public function to(Recipient $recipient): static
	{
		$this->recipients['to'][] = $recipient;
		return $this;
	}

	public function cc(Recipient $recipient): static
	{
		$this->recipients['cc'][] = $recipient;
		return $this;
	}

	public function bcc(Recipient $recipient): static
	{
		$this->recipients['bcc'][] = $recipient;
		return $this;
	}

	public function subject(string $subject): static
	{
		$this->subject = $subject;
		return $this;
	}

	public function content(string $content, $contentType = 'html'): static
	{
		$this->content = [$content, $contentType];
		return $this;
	}

	public function log(\Ext_TC_Communication_Message $log): static
	{
		$this->log = $log;
		return $this;
	}

	public function getFrom(): ?array
	{
		return $this->from;
	}

	public function getTo(): array
	{
		return $this->recipients['to'] ?? [];
	}

	public function getCc(): array
	{
		return $this->recipients['cc'] ?? [];
	}

	public function getBcc(): array
	{
		return $this->recipients['bcc'] ?? [];
	}

	public function getSubject(): ?string
	{
		return $this->subject;
	}

	public function getContent(): array
	{
		return $this->content;
	}

	public function getLog(): ?\Ext_TC_Communication_Message
	{
		return $this->log;
	}

	public function getSendMode(): string
	{
		return $this->sendMode;

	}

	public function toArray(): ?array
	{
		if (empty($this->recipients['to'])) {
			return null;
		}

		$data = [];
		$data['send_mode'] = $this->sendMode;

		if (!empty($this->from)) {
			[$account, $user, $senderName] = $this->from;
			$data['from'] = [
				sprintf('%s::%d', $account::class, $account->id),
				($user) ? sprintf('%s::%d', $user::class, $user->id) : null,
				$senderName
			];
		}

		$data['to'] = array_map(fn (Recipient $recipient) => $recipient->toArray(), $this->recipients['to']);

		if (!empty($this->recipients['cc'])) {
			$data['cc'] = array_map(fn (Recipient $recipient) => $recipient->toArray(), $this->recipients['cc']);
		}
		if (!empty($this->recipients['bcc'])) {
			$data['bcc'] = array_map(fn (Recipient $recipient) => $recipient->toArray(), $this->recipients['bcc']);
		}

		if (!empty($this->subject)) {
			$data['subject'] = $this->subject;
		}

		if (!empty($this->content)) {
			$data['content'] = $this->content;
		}

		if (!empty($this->attachments)) {
			$data['attachments'] = array_map(fn (Attachment $attachment) => [
				'file' => $attachment->getFileName(),
				'path' => $attachment->getFilePath(),
				'model' => $attachment->getEntity() ? sprintf('%s::%d', $attachment->getEntity()::class, $attachment->getEntity()->id) : null,
			], $this->attachments);
		}

		if (!empty($this->relations)) {
			$data['relations'] = array_map(fn (\WDBasic $relation) => sprintf('%s::%d', $relation::class, $relation->id), $this->relations);
		}

		if ($this->log) {
			$data['log'] = sprintf('%s::%d', $this->log::class, $this->log->id);
		}

		if (!empty($additional = $this->getAdditionalData())) {
			$data['additional'] = $additional;
		}

		return $data;
	}

	public static function fromArray(array $payload): static
	{
		$resolveInstance = function ($instance) {
			[$class, $id] = explode('::', $instance, 2);
			return \Factory::getInstance($class, $id);
		};

		$message = new static();

		if (!empty($payload['send_mode'])) {
			$message->sendMode($payload['send_mode']);
		}

		if (!empty($payload['log'])) {
			$message->log($resolveInstance($payload['log']));
		}

		if (!empty($payload['from'])) {
			$message->from(
				$resolveInstance($payload['from'][0]),
				$payload['from'][1] ? $resolveInstance($payload['from'][1]) : null,
				$payload['from'][2] ?? null
			);
		}

		foreach (['to', 'cc', 'bcc'] as $type) {
			foreach ($payload[$type] as $recipient) {
				$message->{$type}(Recipient::fromArray($recipient));
			}
		}

		if (!empty($payload['subject'])) {
			$message->subject($payload['subject']);
		}

		if (!empty($payload['content'])) {
			$message->content($payload['content'][0], $payload['content'][1] ?? 'html');
		}

		if (!empty($payload['attachments'])) {
			array_walk($payload['attachments'], fn ($attachment) => $message->attach(new Attachment(
				$attachment['path'],
				$attachment['file'],
				(!empty($attachment['model'])) ? $resolveInstance($attachment['model']) : null
			)));
		}

		if (!empty($payload['relations'])) {
			array_walk($payload['relations'], fn ($relation) => $message->relation($resolveInstance($relation)));
		}

		if (!empty($payload['additional'])) {
			$message->additional($payload['additional']);
		}

		return $message;
	}

	public function getFlags(): array
	{
		return [];
	}
}
