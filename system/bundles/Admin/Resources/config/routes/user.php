<?php

use Admin\Http\Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
	->name('user.')
	->group(function () {

		Route::get('load',  [Controller\UserController::class, 'load'])->name('load');

		Route::post('color-scheme/save', [Controller\UserController::class, 'saveColorScheme'])->name('color_scheme.save');

		Route::prefix('notifications')
			->name('notifications.')
			->group(function () {
				Route::get('{id}/action/{button}',  [Controller\UserController::class, 'notificationAction'])->name('action');
				Route::get('{id}',  [Controller\UserController::class, 'readNotification'])->name('read');
				Route::delete('/',  [Controller\UserController::class, 'deleteNotifications'])->name('delete');
			});

		Route::prefix('bookmark')
			->name('bookmark.')
			->group(function () {
				Route::put('/',  [Controller\UserController::class, 'addBookmark'])->name('add');
				Route::post('/',  [Controller\UserController::class, 'toggleBookmark'])->name('toggle');
				Route::delete('/',  [Controller\UserController::class, 'deleteBookmark'])->name('delete');
			});

	});
