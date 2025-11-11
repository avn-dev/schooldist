<?php

use Core\Facade\Routing\Route;
use Core\Middleware\Frontend;
use TcFrontend\Middleware;
use TsRegistrationForm\Controller\RegistrationController;
use TsRegistrationForm\Controller\ResourceController;

Route::get('assets/ts-registration-form/{sFile}', [ResourceController::class, 'printFile'])->name('assets')->where(['sFile' => '.+?']);

Route::group([
	'prefix' => 'api/1.0/ts/frontend/registration',
	'middleware' => [Frontend::class, Middleware\CorsHeaders::class, Middleware\CombinationByHeader::class],
	'as' => 'api.registration.'
], function () {
	Route::match(['OPTIONS', 'POST'], 'booking', [RegistrationController::class, 'booking'])->name('booking');
	Route::match(['OPTIONS', 'POST'], 'school_change', [RegistrationController::class, 'changeSchool'])->name('school_change');
	Route::match(['OPTIONS', 'POST'], 'dates', [RegistrationController::class, 'dates'])->name('dates');
	Route::match(['OPTIONS', 'POST'], 'submit', [RegistrationController::class, 'submit'])->name('submit');
	Route::match(['OPTIONS', 'POST'], 'prices', [RegistrationController::class, 'prices'])->name('prices');
	Route::match(['OPTIONS', 'POST'], 'payment', [RegistrationController::class, 'payment'])->name('payment');

	// PUT statt Post als Workaround, damit der PHP-Proxy den POST-Request nicht parst und den Request-Body vernichtet – siehe Middleware
	Route::match(['OPTIONS', 'PUT'], 'upload', [RegistrationController::class, 'upload'])->middleware(Middleware\MultipartParser::class)->name('upload');

	Route::match(['OPTIONS', 'POST'], 'file', [ResourceController::class, 'file'])->name('file');
});
