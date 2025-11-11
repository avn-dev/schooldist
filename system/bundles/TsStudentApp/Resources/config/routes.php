<?php

use Illuminate\Support\Facades\Route;
use TsStudentApp\Http\Controller;
use TsStudentApp\Http\Middleware;

Route::group([
	'prefix' => 'api/1.0/ts/student-app',
	'middleware' => [\Core\Middleware\Frontend::class, \TcFrontend\Middleware\CorsHeaders::class, Middleware\Error::class],
	'as' => 'api.student_app.'
], function() {

	Route::group([
		'prefix' => 'auth',
		'as' => 'auth.',
		'middleware' => [Middleware\AppInterface::class],
	], function() {
		Route::match(['OPTIONS', 'POST'], 'login', [Controller\AuthController::class, 'login'])->name('login');

		Route::match(['OPTIONS', 'GET'], 'logout', [Controller\AuthController::class, 'logout'])
			->middleware([\TcFrontend\Middleware\TokenAuth::class])
			->name('logout');

		Route::group([
			'prefix' => 'access_code',
			'as' => 'access_code.'
		], function() {
			Route::match(['OPTIONS', 'POST'], '/request', [Controller\AuthController::class, 'requestAccessCode'])->name('request');
			Route::match(['OPTIONS', 'POST'], '/login', [Controller\AuthController::class, 'loginViaAccessCode'])->name('login');
		});
	});

	Route::group([
		'middleware' => [
			\TcFrontend\Middleware\TokenAuth::class,
			Middleware\AppInterface::class
		],
	], function () {

		// Device

		Route::group([
			'prefix' => 'device',
			'as' => 'device.',
		], function() {
			Route::match(['OPTIONS', 'POST'], '/messaging/token', [Controller\DeviceController::class, 'storeMessagingToken'])->name('messaging_token');
		});

		// Interface

		Route::group([
			'prefix' => 'interface',
			'as' => 'interface.',
		], function() {
			Route::match(['OPTIONS', 'GET'],'init', [Controller\InterfaceController::class, 'init'])->name('app');
			Route::match(['OPTIONS', 'GET'],'image/{type}/{id}', [Controller\FileController::class, 'image'])->name('image');
			Route::match(['OPTIONS', 'GET'],'document/{type}/{id}', [Controller\FileController::class, 'document'])->name('document');
			Route::match(['OPTIONS', 'GET'],'properties/data', [Controller\InterfaceController::class, 'properties'])->name('properties');
			Route::match(['OPTIONS', 'GET'],'intro/finished', [Controller\InterfaceController::class, 'finishIntro'])->name('intro.finish');
			Route::match(['OPTIONS', 'GET', 'POST', 'PUT', 'DELETE'],'page/{page}/{action}', [Controller\InterfaceController::class, 'pageAction'])
				->middleware([
					Middleware\Page::class,
					// @todo - die Middleware für alle anzugeben ist nicht optimal
					Middleware\MessengerThread::class
				])
				->name('page.action');
		});

	});

});
