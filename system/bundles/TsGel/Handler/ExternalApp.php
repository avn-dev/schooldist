<?php

namespace TsGel\Handler;

use Core\Factory\ValidatorFactory;
use Core\Handler\SessionHandler;
use TcExternalApps\Service\AppService;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'gel';

	const CONFIG_SERVER = 'gel_server';

	const CONFIG_API_TOKEN = 'gel_api_token';

	const CONFIG_SCHOOLS = 'gel_schools';

	const CONFIG_COURSE_CATEGORIES = 'gel_course_categories';

	const CONFIG_PAYMENT_STATE = 'gel_payment_state';

	const CONFIG_BOOKING_STATUS = 'gel_booking_status';

	/**
	 * @return string
	 */
	public function getTitle() : string
	{
		return \L10N::t('GEL');
	}

	public function getDescription() : string
	{
		return \L10N::t('GEL - Description');
	}

	public function getIcon(): string {
		return 'fas fa-plug';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

    public function getContent() : ?string
	{
		$schools = \Ext_Thebing_School::query()->pluck('ext_1', 'id')->toArray();
		$courseCategories = \Ext_Thebing_Tuition_Course_Category::query()->get();

		$categorySchoolGroups = [];
		foreach ($courseCategories as $category) {
			foreach ($category->schools as $schoolId) {
				$categorySchoolGroups[$schoolId][$category->id] = $category->getName();
			}
		}

	    $smarty = new \SmartyWrapper();
	    $smarty->assign('appKey', self::APP_NAME);
	    $smarty->assign('fieldErrors', SessionHandler::getInstance()->getFlashBag()->get('field_errors', []));
	    $smarty->assign('schoolsSelection', $schools);
	    $smarty->assign('categoriesSelection', $categorySchoolGroups);
	    $smarty->assign('allCategoriesSelection', $courseCategories->mapWithKeys(fn ($category) => [$category->id => $category->getName()])->toArray());
	    $smarty->assign('paymentStatesSelection', [
			'all' => \L10N::t('Alle'),
			'deposit' => \L10N::t('Angezahlt'),
			'full_payed' => \L10N::t('Vollzahlung'),
		]);
		$smarty->assign('bookingStatusSelection', [
			'all' => \L10N::t('Alle'),
			'confirmed' => \L10N::t('Nur bestätigte Buchungen'),
		]);

	    return $smarty->fetch('@TsGel/external_apps/config.tpl');
    }

    public function saveSettings(\Core\Handler\SessionHandler $session, \MVC_Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			self::CONFIG_SERVER => 'required|url',
			self::CONFIG_API_TOKEN => 'required',
			self::CONFIG_SCHOOLS => 'required',
			self::CONFIG_COURSE_CATEGORIES => 'required',
			self::CONFIG_PAYMENT_STATE => 'required',
			self::CONFIG_BOOKING_STATUS => 'required',
		]);

		if ($validator->fails()) {
			$session->getFlashBag()->add('error', \L10N::t('Ihre Einstellungen konnten nicht gespeichert werden'));
			$session->getFlashBag()->set('field_errors', $validator->getMessageBag()->toArray());
			return;
		}

		\System::s(self::CONFIG_SERVER, rtrim($request->input(self::CONFIG_SERVER), '/'));
		\System::s(self::CONFIG_API_TOKEN, $request->input(self::CONFIG_API_TOKEN));
		\System::s(self::CONFIG_SCHOOLS, json_encode($request->input(self::CONFIG_SCHOOLS)));
		\System::s(self::CONFIG_COURSE_CATEGORIES, json_encode($request->input(self::CONFIG_COURSE_CATEGORIES)));
		\System::s(self::CONFIG_PAYMENT_STATE, $request->input(self::CONFIG_PAYMENT_STATE));
		\System::s(self::CONFIG_BOOKING_STATUS, $request->input(self::CONFIG_BOOKING_STATUS));

		$session->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));

    }

	public static function isEnabled(): bool
	{
		return AppService::hasApp(self::APP_NAME) &&
			!empty(self::getServer()) &&
			!empty(self::getApiToken());
	}

	public static function getServer(): string
	{
		return \System::d(self::CONFIG_SERVER, '');
	}

	public static function getApiToken(): string
	{
		return \System::d(self::CONFIG_API_TOKEN);
	}

	public static function getSchools(): array {
		return json_decode(\System::d(self::CONFIG_SCHOOLS, '[]'), true);
	}

	public static function getCourseCategories(): array {
		return json_decode(\System::d(self::CONFIG_COURSE_CATEGORIES, '[]'), true);
	}

	public static function getPaymentState(): string {
		return \System::d(self::CONFIG_PAYMENT_STATE);
	}

	public static function getBookingStatus(): string {
		return \System::d(self::CONFIG_BOOKING_STATUS, 'all');
	}
}
