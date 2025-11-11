<?php

namespace TsStudentApp\Messenger\Thread;

use Communication\Enums\MessageStatus;
use DateTime;
use Illuminate\Support\Collection;
use TsStudentApp\Messenger\Message;

abstract class AbstractThread {

	protected $token;

	protected $student;

	protected $entity;

	protected $inquiry;

	protected $threadConfig;

	public function __construct(string $token, \Ext_TS_Inquiry_Contact_Traveller $student, \WDBasic $entity, \Ext_TS_Inquiry $inquiry, array $threadConfig) {
		$this->token = $token;
		$this->student = $student;
		$this->entity = $entity;
		$this->inquiry = $inquiry;
		$this->threadConfig = $threadConfig;
	}

	public function getToken() {
		return $this->token;
	}

	public function getIcon() {
		return $this->threadConfig['icon'];
	}

	public function getInquiry(): \Ext_TS_Inquiry {
		return $this->inquiry;
	}

	public function getEntity(): \WDBasic {
		return $this->entity;
	}

	public function getLastContact(): ?DateTime {

		if(null !== $message = $this->getLastMessage()) {
			return $message->getDate();
		}

		return null;
	}

	public function getLastMessage():?Message {

		$messages = $this->getMessages(1);

		if($messages->isNotEmpty()) {
			return $messages->first();
		}

		return null;
	}

	abstract public function getName(): string;

	abstract public function getImage(): ?string;

	abstract public function canCommunicate(): bool;

	/**
	 * Liefert die letzten Nachrichten
	 *
	 * @param int $limit
	 * @param string|null $lastMessageId
	 * @param array|string[] $directions
	 * @return Collection|Message[]
	 * @throws \Exception
	 */
	abstract public function getMessages(int $limit, string $lastMessageId = null, array $directions = ['in', 'out']): Collection;

	abstract public function getNumberOfUnreadMessages(): int;

	abstract public function storeMessage(string $message, int $timestamp, string $direction, MessageStatus $status, string $subject = null): \Ext_TC_Communication_Message;

	abstract public function markMessageAsSeen($messageKey): bool;

}
