<?php

namespace TsStudentApp\Messenger;

use Communication\Enums\MessageStatus;
use DateTime;
use TsStudentApp\AppInterface;
use TsStudentApp\Messenger\Thread\AbstractThread;

class Message {

	private $thread;

	private $id;

	private $direction;

	private $date;

	private $message;

	private ?MessageStatus $status = null;

	private array $attachments = [];

	private AppInterface $appInterface;

	public function __construct(AppInterface $appInterface, AbstractThread $thread, string $id, string $direction, DateTime $date, string $message) {
		$this->thread = $thread;
		$this->id = $id;
		$this->direction = $direction;
		$this->date = $date;
		$this->message = strip_tags($message);
		$this->status(MessageStatus::NULL);
		$this->appInterface = $appInterface;
	}

	public function attachment($filename, $url, $icon = 'download-outline', /*$label = ''*/) {
		$this->attachments[] = [
			'icon' => $icon,
//			'label' => $label,
			'file_name' => $filename,
			'url' => $url
		];
		return $this;
	}

	public function status(MessageStatus $status): self {
		$this->status = $status;
		return $this;
	}

	public function getDate(): DateTime {
		return $this->date;
	}

	public function toArray() {
		return [
			'id' => $this->id,
			'thread' => $this->thread->getToken(),
			'type' => $this->direction,
			'date' => $this->date->getTimestamp() * 1000, // Deprecated App >= 3.0.0
			'date_formatted' => $this->appInterface->formatDate2($this->date, 'LLLL'),
			'text' => $this->message, // TODO Warum heißt der Key plötzlich anders?
			'status' => $this->status->value,
			'attachments' => $this->attachments
		];
	}

}
