<?php

use Admin\Http\Middleware;
use Illuminate\Support\Facades\Route;

// TODO In eigenes Bundle verschieben
Route::get('/image.php', [\Admin\Http\Controller\ImgBuilderController::class, 'execute'])->name('image.builder');

// TODO eigentlich /assets/admin
Route::get('/admin/assets/{sType}/{sFile}', [\Admin\Http\Controller\ResourceController::class, 'outputResource'])
	->where('sFile','.+')
	->name('assets');

Route::prefix('admin')
	->middleware([\Core\Middleware\Backend::class])
	->group(function () {

		Route::middleware([])->group(__DIR__.'/routes/auth.php');

		Route::post('ping', [\Admin\Http\Controller\AdminController::class, 'ping'])
			->middleware([\Core\Middleware\Auth\BackendAuthInit::class])
			->name('ping');

		Route::middleware([
			\Core\Middleware\Auth\BackendAuth::class,
			Middleware\HandleHeadRequests::class,
			Middleware\ErrorHandling::class,
		])->group(function () {

			Route::middleware([])->group(__DIR__.'/routes/user.php');

			Route::middleware([])->group(__DIR__.'/routes/interface.php');

		});
	});