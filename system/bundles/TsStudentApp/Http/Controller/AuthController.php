<?php

namespace TsStudentApp\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ts\Notifications\InquiryNotification;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\AppInterfaceResource;
use TsStudentApp\Service\AccessService;

class AuthController extends \Illuminate\Routing\Controller {

	/**
	 * Login
	 * POST => ['username', 'password']
	 *
	 * @param Request $request
	 * @param AccessService $accessService
	 * @param AppInterface $appInterface
	 * @return JsonResponse
	 */
	public function login(Request $request, AccessService $accessService, AppInterface $appInterface) {

		if(
			$request->has('username') &&
			$request->has('password')
		) {
			$token = $accessService->login((string)$request->input('username'), (string)$request->input('password'));

			if(is_string($token) && $accessService->check()) {

				$appInterface->setUser($accessService->getUser());

				$interface = null;
				if (version_compare($appInterface->getAppVersion(), '3', '<')) {
					$interface = (new AppInterfaceResource($appInterface))->toArray($request);
				}

				return response()
					->json([
						'success' => true,
						'token' => $token,
						'interface' => $interface
					]);
			}
		}

		return response()
			->json([
				'success' => false,
				'alert' => [ // TODO Deprecated App >= 3.0.0
					'header' => $appInterface->t('Login failed'),
					'message' => $appInterface->t('Please insert your correct credentials.'),
					'btn' => $appInterface->t('Okay'),
				]
			]);
	}

	/**
	 * Passwort vergessen
	 * POST => ['email']
	 *
	 * @param Request $request
	 * @param AccessService $accessService
	 * @param AppInterface $appInterface
	 * @return JsonResponse
	 */
	public function requestAccessCode(Request $request, AccessService $accessService, AppInterface $appInterface) {

		if($request->has('email')) {

			$email = (string)$request->input('email');

			$generate = $accessService->generateAccessCode($email);

			if(is_array($generate)) {

				list($accessCode, $user) = array_values($generate);
				/* @var \Ext_TS_Inquiry_Contact_Login $user */

				$traveller = \Ext_TS_Inquiry_Contact_Traveller::getInstance($user->contact_id);

				$inquiry = $traveller->getClosestInquiry();
				$school = $inquiry->getSchool();

				$template = $school->getTemplateForMobileAppForgottenPassword();

				if($template instanceof \Ext_TC_Communication_Template) {

					(new \Ext_Thebing_Inquiry_Placeholder())->addMonitoringEntry('user_login_code');

					$notification = (new InquiryNotification($inquiry, $template, \Ext_TC_Communication::SEND_MODE_AUTOMATIC))
						->additionalPlaceholders(['user_login_code' => $accessCode]);

					$traveller->notifyNow($notification, ['mail']);

					// TODO: Ablaufdatum für den Access-Code
					// Access-Code nicht löschen, da beim PW-Reset nochmal das alte PW abgefragt wird. Der Access-Code
					// kann dort ebenfalls als Passwort eingegeben werden

					return response()
						->json([
							'success' => true,
						]);
				} else {
					$errorMail = new \WDMail();
					$errorMail->subject = 'TsMobile API: Anforderung von Passwort, aber kein Template konfiguriert – '.\System::d('domain');
					$errorMail->text = print_r($request, 1);
					$errorMail->send(['TsMobile@p32.de']);

					// TODO Event?

					return response()
						->json([
							'success' => false,
							'alert' => [
								'header' => $appInterface->t('Request failed'),
								'message' => $appInterface->t('We were not able to send you your access code. Please contact your school.'),
								'btn' => $appInterface->t('Okay'),
							]
						]);
				}
			}
		} else {
			return response()
				->json([
					'success' => false,
					'alert' => [
						'header' => $appInterface->t('Request failed'),
						'message' => $appInterface->t('Please insert your correct credentials.'),
						'btn' => $appInterface->t('Okay'),
					]
				]);
		}

		// Es macht mMn keinen Sinn einem möglichen Angreifer mitzuteilen dass die verwendete E-Mail-Adresse nicht funktioniert
		return response()
			->json(['success' => true]);

		/*return response()
			->json([
				'success' => false,
				'alert' => [
					'header' => $appInterface->t('Request failed'),
					'message' => $appInterface->t('Your email address could not be verified.'),
					'btn' => $appInterface->t('Okay'),
				]
			]);*/
	}

	/**
	 * Login (via Access-Code)
	 * POST ['code']
	 *
	 * @param Request $request
	 * @param AccessService $accessService
	 * @param AppInterface $appInterface
	 * @return JsonResponse
	 */
	public function loginViaAccessCode(Request $request, AccessService $accessService, AppInterface $appInterface) {

		if($request->has('code')) {

			$token = $accessService->loginViaAccessCode((string)$request->input('code', ''));

			if(is_string($token) && $accessService->check()) {

				$appInterface->setUser($accessService->getUser());

				return response()
					->json([
						'success' => true,
						'token' => $token,
						'interface' => (new AppInterfaceResource($appInterface))->toArray($request)
					]);
			}
		}

		return response()
			->json([
				'success' => false,
				'alert' => [
					'header' => $appInterface->t('Failed'),
					'message' => $appInterface->t('Your access code is not valid.'),
					'btn' => $appInterface->t('Okay'),
				],
			]);
	}

	/**
	 * Logout
	 * @return JsonResponse
	 */
	public function logout(AccessService $accessService, AppInterface $appInterface) {

		// Bei explizitem Logout Push Notifications für dieses Device deaktivieren
		if (
			$appInterface->isRunningNative() &&
			($loginDevice = $appInterface->getDevice()->getLoginDevice($accessService->getUser())) !== null
		) {
			$loginDevice->push_permission = 0;
			$loginDevice->save();
		}

		$accessService->logout();

		return response()
			->json([
				'success' => true
			]);
	}

}
