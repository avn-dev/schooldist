<?php

namespace TsTuition\Controller\HalloAi;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TsTuition\Handler\HalloAiApp;
use TsTuition\Service\HalloAiApiService;

class ApiController extends Controller
{
	/**
	 * @param Request $request
	 * @param int $inquiryId
	 * @param string $courselanguageId
	 * @return Response
	 */
	public function getAssessmentUrl(Request $request, int $inquiryId, string $courselanguageId): Response
	{
		try {
			$halloAiApi = new HalloAiApiService();
			$result = $halloAiApi->getAssessmentUrlByInquiryId($inquiryId, $courselanguageId);
			return new Response($result);
		} catch (\Exception $e) {
			return new Response(['error' => $e->getMessage()], 500);
		}
	}

	/*
	 * Request Header:
	 * required contentHash: string: a SHA256 hash of the content provided in the body.
	 * required timestampString: string: the timestamp of the current callback attempt, in ISO format.
	 * required hmacSignature: string: a string representing the HMAC-SHA256 of the contentHash concatenated with a ";" and the timestampString, using your secret API key as the key.
	 * The body of the response will be a stringified JSON object with information about the assessment that was taken:
	 * optional firstName: string: the first name of the user (if provided).
	 * optional lastName: string: the last name of the user (if provided).
	 * optional email: string: the email address of the user (if provided).
	 * optional userId: string: your UUID of the user (if provided).
	 * required language: string: the language code of the language that was assessed.
	 * optional assessmentType: string: the type of assessment taken, from the following options: speaking, writing, listening, reading, speaking_writing, reading_listening_speaking_writing.
	 * required halloScore: number: a number between 0 and 10 representing the score the user got on the assessment.
	 * required cefrScore: string: the approximate CEFR score equivalent of halloScore.
	 * required subScores: object: the breakdown of the overall scores (fields are coherenceScore, fluencyScore, grammarScore, pronunciationScore, and vocabularyScore).
	 * required levelDescription: string: the assigned level description based on the user's score.
	 * required assessmentId: string: the unique ID of the relevant assessment.
	 * required improvements: array of strings: a list of language improvements for the user.
	 * required content: array of maps of strings to strings: a list of each of the questions of the assessment, in the order they were given. Each item will have 2 keys: question (the question that was asked, in
	 * plain text), and recording (a URL to an MP3 file of the student's answer).
	 * required securitySnapshots: array of strings: a list of URLS for each of the snapshots taken during the test. If none were taken, this array will be empty.
	 * optional infractions: object: the breakdown of detected rule infractions (fields are numTimesExitedFullscreen: num, numTimesLeftPage: num, numTimesSnapshotFailed: num, cameraDisabled: boolean,
	 * probabilityCheatingFromSnapshots: num between 0 and 1, and probabilityCameraBlocked: num between 0 and 1).
	 */
	/**
	 * @param Request $request
	 * @return Response
	 * @throws \Exception
	 */
	public function assessmentWebhook(Request $request): Response
	{
		try {
			/*
			 * Prüfe Datenintegrität
			 */
			if ($request->header('x-content-sha256') !== hash('sha256', $request->getContent())) {
				throw new \Exception("Request content hash does not match");
			}

			$hash = $request->header('x-content-sha256').";".$request->header('x-request-timestamp');
			$hmacSignature = str_replace("Algorithm=HMAC-SHA256; Signature=", "", $request->header('x-signature'));
			if (hash_hmac('sha256', $hash, HalloAiApiService::getApiKey()) !== $hmacSignature) {
				throw new \Exception("Request signature does not match");
			}

			$requestData = $request
				->json()
				->all();
			if (empty($requestData)) {
				throw new \Exception("Request is not valid");
			}

			/*
			 * Prüfe ob es das placementtestResult gibt
			 */
			$placementtestResult = \Ext_Thebing_Placementtests_Results::getInstance($requestData['userId']);
			if (!$placementtestResult) {
				return new Response(['error' => 'No placementtest results found.'], 404);
			}

			/*
			 * Ergebnis akzeptiert, Datum setzen und Werte in ausgewählten Flexfeldern speichern
			 */
			$placementtestResult->placementtest_result_date = \Carbon\Carbon::now()->format('Y-m-d');
			$flexFields = $placementtestResult->getFlexibleFields();
			foreach (HalloAiApp::$fieldMapping as $mappingKey => $halloAiField) {
				$flexFieldId = HalloAiApp::getFlexFieldId($mappingKey);
				if (!empty($flexFieldId) && !empty($flexFields[$flexFieldId])) {
					if (strpos($halloAiField, '.')) {
						$fieldKeys = explode('.', $halloAiField);
						$value = $requestData[$fieldKeys[0]][$fieldKeys[1]];
					} else {
						$value = $requestData[$halloAiField];
					}
					$placementtestResult->setFlexValue($flexFieldId, $value);
				}
			}
			$placementtestResult->saveFlexValues();
			$placementtestResult->save();
			HalloAiApiService::getLogger()->info('Webhook info', ['operation' => self::class." -> ".__FUNCTION__, 'content' => $requestData]);

		} catch (\Exception $e) {
			HalloAiApiService::getLogger()->error('Webhook error', ['operation' => self::class." -> ".__FUNCTION__, 'message' => $e->getMessage(), 'content' => $requestData]);
			return new Response([
				'success' => false
			]);
		}
		/*
		 * Wird nicht success true zurückgegeben, versucht Hallo.ai es noch 2 mal.
		 */
		return new Response([
			'success' => true
		]);
	}
}