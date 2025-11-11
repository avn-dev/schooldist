<?php

use Illuminate\Support\Facades\Route;
use TcApi\Middleware\Auth;
use TsApi\Controller\CustomFieldsController;
use TsApi\Controller\EnquiryController;
use TsApi\Controller\InquiryController;
use TsApi\Controller\PaymentController;
use TsApi\Controller\PlacementtestController;

Route::group([
	'prefix' => '/api/{version}/ts',
	'where' => [
		'version' => '1\.[01]'
	],
	'as' => 'ts_api.',
	'middleware' => [\Api\Middleware\ErrorHandling::class, \Core\Middleware\Frontend::class]
], function () {

	Route::group([
		'prefix' => 'enquiries',
		'as' => 'enquiries.',
		'middleware' => [Auth::class.':ts_api_enquiries']
	], function() {

		Route::post('/', [EnquiryController::class, 'store'])->name('store');

		Route::get('/{id}', [EnquiryController::class, 'show'])->name('show')->where(['id' => '\d+']);

		Route::patch('/{id}', [EnquiryController::class, 'update'])->name('update')->where(['id' => '\d+']);

		Route::get('/search', [EnquiryController::class, 'search'])->name('search');

	});

	Route::group([
		'prefix' => 'bookings',
		'as' => 'bookings.',
		'middleware' => [Auth::class.':ts_api_bookings']
	], function () {

		Route::post('/', [InquiryController::class, 'store'])->name('store');

		Route::get('/{id}', [InquiryController::class, 'show'])->name('show')->where(['id' => '\d+']);

		Route::patch('/{id}', [InquiryController::class, 'update'])->name('update')->where(['id' => '\d+']);

		Route::get('/search', [InquiryController::class, 'search'])->name('search');

		Route::get('/{legacy_token?}', [InquiryController::class, 'list'])->name('list');

	});

	Route::group([
		'prefix' => 'payments',
		'as' => 'payments.',
		'middleware' => [Auth::class.':ts_api_payments']
	], function () {

		Route::post('/', [PaymentController::class, 'store'])->name('store');

	});

	Route::group([
		'prefix' => 'booking',
		'as' => 'booking.',
		'middleware' => [Auth::class.':ts_api_booking_details']
	], function () {

		Route::get('/{id}', [InquiryController::class, 'details'])->name('details');

	});

	Route::group([
		'prefix' => 'placementtest',
		'as' => 'placementtest.',
		'middleware' => [Auth::class.':ts_api_placementtest']
	], function () {

		Route::post('result', [PlacementtestController::class, 'update'])->name('update');

	});

	Route::group([
		'prefix' => 'custom-fields',
		'as' => 'custom_fields.',
		'middleware' => [Auth::class.':ts_api_custom_fields']
	], function () {
		Route::get('/student-record-fields', [CustomFieldsController::class, 'studentRecordFields'])->name('student_record_fields');
	});
});
