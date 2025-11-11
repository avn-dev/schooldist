<?php

namespace TsGel;
use TcApi\Client\Traits\ShouldQueue;
use TsGel\Api\Operations\SendBooking;
use TsGel\Exceptions\AuthenticateException;
use TsGel\Exceptions\FailedException;
use TsGel\Handler\ExternalApp;
use TcApi\Client\Interfaces\Operation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;


class Api
{
	public function __construct(private string $server, private string $apiToken) {}

	public static function default(): static
	{
		return new self(ExternalApp::getServer(), ExternalApp::getApiToken());
	}

	public function sendBooking(\Ext_TS_Inquiry $inquiry, bool $force = false): ?Response
	{
		if (!SendBooking::checkInquiry($inquiry)) {
			return null;
		}

		return $this->request(new SendBooking($inquiry), $force);
	}

	public function request(Operation $operation, bool $force = false): ?Response
	{
		if (!$force && in_array(ShouldQueue::class, class_uses($operation))) {
			$operation->writeToQueue('ts-gel/api-request');
			return null;
		}

		$request = Http::baseUrl($this->server)
			->withHeaders(['Token' => $this->apiToken])
			->beforeSending(fn ($request) => self::getLogger()->info('Send request', ['operation' => $operation::class, 'data' => $request->data()]))
			->asJson();

		$response = $operation->send($request);

		if (!$response->successful()) {
			if ($response->unauthorized()) {
				self::getLogger()->error('Unauthorized');
				throw new AuthenticateException('Unauthorized', ['operation' => get_class($operation)]);
			} else {
				self::getLogger()->error('Request failed', ['operation' => get_class($operation), 'status' => $response->status(), 'message' => $response->reason()]);
				throw (new FailedException('Request failed'))->response($response);
			}
		}

		if (method_exists($operation, 'handleResponse')) {
			$operation->handleResponse($response);
		}

		return $response;
	}

	public static function getLogger() {
		return \Log::getLogger('api', 'gel');
	}

}