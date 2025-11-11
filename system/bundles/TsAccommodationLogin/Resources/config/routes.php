<?php

use Illuminate\Support\Facades\Route;


# Aus der routes.yml:
# TODO Alle Routen mit save müssen entfernt werden, da ein erneutes Aufrufen keine funktionierende Seite mehr erzeugt (reines POST)

Route::group(['prefix' => '/accommodation'], function() {

	Route::any('/login', [TsAccommodationLogin\Controller\InterfaceController::class, 'login'])
		->name('accommodation_login');

	Route::any('/logout', [TsAccommodationLogin\Controller\InterfaceController::class, 'logout'])
		->name('accommodation_logout');

	Route::any('', [TsAccommodationLogin\Controller\InterfaceController::class, 'accommodation'])
		->name('accommodation');

	Route::get('/forgot-password', [TsAccommodationLogin\Controller\PasswordController::class, 'getForgotPasswordView'])
		->name('accommodation_forgot_password');

	Route::post('/password-reset/send', [TsAccommodationLogin\Controller\PasswordController::class, 'postResetPassword'])
		->name('accommodation_reset_password_send');

	/*
	 * @todo Route prüfen
	 */
	Route::post('/password-reset/{sToken}/save', [TsAccommodationLogin\Controller\PasswordController::class, 'postResetPasswordSave'])
		->name('accommodation_reset_password_save');

	/*
	 * @todo Route prüfen
	 */
	Route::get('/password-reset/{sToken}', [TsAccommodationLogin\Controller\PasswordController::class, 'getResetPasswordView'])
		->name('accommodation_reset_password_link');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/change-language/{sLanguage}', [TsAccommodationLogin\Controller\AbstractController::class, 'changeLanguage'])
		->name('accommodation_change_language');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/request-availability/{task}/{key}', [TsAccommodationLogin\Controller\RequestsController::class, 'requestAvailability'])
		->name('accommodation_request_availability');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/request-availability/{task}/{key}/confirm', [TsAccommodationLogin\Controller\RequestsController::class, 'requestAvailabilityConfirm'])
		->name('accommodation_request_availability_confirm');

	Route::any('/data', [TsAccommodationLogin\Controller\AccommodationDataController::class, 'profile'])
		->name('accommodation_data');

	Route::post('/data/save', [TsAccommodationLogin\Controller\AccommodationDataController::class, 'saveData'])
		->name('accommodation_data_save');

	Route::post('/password/save', [TsAccommodationLogin\Controller\AccommodationDataController::class, 'savePassword'])
		->name('accommodation_password_save');

	Route::any('/requests', [TsAccommodationLogin\Controller\RequestsController::class, 'overview'])
		->name('accommodation_requests');

	Route::any('/payments', [TsAccommodationLogin\Controller\PaymentsController::class, 'payments'])
		->name('accommodation_payments');

	Route::get('/payments/pdf/{groupingId}', [TsAccommodationLogin\Controller\PaymentsController::class, 'getPdf'])
		->name('accommodation_payments_pdf');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/profile-picture/{allocationId}', [TsAccommodationLogin\Controller\AllocationController::class, 'profilePicture'])
		->name('accommodation_profile_picture');

	/*
	 * @todo Route prüfen
	 */
	Route::get('/resources/{sFile}', [TsAccommodationLogin\Controller\ResourceController::class, 'printFile'])
		->name('accommodation_resources')
		->where('sFile', '.+');

	Route::get('/logo', [TsAccommodationLogin\Controller\StorageResourceController::class, 'getLogo'])
		->name('accommodation_logo');

	/*
	 * @todo Route prüfen
	 */
	Route::get('/storage{sFile}', [TsAccommodationLogin\Controller\StorageResourceController::class, 'getFile'])
		->name('accommodation_storage')
		->where('sFile','.+');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/{sPath}', [TsAccommodationLogin\Controller\InterfaceController::class, 'redirectToHttps'])
		->name('accommodation_pages_redirect')
		->where('sPath','.+');
});