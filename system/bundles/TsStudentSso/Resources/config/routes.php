<?php

use Core\Facade\Routing\Route;
use Core\Middleware\Auth\BackendAuth;
use Core\Middleware\Backend;

Route::group([
	'prefix' => 'saml2/student',
	'middleware' => [\Core\Middleware\Frontend::class, \TsStudentSso\Middleware\Config::class]
], function () {

	Route::get('/login', [TsStudentSso\Controller\SamlController::class, 'login'])->name('login');
	Route::post('/login', [TsStudentSso\Controller\SamlController::class, 'executeLogin'])->name('execute_login');
	
	Route::get('/metadata', [TsStudentSso\Controller\SamlController::class, 'metadata'])->name('metadata');
	Route::get('/sso', [TsStudentSso\Controller\SamlController::class, 'sso'])->name('sso');
	Route::get('/slo', [TsStudentSso\Controller\SamlController::class, 'slo'])->name('slo');

	#Route::resource('metadata', 'MetadataController')->only('index')->name('index', 'metadata');
	#Route::resource('logout', 'LogoutController')->only('index')->name('index', 'logout');
	
});
