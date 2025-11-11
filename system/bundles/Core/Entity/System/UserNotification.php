<?php

namespace Core\Entity\System;

/**
 * @property string $id;
 * @property string $type;
 * @property string $created;
 * @property string $read_at;
 * @property int notifiable;
 * @property string $data;
 */
class UserNotification extends \WDBasic
{
	protected $_sTable = 'system_user_notifications';

	public function setDataArray(array $data): static
	{
		$this->data = json_encode($data);
		return $this;
	}

	public function getDataArray(): array
	{
		if (empty($this->data)) return [];
		return json_decode($this->data, true);
	}

	public function isRead(): bool {
		return $this->read_at !== null;
	}

}
