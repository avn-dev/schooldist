<?php

namespace Core\Notifications;

use Core\Enums\AlertLevel;
use Core\Traits\WithAlertLevel;

class ToastrNotification extends SystemUserNotification
{
	use WithAlertLevel;

	private ?\User $sender = null;

	public function __construct(
		string $message,
		AlertLevel $alertLevel = AlertLevel::INFO,
		private int $timeout = 5000
	) {
		$this->alert($alertLevel);
		parent::__construct($message);
	}

	public function via(): array
	{
		return ['database'];
	}

	public function getAlertLevel(): AlertLevel
	{
		return $this->alertLevel;
	}

	/**
	 * Absender definieren
	 *
	 * @param \User $user
	 * @return $this
	 */
	public function sender(\User $user): self
	{
		$this->sender = $user;
		return $this;
	}

	/**
	 * Persistent, Notification muss weggeklickt werden
	 *
	 * @return $this
	 */
	public function persist(): self
	{
		$this->timeout = 0;
		return $this;
	}

	public function toArray(): array
	{
		$array = parent::toArray();
		$array['timeout'] = $this->timeout;

		if ($this->sender !== null) {
			$array['subject'] = implode(', ', [$this->sender->lastname, $this->sender->firstname]);
		}

		return $array;
	}

}
