<?php

use OpenBanking\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/assets/open-banking/{sFile}', [Controllers\ResourceController::class, 'printFile'])
	->where(['sFile' => '.+?'])
	->name('assets');

Route::prefix('/api/1.0/open-banking')
	->as('api.')
	->group(function () {
		Route::middleware([\TcExternalApps\Middleware\AppInstalled::class.':finAPI'])
			->post('finAPI/webform/callback', [Controllers\ExternalApps\finAPIController::class, 'webformCallback'])->name('finApi.webform.callback');
	});

Route::prefix('/open-banking/app')
	->middleware([
		\Core\Middleware\Backend::class,
		\Core\Middleware\Auth\BackendAuth::class
	])
	->as('external_apps.')
	->group(function () {
		Route::middleware([\TcExternalApps\Middleware\AppInstalled::class.':finAPI'])
			->prefix('finAPI')
			->as('finAPI.')
			->group(function () {
				Route::get('/', [Controllers\ExternalApps\finAPIController::class, 'init'])->name('init');
				Route::get('webform', [Controllers\ExternalApps\finAPIController::class, 'webform'])->name('webform');
				Route::post('account', [Controllers\ExternalApps\finAPIController::class, 'toggleAccount'])->name('account.toggle');
				Route::delete('account/{id}', [Controllers\ExternalApps\finAPIController::class, 'deleteAccount'])->name('account.delete');
				Route::post('account/{id}/payment-method', [Controllers\ExternalApps\finAPIController::class, 'setAccountPaymentMethod'])->name('account.payment_method');
				Route::post('connection/{id}/execution-times', [Controllers\ExternalApps\finAPIController::class, 'setBankConnectionsExecutionTimes'])->name('connection.execution_times');
			});
	});