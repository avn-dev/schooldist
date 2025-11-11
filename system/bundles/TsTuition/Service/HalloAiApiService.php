<?php

namespace TsTuition\Service;

use Illuminate\Support\Facades\Http;
use TcApi\Client\Interfaces\Operation;
use TsTuition\Handler\HalloAiApp;
use TsTuition\Operations\HalloAi\GetAssessment;
use TsTuition\Operations\HalloAi\GetAssessmentUrl;


class HalloAiApiService
{
	/**
	 * Gibt den Api Key
	 * @return string
	 */
	public static function getApiKey(): string
	{
		return HalloAiApp::getApiKey();
	}

	/**
	 * Gibt die Url für die Hallo.Ai Api
	 * @return string
	 */
	private static function getApiUrl(): string
	{
		return 'https://dev.hallo.ai/api';
	}

	/**
	 * Gibt die Url für Origin Header und Callbackurl
	 * @return string
	 */
	private static function getOriginUrl(): string
	{
		return 'https://'.\Util::getHost();
	}

	/**
	 * @param int $inquiryId
	 * @param string $courselanguageId
	 * @return array
	 * @throws \Exception
	 */
	public function getAssessmentUrlByInquiryId(int $inquiryId, string $courselanguageId): array
	{
		$placementtestResult = \Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($inquiryId, $courselanguageId);
		if (!$placementtestResult) {
			throw new \Exception('Placementtest nicht gefunden.');
		}
		return $this->getAssessmentUrl($placementtestResult);
	}

	/**
	 * Führt eine Operation für den Endpoint getAssessmentUrl aus und gibt das Ergebnis zurück
	 * @param \Ext_Thebing_Placementtests_Results $placementtestResult
	 * @return array
	 * @throws \Exception
	 */
	public function getAssessmentUrl(\Ext_Thebing_Placementtests_Results $placementtestResult): array
	{
		$courselanguage = \Ext_Thebing_Tuition_LevelGroup::getInstance($placementtestResult->courselanguage_id);
		if (empty($courselanguage) || empty($courselanguage->language_iso)) {
			throw new \Exception('Sprache konnte nicht ermittelt werden.');
		}
		$operation = $this->getGetAssessmentUrlOperation();
		$inquiry = \Ext_TS_Inquiry::getInstance($placementtestResult->inquiry_id);
		$contact = $inquiry->getCustomer();
		$operation->setEmail($contact->email);
		$operation->setFirstName($contact->firstname);
		$operation->setLastName($contact->lastname);
		$operation->setUserId($placementtestResult->id);
		$operation->setLanguage($courselanguage->language_iso);
		if ($contact->getLanguage()) {
			$operation->setInstructionLanguage($contact->getLanguage());
		}
		$operation->setExpirationTimestampInMillis((HalloAiApp::getLinkDuration() * (60*60*1000)) + (time() * 1000));
		$operation->setCallbackUrl(self::getOriginUrl().'/api/1.0/ts/halloai/webhooks/assessment');
		self::getLogger()->info('Info', ['operation' => self::class.' -> '.__FUNCTION__, 'content' => $operation]);
		$result = $this->request($operation);
		self::getLogger()->info('Info', ['operation' => self::class.' -> '.__FUNCTION__, 'content' => $result]);
		return $result;
	}

	/**
	 * Gibt ein Operation Objekt für den Hallo.Ai Endpoint getAssessmentUrl
	 * @return GetAssessmentUrl
	 */
	public function getGetAssessmentUrlOperation(): GetAssessmentUrl
	{
		return new GetAssessmentUrl();
	}

	/**
	 * Gibt ein Operation Objekt für den Hallo.Ai Endpoint getAssessment
	 * @return GetAssessment
	 */
	public function getGetAssessmentOperation(): GetAssessment
	{
		return new GetAssessment();
	}

	/**
	 * Sendet eine Anfrage an Hallo.Ai
	 * @param Operation $operation
	 * @return array
	 * @throws \Exception
	 */
	public function request(Operation $operation): array
	{
		$request = Http::baseUrl(self::getApiUrl())
			->withHeaders([
				'Authorization' => 'Bearer '.self::getApiKey(),
				'Origin' => self::getOriginUrl(),
				'Content-Type' => 'application/json',
				'Accept' =>	'application/json'
			])
			->beforeSending(fn ($request) => self::getLogger()->info('Send request', ['operation' => $operation::class, 'data' => $request->data()]))
			->asJson();
		$response = $operation->send($request);
		if (!$response->successful()) {
			if ($response->unauthorized()) {
				self::getLogger()->error('Unauthorized', ['operation' => get_class($operation), 'status' => $response->status(), 'message' => $response->reason()]);
				throw new \Exception('Unauthorized');
			} else {
				self::getLogger()->error('Request failed', ['operation' => get_class($operation), 'status' => $response->status(), 'message' => $response->reason()]);
				throw new \Exception('Request failed');
			}
		}
		$responseJson = $response->json();
		if (empty($responseJson)) {
			self::getLogger()->error('Response error', ['operation' => get_class($operation), 'body' => $response->body()]);
			throw new \Exception('Response error');
		} elseif (!$responseJson['success']) {
			self::getLogger()->error('Response error', ['operation' => get_class($operation), 'body' => $response->body()]);
			throw new \Exception('Response error');
		}

		return $response['result'];
	}

	/**
	 * Gibt den Logger für den Service
	 * @return \Core\Helper\MonologLogger|\Core\Service\LogService|\Monolog\Logger
	 */
	public static function getLogger()
	{
		return \Log::getLogger('api', 'halloai');
	}

}