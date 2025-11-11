<?php

use Core\Facade\Routing\Route;
use Core\Middleware\Auth\BackendAuth;
use Core\Middleware\Backend;
use TsActivities\Controller;

Route::get('/ts/activities/resources/{sFile}', [Controller\ResourceController::class, 'printFile'])
	->where(['sFile' => '.+?'])
	->name('resources');

Route::group([
	'prefix' => 'ts/activities/scheduling',
	'middleware' => [Backend::class, BackendAuth::class]
], function () {

	Route::get('/', [Controller\SchedulingController::class, 'index']);

	Route::get('events', [Controller\SchedulingController::class, 'events']);

	Route::post('unallocated', [Controller\SchedulingController::class, 'unallocated']);

	Route::post('allocated', [Controller\SchedulingController::class, 'allocated']);

	Route::post('allocate', [Controller\SchedulingController::class, 'allocate']);

	Route::get('exportBlock', [Controller\SchedulingController::class, 'exportBlock']);

	Route::delete('deleteAllocation', [Controller\SchedulingController::class, 'deleteAllocation']);

	Route::delete('deleteBlock', [Controller\SchedulingController::class, 'deleteBlock']);

});

Route::group([
	'prefix' => 'ts/activities/prices',
	'middleware' => [Backend::class, BackendAuth::class]
], function () {

	Route::match(['GET', 'POST'], '/', [Controller\PricesController::class, 'prices'])->name('prices');

	Route::post('/save', [Controller\PricesController::class, 'saveActivityPrices'])->name('prices_save');

});
