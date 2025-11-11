<?php

namespace TsStudentApp\Http\Middleware;

use Closure;
use TsActivities\Enums\AssignmentSource;
use TsActivities\Service\ActivityService;
use TsStudentApp\Device;
use TsStudentApp\Service\AccessService;
use TsStudentApp\Service\LoggingService;
use TsStudentApp\Service\MessengerService;
use Core\Helper\BundleConfig;
use Illuminate\Http\Request;
use Ts\Service\Inquiry\SchedulerService;

class AppInterface {

	public function __construct(
		private BundleConfig $bundleConfig,
		private AccessService $accessService,
		private LoggingService $loggingService
	) {}

	public function handle(Request $request, Closure $next) {

		if(!$this->isValid($request)) {
			return response('Bad request', 400);
		}

		$appInterface = $this->buildInterface($request);

		// Frontend-Auth-Middleware muss vorher durchgelaufen sein
		if($this->accessService->check()) {

			$appInterface->setUser($this->accessService->getUser());

			app()->instance(\Ext_TS_Inquiry::class, $appInterface->getInquiry());
			app()->instance(\Ext_TS_Inquiry_Contact_Traveller::class, $appInterface->getStudent());
			app()->instance(\Ext_Thebing_School::class, $appInterface->getSchool());
			app()->singleton(MessengerService::class, MessengerService::class);
			app()->singleton(ActivityService::class, fn () => new ActivityService(AssignmentSource::APP));
			app()->singleton(SchedulerService::class, function ($app) use ($appInterface) {
				return new SchedulerService($appInterface->getInquiry(), $appInterface->getLanguageObject());
			});

			if(
				$appInterface->isRunningNative() &&
				strpos($request->path(), '/image/') === false
			) {
				$this->loggingService->device($this->accessService->getUser(), $appInterface->getDevice(), $appInterface->getAppVersion(), $appInterface->getAppEnvironment());
			}
		}

		$this->setTimezone($appInterface);

		app()->instance(\TsStudentApp\AppInterface::class, $appInterface);

		$response = $next($request);

		\Ext_Gui2_Index_Stack::save(true);

		return $response;

	}

	/**
	 * App-Objekt aufbauen
	 *
	 * @param $request
	 * @return \TsStudentApp\AppInterface
	 */
	private function buildInterface(Request $request): \TsStudentApp\AppInterface {

		$appInterface = new \TsStudentApp\AppInterface(
			$this->bundleConfig,
			$this->matchInterfaceLanguage((string)$request->header('X-Interface-Language')),
			(string)$request->header('X-App-Version'),
			$request->header('X-App-Environment')
		);

		if($request->headers->has('X-Inquiry-Id')) {
			$appInterface->setRequestInquiryId((int)$request->header('X-Inquiry-Id'));
		}

		if($request->headers->has('X-App-Id')) {

			$device = new Device(
				(string)$request->header('X-App-Os'),
				(string)$request->header('X-App-Os-Version'),
				(string)$request->header('X-App-Id')
			);

			if (
				$request->hasHeader('X-App-Device') &&
				($additional = json_decode($request->header('X-App-Device'), true)) !== false
			) {
				$device->additional = $additional;
			}

			$appInterface->setDevice($device);
		}

		return $appInterface;
	}

	/**
	 * Zeitzone setzen
	 *
	 * @param \TsStudentApp\AppInterface $appInterface
	 */
	private function setTimezone(\TsStudentApp\AppInterface $appInterface): void {

		$timezone = \Ext_Thebing_Client::getFirstClient()->timezone;
		$school = $appInterface->getSchool();

		if($school && !empty($school->timezone)) {
			$timezone = $school->timezone;
		}

		\Ext_Thebing_Util::setTimezone($timezone);
	}

	/**
	 * Prüfen ob der Request alle Anforderung erfüllt
	 *
	 * @param $request
	 * @return bool
	 */
	private function isValid($request): bool {

		if (
			!empty($request->header('X-Interface-Language', '')) &&
			!empty($request->header('X-App-Version', ''))
		) {
			if ($this->matchInterfaceLanguage((string)$request->header('X-Interface-Language')) !== null) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Device-Language mit Frontendsprachen abgleichen
	 *
	 * @param string $language
	 * @return string|null
	 */
	private function matchInterfaceLanguage(string $language): ?string {

		// @ngx-translate/core::getBrowserLang() liefert immer nur einen zweistelligen ISO-Code
		// Wenn also Device und Software fr_CA verwenden, liefert das Device trotzdem nur fr, aber fr_CA muss gematcht werden
		// TODO In der App ggf. auf getBrowserCultureLang() umstellen, damit man fr-CA bekäme
		foreach ((array)\Ext_TS_Config::getInstance()->frontend_languages as $frontendLanguage) {
			if ($language === substr($frontendLanguage, 0, 2)) {
				return $frontendLanguage;
			}
		}

		return null;

	}

}
