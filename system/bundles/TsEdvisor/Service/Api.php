<?php

namespace TsEdvisor\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TsEdvisor\Exceptions\ApiException;
use TsEdvisor\Handler\ExternalApp;

/**
 * @link https://stackoverflow.com/questions/56494526/can-i-use-guzzle-for-graphql-api-consumption
 *
 * @link https://docs.edvisor.io/
 * @link https://docs.edvisor.io/schema/
 */
class Api
{
	// revertStudentEnrollmentToPending
	const ENROLLMENT_STATUS_PENDING = 3;

	// acceptStudentEnrollment
	const ENROLLMENT_STATUS_ACCEPTED = 4;

	// declineStudentEnrollment
	const ENROLLMENT_STATUS_REJECTED = 5;

	// revertStudentEnrollmentToPending
	const ENROLLMENT_STATUS_PROCESSING = 8;

	const DURATION_TYPE_DAY = 2;

	const DURATION_TYPE_WEEK = 3;

	const DURATION_TYPE_MONTH = 4;

	private string $apiKey;

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	public function getSchoolCompanySchools(): array
	{
		$response = $this->createClient()->post('', [
			'json' => [
				'query' => '
					query {
						schoolCompany {
							schools {
								schoolId
								name
							}
						}
					}
				'
			]
		]);

		$result = json_decode($response->getBody(), true);

		return data_get($result, 'data.schoolCompany.schools', []);
	}

	/**
	 * Webhook anlegen
	 *
	 * Sollte das nicht funktionieren, wird eine Exception geworfen.
	 *
	 * @link https://docs.edvisor.io/schema/rootmutationtype.doc.html createWebhook
	 * @link https://docs.edvisor.io/schema/webhookinput.doc.html
	 * @throws ApiException
	 */
	public function createWebhook(): void
	{
		$config = (new \Core\Helper\Bundle())->readBundleFile('TsEdvisor', 'api');
		$url = \Core\Helper\Routing::generateUrl('TsEdvisor.webhook');

		$payload = [
			'query' => '
				mutation createWebhook ($input: WebhookInput!) {
					createWebhook(input: $input) {
						created
					}
				}
			',
			'variables' => [
				'input' => [
					'webhookUrl' => $url,
					'events' => $config['webhook_events']
				]
			]
		];

		$this->createClient()->post('', [
			'json' => $payload
		]);
	}

	/**
	 * @link https://docs.edvisor.io/schema/studentenrollmentreceived.doc.html
	 */
	public function queryEnrollment(int $enrollmentId)
	{
		$payload = [
			'query' => '
				query ($enrollmentId: Int!) { 
					studentEnrollmentReceived (studentEnrollmentId: $enrollmentId) {
						studentEnrollmentId
						studentEnrollmentStatusId
						schoolId
						created
						isDeletedBySchool
						student {
							studentId
							nationality {
								code
							}
							firstname
							lastname
							email
							phone
							address
							gender
							birthdate
							passportNumber
							visaTypeId
							visaType {
								codeName
							}
						}
						studentEnrollmentOfferingItems {
							__typename
							... on StudentEnrollmentOfferingCourseItem {
								durationAmount
								durationTypeId
								startDate
								courseSnapshot {
									name
								}
							}
							... on StudentEnrollmentOfferingAccommodationItem {
								durationAmount
								durationTypeId
								startDate
								accommodationSnapshot {
									name
								}
							}
							... on StudentEnrollmentOfferingServiceItem {
								durationAmount
								durationTypeId
								startDate
								serviceQuantity
								serviceSnapshot {
									name
								}
							}
						}
						agency {
							agencyId
							name
							phone
							address
							city
							postalCode
							email
							websiteUrl
							agencyCompany {
								agencyCompanyId
								name
							}
						}
					}
				}
			',
			'variables' => [
				'enrollmentId' => $enrollmentId
			]
		];

		$response = $this->createClient()->post('', [
			'json' => $payload
		]);

		$result = json_decode($response->getBody(), true);

		return $result['data']['studentEnrollmentReceived'];
	}

	public function acceptEnrollment(int $enrollmentId)
	{
		$payload = [
			'query' => '
				mutation acceptStudentEnrollment ($enrollmentId: Int!) {
					acceptStudentEnrollment(studentEnrollmentId: $enrollmentId) {
						studentEnrollmentStatusId
					}
				}
			',
			'variables' => [
				'enrollmentId' => $enrollmentId
			]
		];

		$this->createClient()->post('', [
			'json' => $payload
		]);
	}

	private function createClient(): Client
	{
		$handlerStack = HandlerStack::create();
		$handlerStack->push(function (callable $handler) {
			return function (RequestInterface $request, array $options) use ($handler) {
				$promise = $handler($request, $options);
				return $promise->then(
					function (ResponseInterface $response) use ($request) {
						// ClientException etc. zu ApiException
						if ($response->getStatusCode() !== 200) {
							$e = new ApiException(sprintf('Status %d: %s', $response->getStatusCode(), $response->getReasonPhrase()));
							$this->createLogger()->error('Request error: '.$e->getMessage(), ['body' => (string)$request->getBody()]);
							throw $e;
						}

						// Fehler von API sind auch 200, auch zu ApiException
						$result = json_decode($response->getBody(), true);
						if (!empty($result['errors'])) {
							$e = new ApiException('API Error: '. join('; ', array_column($result['errors'], 'message')));
							$this->createLogger()->error('Request error: '.$e->getMessage(), ['body' => (string)$request->getBody()]);
							throw $e;
						}

						return $response;
					}
				);
			};
		});

		return new \GuzzleHttp\Client([
			'base_uri' => 'https://api.edvisor.io/graphql',
			'handler' => $handlerStack,
			'headers' => [
				'Authorization' => 'Bearer '.$this->apiKey,
				'Content-Type' => 'application/json'
			],
		]);
	}

	public static function default(): self
	{
		$apiKey = \System::d(ExternalApp::CONFIG_API_KEY);

		if (empty($apiKey)) {
			throw new \RuntimeException('Missing API key for Edvisor');
		}

		return new self($apiKey);
	}

	public static function createLogger(): Logger {
		return \Log::getLogger('ts_edvisor');
	}
}