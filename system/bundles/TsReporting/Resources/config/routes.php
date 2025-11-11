<?php

use Illuminate\Support\Facades\Route;
use Core\Middleware\Auth\BackendAuth;
use Core\Middleware\Backend;

Route::get('assets/ts-reporting/{sFile}', [TsReporting\Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where(['sFile' => '.+?']);

Route::group([
	'prefix' => 'ts/reports',
	'middleware' => [Backend::class, BackendAuth::class]
], function () {

	Route::get('/', [TsReporting\Controller\ReportController::class, 'index']);

	Route::post('query', [TsReporting\Controller\ReportController::class, 'query']);

	Route::post('export', [TsReporting\Controller\ReportController::class, 'export']);

});
