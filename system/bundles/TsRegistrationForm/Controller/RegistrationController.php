<?php

namespace TsRegistrationForm\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use TsRegistrationForm\Factory\BookingByKeyFactory;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Generator\SubmitMessageGenerator;
use TsRegistrationForm\Helper\FormValidatorHelper;
use TsRegistrationForm\Helper\ServiceMutationHelper;
use TsRegistrationForm\Helper\UploadHelper;
use TsRegistrationForm\Service\InquiryBuilder;

class RegistrationController extends \Illuminate\Routing\Controller {

	/**
	 * Buchungsvorlage über Parameter auf einbindender Seite
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return array
	 */
	public function booking(Request $request, CombinationGenerator $combination): Response {

		$inquiry = (new BookingByKeyFactory($combination))->make($request->input('key'));

		if ($inquiry === null) {
			if ($combination->getForm()->purpose === \Ext_Thebing_Form::PURPOSE_NEW) {
				// Key ist optional
				$combination->log('RegistrationController::booking::invalid_key');
				return response('Invalid key', 400);
			} else {
				// Key ist required
				$combination->log('RegistrationController::booking::invalid_key_required');
				return response(['actions' => [[
					'handler' => 'addNotification',
					'key' => 'booking_error',
					'type' => 'danger',
					'message' => $combination->getForm()->getTranslation('error_key', $combination->getLanguage())
				]]], 400);
			}
		}

		$combination->setInquiry($inquiry);

		return response($this->replaceBookingData($request, $combination));

	}

	public function changeSchool(Request $request, CombinationGenerator $combination): Response {

		// Parameter wird auch in CombinationGenerator::initCombination() ausgelesen
		$schoolId = $request->input('fields.school');

		if (!in_array($schoolId, $combination->getForm()->schools)) {
			$combination->log('RegistrationController::changeSchool::invalid_school');
			return response('Unknown school', 400);
		}

		return response($this->replaceBookingData($request, $combination));

	}

	/**
	 * Beim Wechsel von Zeiträumen: Zeiträume aller Leistungen und Ferien überprüfen
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return array
	 */
	public function dates(Request $request, CombinationGenerator $combination): array {

		$saver = new InquiryBuilder($combination, $request, FormValidatorHelper::VALIDATE_SERVER);
		$inquiry = $saver->generate();

		$helper = new ServiceMutationHelper($combination, $inquiry, $request->input('trigger', ''));
		$helper->execute();

		return [
			'mutations' => $helper->getMutations(),
			'actions' => $helper->getActions(),
			'times' => [$helper->getDebugTimes(), $saver->getDebugTimes()]
		];

	}

	/**
	 * Submit-Request
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return Response
	 */
	public function submit(Request $request, CombinationGenerator $combination): Response {

		$combination->log('RegistrationController::submit::begin', [], false);

		$combination->logUsage('submit', false);

		$level = FormValidatorHelper::VALIDATE_SERVER | FormValidatorHelper::VALIDATE_SERVER_ALL | FormValidatorHelper::VALIDATE_SERVER_PAYMENT;
		$saver = new InquiryBuilder($combination, $request, $level);
		$saver->generate();

		if ($saver->hasErrors()) {

			$combination->log('RegistrationController::submit::validation_error', ['validation' => $saver->getErrors()]);

			return $this->generateSaverErrorResponse($combination, $saver);

		}

		\DB::begin(__METHOD__);

		$saved = $saver->save();

		if (!$saved) {

			\DB::rollback(__METHOD__);

			$combination->log('RegistrationController::submit::rollback', [], true);

			return new Response([
				'actions' => [
					[
						'handler' => 'addNotification',
						'key' => 'submit_error',
						'type' => 'danger',
						'message' => $combination->getForm()->getTranslation('errorinternal', $combination->getLanguage())
					]
				]
			], 500);

		}

		\DB::commit(__METHOD__);

		$combination->log('RegistrationController::submit::commit', ['inquiry' => $saver->getInquiry()->getData()], false);

		return new Response([
			'mutations' => [
				[
					'handler' => 'REPLACE_DATA',
					'key' => 'confirm_message',
					'value' => (new SubmitMessageGenerator($combination, $saver))->generate()
				],
				[
					'handler' => 'NEXT_PAGE'
				]
			],
			'actions' => [
				[
					// Dirty-State resetten, damit beforeunload nicht mehr auslöst
					'handler' => 'resetVuelidate',
					'pass' => []
				]
			],
			'times' => $saver->getDebugTimes()
		]);

	}

	/**
	 * Preise
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return array
	 */
	public function prices(Request $request, CombinationGenerator $combination): array {

		// Light-Validierung, da beim Entfernen vom Kurs keine Service-Dates mehr verfügbar sind und der prices-Request wg. Validator-Rules abstürzt
		$saver = new InquiryBuilder($combination, $request, FormValidatorHelper::VALIDATE_SERVER);
		$inquiry = $saver->generate();

		$debugTime = microtime(true);
		$priceHelper = new \TsRegistrationForm\Helper\PriceBlockHelper($combination, $inquiry);
		$prices = $priceHelper->generatePriceData();
		$debugTime = microtime(true) - $debugTime;

		// Es sollte keine Session da sein, da sich ansonsten alle Requests blockieren
		if (session_status() === PHP_SESSION_ACTIVE) {
			throw new \RuntimeException('Session has been started!');
		}

		return [
			'mutations' => [
				[
					'handler' => 'REPLACE_DATA',
					'key' => 'prices',
					'value' => $prices
				]
			],
			'times' => array_merge($saver->getDebugTimes(), ['prices' => $debugTime])
		];

	}

	/**
	 * Upload-Request (asynchron)
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return Response
	 */
	public function upload(Request $request, CombinationGenerator $combination): \Illuminate\Http\Response {

		$helper = new UploadHelper($combination, $request);
		$result = $helper->handleUploadRequest();

		return response(Arr::except($result, ['status']), $result['status']);

	}

	public function payment(Request $request, CombinationGenerator $combination) {

		$combination->logUsage('payment', false);

		$block = $combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_PAYMENT);
		if ($block === null) {
			abort(400, 'No payment block');
			return null;
		}

		$builder = new InquiryBuilder($combination, $request, FormValidatorHelper::VALIDATE_SERVER | FormValidatorHelper::VALIDATE_SERVER_ALL);
		$inquiry = $builder->generate();

		if ($builder->hasErrors()) {
			$combination->log('RegistrationController::payment::error', ['errors' => $builder->getErrors()]);
			return $this->generateSaverErrorResponse($combination, $builder, ['fields.payment']);
		}

		$providers = collect($block->getSetting('provider'));
		if (!$block->required) {
			$providers->push('skip');
		}

		$helper = new \TsFrontend\Helper\PaymentRequestHelper($combination, $request, $inquiry, $providers);
		$helper->setItemBuilder($builder->createPaymentItemFunction($block));

		return $helper->handle();

	}

	/**
	 * Alle Daten der Buchung ersetzen
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return array
	 */
	private function replaceBookingData(Request $request, CombinationGenerator $combination): array {

		$data = $combination->getWidgetData();
		$data['handler'] = 'INITIAL_DATA';

		// Felder aus Request übernehmen, damit nicht alles beim Schulwechsel gelöscht wird
		// Werte, die nicht mehr passen, sollten automatisch getilgt werden und den Validator triggern
		// Außerdem sollte ein interner Wert wie der tracking_key erhalten bleiben
		collect($data['actions'])->transform(function (array $action) use ($request) {
			if ($action['handler'] === 'setInitialBookingData') {
				foreach ($action['fields'] as $field => &$value) {
					if ($request->filled('fields.'.$field)) {
						$value = $request->input('fields.'.$field);
					}
				}
			}
			return $action;
		});

		$data['actions'][] = ['handler' => 'resetVuelidate', 'pass' => []];

		return [
			'mutations' => [
				$data, [
					'handler' => 'DISABLE_STATE',
					'key' => 'form',
					'status' => false
				]
			],
			'actions' => $data['actions']
		];

	}

	private function generateSaverErrorResponse(CombinationGenerator $combination, InquiryBuilder $saver, array $except = []): Response {

		return new Response([
			'mutations' => [
				[
					'handler' => 'REPLACE_DATA',
					'key' => 'remote_validation',
					'value' => $saver->getErrors()
				]
			],
			// Bei Submit wird das irgendwie nicht benötigt, bei der Zahlung aber schon
			// Ohne den manuellen $touch-Trigger würden in dem anderen Fall keine der Fehler angezeigt werden
			'actions' => [
				[
					'handler' => 'triggerVuelidate',
					'except' => $except
				],
				[
					'handler' => 'addNotification',
					'key' => 'validation_error', // Wird auch in Vue benutzt, damit Fehlermeldung nicht doppelt vorkommt und vom JS gelöscht werden kann
					'type' => 'danger',
					'message' => $combination->getForm()->getTranslation('error', $combination->getLanguage())
				],
			]
		], 400);

	}

}
