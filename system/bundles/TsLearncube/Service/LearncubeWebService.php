<?php

namespace TsLearncube\Service;

use Ext_Thebing_School;
use TsLearncube\Handler\ExternalApp;
use GuzzleHttp\Client;

abstract class LearncubeWebService {

	protected Client $client;

	protected Ext_Thebing_School $school;

	protected string $token;

	public function __construct(Ext_Thebing_School $school) {

		$this->school = $school;

		$uri = \System::d(ExternalApp::KEY_URL.'_'.$school->id);

		$this->client = new Client(['base_uri' => $uri]);

		$tokenRequest = $this::getTokenRequest(
			$this->client,
			\System::d(ExternalApp::KEY_PUBLIC_API.'_'.$school->id),
			\System::d(ExternalApp::KEY_PRIVATE_API.'_'.$school->id)
		);

		$this->token = json_decode($tokenRequest->getBody()->getContents())->token;
	}

	/**
	 * @return Ext_Thebing_School
	 */
	public function getSchool(): Ext_Thebing_School
	{
		return $this->school;
	}

	/**
	 * @return Client
	 */
	public function getClient(): Client
	{
		return $this->client;
	}

	/**
	 * @return string
	 */
	public function getToken(): string
	{
		return $this->token;
	}

	/**
	 * @return array
	 */
	public function getAuthArray() :array
	{
		return ['Authorization' => 'Bearer '.$this->token];
	}

	public static function getCacheKey(int $userReference): string
	{
		return 'token__'.$userReference;
	}

	public static function getTokenRequest(Client $client, string $publicKey, string $privateKey) {

		return $client->post('api/virtual-classroom/get-api-token/', [
			'json' =>
				[
					'api_public_key' => $publicKey,
					'api_private_key' => $privateKey,
				],
		]);
	}

}
