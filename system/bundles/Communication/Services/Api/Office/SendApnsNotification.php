<?php

namespace Communication\Services\Api\Office;

class SendApnsNotification extends \Licence\Service\Office\Api\AbstractObject
{
	public function __construct(
		readonly private string $identifier,
		readonly private string $token,
		readonly private string $title,
		readonly private string $message,
		readonly private array $additional,
		readonly private bool $production,
		readonly private string $image
	) {}

	public function getUrl()
	{
		return '/customer/api/push/apns';
	}

	public function getRequestMethod()
	{
		return 'POST';
	}

	public function prepareRequest(\Licence\Service\Office\Api\Request $request)
	{
		$request->add('identifier', $this->identifier);
		$request->add('token', $this->token);
		$request->add('title', $this->title);
		$request->add('message', $this->message);
		$request->add('additional', $this->additional);
		$request->add('production', $this->production);
		$request->add('image', $this->image);
	}
}
