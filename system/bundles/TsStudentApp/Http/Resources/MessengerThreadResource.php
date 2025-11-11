<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use TsStudentApp\AppInterface;
use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Service\MessengerService;
use TsStudentApp\Service\Util;

/**
 * @mixin AbstractThread
 */
class MessengerThreadResource extends JsonResource
{
	public function toArray($request)
	{
		$lastMessage = $this->getLastMessage();

		return [
			'token' => $this->getToken(),
			'image' => (!empty($this->getImage())) ? Util::imageUrl('messenger_thread', $this->getToken()) : null,
			'icon' => $this->getIcon(),
			'name' => $this->getName(),
			'last_message' => ($lastMessage) ? $lastMessage->toArray() : null,
			'unseen' => $this->getNumberOfUnreadMessages()
		];
	}
}