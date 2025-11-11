<?php

namespace Communication\Notifications\Channels;

use Admin\Facades\Admin;
use Communication\Dto\ChannelConfig;
use Communication\Interfaces\CommunicationChannel;
use Communication\Traits\Channel\WithCommunication;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;

class NoticeChannel implements CommunicationChannel
{
	use WithCommunication;

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('NoticeChannel');
	}

	public function getCommunicationConfig(): ChannelConfig
	{
		$default = [
			'icon' => 'far fa-sticky-note',
			'text' => Admin::translate('Notiz', 'Communication'),
			'content_types' => [ChannelConfig::CONTENT_HTML],
			'actions' => [
				ChannelConfig::ACTION_DELETE => [],
				ChannelConfig::ACTION_FORWARD => [],
			]
		];

		return new ChannelConfig([
			...$default,
			...$this->config,
		]);
	}

	public function validateRoute($route): bool
	{
		return true;
	}

}