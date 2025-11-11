<?php

use Admin\Http\Controller;
use Admin\Http\Middleware;
use Illuminate\Support\Facades\Route;

Route::middleware([Middleware\HandleAuthInertiaRequests::class])
	->group(function () {
		Route::get('login', [Controller\AuthController::class, 'login'])->name('login');
		Route::get('forgot-password/{token}', [Controller\AuthController::class, 'reset'])->name('forgot.reset');
		Route::get('forgot-password', [Controller\AuthController::class, 'forgot'])->name('forgot');
	});

Route::get('login/image', [Controller\AuthController::class, 'image'])->name('image');
Route::post('login/attempt', [Controller\AuthController::class, 'attempt'])->name('login.attempt');
Route::post('login/passkeys/challenge', [Controller\AuthController::class, 'passkeyChallenge'])->name('login.passkeys.challenge');
Route::post('login/language/change', [Controller\AuthController::class, 'changeLanguage'])->name('login.language.change');
Route::post('forgot-password/request', [Controller\AuthController::class, 'requestNewPassword'])->name('forgot.request');
Route::post('forgot-password/reset', [Controller\AuthController::class, 'resetPassword'])->name('forgot.reset.request');
Route::get('logout', [Controller\AuthController::class, 'logout'])
	->middleware([\Core\Middleware\Auth\BackendAuthInit::class])
	->name('logout');