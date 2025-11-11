<?php

namespace Communication\Notifications\Channels\Messages;

use Communication\Interfaces\Notifications\LoggableMessage;
use Core\Notifications\Attachment;
use Core\Notifications\Recipient;
use Core\Traits\Notification;
use Core\Traits\WithAdditionalData;

class AppMessage implements LoggableMessage
{
	use Notification\WithModelsRelations,
		Notification\WithAttachments,
		WithAdditionalData;

	private array $from = [];
	private array $recipients = [];
	private array $content = [];

	private ?string $imageUrl = null;

	private string $sendMode = \Ext_TC_Communication::SEND_MODE_AUTOMATIC;

	private ?\Ext_TC_Communication_Message $log = null;

	public function from(\User $user, string $senderName = null): static
	{
		$this->from = [$user, $senderName];
		return $this;
	}

	public function to(Recipient $address): static
	{
		$this->recipients[] = $address;
		return $this;
	}

	public function content(string $content): static
	{
		$this->content = [$content, 'text'];
		return $this;
	}

	public function image(string $imageUrl): static
	{
		$this->imageUrl = $imageUrl;
		return $this;
	}

	public function sendMode(string $sendMode): static
	{
		$this->sendMode = $sendMode;
		return $this;
	}

	public function log(\Ext_TC_Communication_Message $log): static
	{
		$this->log = $log;
		return $this;
	}

	public function getFrom(): array
	{
		return $this->from;
	}

	public function getTo(): array
	{
		return $this->recipients;
	}

	public function getCc(): array
	{
		return [];
	}

	public function getBcc(): array
	{
		return [];
	}

	public function getSubject(): ?string
	{
		return null;
	}

	public function getContent(): array
	{
		return $this->content;
	}

	public function getImageUrl(): ?string
	{
		return $this->imageUrl;
	}

	public function getLog(): ?\Ext_TC_Communication_Message
	{
		return $this->log;
	}

	public function getSendMode(): string
	{
		return $this->sendMode;

	}

	public function getFlags(): array
	{
		return [];
	}

	public function toArray(): ?array
	{
		if (empty($this->recipients)) {
			return null;
		}

		$data = [];
		$data['send_mode'] = $this->sendMode;

		if ($this->log) {
			$data['log_id'] = $this->log->id;
		}

		if (!empty($from)) {
			[$user, $senderName] = $this->from;
			$data['from'] = [
				sprintf('%s::%d', $user::class, $user->id),
				$senderName
			];
		}

		$data['to'] = array_map(fn (Recipient $recipient) => $recipient->toArray(), $this->recipients);

		if (!empty($this->content)) {
			$data['content'] = $this->content[0];
		}

		if (!empty($this->attachments)) {
			$data['attachments'] = array_map(fn (Attachment $attachment) => [
				'file' => $attachment->getFileName(),
				'path' => $attachment->getFilePath(),
				'model' => $attachment->getEntity() ? sprintf('%s::%d', $attachment->getEntity()::class, $attachment->getEntity()->id) : null,
			], $this->attachments);
		}

		if (!empty($this->relations)) {
			$data['relations'] = array_map(fn ($relation) => [sprintf('%s::%d', $relation::class, $relation->id)], $this->relations);
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

		if (!empty($payload['to'])) {
			foreach ($payload['to'] as $recipient) {
				$message->to(Recipient::fromArray($recipient));
			}
		}

		if (!empty($payload['from'])) {
			$message->from(
				$resolveInstance($payload['from'][0]),
				$payload['from'][1] ?? null
			);
		}

		if (!empty($payload['content'])) {
			$message->content($payload['content']);
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
}
