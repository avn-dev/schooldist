<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/admin/apps/ivvy')
	->as('app.')
	->middleware([\Core\Middleware\Backend::class, \Core\Middleware\Auth\BackendAuth::class])
	->group(function () {
		Route::post('sync', [\TsIvvy\Http\Controller\ExternalAppController::class, 'sync'])->name('sync');
	});


