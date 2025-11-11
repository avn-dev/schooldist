<?php

namespace Api\Handler\ParallelProcessing;

use Core\Factory\ValidatorFactory;
use Core\Handler\ParallelProcessing\TypeHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Webhook extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Webhook');
	}

	public function execute(array $data, $debug = false)
	{
		$url = $data['url'];
		$payload = $data['payload'];

		if (empty($url) || empty($payload)) {
			throw new \RuntimeException(\L10N::t('Webhook is empty'));
		}

		$validator = (new ValidatorFactory())->make(['url' => $url], ['url' => 'url:http,https']);

		if ($validator->fails()) {
			throw new \RuntimeException('Invalid webhook url');
		}

		Arr::set($payload, 'webhook.id', 'WBHK-'.\Util::generateRandomString(10));

		$logger = \Log::getLogger('api', 'Webhook');
		$logger->info('Sending webhook', ['url' => $url, 'payload' => $payload]);

		$response = Http::post($url, $payload);

		if (!$response->successful()) {
			$logger->error('Webhook failed', ['url' => $url, 'payload' => $payload, 'status' => $response->status(), 'response' => $response->reason()]);
			throw new \RuntimeException('Webhook failed: '.$response->reason());
		}

		return true;
	}

}