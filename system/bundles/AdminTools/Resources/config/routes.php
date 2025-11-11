<?php

use Illuminate\Support\Facades\Route;
use AdminTools\Http\Controller;
use AdminTools\Http\Middleware;
use Core\Middleware\Auth;
use Core\Middleware\Backend;

Route::get('assets/admin-tools/{sFile}', [Controller\ResourceController::class, 'printFile'])
	->where(['sFile' => '.+?'])
	->name('assets');

Route::group([
	'prefix' => 'admin/tools',
	'middleware' => [
		Backend::class,
		Auth\BackendAuth::class,
		Auth\FideloUser::class,
		Middleware\HandleInertiaRequests::class
	]
], function () {

	Route::get('/', [Controller\AdminToolsController::class, 'index']);
	Route::get('/debug-mode', [Controller\AdminToolsController::class, 'toggleDebugMode']);
	Route::get('/debug-ip', [Controller\AdminToolsController::class, 'toggleDebugIp']);
	Route::post('/button', [Controller\AdminToolsController::class, 'buttonAction']);
	Route::post('/action', [Controller\AdminToolsController::class, 'action']);

	Route::group(['prefix' => 'log-viewer'], function () {
		Route::get('/', [Controller\LogViewController::class, 'index']);
		Route::post('load-log', [Controller\LogViewController::class, 'loadLog']);
	});

	Route::get('/settings', [Controller\AdminToolsController::class, 'settings']);

	Route::match(['get','post'], '/elasticsearch', [Controller\AdminToolsController::class, 'elasticsearch'])
		->middleware(Auth\BackendAuth::class . ':control');
	
	Route::get('/legacy-tools', [Controller\AdminToolsController::class, 'legacyTools']);

	Route::get('/support-sessions', [Controller\AdminToolsController::class, 'supportSessions']);

	Route::any('/colors', [Controller\AdminToolsController::class, 'colors']);

});
